<?php
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

$searchQuery = '';
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $searchQuery = trim($_GET['q']);
}

$itemsResults = [];

if ($searchQuery !== '') {
    $stmt = $conn->prepare("
        SELECT id, name, quantity, conditions, description
        FROM items
        WHERE name LIKE ?
        ORDER BY created_at DESC
    ");
    $like = "%{$searchQuery}%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $itemsResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $itemsQuery = $conn->query("
        SELECT id, name, quantity, conditions, description
        FROM items
        ORDER BY created_at DESC
    ");
    $itemsResults = $itemsQuery->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($itemsResults);
exit();
