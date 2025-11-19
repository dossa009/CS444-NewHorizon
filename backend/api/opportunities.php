<?php
/**
 * Opportunities API endpoints
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

if ($method === 'GET' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    handleGetOpportunity($mysqli, (int)$matches[1]);
} elseif ($method === 'GET') {
    handleGetAllOpportunities($mysqli);
} elseif ($method === 'POST') {
    $user = requireAuth($mysqli);
    handleCreateOpportunity($mysqli, $user);
} elseif ($method === 'PUT' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleUpdateOpportunity($mysqli, $user, (int)$matches[1]);
} elseif ($method === 'DELETE' && preg_match('/^\/(\d+)$/', $path, $matches)) {
    $user = requireAuth($mysqli);
    handleDeleteOpportunity($mysqli, $user, (int)$matches[1]);
} else {
    sendError('Invalid endpoint', 404);
}

function handleGetAllOpportunities($mysqli) {
    $result = $mysqli->query(
        "SELECT o.*, u.First_Name, u.Last_Name
         FROM Opportunities o
         LEFT JOIN Users u ON o.Created_By = u.User_ID
         ORDER BY o.Created_At DESC"
    );

    $opportunities = [];
    while ($row = $result->fetch_assoc()) {
        $opportunities[] = [
            'id' => (int)$row['Opportunity_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'opportunity_type' => $row['Opportunity_Type'],
            'organization' => $row['Organization'],
            'location' => $row['Location'],
            'is_remote' => (bool)$row['Is_Remote'],
            'contact_email' => $row['Contact_Email'],
            'apply_url' => $row['Apply_URL'],
            'is_published' => (bool)$row['Is_Published'],
            'author' => $row['First_Name'] ? $row['First_Name'] . ' ' . $row['Last_Name'] : null,
            'created_at' => $row['Created_At']
        ];
    }

    sendResponse(['success' => true, 'opportunities' => $opportunities]);
}

function handleGetOpportunity($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM Opportunities WHERE Opportunity_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        sendError('Opportunity not found', 404);
    }

    sendResponse([
        'success' => true,
        'opportunity' => [
            'id' => (int)$row['Opportunity_ID'],
            'title' => $row['Title'],
            'description' => $row['Description'],
            'opportunity_type' => $row['Opportunity_Type'],
            'organization' => $row['Organization'],
            'location' => $row['Location'],
            'is_remote' => (bool)$row['Is_Remote'],
            'contact_email' => $row['Contact_Email'],
            'apply_url' => $row['Apply_URL'],
            'is_published' => (bool)$row['Is_Published'],
            'created_at' => $row['Created_At']
        ]
    ]);
}

function handleCreateOpportunity($mysqli, $user) {
    $input = getJsonInput();
    validateRequired($input, ['title', 'organization']);

    $stmt = $mysqli->prepare(
        "INSERT INTO Opportunities (Title, Description, Opportunity_Type, Organization, Location, Is_Remote, Contact_Email, Apply_URL, Is_Published, Created_By)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'sssssissii',
        $input['title'],
        $input['description'] ?? null,
        $input['opportunity_type'] ?? null,
        $input['organization'],
        $input['location'] ?? null,
        $input['is_remote'] ?? false,
        $input['contact_email'] ?? null,
        $input['apply_url'] ?? null,
        $input['is_published'] ?? false,
        $user['User_ID']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        sendError('Failed to create opportunity', 500);
    }

    $id = $stmt->insert_id;
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Opportunity created', 'opportunity_id' => $id], 201);
}

function handleUpdateOpportunity($mysqli, $user, $id) {
    $input = getJsonInput();

    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $updates = [];
    $params = [];
    $types = '';

    $fields = [
        'title' => 's', 'description' => 's', 'opportunity_type' => 's', 'organization' => 's',
        'location' => 's', 'is_remote' => 'i', 'contact_email' => 's', 'apply_url' => 's',
        'is_published' => 'i'
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

    $sql = "UPDATE Opportunities SET " . implode(', ', $updates) . " WHERE Opportunity_ID = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Opportunity updated']);
}

function handleDeleteOpportunity($mysqli, $user, $id) {
    if ($user['Account_Type'] !== 'admin') {
        sendError('Admin access required', 403);
    }

    $stmt = $mysqli->prepare("DELETE FROM Opportunities WHERE Opportunity_ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sendResponse(['success' => true, 'message' => 'Opportunity deleted']);
}
