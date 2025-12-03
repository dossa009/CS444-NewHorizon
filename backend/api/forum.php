<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/forum(\/.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

if ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    handleGetPost($mysqli, (int)$matches[1]);
} elseif ($method === 'GET') {
    handleGetAllPosts($mysqli);
} elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreatePost($mysqli, $user);
} elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleUpdatePost($mysqli, $user, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleDeletePost($mysqli, $user, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

function handleGetAllPosts($mysqli) {
    $result = $mysqli->query(
        "SELECT d.Discussion_ID, d.User_ID, d.Discussion_Title, d.Date_Created, u.First_Name, u.Last_Name
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
                'user_id' => $row['User_ID'] ? (int)$row['User_ID'] : null,
                'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Anonymous',
                'created_at' => $row['Date_Created']
            ];
        }
    }

    sendResponse(['success' => true, 'posts' => $posts]);
}

function handleGetPost($mysqli, $id) {
    $stmt = $mysqli->prepare(
        "SELECT d.Discussion_ID, d.User_ID, d.Discussion_Title, d.Date_Created, u.First_Name, u.Last_Name
         FROM Discussion d
         LEFT JOIN Users u ON d.User_ID = u.User_ID
         WHERE d.Discussion_ID = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Post not found', 404);
    }

    sendResponse([
        'success' => true,
        'post' => [
            'id' => (int)$row['Discussion_ID'],
            'title' => $row['Discussion_Title'],
            'user_id' => $row['User_ID'] ? (int)$row['User_ID'] : null,
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : 'Anonymous',
            'created_at' => $row['Date_Created']
        ]
    ]);
}

function handleCreatePost($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title']);

    $title = sanitizeString($input['title']);
    $userId = $user['User_ID'];

    $stmt = $mysqli->prepare("INSERT INTO Discussion (User_ID, Discussion_Title) VALUES (?, ?)");
    $stmt->bind_param('is', $userId, $title);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create post', 500);
    }

    $postId = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Post created', 'post_id' => $postId], 201);
}

function handleUpdatePost($mysqli, $user, $id) {
    $input = getJsonInput();

    $stmt = $mysqli->prepare("SELECT User_ID FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    if (!$post) {
        sendError('Post not found', 404);
    }

    if ($post['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    if (!isset($input['title'])) {
        sendError('No fields to update', 400);
    }

    $title = sanitizeString($input['title']);

    $stmt = $mysqli->prepare("UPDATE Discussion SET Discussion_Title = ? WHERE Discussion_ID = ?");
    $stmt->bind_param('si', $title, $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Post updated']);
}

function handleDeletePost($mysqli, $user, $id) {
    $stmt = $mysqli->prepare("SELECT User_ID FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $stmt->close();

    if (!$post) {
        sendError('Post not found', 404);
    }

    if ($post['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Discussion WHERE Discussion_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Post deleted']);
}
