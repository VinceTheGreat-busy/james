<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Fetch user info
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT username, email, floor_id, room_id FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Example: Fetch total items per condition
$itemsQuery = $conn->query("
    SELECT conditions, SUM(quantity) AS total
    FROM items
    GROUP BY conditions
");
$itemsSummary = [];
while ($row = $itemsQuery->fetch_assoc()) {
    $itemsSummary[$row['conditions']] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/university.css">
</head>

<body>
    <div class="layout">
        <aside>
            <nav>
                <h1>SHJCS - <?= htmlspecialchars($_SESSION['user']['username']); ?></h1>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="reports.php" class="active">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main>
            <h2>Inventory Reports</h2>

            <h3>Item Summary by Condition</h3>
            <table>
                <thead>
                    <tr>
                        <th>Condition</th>
                        <th>Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itemsSummary as $condition => $total): ?>
                        <tr>
                            <td><?= htmlspecialchars($condition); ?></td>
                            <td><?= htmlspecialchars($total); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Optional: add more reports here (per room, per floor, recent activity, etc.) -->
        </main>
    </div>
</body>

</html>