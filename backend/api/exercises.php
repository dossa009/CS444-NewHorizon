<?php
/**
 * Exercises API Endpoint
 * GET /exercises.php - Get all exercises
 * GET /exercises.php/{id} - Get single exercise
 * POST /exercises.php - Create exercise (admin)
 * PUT /exercises.php/{id} - Update exercise (admin)
 * DELETE /exercises.php/{id} - Delete exercise (admin)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

// Parse path info for ID
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$exerciseId = null;
if (preg_match('/^\/(\d+)$/', $pathInfo, $matches)) {
    $exerciseId = (int)$matches[1];
}

switch ($method) {
    case 'GET':
        if ($exerciseId) {
            // Get single exercise
            $stmt = $mysqli->prepare("SELECT Exercise_ID as id, Name as name, Description as description, Exercise_URL as exercise_url FROM Exercises WHERE Exercise_ID = ?");
            $stmt->bind_param('i', $exerciseId);
            $stmt->execute();
            $result = $stmt->get_result();
            $exercise = $result->fetch_assoc();
            $stmt->close();

            if (!$exercise) {
                sendError('Exercise not found', 404);
            }
            sendResponse(['exercise' => $exercise]);
        } else {
            // Get all exercises
            $result = $mysqli->query("SELECT Exercise_ID as id, Name as name, Description as description, Exercise_URL as exercise_url FROM Exercises ORDER BY Name");
            $exercises = [];
            while ($row = $result->fetch_assoc()) {
                $exercises[] = $row;
            }
            sendResponse(['exercises' => $exercises]);
        }
        break;

    case 'POST':
        // Create exercise - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        $data = getJsonInput();

        if (empty($data['name'])) {
            sendError('Name is required', 400);
        }

        $stmt = $mysqli->prepare("INSERT INTO Exercises (Name, Description, Exercise_URL) VALUES (?, ?, ?)");
        $name = $data['name'];
        $description = $data['description'] ?? null;
        $url = $data['exercise_url'] ?? null;
        $stmt->bind_param('sss', $name, $description, $url);
        $stmt->execute();

        $newId = $mysqli->insert_id;
        $stmt->close();

        sendResponse(['success' => true, 'id' => $newId, 'message' => 'Exercise created']);
        break;

    case 'PUT':
        // Update exercise - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        if (!$exerciseId) {
            sendError('Exercise ID required', 400);
        }

        $data = getJsonInput();

        // Build update query dynamically
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'Name = ?';
            $types .= 's';
            $values[] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = 'Description = ?';
            $types .= 's';
            $values[] = $data['description'];
        }
        if (isset($data['exercise_url'])) {
            $fields[] = 'Exercise_URL = ?';
            $types .= 's';
            $values[] = $data['exercise_url'];
        }

        if (empty($fields)) {
            sendError('No fields to update', 400);
        }

        $types .= 'i';
        $values[] = $exerciseId;

        $sql = "UPDATE Exercises SET " . implode(', ', $fields) . " WHERE Exercise_ID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        sendResponse(['success' => true, 'message' => 'Exercise updated']);
        break;

    case 'DELETE':
        // Delete exercise - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        if (!$exerciseId) {
            sendError('Exercise ID required', 400);
        }

        $stmt = $mysqli->prepare("DELETE FROM Exercises WHERE Exercise_ID = ?");
        $stmt->bind_param('i', $exerciseId);
        $stmt->execute();
        $stmt->close();

        sendResponse(['success' => true, 'message' => 'Exercise deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}
