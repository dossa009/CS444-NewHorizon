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
    $stats['total_users'] = $result->fetch_assoc()['total_users'];

    // New users this month
    $result = $mysqli->query(
        "SELECT COUNT(*) as new_users FROM Users
         WHERE Date_Created >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    $stats['new_users_month'] = $result->fetch_assoc()['new_users'];

    // Admin count
    $result = $mysqli->query("SELECT COUNT(*) as admin_count FROM Users WHERE Account_Type = 'admin'");
    $stats['admin_count'] = $result->fetch_assoc()['admin_count'];

    // Regular users count
    $result = $mysqli->query("SELECT COUNT(*) as user_count FROM Users WHERE Account_Type = 'user'");
    $stats['user_count'] = $result->fetch_assoc()['user_count'];

    // Recent activity
    $result = $mysqli->query(
        "SELECT COUNT(*) as active_users FROM Users
         WHERE Last_Login >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $stats['active_users_week'] = $result->fetch_assoc()['active_users'];

    // Resources stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Resources");
    $resourcesTotal = $result->fetch_assoc()['total'];
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Resources WHERE Is_Published = TRUE");
    $resourcesPublished = $result->fetch_assoc()['total'];

    // Forum stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Forum_Posts");
    $forumTotal = $result->fetch_assoc()['total'];
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Forum_Posts WHERE Status = 'pending'");
    $forumPending = $result->fetch_assoc()['total'];

    // Events stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar_Events");
    $eventsTotal = $result->fetch_assoc()['total'];
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar_Events WHERE Event_Date >= CURDATE()");
    $eventsUpcoming = $result->fetch_assoc()['total'];

    // Contact messages stats
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Contact_Messages");
    $messagesTotal = $result->fetch_assoc()['total'];
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Contact_Messages WHERE Status = 'new'");
    $messagesUnread = $result->fetch_assoc()['total'];

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
                'total_resources' => (int)$resourcesTotal,
                'published_resources' => (int)$resourcesPublished
            ],
            'forum' => [
                'total_posts' => (int)$forumTotal,
                'pending_posts' => (int)$forumPending
            ],
            'events' => [
                'total_events' => (int)$eventsTotal,
                'upcoming_events' => (int)$eventsUpcoming
            ],
            'messages' => [
                'total_messages' => (int)$messagesTotal,
                'unread_messages' => (int)$messagesUnread
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

    // Log admin action
    $details = implode('; ', $changes);
    logAdminAction($mysqli, $admin['User_ID'], 'UPDATE_USER', 'user', $userId, $details);

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

    // Log admin action
    logAdminAction($mysqli, $admin['User_ID'], 'DELETE_USER', 'user', $userId, "Deactivated user: {$user['Email']}");

    sendResponse([
        'success' => true,
        'message' => 'User deleted successfully'
    ]);
}

/**
 * Get audit log
 */
function handleGetAuditLog($mysqli) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(MAX_PAGE_SIZE, max(1, (int)$_GET['limit'])) : DEFAULT_PAGE_SIZE;
    $offset = ($page - 1) * $limit;

    // Get total count
    $result = $mysqli->query("SELECT COUNT(*) as total FROM Admin_Audit_Log");
    $total = $result->fetch_assoc()['total'];

    // Get audit logs
    $stmt = $mysqli->prepare(
        "SELECT l.*, u.First_Name, u.Last_Name, u.Email
         FROM Admin_Audit_Log l
         JOIN Users u ON l.Admin_ID = u.User_ID
         ORDER BY l.Created_At DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id' => (int)$row['Log_ID'],
            'admin' => [
                'id' => (int)$row['Admin_ID'],
                'name' => $row['First_Name'] . ' ' . $row['Last_Name'],
                'email' => $row['Email']
            ],
            'action' => $row['Action'],
            'target_type' => $row['Target_Type'],
            'target_id' => $row['Target_ID'] ? (int)$row['Target_ID'] : null,
            'details' => $row['Details'],
            'ip_address' => $row['IP_Address'],
            'created_at' => $row['Created_At']
        ];
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'logs' => $logs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get all resources
 */
function handleGetResources($mysqli) {
    $result = $mysqli->query(
        "SELECT r.*, u.First_Name, u.Last_Name
         FROM Resources r
         LEFT JOIN Users u ON r.Created_By = u.User_ID
         ORDER BY r.Created_At DESC"
    );

    $resources = [];
    while ($row = $result->fetch_assoc()) {
        $resources[] = [
            'id' => (int)$row['Resource_ID'],
            'title' => $row['Title'],
            'category' => $row['Category'],
            'views_count' => (int)$row['Views_Count'],
            'is_published' => (bool)$row['Is_Published'],
            'created_at' => $row['Created_At']
        ];
    }

    sendResponse([
        'success' => true,
        'resources' => $resources
    ]);
}

/**
 * Get all forum posts
 */
function handleGetForumPosts($mysqli) {
    $result = $mysqli->query(
        "SELECT p.*, u.First_Name, u.Last_Name
         FROM Forum_Posts p
         LEFT JOIN Users u ON p.Author_ID = u.User_ID
         ORDER BY p.Created_At DESC"
    );

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id' => (int)$row['Post_ID'],
            'title' => $row['Title'],
            'author_name' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Anonymous',
            'status' => $row['Status'],
            'views_count' => (int)$row['Views_Count'],
            'created_at' => $row['Created_At']
        ];
    }

    sendResponse(['success' => true, 'posts' => $posts]);
}

/**
 * Get all events
 */
function handleGetEvents($mysqli) {
    $result = $mysqli->query(
        "SELECT e.*,
         (SELECT COUNT(*) FROM Event_Registrations WHERE Event_ID = e.Event_ID) as registered_count
         FROM Calendar_Events e
         ORDER BY e.Event_Date DESC"
    );

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => (int)$row['Event_ID'],
            'title' => $row['Title'],
            'event_date' => $row['Event_Date'],
            'event_type' => $row['Event_Type'],
            'registered_count' => (int)$row['registered_count'],
            'max_participants' => $row['Max_Participants'] ? (int)$row['Max_Participants'] : null,
            'is_published' => (bool)$row['Is_Published']
        ];
    }

    sendResponse(['success' => true, 'events' => $events]);
}

/**
 * Get all exercises
 */
function handleGetExercises($mysqli) {
    $result = $mysqli->query(
        "SELECT * FROM Exercises ORDER BY Created_At DESC"
    );

    $exercises = [];
    while ($row = $result->fetch_assoc()) {
        $exercises[] = [
            'id' => (int)$row['Exercise_ID'],
            'title' => $row['Title'],
            'category' => $row['Exercise_Type'],
            'difficulty' => $row['Difficulty'],
            'duration_minutes' => (int)$row['Duration_Minutes'],
            'is_published' => (bool)$row['Is_Published']
        ];
    }

    sendResponse(['success' => true, 'exercises' => $exercises]);
}

/**
 * Get all opportunities
 */
function handleGetOpportunities($mysqli) {
    $result = $mysqli->query(
        "SELECT * FROM Opportunities ORDER BY Created_At DESC"
    );

    $opportunities = [];
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = [
            'id' => (int)$row['Opportunity_ID'],
            'title' => $row['Title'],
            'opportunity_type' => $row['Opportunity_Type'],
            'organization' => $row['Organization'],
            'is_remote' => (bool)$row['Is_Remote'],
            'is_published' => (bool)$row['Is_Published']
        ];
    }

    sendResponse(['success' => true, 'opportunities' => $opportunities]);
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
