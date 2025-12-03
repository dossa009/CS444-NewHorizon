<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

$path = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/calendar(\/.*)/', $uri, $matches)) {
        $path = $matches[1];
    }
}

if ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    handleGetEvent($mysqli, (int)$matches[1]);
} elseif ($method === 'GET') {
    handleGetAllEvents($mysqli);
} elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreateEvent($mysqli, $user);
} elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleUpdateEvent($mysqli, $user, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleDeleteEvent($mysqli, $user, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

function handleGetAllEvents($mysqli) {
    $result = $mysqli->query(
        "SELECT c.Event_ID, c.User_ID, c.URL, c.Date, u.First_Name, u.Last_Name
         FROM Calendar c
         LEFT JOIN Users u ON c.User_ID = u.User_ID
         ORDER BY c.Date DESC"
    );

    $events = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => (int)$row['Event_ID'],
                'user_id' => $row['User_ID'] ? (int)$row['User_ID'] : null,
                'user_name' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
                'url' => $row['URL'],
                'date' => $row['Date']
            ];
        }
    }

    sendResponse(['success' => true, 'events' => $events]);
}

function handleGetEvent($mysqli, $id) {
    $stmt = $mysqli->prepare(
        "SELECT c.Event_ID, c.User_ID, c.URL, c.Date, u.First_Name, u.Last_Name
         FROM Calendar c
         LEFT JOIN Users u ON c.User_ID = u.User_ID
         WHERE c.Event_ID = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Event not found', 404);
    }

    sendResponse([
        'success' => true,
        'event' => [
            'id' => (int)$row['Event_ID'],
            'user_id' => $row['User_ID'] ? (int)$row['User_ID'] : null,
            'user_name' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'url' => $row['URL'],
            'date' => $row['Date']
        ]
    ]);
}

function handleCreateEvent($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['date']);

    $url = isset($input['url']) ? sanitizeString($input['url']) : null;
    $date = sanitizeString($input['date']);
    $userId = $user['User_ID'];

    $stmt = $mysqli->prepare("INSERT INTO Calendar (User_ID, URL, Date) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $url, $date);

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create event', 500);
    }

    $eventId = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event created', 'event_id' => $eventId], 201);
}

function handleUpdateEvent($mysqli, $user, $id) {
    $input = getJsonInput();

    $stmt = $mysqli->prepare("SELECT Event_ID, User_ID FROM Calendar WHERE Event_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();

    if (!$event) {
        sendError('Event not found', 404);
    }

    if ($event['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    $updates = [];
    $params = [];
    $types = '';

    if (isset($input['url'])) {
        $updates[] = "URL = ?";
        $params[] = sanitizeString($input['url']);
        $types .= 's';
    }
    if (isset($input['date'])) {
        $updates[] = "Date = ?";
        $params[] = sanitizeString($input['date']);
        $types .= 's';
    }

    if (empty($updates)) {
        sendError('No fields to update', 400);
    }

    $params[] = $id;
    $types .= 'i';

    $sql = "UPDATE Calendar SET " . implode(', ', $updates) . " WHERE Event_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event updated']);
}

function handleDeleteEvent($mysqli, $user, $id) {
    $stmt = $mysqli->prepare("SELECT User_ID FROM Calendar WHERE Event_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();

    if (!$event) {
        sendError('Event not found', 404);
    }

    if ($event['User_ID'] != $user['User_ID'] && $user['Account_Type'] !== 'admin') {
        sendError('Permission denied', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Calendar WHERE Event_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event deleted']);
}
