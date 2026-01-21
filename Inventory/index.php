<?php

require 'config/auth.php';
require 'config/db.php';

$events = [];
$query = "SELECT DATE(created_at) as date, COUNT(*) as count FROM items GROUP BY DATE(created_at)";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'title' => $row['count'] . ' items added',
            'start' => $row['date']
        ];
    }
}

$history = [];
$query = "SELECT h.*, i.name AS item_name 
        FROM item_history h
        LEFT JOIN items i ON h.item_id = i.id
        ORDER BY h.created_at DESC
          LIMIT 10"; // show last 10 actions

$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
</head>

<body>
    <img src="asset/bg.png">
    <header>
        <div class="head">
            <h1>SHJCS Inventory</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="views/dashboard.php">Dashboard</a></li>
                    <li><a href="views/reports.php">Rooms</a></li>
                    <li><a href="views/reports.php">Reports</a></li>
                    <li><a href="config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div id="body">
            <section id="essentials">
                <div class="calendar">
                    <div id="itemCalendar"></div>
                </div>
                <div class="line-graph">
                    <canvas id="itemChart" width="400" height="200"></canvas>
                </div>
            </section>

            <section id="hero">
                <div class="history">
                    <h2>Recent Activity</h2>
                    <ul>
                        <?php if (!empty($history)) : ?>
                            <?php foreach ($history as $h) : ?>
                                <li>
                                    <strong><?= htmlspecialchars($h['performed_by']); ?></strong>
                                    <?= htmlspecialchars($h['action']); ?>
                                    <em><?= htmlspecialchars($h['item_name']); ?></em>
                                    <span>(<?= date('M d, Y H:i', strtotime($h['created_at'])); ?>)</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No recent activity</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>

            <section id="data">
                <div class="total">
                    <?php 
                        $query = "SELECT * FROM items"
                    ?>
                </div>
                <div class="available">

                </div>
                <div class="damaged">

                </div>
            </section>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('itemChart').getContext('2d');
        const itemChart = new Chart(ctx, {
            type: 'line',

            data: {
                labels: <?php echo json_encode($dates); ?>,

                datasets: [{
                    label: 'Items Added',
                    data: <?php echo json_encode($counts); ?>,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1,
                    pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                    pointRadius: 5
                }]
            },

            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Frequency of Adding Items'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Items'
                        },
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('itemCalendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events); ?>,
                eventColor: '#4bc0c0',
                height: 400
            });
            calendar.render();
        });
    </script>
</body>

</html>