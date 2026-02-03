<?php
require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();
setSecurityHeaders();

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

// Fetch floors for the Add Room modal
$floors = fetchAll($conn, "SELECT id, floor_number, name FROM floors ORDER BY floor_number ASC");
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
    <link rel="stylesheet" href="../style/universal.css">
    <link rel="stylesheet" href="../style/dashboards.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../style/reportss.css">
</head>

<body>
    <div class="layout">
        <!-- Mobile Menu Overlay -->
        <div class="sidebar-overlay no-print" id="sidebarOverlay"></div>

        <aside class="no-print sidebar" id="sidebar">
            <div class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </div>
            <nav>
                <div class="logo">
                    <h1><i class="fas fa-boxes"></i> SHJCS</h1>
                    <p class="user-greeting"><?= htmlspecialchars($_SESSION['user']['username']); ?></p>
                </div>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="reports.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-wrapper">
            <div class="report-header no-print">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Inventory Reports</h2>
                </div>
                <div class="report-actions">
                    <button class="print-btn" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Print Report
                    </button>
                    <button class="add-room-btn" onclick="openRoomModal()">
                        <i class="fa-solid fa-plus"></i> Add Room
                    </button>
                </div>
            </div>

            <div class="print-only">
                <h1>SHJCS Inventory Report</h1>
                <p>Generated on <?= date('F d, Y h:i A'); ?></p>
            </div>

            <!-- ================= PRINT AREA ================= -->
            <div id="print-area">

                <div class="report-section">
                    <h3>Item Summary by Condition</h3>
                    <div class="table-responsive">
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
                </div>

                <div class="report-section">
                    <h3>Items by Room</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Room #</th>
                                    <th>Room Name</th>
                                    <th>Unique Items</th>
                                    <th>Total Quantity</th>
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
                </div>

                <div class="report-section">
                    <h3>Recent Activity</h3>
                    <div class="table-responsive">
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

            </div>
            <!-- =============== END PRINT AREA =============== -->

            <div id="roomModal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add New Room</h3>
                        <span class="close" onclick="closeRoomModal()">&times;</span>
                    </div>

                    <form id="addRoomForm" action="../config/addRoom.php" method="POST">
                        <label>Room Number</label>
                        <input type="text" name="rn" required>

                        <label>Room Name</label>
                        <input type="text" name="name" required>

                        <label>Floor</label>
                        <select name="floor_number" required>
                            <option value="">Select floor</option>
                            <?php foreach ($floors as $f): ?>
                                <option value="<?= htmlspecialchars($f['id']); ?>">
                                    <?= htmlspecialchars($f['name']); ?> (<?= htmlspecialchars($f['floor_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

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
    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarClose = document.getElementById('sidebarClose');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (menuToggle) {
            menuToggle.addEventListener('click', openSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }

        // Close sidebar when clicking on a link (mobile)
        if (window.innerWidth <= 768) {
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', closeSidebar);
            });
        }
    </script>
</body>

</html>