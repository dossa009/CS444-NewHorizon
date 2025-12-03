<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/resources(\/.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

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

function handleGetAllResources($mysqli) {
    $result = $mysqli->query(
        "SELECT Resource_ID, Webpage_ID, Title, Description, Resource_URL FROM Resources ORDER BY Resource_ID DESC"
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

    sendResponse(['success' => true, 'resources' => $resources]);
}

function handleGetResource($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT Resource_ID, Webpage_ID, Title, Description, Resource_URL FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Resource not found', 404);
    }

    sendResponse([
        'success' => true,
        'resource' => [
            'id' => (int)$row['Resource_ID'],
            'webpage_id' => $row['Webpage_ID'] ? (int)$row['Webpage_ID'] : null,
            'title' => $row['Title'],
            'description' => $row['Description'],
            'resource_url' => $row['Resource_URL']
        ]
    ]);
}

function handleCreateResource($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title']);

    $title = sanitizeString($input['title']);
    $description = isset($input['description']) ? sanitizeString($input['description']) : null;
    $resourceUrl = isset($input['resource_url']) ? sanitizeString($input['resource_url']) : null;
    $webpageId = isset($input['webpage_id']) ? (int)$input['webpage_id'] : null;

    $stmt = $mysqli->prepare("INSERT INTO Resources (Webpage_ID, Title, Description, Resource_URL) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $webpageId, $title, $description, $resourceUrl);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create resource', 500);
    }

    $resourceId = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Resource created', 'resource_id' => $resourceId], 201);
}

function handleUpdateResource($mysqli, $user, $id) {
    $input = getJsonInput();

    $stmt = $mysqli->prepare("SELECT Resource_ID FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        sendError('Resource not found', 404);
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
    if (isset($input['resource_url'])) {
        $updates[] = "Resource_URL = ?";
        $params[] = sanitizeString($input['resource_url']);
        $types .= 's';
    }
    if (isset($input['webpage_id'])) {
        $updates[] = "Webpage_ID = ?";
        $params[] = (int)$input['webpage_id'];
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
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Resource updated']);
}

function handleDeleteResource($mysqli, $user, $id) {
    $stmt = $mysqli->prepare("DELETE FROM Resources WHERE Resource_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Resource deleted']);
}
