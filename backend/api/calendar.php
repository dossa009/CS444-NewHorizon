<?php
/**
 * Calendar Events API endpoints
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

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
        "SELECT e.*, u.First_Name, u.Last_Name,
         (SELECT COUNT(*) FROM Event_Registrations WHERE Event_ID = e.Event_ID) as registered_count
         FROM Calendar_Events e
         LEFT JOIN Users u ON e.Created_By = u.User_ID
         ORDER BY e.Event_Date DESC"
    );

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => (int)$row['Event_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'event_date' => $row['Event_Date'],
            'start_time' => $row['Start_Time'],
            'end_time' => $row['End_Time'],
            'event_type' => $row['Event_Type'],
            'location' => $row['Location'],
            'is_online' => (bool)$row['Is_Online'],
            'max_participants' => $row['Max_Participants'] ? (int)$row['Max_Participants'] : null,
            'registered_count' => (int)$row['registered_count'],
            'is_published' => (bool)$row['Is_Published'],
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'created_at' => $row['Created_At']
        ];
    }

    sendResponse(['success' => true, 'events' => $events]);
}

function handleGetEvent($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM Calendar_Events WHERE Event_ID = ?");
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
            'title' => $row['Title'],
            'description' => $row['Description'],
            'event_date' => $row['Event_Date'],
            'start_time' => $row['Start_Time'],
            'end_time' => $row['End_Time'],
            'event_type' => $row['Event_Type'],
            'location' => $row['Location'],
            'is_online' => (bool)$row['Is_Online'],
            'max_participants' => $row['Max_Participants'] ? (int)$row['Max_Participants'] : null,
            'is_published' => (bool)$row['Is_Published'],
            'created_at' => $row['Created_At']
        ]
    ]);
}

function handleCreateEvent($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title', 'event_date']);

    $stmt = $mysqli->prepare(
        "INSERT INTO Calendar_Events (Title, Description, Event_Date, Start_Time, End_Time, Event_Type, Location, Is_Online, Max_Participants, Is_Published, Created_By)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'sssssssiiii',
        $input['title'],
        $input['description'] ?? null,
        $input['event_date'],
        $input['start_time'] ?? null,
        $input['end_time'] ?? null,
        $input['event_type'] ?? null,
        $input['location'] ?? null,
        $input['is_online'] ?? false,
        $input['max_participants'] ?? null,
        $input['is_published'] ?? false,
        $user['User_ID']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create event', 500);
    }

    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event created', 'event_id' => $id], 201);
}

function handleUpdateEvent($mysqli, $user, $id) {
    $input = getJsonInput();

    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $updates = [];
    $params = [];
    $types = '';

    $fields = [
        'title' => 's', 'description' => 's', 'event_date' => 's', 'start_time' => 's',
        'end_time' => 's', 'event_type' => 's', 'location' => 's', 'is_online' => 'i',
        'max_participants' => 'i', 'is_published' => 'i'
    ];

    foreach ($fields as $field => $type) {
        if (isset($input[$field])) {
            $dbField = implode('_', array_map('ucfirst', explode('_', $field)));
            $updates[] = "$dbField = ?";
            $params[] = $type === 'i' ? (int)$input[$field] : $input[$field];
            $types .= $type;
        }
    }

    if (empty($updates)) {
        sendError('No fields to update', 400);
    }

    $params[] = $id;
    $types .= 'i';

    $sql = "UPDATE Calendar_Events SET " . implode(', ', $updates) . " WHERE Event_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event updated']);
}

function handleDeleteEvent($mysqli, $user, $id) {
    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Calendar_Events WHERE Event_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Event deleted']);
}
