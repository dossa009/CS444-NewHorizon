<?php
/**
 * Resources API Endpoint
 * GET /resources.php - Get all resources
 * GET /resources.php/{id} - Get single resource
 * POST /resources.php - Create resource (admin)
 * PUT /resources.php/{id} - Update resource (admin)
 * DELETE /resources.php/{id} - Delete resource (admin)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

// Parse path info for ID
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$resourceId = null;
if (preg_match('/^\/(\d+)$/', $pathInfo, $matches)) {
    $resourceId = (int)$matches[1];
}

switch ($method) {
    case 'GET':
        if ($resourceId) {
            // Get single resource
            $stmt = $mysqli->prepare("SELECT Resource_ID as id, Title as title, Description as description, Resource_URL as resource_url FROM Resources WHERE Resource_ID = ?");
            $stmt->bind_param('i', $resourceId);
            $stmt->execute();
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
            $stmt->close();

            if (!$resource) {
                sendError('Resource not found', 404);
            }
            sendResponse(['resource' => $resource]);
        } else {
            // Get all resources
            $result = $mysqli->query("SELECT Resource_ID as id, Title as title, Description as description, Resource_URL as resource_url FROM Resources ORDER BY Title");
            $resources = [];
            while ($row = $result->fetch_assoc()) {
                $resources[] = $row;
            }
            sendResponse(['resources' => $resources]);
        }
        break;

    case 'POST':
        // Create resource - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        $data = getJsonInput();

        if (empty($data['title'])) {
            sendError('Title is required', 400);
        }

        $stmt = $mysqli->prepare("INSERT INTO Resources (Title, Description, Resource_URL) VALUES (?, ?, ?)");
        $title = $data['title'];
        $description = $data['description'] ?? null;
        $url = $data['resource_url'] ?? null;
        $stmt->bind_param('sss', $title, $description, $url);
        $stmt->execute();

        $newId = $mysqli->insert_id;
        $stmt->close();

        sendResponse(['success' => true, 'id' => $newId, 'message' => 'Resource created']);
        break;

    case 'PUT':
        // Update resource - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        if (!$resourceId) {
            sendError('Resource ID required', 400);
        }

        $data = getJsonInput();

        // Build update query dynamically
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['title'])) {
            $fields[] = 'Title = ?';
            $types .= 's';
            $values[] = $data['title'];
        }
        if (isset($data['description'])) {
            $fields[] = 'Description = ?';
            $types .= 's';
            $values[] = $data['description'];
        }
        if (isset($data['resource_url'])) {
            $fields[] = 'Resource_URL = ?';
            $types .= 's';
            $values[] = $data['resource_url'];
        }

        if (empty($fields)) {
            sendError('No fields to update', 400);
        }

        $types .= 'i';
        $values[] = $resourceId;

        $sql = "UPDATE Resources SET " . implode(', ', $fields) . " WHERE Resource_ID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        sendResponse(['success' => true, 'message' => 'Resource updated']);
        break;

    case 'DELETE':
        // Delete resource - requires admin
        $user = requireAuth($mysqli);
        requireAdmin($mysqli);

        if (!$resourceId) {
            sendError('Resource ID required', 400);
        }

        $stmt = $mysqli->prepare("DELETE FROM Resources WHERE Resource_ID = ?");
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        $stmt->close();

        sendResponse(['success' => true, 'message' => 'Resource deleted']);
        break;

    default:
        sendError('Method not allowed', 405);
}
