<?php

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . '/public' . $requestPath;

if (is_file($filePath)) {
    return false;
}

require __DIR__ . '/public/index.php';