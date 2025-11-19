<?php
/**
 * Resources API endpoints
 * Handles CRUD operations for resources
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// Route based on request method and path
if ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    handleGetResource($mysqli, (int)$matches[1]);
} elseif ($method === 'GET') {
    handleGetAllResources($mysqli);
} elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreateResource($mysqli, $user);
} elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleUpdateResource($mysqli, $user, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleDeleteResource($mysqli, $user, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

/**
 * Get all resources
 */
function handleGetAllResources($mysqli) {
    $published = isset($_GET['published']) ? (bool)$_GET['published'] : null;
    $category = isset($_GET['category']) ? sanitizeString($_GET['category']) : null;

    $where = [];
    $params = [];
    $types = '';

    if ($published !== null) {
        $where[] = "Is_Published = ?";
        $params[] = $published ? 1 : 0;
        $types .= 'i';
    }

    if ($category) {
        $where[] = "Category = ?";
        $params[] = $category;
        $types .= 's';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT r.*, u.First_Name, u.Last_Name
            FROM Resources r
            LEFT JOIN Users u ON r.Created_By = u.User_ID
            $whereClause
            ORDER BY r.Created_At DESC";

    if (!empty($params)) {
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sql);
    }

    $resources = [];
    while ($row = $result->fetch_assoc()) {
        $resources[] = [
            'id' => (int)$row['Resource_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'content' => $row['Content'],
            'category' => $row['Category'],
            'tags' => $row['Tags'] ? json_decode($row['Tags'], true) : [],
            'external_url' => $row['External_URL'],
            'views_count' => (int)$row['Views_Count'],
            'is_published' => (bool)$row['Is_Published'],
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'created_at' => $row['Created_At'],
            'updated_at' => $row['Updated_At']
        ];
    }

    if (!empty($params)) {
        $stmt->close();
    }

    sendResponse([
        'success' => true,
        'resources' => $resources
    ]);
}

/**
 * Get single resource
 */
function handleGetResource($mysqli, $id) {
    $stmt = $mysqli->prepare(
        "SELECT r.*, u.First_Name, u.Last_Name
         FROM Resources r
         LEFT JOIN Users u ON r.Created_By = u.User_ID
         WHERE r.Resource_ID = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Resource not found', 404);
    }

    // Increment view count
    $mysqli->query("UPDATE Resources SET Views_Count = Views_Count + 1 WHERE Resource_ID = $id");

    sendResponse([
        'success' => true,
        'resource' => [
            'id' => (int)$row['Resource_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'content' => $row['Content'],
            'category' => $row['Category'],
            'tags' => $row['Tags'] ? json_decode($row['Tags'], true) : [],
            'external_url' => $row['External_URL'],
            'views_count' => (int)$row['Views_Count'] + 1,
            'is_published' => (bool)$row['Is_Published'],
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'created_at' => $row['Created_At'],
            'updated_at' => $row['Updated_At']
        ]
    ]);
}

/**
 * Create resource
 */
function handleCreateResource($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title', 'description']);

    $title = sanitizeString($input['title']);
    $description = sanitizeString($input['description']);
    $content = isset($input['content']) ? sanitizeString($input['content']) : null;
    $category = isset($input['category']) ? sanitizeString($input['category']) : null;
    $tags = isset($input['tags']) && is_array($input['tags']) ? json_encode($input['tags']) : null;
    $externalUrl = isset($input['external_url']) ? sanitizeString($input['external_url']) : null;
    $isPublished = isset($input['is_published']) ? (bool)$input['is_published'] : false;

    $stmt = $mysqli->prepare(
        "INSERT INTO Resources (Title, Description, Content, Category, Tags, External_URL, Is_Published, Created_By)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'ssssssii',
        $title,
        $description,
        $content,
        $category,
        $tags,
        $externalUrl,
        $isPublished,
        $user['User_ID']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create resource', 500);
    }

    $resourceId = $stmt->insert_id;
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Resource created successfully',
        'resource_id' => $resourceId
    ], 201);
}

/**
 * Update resource
 */
function handleUpdateResource($mysqli, $user, $id) {
    $input = getJsonInput();

    // Check if resource exists and user has permission
    $stmt = $mysqli->prepare("SELECT Created_By FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();
    $stmt->close();

    if (!$resource) {
        sendError('Resource not found', 404);
    }

    // Only creator or admin can update
    if ($resource['Created_By'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    $updates = [];
    $params = [];
    $types = '';

    if (isset($input['title'])) {
        $updates[] = "Title = ?";
        $params[] = sanitizeString($input['title']);
        $types .= 's';
    }

    if (isset($input['description'])) {
        $updates[] = "Description = ?";
        $params[] = sanitizeString($input['description']);
        $types .= 's';
    }

    if (isset($input['content'])) {
        $updates[] = "Content = ?";
        $params[] = sanitizeString($input['content']);
        $types .= 's';
    }

    if (isset($input['category'])) {
        $updates[] = "Category = ?";
        $params[] = sanitizeString($input['category']);
        $types .= 's';
    }

    if (isset($input['tags'])) {
        $updates[] = "Tags = ?";
        $params[] = is_array($input['tags']) ? json_encode($input['tags']) : null;
        $types .= 's';
    }

    if (isset($input['external_url'])) {
        $updates[] = "External_URL = ?";
        $params[] = sanitizeString($input['external_url']);
        $types .= 's';
    }

    if (isset($input['is_published'])) {
        $updates[] = "Is_Published = ?";
        $params[] = (bool)$input['is_published'] ? 1 : 0;
        $types .= 'i';
    }

    if (empty($updates)) {
        sendError('No fields to update', 400);
    }

    $params[] = $id;
    $types .= 'i';

    $sql = "UPDATE Resources SET " . implode(', ', $updates) . " WHERE Resource_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to update resource', 500);
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Resource updated successfully'
    ]);
}

/**
 * Delete resource
 */
function handleDeleteResource($mysqli, $user, $id) {
    // Check if resource exists and user has permission
    $stmt = $mysqli->prepare("SELECT Created_By FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();
    $stmt->close();

    if (!$resource) {
        sendError('Resource not found', 404);
    }

    // Only creator or admin can delete
    if ($resource['Created_By'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to delete resource', 500);
    }
    $stmt->close();

    sendResponse([
        'success' => true,
        'message' => 'Resource deleted successfully'
    ]);
}
