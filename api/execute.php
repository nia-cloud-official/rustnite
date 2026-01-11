<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$lesson_id = (int)($input['lesson_id'] ?? 0);

if (empty($code)) {
    echo json_encode(['error' => 'No code provided']);
    exit;
}

// Security: Basic code validation
if (strpos($code, 'std::process') !== false || 
    strpos($code, 'std::fs') !== false || 
    strpos($code, 'std::net') !== false ||
    strpos($code, 'unsafe') !== false) {
    echo json_encode([
        'error' => 'Code contains restricted operations',
        'output' => 'Error: Code execution blocked for security reasons. Avoid using file system, network, or unsafe operations.'
    ]);
    exit;
}

// Try Rust Playground API first
$playground_result = execute_with_playground($code);
if ($playground_result['success']) {
    echo json_encode($playground_result);
    exit;
}

// Fallback to local Docker execution if available
$docker_result = execute_with_docker($code);
if ($docker_result['success']) {
    echo json_encode($docker_result);
    exit;
}

// Final fallback to simulation
echo json_encode(simulate_execution($code, $lesson_id));

function execute_with_playground($code) {
    $payload = json_encode([
        'channel' => 'stable',
        'mode' => 'debug',
        'edition' => '2021',
        'crateType' => 'bin',
        'tests' => false,
        'code' => $code,
        'backtrace' => false
    ]);
    
    $ch = curl_init('https://play.rust-lang.org/execute');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        
        if (isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'output' => $result['stdout'] ?? '',
                'stderr' => $result['stderr'] ?? '',
                'execution_time' => 'Real execution via Rust Playground'
            ];
        } else {
            return [
                'success' => true,
                'output' => '',
                'stderr' => $result['stderr'] ?? 'Compilation failed',
                'execution_time' => 'Compilation error'
            ];
        }
    }
    
    return ['success' => false];
}

function execute_with_docker($code) {
    // Check if Docker is available
    $docker_check = shell_exec('which docker 2>/dev/null');
    if (empty($docker_check)) {
        return ['success' => false];
    }
    
    // Create temporary file
    $temp_dir = sys_get_temp_dir() . '/rustnite_' . uniqid();
    mkdir($temp_dir, 0755, true);
    $rust_file = $temp_dir . '/main.rs';
    file_put_contents($rust_file, $code);
    
    // Execute with Docker (timeout after 5 seconds)
    $cmd = sprintf(
        'timeout 5s docker run --rm -v %s:/app -w /app rust:1.70 sh -c "rustc main.rs && ./main" 2>&1',
        escapeshellarg($temp_dir)
    );
    
    $start_time = microtime(true);
    $output = shell_exec($cmd);
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Cleanup
    unlink($rust_file);
    rmdir($temp_dir);
    
    if ($output !== null) {
        return [
            'success' => true,
            'output' => $output,
            'stderr' => '',
            'execution_time' => $execution_time . 'ms (Docker)'
        ];
    }
    
    return ['success' => false];
}

function simulate_execution($code, $lesson_id) {
    global $pdo;
    
    // Get expected output for comparison
    $expected_output = '';
    if ($lesson_id > 0) {
        $stmt = $pdo->prepare("SELECT expected_output FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $expected_output = $stmt->fetchColumn() ?: '';
    }
    
    $output = "=== SIMULATION MODE ===\n";
    $output .= "Note: This is simulated execution. For real compilation, Docker or Rust Playground integration is needed.\n\n";
    
    // Basic syntax checking
    if (!preg_match('/fn\s+main\s*\(\s*\)/', $code)) {
        return [
            'success' => true,
            'output' => '',
            'stderr' => 'error: `main` function not found in crate `main`',
            'execution_time' => 'Simulation'
        ];
    }
    
    // Extract println! statements
    preg_match_all('/println!\s*\(\s*"([^"]*)"(?:\s*,\s*([^)]*))?\s*\)/', $code, $matches);
    
    if (!empty($matches[1])) {
        $output .= "Program output:\n";
        foreach ($matches[1] as $i => $text) {
            // Handle format arguments
            if (!empty($matches[2][$i])) {
                $args = explode(',', $matches[2][$i]);
                $formatted_text = $text;
                foreach ($args as $j => $arg) {
                    $arg = trim($arg);
                    // Simple variable substitution
                    if (preg_match('/(\w+)/', $arg, $var_match)) {
                        $var_name = $var_match[1];
                        // Try to find variable value in code
                        if (preg_match('/let\s+' . $var_name . '\s*=\s*([^;]+)/', $code, $val_match)) {
                            $value = trim($val_match[1]);
                            $value = trim($value, '"\'');
                            $formatted_text = str_replace('{}', $value, $formatted_text);
                        }
                    }
                }
                $output .= $formatted_text . "\n";
            } else {
                $output .= $text . "\n";
            }
        }
    } else {
        $output .= "Program compiled successfully!\n";
        $output .= "(No println! statements found)\n";
    }
    
    // Compare with expected output
    if ($expected_output) {
        $output .= "\n--- Expected vs Actual ---\n";
        $output .= "Expected: " . $expected_output . "\n";
        
        $actual_lines = [];
        foreach ($matches[1] as $text) {
            $actual_lines[] = $text;
        }
        $actual_output = implode("\n", $actual_lines);
        
        $output .= "Actual: " . $actual_output . "\n";
        
        if (trim($actual_output) === trim($expected_output)) {
            $output .= "✅ Output matches expected result!\n";
        } else {
            $output .= "❌ Output doesn't match expected result.\n";
        }
    }
    
    return [
        'success' => true,
        'output' => $output,
        'stderr' => '',
        'execution_time' => 'Simulation'
    ];
}
?>