<?php
require_once '../config/auth.php';
require_once '../config/db.php';


/*
|--------------------------------------------------------------------------
| FLOOR SELECTION
|--------------------------------------------------------------------------
*/
if (isset($_GET['floor'])) {
    $_SESSION['user']['floor'] = (int) $_GET['floor'];
    header("Location: dashboard.php");
    exit;
}

$floorNumber = $_SESSION['user']['floor'] ?? null;
$floorData = null;

if ($floorNumber) {
    $stmt = $conn->prepare("SELECT id, numbers FROM floors WHERE numbers = ?");
    $stmt->bind_param("i", $floorNumber);
    $stmt->execute();
    $floorData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| USER INFO
|--------------------------------------------------------------------------
*/
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("
    SELECT u.username, u.email, r.NAME AS room_name
    FROM users u
    LEFT JOIN rooms r ON u.room_id = r.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - SHJCS Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../style/university.css">
    <link rel="stylesheet" href="../style/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="layout">

        <!-- Sidebar -->
        <aside style="display:none;">
            <nav>
                <h1>SHJCS - <?= htmlspecialchars($_SESSION['user']['username']); ?></h1>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">

            <!-- Header -->
            <header>
                <button id="toggleSidebar">&#9776;</button>
                <h1>
                    SHJCS <span>Inventory Dashboard</span>
                    4th Floor
                </h1>
            </header>

            <div class="main-content">

                <!-- ITEMS -->
                <div class="listofItems">
                    <div class="search-container">
                        <input type="search" id="itemSearch" placeholder="Search items...">
                    </div>

                    <div id="itemCards" class="cards-wrapper">
                        <!-- Search results will populate here as cards -->
                    </div>
                </div>

                <!-- QUANTITY MODAL -->
                <div id="quantityModal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <span id="modalClose" class="close">&times;</span>

                        <h2>Assign Quantity</h2>
                        <p id="modalItemName"></p>
                        <p>Available: <strong id="modalItemQty"></strong></p>

                        <input type="number" id="modalAssignQty" min="1">
                        <button id="modalSubmit">Assign</button>
                    </div>
                </div>

                <!-- ROOMS -->
                <div class="listofRooms">
                    <?php
                    $roomQuery = $floorData
                        ? $conn->query("SELECT * FROM rooms WHERE floor_id = {$floorData['id']}")
                        : $conn->query("SELECT * FROM rooms");

                    $userQuery = $conn->prepare("SELECT room_id FROM users WHERE id = ?");
                    $userQuery->bind_param("i", $userId);
                    $userQuery->execute();
                    while ($room = $roomQuery->fetch_assoc()):
                    
                        ?>
                        <div class="roomCard" data-room-id="<?= $room['id']; ?>">
                            <div>
                                <h1>
                                    <?= htmlspecialchars($room['rn']); ?> -
                                    <?= htmlspecialchars($room['name']); ?>
                                </h1>
                            </div>

                            <div class="okas">
                                <h2>
                                </h2>
                                <button id="viewRoom" class="fa-solid fa-angle-right"></button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/side.js"></script>
    <script src="../js/search.js"></script>
    <script src="../js/dragdrop.js"></script>

</body>

</html>