<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . '/public' . $requestPath;

if (is_file($filePath)) {
    return false;
}

// Capture everything index.php outputs to inspect it
ob_start();

try {
    require __DIR__ . '/public/index.php';
} catch (\Throwable $e) {
    // If index.php throws any error at all, catch it here
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'caught_error' => true,
        'message' => $e->getMessage(),
        'type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit;
}

$output = ob_get_clean();

// Check whether index.php produced PHP source code or real output
header('Content-Type: application/json');
echo json_encode([
    'output_length' => strlen($output),
    'looks_like_source' => str_starts_with(ltrim($output), '<?'),
    'first_100_chars' => substr($output, 0, 100),
]);