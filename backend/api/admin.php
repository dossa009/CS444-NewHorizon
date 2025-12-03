<?php
/**
 * Admin API endpoints
 * Handles admin dashboard stats and user management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// All admin endpoints require admin authentication
$admin = requireAdmin($mysqli);

// Route based on request method and path
if ($method === 'GET' && strpos($path, '/dashboard/stats') !== false) {
    handleGetDashboardStats($mysqli);
} elseif ($method === 'GET' && strpos($path, '/users') !== false) {
    handleGetUsers($mysqli);
} elseif ($method === 'PUT' && preg_match('/\/users\/(\d+)/', $path, $matches)) {
    handleUpdateUser($mysqli, $admin, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/users\/(\d+)/', $path, $matches)) {
    handleDeleteUser($mysqli, $admin, (int)$matches[1]);
} elseif ($method === 'GET' && strpos($path, '/audit-log') !== false) {
    handleGetAuditLog($mysqli);
} elseif ($method === 'GET' && strpos($path, '/resources') !== false) {
    handleGetResources($mysqli);
} elseif ($method === 'GET' && strpos($path, '/forum-posts') !== false) {
    handleGetForumPosts($mysqli);
} elseif ($method === 'GET' && strpos($path, '/calendar-events') !== false) {
    handleGetEvents($mysqli);
} elseif ($method === 'GET' && strpos($path, '/exercises') !== false) {
    handleGetExercises($mysqli);
} elseif ($method === 'GET' && strpos($path, '/opportunities') !== false) {
    handleGetOpportunities($mysqli);
} elseif ($method === 'GET' && strpos($path, '/contact-messages') !== false) {
    handleGetContactMessages($mysqli);
} elseif ($method === 'PUT' && preg_match('/\/contact-messages\/(\d+)/', $path, $matches)) {
    handleUpdateContactMessage($mysqli, $admin, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

/**
 * Get dashboard statistics
 */
function handleGetDashboardStats($mysqli) {
    $stats = [];

    // Total users
    $result = $mysqli->query("SELECT COUNT(*) as total_users FROM Users WHERE Is_Active = TRUE");
    $stats['total_users'] = $result ? $result->fetch_assoc()['total_users'] : 0;

    // New users this month
    $result = $mysqli->query(
        "SELECT COUNT(*) as new_users FROM Users
         WHERE Date_Created >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $stats['new_users_month'] = $result ? $result->fetch_assoc()['new_users'] : 0;

    // Admin count
    $result = $mysqli->query("SELECT COUNT(*) as admin_count FROM Users WHERE Account_Type = 'admin'");
    $stats['admin_count'] = $result ? $result->fetch_assoc()['admin_count'] : 0;

    // Regular users count
    $result = $mysqli->query("SELECT COUNT(*) as user_count FROM Users WHERE Account_Type = 'user'");
    $stats['user_count'] = $result ? $result->fetch_assoc()['user_count'] : 0;

    // Recent activity
    $result = $mysqli->query(
        "SELECT COUNT(*) as active_users FROM Users
         WHERE Last_Login >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $stats['active_users_week'] = $result ? $result->fetch_assoc()['active_users'] : 0;

    // Resources stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Resources");
    $resourcesTotal = $result ? $result->fetch_assoc()['total'] : 0;

    // Discussion stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Discussion");
    $discussionTotal = $result ? $result->fetch_assoc()['total'] : 0;

    // Calendar stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar");
    $eventsTotal = $result ? $result->fetch_assoc()['total'] : 0;
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar WHERE Date >= CURDATE()");
    $eventsUpcoming = $result ? $result->fetch_assoc()['total'] : 0;

    // Exercises stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Exercises");
    $exercisesTotal = $result ? $result->fetch_assoc()['total'] : 0;

    sendResponse([
        'success' => true,
        'stats' => [
            'users' => [
                'total_users' => (int)$stats['total_users'],
                'new_users_month' => (int)$stats['new_users_month'],
                'admin_count' => (int)$stats['admin_count'],
                'user_count' => (int)$stats['user_count'],
                'active_users_week' => (int)$stats['active_users_week']
            ],
            'resources' => [
                'total_resources' => (int)$resourcesTotal
            ],
            'discussion' => [
                'total_posts' => (int)$discussionTotal
            ],
            'events' => [
                'total_events' => (int)$eventsTotal,
                'upcoming_events' => (int)$eventsUpcoming
            ],
            'exercises' => [
                'total_exercises' => (int)$exercisesTotal
            ]
        ]
    ]);
}

/**
 * Get all users with pagination
 */
function handleGetUsers($mysqli) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(MAX_PAGE_SIZE, max(1, (int)$_GET['limit'])) : DEFAULT_PAGE_SIZE;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';
    $role = isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin']) ? $_GET['role'] : null;

    // Build query
    $where = ["Is_Active = TRUE"];
    $params = [];
    $types = '';

    if ($search) {
        $where[] = "(First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    if ($role) {
        $where[] = "Account_Type = ?";
        $params[] = $role;
        $types .= 's';
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM Users WHERE $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($countSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $result = $mysqli->query($countSql);
        $total = $result->fetch_assoc()['total'];
    }

    // Get users
    $sql = "SELECT User_ID, First_Name, Last_Name, Email, Account_Type, Is_Active, Date_Created, Last_Login
            FROM Users
            WHERE $whereClause
            ORDER BY Date_Created DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['User_ID'],
            'first_name' => $row['First_Name'],
            'last_name' => $row['Last_Name'],
            'email' => $row['Email'],
            'role' => $row['Account_Type'],
            'is_active' => (bool)$row['Is_Active'],
            'created_at' => $row['Date_Created'],
            'last_login' => $row['Last_Login']
        ];
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'users' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Update user (role or status)
 */
function handleUpdateUser($mysqli, $admin, $userId) {
    $input = getJsonInput();

    // Prevent self-modification
    if ($userId === $admin['User_ID']) {
        sendError('Cannot modify your own account', 403);
    }

    // Check if user exists
    $stmt = $mysqli->prepare("SELECT User_ID, Account_Type FROM Users WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendError('User not found', 404);
    }

    $allowedFields = ['role', 'is_active'];
    $updates = [];
    $params = [];
    $types = '';
    $changes = [];

    // Update role
    if (isset($input['role']) && in_array($input['role'], ['user', 'admin'])) {
        $updates[] = "Account_Type = ?";
        $params[] = $input['role'];
        $types .= 's';
        $changes[] = "Changed role from {$user['Account_Type']} to {$input['role']}";
    }

    // Update active status
    if (isset($input['is_active'])) {
        $isActive = (bool)$input['is_active'];
        $updates[] = "Is_Active = ?";
        $params[] = $isActive ? 1 : 0;
        $types .= 'i';
        $changes[] = ($isActive ? 'Activated' : 'Deactivated') . ' account';
    }

    if (empty($updates)) {
        sendError('No valid fields to update', 400);
    }

    $params[] = $userId;
    $types .= 'i';

    // Update user
    $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE User_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to update user', 500);
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
}

/**
 * Delete user (soft delete by deactivating)
 */
function handleDeleteUser($mysqli, $admin, $userId) {
    // Prevent self-deletion
    if ($userId === $admin['User_ID']) {
        sendError('Cannot delete your own account', 403);
    }

    // Check if user exists
    $stmt = $mysqli->prepare("SELECT User_ID, Email FROM Users WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendError('User not found', 404);
    }

    // Soft delete (deactivate) instead of hard delete
    $stmt = $mysqli->prepare("UPDATE Users SET Is_Active = FALSE WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to delete user', 500);
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
}

/**
 * Get audit log
 */
function handleGetAuditLog($mysqli) {
    sendResponse([
        'success' => true,
        'logs' => [],
        'pagination' => [
            'page' => 1,
            'limit' => 20,
            'total' => 0,
            'pages' => 0
        ]
    ]);
}

/**
 * Get all resources
 */
function handleGetResources($mysqli) {
    $result = $mysqli->query(
        "SELECT Resource_ID, Webpage_ID, Title, Description, Resource_URL
         FROM Resources
         ORDER BY Resource_ID DESC"
    );

    $resources = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $resources[] = [
                'id' => (int)$row['Resource_ID'],
                'webpage_id' => $row['Webpage_ID'] ? (int)$row['Webpage_ID'] : null,
                'title' => $row['Title'],
                'description' => $row['Description'],
                'resource_url' => $row['Resource_URL']
            ];
        }
    }

    sendResponse([
        'success' => true,
        'resources' => $resources
    ]);
}

/**
 * Get all discussions
 */
function handleGetForumPosts($mysqli) {
    $result = $mysqli->query(
        "SELECT d.Discussion_ID, d.Discussion_Title, d.Date_Created, d.User_ID,
                u.First_Name, u.Last_Name
         FROM Discussion d
         LEFT JOIN Users u ON d.User_ID = u.User_ID
         ORDER BY d.Date_Created DESC"
    );

    $posts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = [
                'id' => (int)$row['Discussion_ID'],
                'title' => $row['Discussion_Title'],
                'author_name' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Anonymous',
                'created_at' => $row['Date_Created']
            ];
        }
    }

    sendResponse(['success' => true, 'posts' => $posts]);
}

/**
 * Get all events
 */
function handleGetEvents($mysqli) {
    $result = $mysqli->query(
        "SELECT c.Event_ID, c.User_ID, c.URL, c.Date,
                u.First_Name, u.Last_Name
         FROM Calendar c
         LEFT JOIN Users u ON c.User_ID = u.User_ID
         ORDER BY c.Date DESC"
    );

    $events = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => (int)$row['Event_ID'],
                'user_name' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Unknown',
                'url' => $row['URL'],
                'event_date' => $row['Date']
            ];
        }
    }

    sendResponse(['success' => true, 'events' => $events]);
}

/**
 * Get all exercises
 */
function handleGetExercises($mysqli) {
    $result = $mysqli->query(
        "SELECT Exercise_ID, Webpage_ID, Name, Description, Exercise_URL
         FROM Exercises
         ORDER BY Exercise_ID DESC"
    );

    $exercises = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $exercises[] = [
                'id' => (int)$row['Exercise_ID'],
                'webpage_id' => $row['Webpage_ID'] ? (int)$row['Webpage_ID'] : null,
                'name' => $row['Name'],
                'description' => $row['Description'],
                'exercise_url' => $row['Exercise_URL']
            ];
        }
    }

    sendResponse(['success' => true, 'exercises' => $exercises]);
}

/**
 * Get all opportunities
 */
function handleGetOpportunities($mysqli) {
    sendResponse(['success' => true, 'opportunities' => []]);
}

/**
 * Get all contact messages (mock data)
 */
function handleGetContactMessages($mysqli) {
    sendResponse([
        'success' => true,
        'messages' => []
    ]);
}

/**
 * Update contact message (mock)
 */
function handleUpdateContactMessage($mysqli, $admin, $messageId) {
    $input = getJsonInput();

    sendResponse([
        'success' => true,
        'message' => 'Contact message updated successfully'
    ]);
}
