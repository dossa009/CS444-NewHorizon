<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/admin(\/.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

$admin = requireAdmin($mysqli);

if ($method === 'GET' && strpos($path, '/dashboard/stats') !== false) {
    handleGetDashboardStats($mysqli);
} elseif ($method === 'GET' && strpos($path, '/users') !== false && !preg_match('/\/users\/\d+/', $path)) {
    handleGetUsers($mysqli);
} elseif ($method === 'PUT' && preg_match('/\/users\/(\d+)/', $path, $matches)) {
    handleUpdateUser($mysqli, $admin, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/users\/(\d+)/', $path, $matches)) {
    handleDeleteUser($mysqli, $admin, (int)$matches[1]);
} elseif ($method === 'GET' && strpos($path, '/resources') !== false) {
    handleGetResources($mysqli);
} elseif ($method === 'GET' && strpos($path, '/forum-posts') !== false) {
    handleGetForumPosts($mysqli);
} elseif ($method === 'GET' && strpos($path, '/calendar-events') !== false) {
    handleGetEvents($mysqli);
} elseif ($method === 'GET' && strpos($path, '/exercises') !== false) {
    handleGetExercises($mysqli);
} else {
    sendError('Invalid endpoint', 404);
}

function handleGetDashboardStats($mysqli) {
    $stats = [];

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Users WHERE Is_Active = TRUE");
    $stats['total_users'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Users WHERE Date_Created >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stats['new_users_month'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Users WHERE Account_Type = 'admin'");
    $stats['admin_count'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Users WHERE Account_Type = 'user'");
    $stats['user_count'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Users WHERE Last_Login >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['active_users_week'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Resources");
    $resourcesTotal = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Discussion");
    $discussionTotal = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar");
    $eventsTotal = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $mysqli->query("SELECT COUNT(*) as total FROM Calendar WHERE Date >= CURDATE()");
    $eventsUpcoming = $result ? $result->fetch_assoc()['total'] : 0;

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
            'resources' => ['total_resources' => (int)$resourcesTotal],
            'discussion' => ['total_posts' => (int)$discussionTotal],
            'events' => ['total_events' => (int)$eventsTotal, 'upcoming_events' => (int)$eventsUpcoming],
            'exercises' => ['total_exercises' => (int)$exercisesTotal]
        ]
    ]);
}

function handleGetUsers($mysqli) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';
    $role = isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin']) ? $_GET['role'] : null;

    $where = ["Is_Active = TRUE"];
    $params = [];
    $types = '';

    if ($search) {
        $where[] = "(First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= 'sss';
    }

    if ($role) {
        $where[] = "Account_Type = ?";
        $params[] = $role;
        $types .= 's';
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM Users WHERE $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($countSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $total = $mysqli->query($countSql)->fetch_assoc()['total'];
    }

    $sql = "SELECT User_ID, First_Name, Last_Name, Email, Account_Type, Is_Active, Date_Created, Last_Login
            FROM Users WHERE $whereClause ORDER BY Date_Created DESC LIMIT ? OFFSET ?";

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
        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => (int)$total, 'pages' => ceil($total / $limit)]
    ]);
}

function handleUpdateUser($mysqli, $admin, $userId) {
    $input = getJsonInput();

    if ($userId === $admin['User_ID']) {
        sendError('Cannot modify your own account', 403);
    }

    $stmt = $mysqli->prepare("SELECT User_ID FROM Users WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        sendError('User not found', 404);
    }
    $stmt->close();

    $updates = [];
    $params = [];
    $types = '';

    if (isset($input['role']) && in_array($input['role'], ['user', 'admin'])) {
        $updates[] = "Account_Type = ?";
        $params[] = $input['role'];
        $types .= 's';
    }

    if (isset($input['is_active'])) {
        $updates[] = "Is_Active = ?";
        $params[] = (bool)$input['is_active'] ? 1 : 0;
        $types .= 'i';
    }

    if (empty($updates)) {
        sendError('No valid fields to update', 400);
    }

    $params[] = $userId;
    $types .= 'i';

    $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE User_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'User updated successfully']);
}

function handleDeleteUser($mysqli, $admin, $userId) {
    if ($userId === $admin['User_ID']) {
        sendError('Cannot delete your own account', 403);
    }

    $stmt = $mysqli->prepare("SELECT User_ID FROM Users WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        sendError('User not found', 404);
    }
    $stmt->close();

    $stmt = $mysqli->prepare("UPDATE Users SET Is_Active = FALSE WHERE User_ID = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'User deleted successfully']);
}

function handleGetResources($mysqli) {
    $result = $mysqli->query("SELECT Resource_ID, Webpage_ID, Title, Description, Resource_URL FROM Resources ORDER BY Resource_ID DESC");

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

    sendResponse(['success' => true, 'resources' => $resources]);
}

function handleGetForumPosts($mysqli) {
    $result = $mysqli->query(
        "SELECT d.Discussion_ID, d.Discussion_Title, d.Date_Created, u.First_Name, u.Last_Name
         FROM Discussion d LEFT JOIN Users u ON d.User_ID = u.User_ID ORDER BY d.Date_Created DESC"
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

function handleGetEvents($mysqli) {
    $result = $mysqli->query(
        "SELECT c.Event_ID, c.URL, c.Date, u.First_Name, u.Last_Name
         FROM Calendar c LEFT JOIN Users u ON c.User_ID = u.User_ID ORDER BY c.Date DESC"
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

function handleGetExercises($mysqli) {
    $result = $mysqli->query("SELECT Exercise_ID, Webpage_ID, Name, Description, Exercise_URL FROM Exercises ORDER BY Exercise_ID DESC");

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
