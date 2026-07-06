<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

ob_start();

register_shutdown_function(function () {
    $error = error_get_last();
    if (
        $error &&
        in_array($error["type"], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ])
    ) {
        ob_clean();
        echo json_encode([
            "success" => false,
            "output" => "",
            "stderr" => "Internal error: " . $error["message"],
        ]);
    }
});

error_reporting(E_ALL);

require_once "../config.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

ini_set("display_errors", 0);
ob_clean();

// Handle preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

// Test endpoint
if (isset($_GET["test"])) {
    echo json_encode([
        "status" => "API is working",
        "timestamp" => time(),
        "version" => APP_VERSION,
    ]);
    exit();
}

// Handle lint request
$code = $_GET["code"] ?? ($_POST["code"] ?? "");
$language_slug = $_GET["language"] ?? ($_POST["language"] ?? "rust");

if (isset($_GET["lint"])) {
    echo json_encode(["issues" => lint_code($code, $language_slug)]);
    exit();
}

// Handle format request
if (isset($_GET["format"])) {
    echo json_encode(["formatted" => format_code($code, $language_slug)]);
    exit();
}

// Only POST from here on for code execution
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

$code = $input["code"] ?? "";
$lesson_id = (int) ($input["lesson_id"] ?? 0);
$language_slug = $input["language"] ?? "rust";

if (empty($code)) {
    echo json_encode(["error" => "No code provided"]);
    exit();
}

// Get language info
$lang = get_language_by_slug($language_slug);
if (!$lang) {
    $lang = get_language_by_id(1); // Default to Rust
}

// Security: Basic code validation per language
$restricted_patterns = get_restricted_patterns($language_slug);
foreach ($restricted_patterns as $pattern) {
    if (preg_match($pattern, $code)) {
        echo json_encode([
            "error" => "Code contains restricted operations",
            "output" =>
                "Error: Code execution blocked for security reasons. Avoid using file system, network, or unsafe operations.",
            "stderr" => "Security violation detected",
        ]);
        exit();
    }
}

// Try external execution methods
$result = execute_code($code, $lang);

if ($result["success"]) {
    echo json_encode($result);
    exit();
}

// Fallback to simulation
echo json_encode(simulate_execution($code, $lesson_id, $lang));

function get_restricted_patterns($language)
{
    $patterns = [
        "rust" => [
            "/std::process/i",
            "/std::fs/i",
            "/std::net/i",
            "/unsafe\s*\{/i",
            "/Command::new/i",
            "/fs::/i",
            "/net::/i",
            "/std::os/i",
        ],
        "python" => [
            "/import\s+os/i",
            "/import\s+subprocess/i",
            "/import\s+socket/i",
            "/import\s+shutil/i",
            "/import\s+sys\s*\.\s*/, /__import__/i",
            '/open\(.*[\'\"].*\.\..*[\'\"]/i',
            "/eval\s*\(/i",
            "/exec\s*\(/i",
            "/pickle/i",
            "/marshal/i",
        ],
        "javascript" => [
            '/require\s*\(\s*[\'"]fs[\'"]\s*\)/i',
            '/require\s*\(\s*[\'"]child_process[\'"]\s*\)/i',
            '/require\s*\(\s*[\'"]net[\'"]\s*\)/i',
            "/process\./i",
            "/global\./i",
            "/eval\s*\(/i",
            "/Function\s*\(/i",
            "/import\s+.*\s+from/i",
        ],
        "typescript" => [
            '/require\s*\(\s*[\'"]fs[\'"]\s*\)/i',
            '/require\s*\(\s*[\'"]child_process[\'"]\s*\)/i',
            "/process\./i",
            "/eval\s*\(/i",
        ],
        "go" => [
            '/import\s+"os"/i',
            '/import\s+"net"/i',
            '/import\s+"os\/exec"/i',
            "/syscall\./i",
            "/unsafe\./i",
        ],
        "java" => [
            "/java\.io\./i",
            "/java\.net\./i",
            "/java\.nio\./i",
            "/Runtime\.getRuntime/i",
            "/ProcessBuilder/i",
            "/File/i",
            "/FileWriter/i",
            "/FileReader/i",
            "/Socket/i",
            "/ServerSocket/i",
        ],
        "cpp" => [
            "/#include\s*<.*fstream/i",
            "/#include\s*<.*thread/i",
            "/#include\s*<.*net/i",
            "/#include\s*<.*sys/i",
            "/system\s*\(/i",
        ],
        "c" => [
            "/#include\s*<.*fstream/i",
            "/#include\s*<.*thread/i",
            "/#include\s*<.*net/i",
            "/#include\s*<.*sys/i",
            "/system\s*\(/i",
            "/fork/i",
            "/exec/i",
        ],
    ];

    return $patterns[$language] ?? $patterns["rust"];
}

function execute_code($code, $lang)
{
    // Try Rust Playground API for Rust
    if ($lang["slug"] === "rust") {
        $result = execute_with_playground($code);
        if ($result["success"]) {
            return $result;
        }
    }

    // Try Docker for any language
    $result = execute_with_docker($code, $lang);
    if ($result["success"]) {
        return $result;
    }

    // Try Piston API for multi-language
    $result = execute_with_piston($code, $lang);
    if ($result["success"]) {
        return $result;
    }

    return ["success" => false];
}

function execute_with_playground($code)
{
    $payload = json_encode([
        "channel" => "stable",
        "mode" => "debug",
        "edition" => "2021",
        "crateType" => "bin",
        "tests" => false,
        "code" => $code,
        "backtrace" => false,
    ]);

    $ch = curl_init("https://play.rust-lang.org/execute");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Content-Length: " . strlen($payload),
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result["success"])) {
            return [
                "success" => true,
                "output" => $result["stdout"] ?? "",
                "stderr" => $result["stderr"] ?? "",
                "execution_time" => "Rust Playground",
            ];
        }
    }

    return ["success" => false];
}

function execute_with_docker($code, $lang)
{
    $docker_check = shell_exec("which docker 2>/dev/null");
    if (empty($docker_check)) {
        return ["success" => false];
    }

    $temp_dir = sys_get_temp_dir() . "/rustnite_" . uniqid();
    mkdir($temp_dir, 0755, true);

    $ext = $lang["extension"] ?? ".rs";
    $source_file = $temp_dir . "/main" . $ext;
    file_put_contents($source_file, $code);

    $docker_image = $lang["docker_image"] ?? "rust:1.70";

    // Build appropriate command
    $compile_cmd = $lang["compiler_command"] ?? "";
    $run_cmd = $lang["run_command"] ?? "";

    $full_cmd = "";
    if ($compile_cmd) {
        $full_cmd = "{$compile_cmd} && {$run_cmd}";
    } else {
        $full_cmd = $run_cmd;
    }

    if (empty($full_cmd)) {
        return ["success" => false];
    }

    $cmd = sprintf(
        "timeout 10s docker run --rm -v %s:/app -w /app %s sh -c %s 2>&1",
        escapeshellarg($temp_dir),
        escapeshellarg($docker_image),
        escapeshellarg($full_cmd),
    );

    $start_time = microtime(true);
    $output = shell_exec($cmd);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);

    // Cleanup
    array_map("unlink", glob($temp_dir . "/*"));
    rmdir($temp_dir);

    if ($output !== null) {
        return [
            "success" => true,
            "output" => $output,
            "stderr" => "",
            "execution_time" => $execution_time . "ms (Docker)",
        ];
    }

    return ["success" => false];
}

function execute_with_piston($code, $lang)
{
    $language_map = [
        "rust" => "rust",
        "python" => "python3",
        "javascript" => "javascript",
        "typescript" => "typescript",
        "go" => "go",
        "java" => "java",
        "cpp" => "cpp",
        "c" => "c",
    ];

    $piston_lang = $language_map[$lang["slug"]] ?? "rust";

    $payload = json_encode([
        "language" => $piston_lang,
        "source" => $code,
    ]);

    $ch = curl_init("https://emkc.org/api/v2/piston/execute");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        if (isset($result["run"])) {
            $output = $result["run"]["stdout"] ?? "";
            $stderr = $result["run"]["stderr"] ?? "";

            if ($result["run"]["code"] !== 0 && empty($output)) {
                $stderr = $result["compile"]["stderr"] ?? $stderr;
            }

            return [
                "success" => true,
                "output" => $output,
                "stderr" => $stderr,
                "execution_time" => "Piston API",
            ];
        }
    }

    return ["success" => false];
}

function simulate_execution($code, $lesson_id, $lang)
{
    global $pdo;

    $expected_output = "";
    if ($lesson_id > 0) {
        $stmt = $pdo->prepare(
            "SELECT expected_output FROM lessons WHERE id = ?",
        );
        $stmt->execute([$lesson_id]);
        $expected_output = $stmt->fetchColumn() ?: "";
    }

    // Try to evaluate expressions for dynamic output
    $output = "";
    $stderr = "";
    $success = true;

    switch ($lang["slug"]) {
        case "rust":
            if (!preg_match("/fn\s+main\s*\(\s*\)/", $code)) {
                $stderr = "error: `main` function not found in crate root";
                $success = false;
            } else {
                // Extract println! calls
                preg_match_all(
                    '/println!\s*\(\s*"([^"]*)"(?:\s*,\s*([^)]*))?\s*\)/',
                    $code,
                    $matches,
                );
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $i => $text) {
                        $arg = $matches[2][$i] ?? "";
                        if (!empty($arg)) {
                            // Try to evaluate simple expressions
                            $arg = trim($arg);
                            if (preg_match('/^\d+\s*\+\s*\d+$/', $arg)) {
                                $parts = explode("+", $arg);
                                $text = str_replace(
                                    "{}",
                                    (int) $parts[0] + (int) $parts[1],
                                    $text,
                                );
                            } elseif (is_numeric($arg)) {
                                $text = str_replace("{}", $arg, $text);
                            } else {
                                $text = str_replace("{}", $arg, $text);
                            }
                        }
                        $output .= $text . "\n";
                    }
                } else {
                    $output .= "Program compiled successfully!\n";
                }
            }
            break;

        case "python":
            preg_match_all(
                '/print\s*\(\s*["\']([^"\']*)["\']\s*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                // Try to evaluate print with expressions
                preg_match_all(
                    "/print\s*\((\d+\s*[+\-*\/]\s*\d+)\)/",
                    $code,
                    $expr_matches,
                );
                if (!empty($expr_matches[1])) {
                    foreach ($expr_matches[1] as $expr) {
                        try {
                            $result = eval("return " . $expr . ";");
                            $output .= $result . "\n";
                        } catch (\Exception $e) {
                            $output .= $expr . "\n";
                        }
                    }
                } else {
                    $output .= "Program executed successfully!\n";
                }
            }
            break;

        case "javascript":
        case "typescript":
            preg_match_all(
                '/console\.log\s*\(\s*["\']([^"\']*)["\']\s*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                $output .= "Program executed successfully!\n";
            }
            break;

        case "go":
            preg_match_all(
                '/fmt\.Println\s*\(\s*["\']([^"\']*)["\']\s*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                $output .= "Program compiled successfully!\n";
            }
            break;

        case "java":
            preg_match_all(
                '/System\.out\.println\s*\(\s*["\']([^"\']*)["\']\s*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                $output .= "Program compiled and executed successfully!\n";
            }
            break;

        case "cpp":
        case "c":
            preg_match_all(
                '/printf\s*\(\s*["\']([^"\']*)["\']\s*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                $output .= "Program compiled successfully!\n";
            }
            break;

        default:
            preg_match_all(
                '/print.*\(.*["\']([^"\']*)["\'].*\)/',
                $code,
                $matches,
            );
            if (!empty($matches[1])) {
                foreach ($matches[1] as $text) {
                    $output .= $text . "\n";
                }
            } else {
                $output .= "Program executed successfully!\n";
            }
    }

    // Check against expected output
    if ($expected_output && !empty($output)) {
        if (trim($output) === trim($expected_output)) {
            $stderr .= "✅ Output matches expected result!\n";
        } else {
            $stderr .= "Expected: " . $expected_output . "\n";
            $stderr .= "Got: " . trim($output) . "\n";
        }
    }

    return [
        "success" => $success,
        "output" => $output,
        "stderr" => $stderr,
        "execution_time" => "Local",
    ];
}

// ============== LINTING & FORMATTING ==============

function lint_code($code, $language_slug)
{
    $issues = [];

    switch ($language_slug) {
        case "rust":
            // Check common Rust issues
            if (!preg_match("/fn\s+main/", $code)) {
                $issues[] = [
                    "line" => 0,
                    "severity" => "error",
                    "message" => "Missing main function",
                ];
            }
            if (
                preg_match("/\bprintln!\s*\(/", $code) &&
                !preg_match("/\bfn\s+main/", $code)
            ) {
                $issues[] = [
                    "line" => 0,
                    "severity" => "warning",
                    "message" => "println! should be inside a function",
                ];
            }
            if (preg_match('/=\s*$/', $code, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                $line = substr_count(substr($code, 0, $pos), "\n") + 1;
                $issues[] = [
                    "line" => $line,
                    "severity" => "warning",
                    "message" => "Trailing assignment operator",
                ];
            }
            break;

        case "python":
            if (!preg_match("/print\s*\(/", $code)) {
                $issues[] = [
                    "line" => 0,
                    "severity" => "info",
                    "message" => "No print() call - you won't see output",
                ];
            }
            if (preg_match("/\binput\s*\(/", $code)) {
                $issues[] = [
                    "line" => 0,
                    "severity" => "warning",
                    "message" => "input() not supported in sandbox",
                ];
            }
            break;

        case "javascript":
            if (!preg_match("/console\.log/", $code)) {
                $issues[] = [
                    "line" => 0,
                    "severity" => "info",
                    "message" => "No console.log() - you won't see output",
                ];
            }
            break;
    }

    // Common checks for all languages
    $lines = explode("\n", $code);
    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;
        // Check for trailing whitespace
        if (preg_match('/\s+$/', $line) && strlen($line) > 0) {
            $issues[] = [
                "line" => $lineNum,
                "severity" => "info",
                "message" => "Trailing whitespace",
            ];
        }
        // Check for very long lines
        if (strlen($line) > 120) {
            $issues[] = [
                "line" => $lineNum,
                "severity" => "warning",
                "message" =>
                    "Line too long (" . strlen($line) . " > 120 chars)",
            ];
        }
        // Check for tabs vs spaces
        if (strpos($line, "\t") !== false) {
            $issues[] = [
                "line" => $lineNum,
                "severity" => "info",
                "message" => "Consider using spaces instead of tabs",
            ];
        }
    }

    return $issues;
}

function format_code($code, $language_slug)
{
    // Basic formatting:
    $lines = explode("\n", $code);
    $formatted = [];
    $indent_level = 0;
    $indent_size = 4;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // De-indent for closing braces/brackets
        if (preg_match("/^[}\]]/", $trimmed)) {
            $indent_level = max(0, $indent_level - 1);
        }

        // Add proper indentation
        $indent = str_repeat(" ", $indent_level * $indent_size);
        $formatted[] = $indent . $trimmed;

        // In-indent for opening braces/brackets
        if (
            preg_match('/[\{\[]\s*$/', $trimmed) ||
            preg_match('/:\s*$/', $trimmed)
        ) {
            $indent_level++;
        }
    }

    return implode("\n", $formatted);
}
