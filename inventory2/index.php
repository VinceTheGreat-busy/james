<?php
require_once 'config/auth.php';
require_once 'config/db.php';
require_once 'config/counter.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page - SHJCS Inventory</title>

    <link rel="stylesheet" href="style/se.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>

<body>
    <div class="layout">

        <!-- Sidebar -->
        <aside>
            <nav>
                <h1>SHJCS - <?= htmlspecialchars($_SESSION['user']['username']); ?></h1>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="views/dashboard.php">Dashboard</a></li>
                    <li><a href="views/reports.php">Reports</a></li>
                    <li><a href="config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">

            <!-- Header -->
            <header>
                <h1>SHJCS <span>Inventory Home</span></h1>
            </header>

            <!-- Main Content -->
            <main>

                <!-- Cards -->
                <div id="box-cards">
                    <div class="box-card">
                        <h3>Total Items</h3>
                        <p>
                            <?php
                            $totalItems = $conn->query("
                            SELECT COALESCE(SUM(quantity),0) AS total
                            FROM items
                        ")->fetch_assoc()['total'];
                            echo $totalItems;
                            ?>
                        </p>
                    </div>

                    <div class="availItems box-card">
                        <h3>Available</h3>
                        <p>
                            <?php
                            $available = $conn->query("
                            SELECT COALESCE(SUM(quantity),0) AS available
                            FROM items
                            WHERE CONDITIONS='available'
                        ")->fetch_assoc()['available'];
                            echo $available;
                            ?>
                        </p>
                    </div>

                    <div class="dmgItems box-card">
                        <h3>Damaged</h3>
                        <p>
                            <?php
                            $damaged = $conn->query("
                            SELECT COALESCE(SUM(quantity),0) AS damaged
                            FROM items
                            WHERE CONDITIONS='damaged'
                        ")->fetch_assoc()['damaged'];
                            echo $damaged;
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Analytics -->
                <section id="essentials" style="display:flex; gap:1rem; flex-wrap:wrap;">

                    <!-- Line Graph -->
                    <div class="line-graph box-card" style="flex:1 1 400px; height:350px;">
                        <h3>Item History</h3>
                        <canvas id="lineChart" style="width:100%; height:100%; display:block;"></canvas>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar box-card" style="flex:1 1 400px;">
                        <h3>Calendar</h3>
                        <div id="calendar"></div>
                    </div>

                </section>

                <!-- Recent Activity -->
                <section id="hero">
                    <div class="history box-card">
                        <h3>Recent Activity</h3>
                        <ul>
                            <?php
                            $logs = $conn->query("
                            SELECT a.timestamp, a.action, u.username
                            FROM audit_log a
                            LEFT JOIN users u ON a.user_id = u.id
                            ORDER BY a.timestamp DESC
                            LIMIT 5
                        ");

                            while ($log = $logs->fetch_assoc()):
                                ?>
                                <li>
                                    <?= htmlspecialchars($log['timestamp']); ?> —
                                    <strong><?= htmlspecialchars($log['username']); ?></strong>
                                    <?= htmlspecialchars($log['action']); ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('lineChart').getContext('2d');

        const itemsPerMonth = <?= json_encode(getMonthlyItemCounts($conn, date('Y'))); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Items Added',
                    data: itemsPerMonth,
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // important!
                plugins: { legend: { display: true } }
            }
        });
    </script>

</body>

</html>