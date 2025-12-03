<?php
/**
 * Authentication API endpoints
 * Handles login, register, logout, and profile management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

// Route based on request method and path
switch ($method) {
    case 'POST':
        $path = $_SERVER['PATH_INFO'] ?? '';
        if (strpos($path, '/login') !== false) {
            handleLogin($mysqli);
        } elseif (strpos($path, '/register') !== false) {
            handleRegister($mysqli);
        } elseif (strpos($path, '/logout') !== false) {
            handleLogout($mysqli);
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;

    case 'GET':
        $path = $_SERVER['PATH_INFO'] ?? '';
        if (strpos($path, '/me') !== false) {
            handleGetProfile($mysqli);
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;

    case 'PUT':
        $path = $_SERVER['PATH_INFO'] ?? '';
        if (strpos($path, '/me') !== false) {
            handleUpdateProfile($mysqli);
        } elseif (strpos($path, '/change-password') !== false) {
            handleChangePassword($mysqli);
        } else {
            sendError('Invalid endpoint', 404);
        }
        break;

    default:
        sendError('Method not allowed', 405);
}

/**
 * Handle user login
 */
function handleLogin($mysqli) {
    $input = getJsonInput();

    // Validate input
    validateRequired($input, ['email', 'password']);

    $email = sanitizeString($input['email']);
    $password = $input['password'];

    if (!validateEmail($email)) {
        sendError('Invalid email format', 400);
    }

    // Find user by email
    $stmt = $mysqli->prepare(
        "SELECT User_ID, Email, Password_Hash, First_Name, Last_Name, Account_Type, Is_Active
         FROM Users
         WHERE Email = ?"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendError('Invalid email or password', 401);
    }

    if (!$user['Is_Active']) {
        sendError('Account is inactive', 403);
    }

    // Verify password
    if (!password_verify($password, $user['Password_Hash'])) {
        sendError('Invalid email or password', 401);
    }

    // Update last login
    $stmt = $mysqli->prepare("UPDATE Users SET Last_Login = NOW() WHERE User_ID = ?");
    $stmt->bind_param('i', $user['User_ID']);
    $stmt->execute();
    $stmt->close();

    // Generate JWT token
    $payload = [
        'user_id' => $user['User_ID'],
        'email' => $user['Email'],
        'role' => $user['Account_Type']
    ];

    $token = JWT::encode($payload, JWT_SECRET_KEY, JWT_EXPIRATION);

    // Return user data and token
    sendResponse([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['User_ID'],
            'email' => $user['Email'],
            'first_name' => $user['First_Name'],
            'last_name' => $user['Last_Name'],
            'role' => $user['Account_Type']
        ]
    ]);
}

/**
 * Handle user registration
 */
function handleRegister($mysqli) {
    $input = getJsonInput();

    // Validate input
    validateRequired($input, ['first_name', 'last_name', 'email', 'password']);

    $firstName = sanitizeString($input['first_name']);
    $lastName = sanitizeString($input['last_name']);
    $email = sanitizeString($input['email']);
    $password = $input['password'];

    // Validate email
    if (!validateEmail($email)) {
        sendError('Invalid email format', 400);
    }

    // Validate password
    $passwordValidation = validatePassword($password);
    if ($passwordValidation !== true) {
        sendError($passwordValidation, 400);
    }

    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT User_ID FROM Users WHERE Email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        sendError('Email already registered', 409);
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    // Insert new user
    $stmt = $mysqli->prepare(
        "INSERT INTO Users (First_Name, Last_Name, Email, Password_Hash, Account_Type)
         VALUES (?, ?, ?, ?, 'user')"
    );
    $stmt->bind_param('ssss', $firstName, $lastName, $email, $passwordHash);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create account', 500);
    }

    $userId = $stmt->insert_id;
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Account created successfully',
        'user_id' => $userId
    ], 201);
}

/**
 * Handle logout (client-side token removal, server-side logging)
 */
function handleLogout($mysqli) {
    // This is mainly for logging purposes
    // Actual logout happens client-side by removing the token

    try {
        $user = requireAuth($mysqli);
        sendResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    } catch (Exception $e) {
        sendResponse([
            'success' => true,
            'message' => 'Logged out'
        ]);
    }
}

/**
 * Get current user profile
 */
function handleGetProfile($mysqli) {
    $user = requireAuth($mysqli);

    // Get full user details
    $stmt = $mysqli->prepare(
        "SELECT User_ID, First_Name, Last_Name, Email, Account_Type, Country, State, Address, Date_Created, Last_Login
         FROM Users
         WHERE User_ID = ?"
    );
    $stmt->bind_param('i', $user['User_ID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();

    if (!$profile) {
        sendError('User not found', 404);
    }

    sendResponse([
        'success' => true,
        'user' => [
            'id' => $profile['User_ID'],
            'first_name' => $profile['First_Name'],
            'last_name' => $profile['Last_Name'],
            'email' => $profile['Email'],
            'role' => $profile['Account_Type'],
            'country' => $profile['Country'],
            'state' => $profile['State'],
            'address' => $profile['Address'],
            'created_at' => $profile['Date_Created'],
            'last_login' => $profile['Last_Login']
        ]
    ]);
}

/**
 * Update user profile
 */
function handleUpdateProfile($mysqli) {
    $user = requireAuth($mysqli);
    $input = getJsonInput();

    // Fields allowed for update
    $fieldMapping = [
        'first_name' => 'First_Name',
        'last_name' => 'Last_Name',
        'country' => 'Country',
        'state' => 'State',
        'address' => 'Address'
    ];

    $updates = [];
    $params = [];
    $types = '';

    foreach ($fieldMapping as $inputField => $dbField) {
        if (isset($input[$inputField])) {
            $updates[] = "$dbField = ?";
            $params[] = sanitizeString($input[$inputField]);
            $types .= 's';
        }
    }

    if (empty($updates)) {
        sendError('No valid fields to update', 400);
    }

    $params[] = $user['User_ID'];
    $types .= 'i';

    $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE User_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to update profile', 500);
    }

    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
}

/**
 * Change password
 */
function handleChangePassword($mysqli) {
    $user = requireAuth($mysqli);
    $input = getJsonInput();

    validateRequired($input, ['current_password', 'new_password']);

    $currentPassword = $input['current_password'];
    $newPassword = $input['new_password'];

    // Get current password hash
    $stmt = $mysqli->prepare("SELECT Password_Hash FROM Users WHERE User_ID = ?");
    $stmt->bind_param('i', $user['User_ID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();

    // Verify current password
    if (!password_verify($currentPassword, $userData['Password_Hash'])) {
        sendError('Current password is incorrect', 401);
    }

    // Validate new password
    $passwordValidation = validatePassword($newPassword);
    if ($passwordValidation !== true) {
        sendError($passwordValidation, 400);
    }

    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);

    // Update password
    $stmt = $mysqli->prepare("UPDATE Users SET Password_Hash = ? WHERE User_ID = ?");
    $stmt->bind_param('si', $newPasswordHash, $user['User_ID']);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to update password', 500);
    }

    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
}
