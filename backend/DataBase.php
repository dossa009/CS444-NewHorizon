<?php
/**
 * Database Configuration for New Horizon
 * Configured for CSUSM CIS 444 Class Server (Group 8)
 * Uses .env file for credentials
 */

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Database credentials from .env
$DB_HOST     = getenv('DB_HOST') ?: 'localhost';
$DB_USER     = getenv('DB_USER') ?: 'group8';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: '';
$DB_NAME     = getenv('DB_NAME') ?: 'group8';
$DB_PORT     = getenv('DB_PORT') ?: 3306;

// Create connection
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database connection failed',
    ]);
    exit;
}

$mysqli->set_charset('utf8mb4');
