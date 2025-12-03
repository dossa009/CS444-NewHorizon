<?php
// Configuration file for New Horizon backend

// Disable HTML error output - return JSON instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Custom error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'debug' => "$errstr in $errfile:$errline"
    ]);
    exit;
});

// Custom exception handler
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'debug' => $e->getMessage()
    ]);
    exit;
});

// JSON response header FIRST
header('Content-Type: application/json; charset=utf-8');

// CORS Configuration
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 3600');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Load database config (loads .env file)
require_once __DIR__ . '/DataBase.php';

// JWT Secret Key (loaded from .env)
define('JWT_SECRET_KEY', getenv('JWT_SECRET') ?: 'newhorizon_secret');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 604800); // 7 days in seconds

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper function to send error response
function sendError($message, $statusCode = 400, $details = null) {
    http_response_code($statusCode);
    $response = ['success' => false, 'error' => $message];
    if ($details !== null) {
        $response['details'] = $details;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE && !empty($input)) {
        sendError('Invalid JSON input', 400);
    }

    return $data ?? [];
}

// Get authorization token from headers
function getAuthToken() {
    $authHeader = null;

    // Method 1: getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }

    // Method 2: $_SERVER['HTTP_AUTHORIZATION']
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    // Method 3: $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    // Method 4: Apache workaround
    if (!$authHeader && isset($_SERVER['PHP_AUTH_DIGEST'])) {
        $authHeader = $_SERVER['PHP_AUTH_DIGEST'];
    }

    // Extract Bearer token
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

// Validate required fields
function validateRequired($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number";
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must contain at least one special character";
    }

    return true;
}

// Sanitize string input
function sanitizeString($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

// Get user IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Log admin action (disabled - table doesn't exist)
function logAdminAction($mysqli, $adminId, $action, $targetType = null, $targetId = null, $details = null) {
    // Table Admin_Audit_Log not in schema - logging disabled
    return;
}
