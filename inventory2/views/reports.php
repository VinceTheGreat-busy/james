<?php
require_once '../config/db.php';
require_once '../config/auth.php';


// Fetch user info
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT username, email, floor_id, room_id FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch items summary by condition
$stmt = $conn->prepare("
    SELECT conditions, SUM(quantity) AS total
    FROM items
    GROUP BY conditions
    ORDER BY conditions
");
$stmt->execute();
$itemsQuery = $stmt->get_result();
$itemsSummary = [];
while ($row = $itemsQuery->fetch_assoc()) {
    $itemsSummary[$row['conditions']] = $row['total'];
}
$stmt->close();

// Fetch items by room
$stmt = $conn->prepare("
    SELECT r.name AS room_name, r.rn, 
        COUNT(DISTINCT ri.item_id) AS item_count,
        SUM(ri.quantity) AS total_quantity
    FROM rooms r
    LEFT JOIN room_items ri ON r.id = ri.room_id
    GROUP BY r.id, r.name, r.rn
    ORDER BY r.rn
");
$stmt->execute();
$roomsReport = $stmt->get_result();
$stmt->close();

// Fetch recent activity
$stmt = $conn->prepare("
    SELECT a.timestamp, a.action, u.username, i.name AS item_name
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN items i ON a.item_id = i.id
    ORDER BY a.timestamp DESC
    LIMIT 20
");
$stmt->execute();
$recentActivity = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/university.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../style/report.css">
</head>

<body>
    <div class="layout">
        <aside class="no-print">
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
            <button class="print-btn no-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
            <button class="add-room-btn no-print" onclick="openRoomModal()">
                <i class="fa-solid fa-plus"></i> Add New Room
            </button>

            <div class="print-only">
                <h1>SHJCS Inventory Report</h1>
                <p>Generated on
                    <?= date('F d, Y h:i A'); ?>
                </p>
            </div>

            <!-- ================= PRINT AREA ================= -->
            <div id="print-area">

                <div class="print-only">
                    <h1>SHJCS Inventory Report</h1>
                    <p>Generated on <?= date('F d, Y h:i A'); ?></p>
                </div>

                <div class="report-section">
                    <h3>Item Summary by Condition</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Condition</th>
                                <th>Total Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($itemsSummary)): ?>
                                <tr>
                                    <td colspan="2">No items found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($itemsSummary as $condition => $total): ?>
                                <tr>
                                    <td><?= htmlspecialchars($condition) ?></td>
                                    <td><?= number_format($total) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h3>Items by Room</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Room #</th>
                                <th>Room Name</th>
                                <th>Unique Items</th>
                                <th>Total Quanty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $roomsReport->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($room['rn']); ?></td>
                                    <td><?= htmlspecialchars($room['room_name']); ?></td>
                                    <td><?= number_format($room['item_count']); ?></td>
                                    <td><?= number_format($room['total_quantity']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h3>Recent Activity</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $recentActivity->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                    <td><?= htmlspecialchars($log['username']) ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['item_name'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            </div>
            <!-- =============== END PRINT AREA =============== -->

            <div id="roomModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <h3>Add New Room</h3>

                    <form id="addRoomForm" action="../config/addRoom.php" method="POST">
                        <label>Room Number</label>
                        <input type="text" name="rn" required>

                        <label>Room Name</label>
                        <input type="text" name="name" required>

                        <label>Room Floor</label>
                        <input type="number" name="floor_number" required>

                        <div class="modal-actions">
                            <button type="submit">Save</button>
                            <button type="button" onclick="closeRoomModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/reports.js"></script>
</body>

</html>