<?php
/**
 * New Horizon API Router
 * Main entry point for all API requests
 */

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/backend', '', $path);
$path = str_replace('/index.php', '', $path);

// Route to appropriate API file
if (strpos($path, '/api/auth') === 0) {
    require_once __DIR__ . '/api/auth.php';
} elseif (strpos($path, '/api/admin') === 0) {
    require_once __DIR__ . '/api/admin.php';
} elseif (strpos($path, '/api/resources') === 0) {
    require_once __DIR__ . '/api/resources.php';
} elseif (strpos($path, '/api/exercises') === 0) {
    require_once __DIR__ . '/api/exercises.php';
} elseif (strpos($path, '/api/calendar') === 0) {
    require_once __DIR__ . '/api/calendar.php';
} elseif (strpos($path, '/api/opportunities') === 0) {
    require_once __DIR__ . '/api/opportunities.php';
} else {
    // API documentation
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'New Horizon API',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/login' => 'User login',
                'POST /api/auth/register' => 'User registration',
                'POST /api/auth/logout' => 'User logout',
                'GET /api/auth/me' => 'Get current user profile',
                'PUT /api/auth/me' => 'Update user profile',
                'PUT /api/auth/change-password' => 'Change password'
            ],
            'admin' => [
                'GET /api/admin/dashboard/stats' => 'Get dashboard statistics',
                'GET /api/admin/users' => 'Get all users',
                'PUT /api/admin/users/{id}' => 'Update user',
                'DELETE /api/admin/users/{id}' => 'Delete user',
                'GET /api/admin/audit-log' => 'Get audit log'
            ]
        ]
    ], JSON_PRETTY_PRINT);
}
