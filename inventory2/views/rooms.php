<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Get room ID from URL
$roomId = sanitizeId($_GET['room_id'] ?? null);

if (!$roomId) {
    header('Location: dashboard.php');
    exit;
}

// Fetch room details
$stmt = $conn->prepare("
    SELECT r.id, r.rn, r.name, f.numbers AS floor_number
    FROM rooms r
    LEFT JOIN floors f ON r.floor_id = f.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    die("Room not found");
}

// Fetch items assigned to this room
$stmt = $conn->prepare("
    SELECT 
        i.id,
        i.name,
        i.conditions,
        i.description,
        i.type,
        ri.quantity AS assigned_quantity,
        ri.assigned_at,
        u.username AS assigned_by
    FROM room_items ri
    JOIN items i ON ri.item_id = i.id
    LEFT JOIN users u ON ri.assigned_by = u.id
    WHERE ri.room_id = ?
    ORDER BY ri.assigned_at DESC
");
$stmt->bind_param("i", $roomId);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Calculate totals
$totalItems = 0;
$totalQuantity = 0;
$itemsArray = [];
while ($item = $items->fetch_assoc()) {
    $totalItems++;
    $totalQuantity += $item['assigned_quantity'];
    $itemsArray[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room <?= htmlspecialchars($room['rn']); ?> - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/university.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../style/rooms.css">
</head>

<body>
    <div class="layout">
        <aside>
            <nav>
                <h1>SHJCS - <?= htmlspecialchars($_SESSION['user']['username']); ?></h1>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <div class="room-header">
                <div>
                    <h1><?= htmlspecialchars($room['rn']); ?> - <?= htmlspecialchars($room['name']); ?></h1>
                    <p><i class="fas fa-building"></i> Floor <?= htmlspecialchars($room['floor_number'] ?: 'N/A'); ?>
                    </p>
                </div>

                <div>
                    <button onclick="window.print()" class="print-btn no-print">
                        <i class="fas fa-print"></i> Print Room Details
                    </button>

                    <button onclick="addModal()" class="assignItem-btn">
                        <i class="fas fa-plus"></i> Assign New Item
                    </button>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?= $totalItems; ?></h3>
                    <p>Unique Items</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totalQuantity; ?></h3>
                    <p>Total Quantity</p>
                </div>
            </div>

            <h2>Items in this Room</h2>

            <?php if (count($itemsArray) > 0): ?>
                <div class="items-grid">
                    <?php foreach ($itemsArray as $item): ?>
                        <div class="item-card">
                            <h3><?= htmlspecialchars($item['name']); ?></h3>

                            <div class="item-info">
                                <label>Quantity:</label>
                                <span class="quantity-badge"><?= htmlspecialchars($item['assigned_quantity']); ?></span>
                            </div>

                            <div class="item-info">
                                <label>Condition:</label>
                                <span class="condition-badge <?= htmlspecialchars($item['conditions']); ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['conditions']))); ?>
                                </span>
                            </div>

                            <?php if ($item['type']): ?>
                                <div class="item-info">
                                    <label>Type:</label>
                                    <span><?= htmlspecialchars($item['type']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($item['description']): ?>
                                <div class="item-description">
                                    <?= htmlspecialchars($item['description']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="assigned-info">
                                Assigned by <?= htmlspecialchars($item['assigned_by']); ?><br>
                                on <?= htmlspecialchars(date('M d, Y', strtotime($item['assigned_at']))); ?>
                            </div>

                            <button class="remove-btn" onclick="removeItem(<?= $item['id']; ?>, <?= $roomId; ?>)">
                                <i class="fas fa-trash"></i> Remove from Room
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Items Assigned</h3>
                    <p>This room currently has no items assigned to it.</p>
                    <p>Go to <a href="dashboard.php">Dashboard</a> to assign items.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function removeItem(itemId, roomId) {
            if (!confirm('Are you sure you want to remove this item from the room?')) {
                return;
            }

            fetch('../config/removeItem.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    item_id: itemId,
                    room_id: roomId
                })
            })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(text || 'Failed to remove item');
                        });
                    }
                    return res.text();
                })
                .then(() => {
                    alert('Item removed successfully!');
                    location.reload();
                })
                .catch(err => {
                    alert('Error: ' + err.message);
                });
        }

        function addModal() {
            window.location.href = 'dashboard.php?assign_to_room=<?= $roomId; ?>';
        }
    </script>
</body>

</html>