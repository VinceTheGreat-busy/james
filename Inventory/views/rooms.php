<?php
require '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms</title>
    <link rel="stylesheet" href="../styles/room.css">
</head>

<body>
    <img src="asset/bg.png">
    <header>
        <div class="head">
            <h1>SHJCS Inventory</h1>
            <nav>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="rooms.php">Rooms</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <?php
        $query = mysqli_query($conn, "SELECT * FROM rooms");
        if (mysqli_num_rows($query) > 0):
            while ($row = mysqli_fetch_assoc($query)):
        ?>
                <section class="rooms">
                    <div class="title">
                        <h1><?= htmlspecialchars($row['rn']); ?></h1>
                        <button onclick="editModal()">Edit</button> 
                    </div>

                    <div id="img">
                        <img id="room-logo" src="<?= htmlspecialchars($row['photo']); ?>?>">
                    </div>

                    <div class="desc">
                        <div>
                            <h4><?= htmlspecialchars($row['teacher']); ?></h4>
                            <p><?= htmlspecialchars($row['name']); ?></p>
                        </div>
                        <div>
                            <button>View Room</button>
                        </div>
                    </div>
                </section>
        <?php
            endwhile;
        endif;
        ?>
    </main>
</body>

</html>