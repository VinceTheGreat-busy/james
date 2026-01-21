<?php
function getMonthlyItemCounts($conn, $year)
{
    // Initialize 12 months with zeros
    $monthCounts = array_fill(0, 12, 0);

    // Replace 'created_at' with your actual column storing item dates
    $query = "SELECT MONTH(created_at) AS month, COUNT(*) AS count
              FROM items
              WHERE YEAR(created_at) = ?
              GROUP BY MONTH(created_at)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $monthCounts[$row['month'] - 1] = (int) $row['count'];
    }

    return $monthCounts;
}
?>