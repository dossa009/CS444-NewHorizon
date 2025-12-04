<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = '';

if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/forum\.php(.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

if ($method === 'GET' && preg_match('/^\/(\d+)\/replies$/', $path, $m)) {
    handleGetReplies($mysqli, (int)$m[1]);
}
elseif ($method === 'POST' && preg_match('/^\/(\d+)\/replies$/', $path, $m)) {
    $user = requireAuth($mysqli);
    handleCreateReply($mysqli, $user, (int)$m[1]);
}
elseif ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $m)) {
    handleGetPost($mysqli, (int)$m[1]);
}
elseif ($method === 'GET') {
    handleGetAllPosts($mysqli);
}
elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreatePost($mysqli, $user);
}
elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $m)) {
    $user = requireAuth($mysqli);
    handleUpdatePost($mysqli, $user, (int)$m[1]);
}
elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $m)) {
    $user = requireAuth($mysqli);
    handleDeletePost($mysqli, $user, (int)$m[1]);
}
else {
    sendError('Invalid endpoint', 404);
}

function handleGetAllPosts($mysqli) {
    $search   = $_GET['search'] ?? '';
    $sort     = $_GET['sort'] ?? 'new';
    $category = $_GET['category'] ?? '';

    $sql = "
        SELECT d.Discussion_ID, d.User_ID, d.Discussion_Title,
               d.Content, d.Category, d.Date_Created,
               u.First_Name, u.Last_Name
        FROM Discussion d
        LEFT JOIN Users u ON d.User_ID = u.User_ID
        WHERE 1=1
    ";

    if ($search !== '') {
        $searchEsc = '%' . $mysqli->real_escape_string($search) . '%';
        $sql .= " AND (d.Discussion_Title LIKE '$searchEsc'
                  OR d.Content LIKE '$searchEsc')";
    }

    if ($category !== '') {
        $catEsc = $mysqli->real_escape_string($category);
        $sql .= " AND d.Category = '$catEsc'";
    }

    switch ($sort) {
        case 'old': $sql .= " ORDER BY d.Date_Created ASC"; break;
        case 'az':  $sql .= " ORDER BY d.Discussion_Title ASC"; break;
        default:    $sql .= " ORDER BY d.Date_Created DESC"; break;
    }

    $result = $mysqli->query($sql);

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id'         => (int)$row['Discussion_ID'],
            'user_id'    => $row['User_ID'] ? (int)$row['User_ID'] : null,
            'title'      => $row['Discussion_Title'],
            'content'    => $row['Content'],
            'category'   => $row['Category'],
            'author'     => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Anonymous',
            'created_at' => $row['Date_Created']
        ];
    }

    sendResponse(['success' => true, 'posts' => $posts]);
}

function handleGetPost($mysqli, $id) {
    $stmt = $mysqli->prepare("
        SELECT d.Discussion_ID, d.User_ID, d.Discussion_Title,
               d.Content, d.Category, d.Date_Created,
               u.First_Name, u.Last_Name
        FROM Discussion d
        LEFT JOIN Users u ON d.User_ID = u.User_ID
        WHERE d.Discussion_ID = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$r) sendError('Post not found', 404);

    sendResponse([
        'success' => true,
        'post' => [
            'id'         => (int)$r['Discussion_ID'],
            'user_id'    => $r['User_ID'] ? (int)$r['User_ID'] : null,
            'title'      => $r['Discussion_Title'],
            'content'    => $r['Content'],
            'category'   => $r['Category'],
            'author'     => $r['First_Name'] ? $r['First_Name'].' '.$r['Last_Name'] : 'Anonymous',
            'created_at' => $r['Date_Created']
        ]
    ]);
}

function handleCreatePost($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title', 'content', 'category']);

    $title    = sanitizeString($input['title']);
    $content  = sanitizeString($input['content']);
    $category = sanitizeString($input['category']);
    $userId   = $user['User_ID'];

    $stmt = $mysqli->prepare("
        INSERT INTO Discussion (User_ID, Discussion_Title, Content, Category)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('isss', $userId, $title, $content, $category);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'post_id' => $id], 201);
}

function handleUpdatePost($mysqli, $user, $id) {
    $input = getJsonInput();
    validateRequired($input, ['title', 'content', 'category']);

    $stmt = $mysqli->prepare("SELECT User_ID FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$owner) sendError('Post not found', 404);
    if ($owner['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin')
        sendError('Permission denied', 403);

    $title    = sanitizeString($input['title']);
    $content  = sanitizeString($input['content']);
    $category = sanitizeString($input['category']);

    $stmt = $mysqli->prepare("
        UPDATE Discussion SET Discussion_Title = ?, Content = ?, Category = ?
        WHERE Discussion_ID = ?
    ");
    $stmt->bind_param('sssi', $title, $content, $category, $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Post updated']);
}

function handleDeletePost($mysqli, $user, $id) {
    $stmt = $mysqli->prepare("SELECT User_ID FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$owner) sendError('Post not found', 404);
    if ($owner['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin')
        sendError('Permission denied', 403);

    $stmt = $mysqli->prepare("DELETE FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Post deleted']);
}

function handleGetReplies($mysqli, $discussionId) {
    $stmt = $mysqli->prepare("
        SELECT r.Reply_ID, r.Discussion_ID, r.User_ID, r.Content, r.Date_Created,
               u.First_Name, u.Last_Name
        FROM Replies r
        LEFT JOIN Users u ON r.User_ID = u.User_ID
        WHERE r.Discussion_ID = ?
        ORDER BY r.Date_Created ASC
    ");
    $stmt->bind_param('i', $discussionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $replies = [];
    while ($row = $result->fetch_assoc()) {
        $replies[] = [
            'id'          => (int)$row['Reply_ID'],
            'discussion_id' => (int)$row['Discussion_ID'],
            'user_id'     => $row['User_ID'] ? (int)$row['User_ID'] : null,
            'content'     => $row['Content'],
            'author'      => $row['First_Name'] ? $row['First_Name'].' '.$row['Last_Name'] : 'Anonymous',
            'created_at'  => $row['Date_Created']
        ];
    }

    sendResponse(['success' => true, 'replies' => $replies]);
}

function handleCreateReply($mysqli, $user, $discussionId) {
    $input = getJsonInput();
    validateRequired($input, ['content']);

    $content = sanitizeString($input['content']);
    $userId  = $user['User_ID'];

    $stmt = $mysqli->prepare("
        INSERT INTO Replies (Discussion_ID, User_ID, Content)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param('iis', $discussionId, $userId, $content);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'reply_id' => $id], 201);
}
