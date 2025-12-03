<?php
/**
 * Router for PHP built-in server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route /api/* to backend/index.php
if (strpos($uri, '/api/') === 0) {
    $_SERVER['PATH_INFO'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/backend/index.php';
    chdir(__DIR__ . '/backend');
    require __DIR__ . '/backend/index.php';
    return true;
}

// Serve static files
$file = __DIR__ . $uri;

// Check for directory index
if (is_dir($file)) {
    if (file_exists($file . '/index.html')) {
        return false; // Let PHP serve the file
    }
}

// Check if file exists
if (file_exists($file) && is_file($file)) {
    return false; // Let PHP serve the file
}

// 404
http_response_code(404);
echo "404 Not Found";
return true;
