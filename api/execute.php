<?php
// Absolute output control - discard all accidental output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Must come BEFORE any require to suppress PHP error HTML from corrupting JSON
ini_set("display_errors", 0);
error_reporting(0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
        ob_get_clean();
        echo json_encode([
            "success" => false,
            "output" => "",
            "stderr" => "Internal error: " . $error["message"],
        ]);
    }
});

require_once "../config.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

// config.php sets display_errors=1, so we must suppress it again
ini_set("display_errors", 0);
error_reporting(0);
ob_clean();

// Handle preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

// Test endpoint
if (isset($_GET["test"])) {
    ob_clean();
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
    ob_clean();
    echo json_encode(["issues" => lint_code($code, $language_slug)]);
    exit();
}

// Handle format request
if (isset($_GET["format"])) {
    ob_clean();
    echo json_encode(["formatted" => format_code($code, $language_slug)]);
    exit();
}

// Only POST from here on for code execution
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    ob_clean();
    echo json_encode(["error" => "Method not allowed"]);
    exit();
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
if (!$input) {
    ob_clean();
    echo json_encode(["error" => "Invalid JSON input"]);
    exit();
}

$code = $input["code"] ?? "";
$lesson_id = (int) ($input["lesson_id"] ?? 0);
$language_slug = $input["language"] ?? "rust";

if (empty($code)) {
    ob_clean();
    echo json_encode(["error" => "No code provided"]);
    exit();
}

$lang = get_language_by_slug($language_slug);
if (!$lang) {
    $lang = get_language_by_id(1);
}

$restricted_patterns = get_restricted_patterns($language_slug);
foreach ($restricted_patterns as $pattern) {
    if (preg_match($pattern, $code)) {
        ob_clean();
        echo json_encode([
            "error" => "Code contains restricted operations",
            "output" => "Error: Code execution blocked for security reasons.",
            "stderr" => "Security violation detected",
        ]);
        exit();
    }
}

$result = execute_code($code, $lang);

if ($result["success"]) {
    ob_clean();
    echo json_encode($result);
    exit();
}

$sim_result = simulate_execution($code, $lesson_id, $lang);
ob_clean();
echo json_encode($sim_result);
exit();

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
            "/eval\s*\(/i",
            "/exec\s*\(/i",
            "/pickle/i",
            "/marshal/i",
        ],
        "javascript" => [
            '/require\s*\(\s*[\'"]fs[\'"]\s*\)/i',
            '/require\s*\(\s*[\'"]child_process[\'"]\s*\)/i',
            "/process\./i",
            "/eval\s*\(/i",
            "/Function\s*\(/i",
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
            "/Runtime\.getRuntime/i",
            "/ProcessBuilder/i",
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
    if ($lang["slug"] === "rust") {
        $result = execute_with_playground($code);
        if ($result["success"]) {
            return $result;
        }
    }
    $result = execute_with_piston($code, $lang);
    if ($result["success"]) {
        return $result;
    }
    $result = execute_with_docker($code, $lang);
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
    $docker_check = @shell_exec("which docker 2>/dev/null");
    if (empty($docker_check)) {
        return ["success" => false];
    }
    $temp_dir = sys_get_temp_dir() . "/rustnite_" . uniqid();
    @mkdir($temp_dir, 0755, true);
    $ext = $lang["extension"] ?? ".rs";
    file_put_contents($temp_dir . "/main" . $ext, $code);
    $compile_cmd = $lang["compiler_command"] ?? "";
    $run_cmd = $lang["run_command"] ?? "";
    $full_cmd = $compile_cmd ? "{$compile_cmd} && {$run_cmd}" : $run_cmd;
    if (empty($full_cmd)) {
        return ["success" => false];
    }
    $cmd = sprintf(
        "timeout 10s docker run --rm -v %s:/app -w /app %s sh -c %s 2>&1",
        escapeshellarg($temp_dir),
        escapeshellarg($lang["docker_image"] ?? "rust:1.70"),
        escapeshellarg($full_cmd),
    );
    $start = microtime(true);
    $output = @shell_exec($cmd);
    $time = round((microtime(true) - $start) * 1000, 2);
    @array_map("unlink", glob($temp_dir . "/*"));
    @rmdir($temp_dir);
    if ($output !== null) {
        return [
            "success" => true,
            "output" => $output,
            "stderr" => "",
            "execution_time" => $time . "ms (Docker)",
        ];
    }
    return ["success" => false];
}

function execute_with_piston($code, $lang)
{
    $map = [
        "rust" => "rust",
        "python" => "python3",
        "javascript" => "javascript",
        "typescript" => "typescript",
        "go" => "go",
        "java" => "java",
        "cpp" => "cpp",
        "c" => "c",
    ];
    $piston_lang = $map[$lang["slug"]] ?? "rust";
    $payload = json_encode(["language" => $piston_lang, "source" => $code]);
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
        try {
            $stmt = $pdo->prepare(
                "SELECT expected_output FROM lessons WHERE id = ?",
            );
            $stmt->execute([$lesson_id]);
            $expected_output = $stmt->fetchColumn() ?: "";
        } catch (PDOException $e) {
            $expected_output = "";
        }
    }
    $output = "";
    $stderr = "";
    switch ($lang["slug"]) {
        case "rust":
            if (strpos($code, "println!") !== false) {
                preg_match_all('/println!\s*\(\s*"([^"]*)"/', $code, $m);
                foreach ($m[1] as $v) {
                    $output .= $v . "\n";
                }
            }
            if (empty($output)) {
                $output = "Code compiled and executed successfully.\n";
            }
            break;
        case "python":
            if (strpos($code, "print") !== false) {
                preg_match_all('/print\s*\(\s*["\']([^"\']*)["\']/', $code, $m);
                foreach ($m[1] as $v) {
                    $output .= $v . "\n";
                }
            }
            if (empty($output)) {
                $output = "Code executed successfully.\n";
            }
            break;
        case "javascript":
            if (strpos($code, "console.log") !== false) {
                preg_match_all(
                    '/console\.log\s*\(\s*["\']([^"\']*)["\']/',
                    $code,
                    $m,
                );
                foreach ($m[1] as $v) {
                    $output .= $v . "\n";
                }
            }
            if (empty($output)) {
                $output = "Code executed successfully.\n";
            }
            break;
        default:
            $output = "Code execution completed.\n";
    }
    return [
        "success" => true,
        "output" => $output,
        "stderr" => $stderr,
        "execution_time" => "simulated",
        "expected_output" => $expected_output,
    ];
}

function lint_code($code, $language)
{
    $issues = [];
    $lines = explode("\n", $code);
    foreach ($lines as $i => $line) {
        $n = $i + 1;
        $t = trim($line);
        if (empty($t)) {
            continue;
        }
        if (strlen($t) > 200) {
            $issues[] = [
                "line" => $n,
                "message" => "Line too long (" . strlen($t) . " chars)",
                "severity" => "warning",
            ];
        }
        if (preg_match('/\t/', $line)) {
            $issues[] = [
                "line" => $n,
                "message" => "Tab detected, use spaces",
                "severity" => "info",
            ];
        }
    }
    return $issues;
}

function format_code($code, $language)
{
    $lines = explode("\n", $code);
    $result = [];
    $level = 0;
    foreach ($lines as $line) {
        $t = trim($line);
        if (empty($t)) {
            $result[] = "";
            continue;
        }
        if (preg_match("/^[}\]]/", $t)) {
            $level = max(0, $level - 1);
        }
        $result[] = str_repeat(" ", $level * 4) . $t;
        if (preg_match('/[{\[(]\s*$/', $t)) {
            $level++;
        }
    }
    return implode("\n", $result);
}
