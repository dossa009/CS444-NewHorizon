<?php
/**
 * Exercises API endpoints
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

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
        "SELECT e.*, u.First_Name, u.Last_Name
         FROM Exercises e
         LEFT JOIN Users u ON e.Created_By = u.User_ID
         ORDER BY e.Created_At DESC"
    );

    $exercises = [];
    while ($row = $result->fetch_assoc()) {
        $exercises[] = [
            'id' => (int)$row['Exercise_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'instructions' => $row['Instructions'],
            'type' => $row['Exercise_Type'],
            'difficulty' => $row['Difficulty'],
            'duration_minutes' => (int)$row['Duration_Minutes'],
            'is_published' => (bool)$row['Is_Published'],
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'created_at' => $row['Created_At']
        ];
    }

    sendResponse(['success' => true, 'exercises' => $exercises]);
}

function handleGetExercise($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM Exercises WHERE Exercise_ID = ?");
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
            'title' => $row['Title'],
            'description' => $row['Description'],
            'instructions' => $row['Instructions'],
            'type' => $row['Exercise_Type'],
            'difficulty' => $row['Difficulty'],
            'duration_minutes' => (int)$row['Duration_Minutes'],
            'is_published' => (bool)$row['Is_Published'],
            'created_at' => $row['Created_At']
        ]
    ]);
}

function handleCreateExercise($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title']);

    $stmt = $mysqli->prepare(
        "INSERT INTO Exercises (Title, Description, Instructions, Exercise_Type, Difficulty, Duration_Minutes, Created_By)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'sssssii',
        $input['title'],
        $input['description'] ?? null,
        $input['instructions'] ?? null,
        $input['type'] ?? null,
        $input['difficulty'] ?? 'beginner',
        $input['duration_minutes'] ?? null,
        $user['User_ID']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create exercise', 500);
    }

    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Exercise created', 'exercise_id' => $id], 201);
}

function handleUpdateExercise($mysqli, $user, $id) {
    $input = getJsonInput();

    // Check permissions
    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $updates = [];
    $params = [];
    $types = '';

    foreach (['title' => 's', 'description' => 's', 'instructions' => 's', 'type' => 's', 'difficulty' => 's', 'duration_minutes' => 'i', 'is_published' => 'i'] as $field => $type) {
        if (isset($input[$field])) {
            $updates[] = ucfirst(str_replace('_', '_', $field)) . " = ?";
            $params[] = $type === 'i' ? (int)$input[$field] : $input[$field];
            $types .= $type;
        }
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
    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Exercises WHERE Exercise_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Exercise deleted']);
}
