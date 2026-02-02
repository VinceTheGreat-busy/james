<?php
require_once 'db.php';
require_once 'auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get search query
$search = sanitizeInput($_GET['search'] ?? '');

try {
    if (empty($search)) {
        // Return empty array if no search query
        echo json_encode(['status' => 'success', 'items' => []]);
        exit;
    }

    // Search items by name
    $searchTerm = '%' . sanitizeLikeString($search) . '%';
    $stmt = $conn->prepare("
        SELECT 
            id, 
            name, 
            quantity, 
            conditions, 
            type, 
            description 
        FROM items 
        WHERE name LIKE ? 
        AND quantity > 0
        ORDER BY name ASC
        LIMIT 50
    ");

    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int) $row['id'],
            'name' => htmlspecialchars($row['name']),
            'quantity' => (int) $row['quantity'],
            'condition' => htmlspecialchars($row['conditions']),
            'type' => htmlspecialchars($row['type']),
            'description' => htmlspecialchars($row['description'])
        ];
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Exception $e) {
    error_log("searchItems.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Search failed']);
}

$conn->close();