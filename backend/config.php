<?php
// Configuration file for New Horizon backend
// Security and general settings

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// JSON response
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// JWT Secret Key (CHANGE THIS IN PRODUCTION!)
define('JWT_SECRET_KEY', 'your-secret-key-change-this-in-production-2024');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 604800); // 7 days in seconds

// Password requirements
define('PASSWORD_MIN_LENGTH', 8);

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Database configuration (imported from DataBase.php)
require_once __DIR__ . '/DataBase.php';

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
    $headers = getallheaders();

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
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

// Log admin action
function logAdminAction($mysqli, $adminId, $action, $targetType = null, $targetId = null, $details = null) {
    $stmt = $mysqli->prepare(
        "INSERT INTO Admin_Audit_Log (Admin_ID, Action, Target_Type, Target_ID, Details, IP_Address)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $ip = getUserIP();
    $stmt->bind_param('ississ', $adminId, $action, $targetType, $targetId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
