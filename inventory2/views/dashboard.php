<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Require authentication
requireLogin();

// Set security headers
setSecurityHeaders();

// Get current user
$currentUser = getCurrentUser();

// Floor Selection Handler
if (isset($_GET['floor'])) {
    $floorNumber = sanitizeId($_GET['floor']);
    if ($floorNumber) {
        $_SESSION['user']['floor'] = $floorNumber;
    }
    header("Location: dashboard.php");
    exit;
}

$floorNumber = $_SESSION['user']['floor'] ?? null;
$floorData = null;

if ($floorNumber) {
    $floorData = fetchOne($conn, "SELECT id, numbers FROM floors WHERE numbers = ?", [$floorNumber], 'i');
}

// Get all floors for filter
$floors = fetchAll($conn, "SELECT * FROM floors ORDER BY numbers ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/university.css">
    <link rel="stylesheet" href="../style/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside>
            <nav>
                <div class="logo">
                    <h1><i class="fas fa-boxes"></i> SHJCS</h1>
                    <p class="user-greeting"><?= htmlspecialchars($currentUser['username']); ?></p>
                </div>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">
            <!-- Header -->
            <header>
                <button id="toggleSidebar" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>
                    <i class="fas fa-th-large"></i>
                    Inventory Dashboard
                </h1>

                <!-- Floor Filter -->
                <div class="floor-filter">
                    <label for="floorSelect"><i class="fas fa-building"></i> Floor:</label>
                    <select id="floorSelect" onchange="changeFloor(this.value)">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?= $floor['numbers']; ?>" <?= ($floorNumber == $floor['numbers']) ? 'selected' : ''; ?>>
                                Floor <?= $floor['numbers']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </header>

            <div class="addItemModal" id="itemModal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-plus"></i> Add New Item</h2>
                        <span id="itemModalClose" class="close">&times;</span>
                    </div>

                    <div class="modal-body">
                        <form id="addItemForm" action="../config/addItem.php" method="POST">
                            <div class="form-group">
                                <label for="itemName">Item Name:</label>
                                <input type="text" id="itemName" name="itemName" required>

                                <label for="quantity">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" min="1" required>

                                <label for="condition">Condition:</label>
                                <select id="condition" name="condition" required>
                                    <option value="Select">Select condition</option>
                                    <option value="Available">Available</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="For Replacement">For Replacement</option>
                                </select>

                                <label for="type">Remarks:</label>
                                <input type="text" id="type" name="type" required>

                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button id="addItemSubmit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Add Item
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal" id="editItemModal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><i class="fas fa-edit"></i> Edit Item</h2>
                        <span id="editItemModalClose" class="close">&times;</span>
                    </div>

                    <div class="modal-body">
                        <form id="editItemForm" action="../config/editItem.php" method="POST">
                            <input type="hidden" id="editItemId" name="item_id">

                            <div class="form-group">
                                <label for="editItemName">Item Name:</label>
                                <input type="text" id="editItemName" name="itemName" required>

                                <label for="editQuantity">Quantity:</label>
                                <input type="number" id="editQuantity" name="quantity" min="1" required>

                                <label for="editCondition">Condition:</label>
                                <select id="editCondition" name="condition" required>
                                    <option value="Select">Select condition</option>
                                    <option value="Available">Available</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="For Replacement">For Replacement</option>
                                </select>

                                <label for="editType">Remarks:</label>
                                <input type="text" id="editType" name="type" required>

                                <label for="editDescription">Description</label>
                                <textarea id="editDescription" name="description" rows="4"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button id="editItemSubmit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <!-- ITEMS SECTION -->
                <div class="listofItems">
                    <div class="section-header">
                        <div>
                            <h2><i class="fas fa-boxes"></i> Available Items</h2>
                            <p>Search and drag items to rooms</p>
                        </div>
                        <div>
                            <?php if (!empty($_SESSION['success'])): ?>
                                <div class="alert success">
                                    <?= $_SESSION['success'] ?>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['error'])): ?>
                                <div class="alert error">
                                    <?= $_SESSION['error'] ?>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button id="addItem"><i class="fa-solid fa-plus"></i>Add Item</button>
                        </div>
                    </div>

                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="search" id="itemSearch" placeholder="Search items by name..." autocomplete="off">
                    </div>

                    <div id="itemCards" class="cards-wrapper">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>Type to search for items...</p>
                        </div>
                    </div>
                </div>

                <!-- QUANTITY ASSIGNMENT MODAL -->
                <div id="quantityModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-arrow-right"></i> Assign Item to Room</h2>
                            <span id="modalClose" class="close">&times;</span>
                        </div>

                        <div class="modal-body">
                            <p class="modal-item-name">
                                <strong>Item:</strong> <span id="modalItemName"></span>
                            </p>
                            <p class="modal-available">
                                <strong>Available:</strong> <span id="modalItemQty" class="badge"></span>
                            </p>

                            <div class="form-group">
                                <label for="modalAssignQty">
                                    <i class="fas fa-hashtag"></i> Quantity to assign:
                                </label>
                                <input type="number" id="modalAssignQty" min="1" value="1" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button id="modalSubmit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Assign Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ROOMS SECTION -->
                <div class="listofRooms">
                    <div class="section-header">
                        <h2><i class="fas fa-door-open"></i> Rooms</h2>
                        <p><?= $floorData ? "Floor {$floorData['numbers']}" : 'All Floors'; ?></p>
                    </div>

                    <div class="rooms-grid">
                        <?php
                        if ($floorData) {
                            $rooms = fetchAll($conn, "SELECT * FROM rooms WHERE floor_id = ? ORDER BY rn", [$floorData['id']], 'i');
                        } else {
                            $rooms = fetchAll($conn, "SELECT * FROM rooms ORDER BY rn");
                        }

                        if (!empty($rooms)):
                            foreach ($rooms as $room):
                                // Get item count for this room
                                $itemCount = fetchOne(
                                    $conn,
                                    "SELECT COUNT(DISTINCT item_id) as count FROM room_items WHERE room_id = ?",
                                    [$room['id']],
                                    'i'
                                );
                                ?>
                                <div class="roomCard" data-room-id="<?= $room['id']; ?>">
                                    <div class="room-info">
                                        <h3><?= htmlspecialchars($room['rn']); ?></h3>
                                        <p class="room-name"><?= htmlspecialchars($room['name']); ?></p>
                                        <span class="item-badge">
                                            <i class="fas fa-box"></i> <?= $itemCount['count'] ?? 0; ?> items
                                        </span>
                                    </div>
                                    <div class="room-actions">
                                        <a href="rooms.php?room_id=<?= $room['id']; ?>" class="btn-view"
                                            title="View Room Details">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                                <?php
                            endforeach;
                        else:
                            ?>
                            <div class="empty-state">
                                <i class="fas fa-door-closed"></i>
                                <p>No rooms available for this floor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/side.js"></script>
    <script src="../js/search.js"></script>
    <script src="../js/dragdrop.js"></script>
    <script src="../js/modal.js"></script>

    <script>
        function changeFloor(floorNumber) {
            if (floorNumber) {
                window.location.href = 'dashboard.php?floor=' + floorNumber;
            } else {
                window.location.href = 'dashboard.php';
            }
        }
    </script>
</body>

</html>