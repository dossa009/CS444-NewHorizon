<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/exercises(\/.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

if ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    handleGetExercise($mysqli, (int)$matches[1]);
} elseif ($method === 'GET') {
    handleGetAllExercises($mysqli);
} elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreateExercise($mysqli, $user);
} elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleUpdateExercise($mysqli, $user, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleDeleteExercise($mysqli, $user, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

function handleGetAllExercises($mysqli) {
    $result = $mysqli->query(
        "SELECT Exercise_ID, Webpage_ID, Name, Description, Exercise_URL FROM Exercises ORDER BY Exercise_ID DESC"
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

function handleGetExercise($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT Exercise_ID, Webpage_ID, Name, Description, Exercise_URL FROM Exercises WHERE Exercise_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Exercise not found', 404);
    }

    sendResponse([
        'success' => true,
        'exercise' => [
            'id' => (int)$row['Exercise_ID'],
            'webpage_id' => $row['Webpage_ID'] ? (int)$row['Webpage_ID'] : null,
            'name' => $row['Name'],
            'description' => $row['Description'],
            'exercise_url' => $row['Exercise_URL']
        ]
    ]);
}

function handleCreateExercise($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['name']);

    $name = sanitizeString($input['name']);
    $description = isset($input['description']) ? sanitizeString($input['description']) : null;
    $exerciseUrl = isset($input['exercise_url']) ? sanitizeString($input['exercise_url']) : null;
    $webpageId = isset($input['webpage_id']) ? (int)$input['webpage_id'] : null;

    $stmt = $mysqli->prepare("INSERT INTO Exercises (Webpage_ID, Name, Description, Exercise_URL) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $webpageId, $name, $description, $exerciseUrl);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create exercise', 500);
    }

    $exerciseId = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Exercise created', 'exercise_id' => $exerciseId], 201);
}

function handleUpdateExercise($mysqli, $user, $id) {
    $input = getJsonInput();

    $stmt = $mysqli->prepare("SELECT Exercise_ID FROM Exercises WHERE Exercise_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        sendError('Exercise not found', 404);
    }

    $updates = [];
    $params = [];
    $types = '';

    if (isset($input['name'])) {
        $updates[] = "Name = ?";
        $params[] = sanitizeString($input['name']);
        $types .= 's';
    }
    if (isset($input['description'])) {
        $updates[] = "Description = ?";
        $params[] = sanitizeString($input['description']);
        $types .= 's';
    }
    if (isset($input['exercise_url'])) {
        $updates[] = "Exercise_URL = ?";
        $params[] = sanitizeString($input['exercise_url']);
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

    $sql = "UPDATE Exercises SET " . implode(', ', $updates) . " WHERE Exercise_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Exercise updated']);
}

function handleDeleteExercise($mysqli, $user, $id) {
    $stmt = $mysqli->prepare("DELETE FROM Exercises WHERE Exercise_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Exercise deleted']);
}
