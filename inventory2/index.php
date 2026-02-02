<?php
require_once 'config/db.php';
require_once 'config/auth.php';
require_once 'config/counter.php';

// Require authentication
requireLogin();

// Set security headers
setSecurityHeaders();

// Get current user
$currentUser = getCurrentUser();

$year = date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n'); // 1-12
$month = max(1, min(12, $month)); // clamp 1-12
$monthName = date('F', mktime(0, 0, 0, $month, 1));
$firstDayOfMonth = date('N', strtotime("$year-$month-01")); // 1=Mon, 7=Sun
$daysInMonth = date('t', strtotime("$year-$month-01"));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Home - SHJCS Inventory System</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="style/se.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside>
            <nav>
                <div class="logo">
                    <h1><i class="fas fa-boxes"></i> SHJCS</h1>
                    <p class="user-greeting">Welcome, <?= htmlspecialchars($currentUser['username']); ?></p>
                </div>
                <ul>
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="views/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="views/reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <li><a href="config/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header>
                <h1>SHJCS <span>Inventory Management System</span></h1>
                <div class="header-actions">
                    <span class="current-time" id="currentTime"></span>
                </div>
            </header>

            <!-- Main Content -->
            <main>
                <!-- Statistics Cards -->
                <div id="box-cards">
                    <div class="box-card total-items">
                        <div class="card-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Items</h3>
                            <p class="card-number">
                                <?php
                                $totalItems = fetchOne($conn, "SELECT COALESCE(SUM(quantity), 0) AS total FROM items");
                                echo number_format($totalItems['total']);
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="box-card available-items">
                        <div class="card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-content">
                            <h3>Available</h3>
                            <p class="card-number">
                                <?php
                                $available = fetchOne($conn, "SELECT COALESCE(SUM(quantity), 0) AS available FROM items WHERE conditions = 'available'");
                                echo number_format($available['available']);
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="box-card damaged-items">
                        <div class="card-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-content">
                            <h3>Damaged</h3>
                            <p class="card-number">
                                <?php
                                $damaged = fetchOne($conn, "SELECT COALESCE(SUM(quantity), 0) AS damaged FROM items WHERE conditions = 'damaged'");
                                echo number_format($damaged['damaged']);
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="box-card rooms-count">
                        <div class="card-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="card-content">
                            <h3>Total Rooms</h3>
                            <p class="card-number">
                                <?php
                                $roomCount = fetchOne($conn, "SELECT COUNT(*) AS total FROM rooms");
                                echo number_format($roomCount['total']);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Analytics Section -->
                <section id="essentials" style="display:flex; gap:1rem; flex-wrap:wrap; margin-top: 2rem;">
                    <!-- Line Graph -->
                    <div class="line-graph box-card" style="flex:1 1 400px; height:350px;">
                        <h3><i class="fas fa-chart-line"></i> Item Addition History</h3>
                        <canvas id="lineChart" style="width:100%; height:300px;"></canvas>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar box-card" style="flex:1 1 400px;">
                        <h3><i class="fas fa-calendar-alt"></i> Calendar</h3>
                        <div class="calendar-container">
                            <div class="calendar-header">
                                <button id="prevMonth">&laquo; Previous</button>
                                <h3 id="calendarTitle"></h3>
                                <button id="nextMonth">Next &raquo;</button>
                            </div>

                            <table class="calendar-table">
                                <thead>
                                    <tr>
                                        <th>Mon</th>
                                        <th>Tue</th>
                                        <th>Wed</th>
                                        <th>Thu</th>
                                        <th>Fri</th>
                                        <th>Sat</th>
                                        <th>Sun</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarBody"></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Recent Activity -->
                <section id="hero" style="margin-top: 2rem;">
                    <div class="history box-card">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <div class="activity-list">
                            <?php
                            $logs = fetchAll($conn, "
                                    SELECT a.timestamp, a.action, u.username
                                    FROM audit_log a
                                    LEFT JOIN users u ON a.user_id = u.id
                                    ORDER BY a.timestamp DESC
                                    LIMIT 10
                                ");
                            ?>

                            <div class="activity-table-container">
                                <table class="activity-table">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($logs)): ?>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?= date('M d, Y H:i', strtotime($log['timestamp'])); ?></td>
                                                    <td><?= htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                                    <td><?= htmlspecialchars($log['action']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="no-activity">No recent activity</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('currentTime').textContent = now.toLocaleDateString('en-US', options);
        }
        updateTime();
        setInterval(updateTime, 60000);

        // Line Chart
        const ctx = document.getElementById('lineChart').getContext('2d');
        const itemsPerMonth = <?= json_encode(getMonthlyItemCounts($conn, date('Y'))); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Items Added',
                    data: itemsPerMonth,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
    <script src="js/calendar.js"></script>
</body>

</html>