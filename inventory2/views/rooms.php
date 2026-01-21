<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Get current user info
$userId = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT username, email, floor_id, room_id FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch all floors and rooms
$roomsQuery = "
    SELECT r.id AS room_id, r.rn, r.name AS room_name, f.number AS floor_number
    FROM rooms r
    JOIN floors f ON r.floor_id = f.id
    ORDER BY f.number, r.rn
";
$rooms = $conn->query($roomsQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - SHJCS Inventory</title>
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
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main>
            <h2>Rooms List</h2>

            <table>
                <thead>
                    <tr>
                        <th>Floor Number</th>
                        <th>Room RN</th>
                        <th>Room Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($_SESSION['user']['floor_id']); ?></td>
                            <td><?= htmlspecialchars($_SESSION['user']['room_id']); ?></td>
                            <td><?= htmlspecialchars($_SESSION['user']['name']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Optional: display user's assigned room -->
            <?php if ($_SESSION['user']['room_id']): ?>
                <?php
                $userRoom = $conn->query("SELECT rn, name FROM rooms WHERE id = " . intval($_SESSION['user']['room_id']))->fetch_assoc();
                ?>
                <p>Your assigned room:
                    <strong><?= htmlspecialchars($_SESSION['user']['rn'] . ' - ' . $_SESSION['user']['name']); ?></strong></p>
            <?php endif; ?>
        </main>
    </div>

    <script src="additemmodal.js"></script>
</body>

</html>