<?php
require '../config/auth.php';
require '../config/db.php';

// Require authentication
requireLogin();

// CSRF token
$csrf_token = generateCSRFToken();

// Allowed sort options
$allowedSorts = [
    'id_asc' => 'id ASC',
    'name_asc' => 'name ASC',
    'name_desc' => 'name DESC',
    'quantity_asc' => 'quantity ASC',
    'quantity_desc' => 'quantity DESC',
    'date_asc' => 'date_added ASC',
    'date_desc' => 'date_added DESC'
];

// Get sort safely
$sort = $_POST['sort'] ?? 'id_asc';
$orderBy = $allowedSorts[$sort] ?? 'id ASC';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHJCS Inventory System</title>
    <link rel="stylesheet" href="../style/main.css">
    <link rel="stylesheet" href="../style/add.css">
</head>

<body>
    <div class="layout">
        <aside>
            <nav>
                <h1>Navigation</h1>
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../routes/getReport.php">Get Report</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="../config/logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">
            <header>
                <h1>
                    SHJCS Inventory System
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </h1>
                <div>
                    <button onclick="addItem()">+</button>
                </div>
            </header>

            <main>
                <!-- Summary Cards -->
                <section id="genList">
                    <div id="box-cards">
                        <?php
                        $cardQueries = [
                            ['label' => 'Total Items', 'query' => "SELECT SUM(quantity) AS total FROM items", 'key' => 'total'],
                            ['label' => 'Damaged Items', 'query' => "SELECT SUM(quantity) AS total FROM items WHERE conditions='Damaged'", 'key' => 'total'],
                            ['label' => 'Available Items', 'query' => "SELECT SUM(quantity) AS total FROM items WHERE conditions='Available'", 'key' => 'total'],
                            ['label' => 'Under Repair Items', 'query' => "SELECT SUM(quantity) AS total FROM items WHERE conditions='Under Repair'", 'key' => 'total']
                        ];

                        foreach ($cardQueries as $cq) {
                            $result = $conn->query($cq['query']);
                            $value = ($result && $result->num_rows > 0) ? $result->fetch_assoc()[$cq['key']] : 0;
                            echo "<div class='box-card'><h3>{$cq['label']}</h3><p>{$value}</p></div>";
                        }
                        ?>
                    </div>
                </section>

                <!-- Inventory Table -->
                <section id="inventory-list">
                    <form action="" method="POST">
                        <select id="sort" name="sort" onchange="this.form.submit()">
                            <option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>Default</option>
                            <option value="name_asc" <?= $sort === 'NAME_asc' ? 'selected' : '' ?>>Name (A–Z)</option>
                            <option value="name_desc" <?= $sort === 'NAME_desc' ? 'selected' : '' ?>>Name (Z–A)</option>
                            <option value="quantity_asc" <?= $sort === 'quantity_asc' ? 'selected' : '' ?>>Quantity (Low to High)</option>
                            <option value="quantity_desc" <?= $sort === 'quantity_desc' ? 'selected' : '' ?>>Quantity (High to Low)</option>
                            <option value="date_asc" <?= $sort === 'DATE_asc' ? 'selected' : '' ?>>Date Added (Oldest)</option>
                            <option value="date_desc" <?= $sort === 'DATE_desc' ? 'selected' : '' ?>>Date Added (Newest)</option>
                        </select>
                    </form>

                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Type / Remarks</th>
                                <th>Issue</th>
                                <th>Condition</th>
                                <th>Room</th>
                                <th>Description</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM items ORDER BY $orderBy";
                            $result = $conn->query($sql);

                            if (!$result) {
                                echo "<tr><td colspan='10' style='text-align:center'>Error loading items</td></tr>";
                            } elseif ($result->num_rows === 0) {
                                echo "<tr><td colspan='10' style='text-align:center'>No items found</td></tr>";
                            } else {
                                $i = 1;
                                while ($row = $result->fetch_assoc()):
                            ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= htmlspecialchars($row['NAME']); ?></td>
                                        <td><?= htmlspecialchars($row['quantity']); ?></td>
                                        <td><?= htmlspecialchars($row['remarks']); ?></td>
                                        <td><?= htmlspecialchars($row['issue']); ?></td>
                                        <td><?= htmlspecialchars($row['conditions']); ?></td>
                                        <td><?= htmlspecialchars($row['room']); ?></td>
                                        <td><?= htmlspecialchars($row['description']); ?></td>
                                        <td><?= htmlspecialchars($row['DATE']); ?></td>
                                        <td>
                                            <button type="button" class="edit-btn" onclick="openEditModal(
                                            '<?= $row['id'] ?>',
                                            '<?= htmlspecialchars($row['NAME'], ENT_QUOTES) ?>',
                                            '<?= $row['quantity'] ?>',
                                            '<?= htmlspecialchars($row['remarks'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($row['issue'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($row['conditions'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($row['room'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($row['DATE']) ?>')">
                                                Edit
                                            </button>

                                            <form action="routes/deleteItem.php" method="POST" style="display:inline" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="submit" class="delete-btn" value="Delete">
                                            </form>
                                        </td>
                                    </tr>
                            <?php
                                endwhile;
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
            </main>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New Item</h2>
            <form id="addItemForm" action="routes/addItem.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="modal-grid">
                    <!-- Left Column -->
                    <div class="modal-left">
                        <label for="add_name">Name</label>
                        <input type="text" id="add_name" name="name" required>

                        <label for="add_quantity">Quantity</label>
                        <input type="number" id="add_quantity" name="quantity" required min="1">

                        <label for="add_remarks">Type/Remarks</label>
                        <input type="text" id="add_remarks" name="remarks">

                        <label for="add_issue">Issue</label>
                        <input type="text" id="add_issue" name="issue">

                        <label for="add_conditions">Condition</label>
                        <select id="add_conditions" name="conditions" required>
                            <option value="Available">Available</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Under Repair">Under Repair</option>
                        </select>
                    </div>

                    <!-- Right Column -->
                    <div class="modal-right">
                        <label for="add_room">Room</label>
                        <input type="text" id="add_room" name="room">

                        <label for="add_description">Description</label>
                        <textarea id="add_description" name="description"></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Add Item</button>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editItemModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Item</h2>
            <form id="editItemForm" action="routes/editItem.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" id="edit_id" name="id">

                <div class="modal-grid">
                    <!-- Left Column -->
                    <div class="modal-left">
                        <label for="edit_name">Name</label>
                        <input type="text" id="edit_name" name="name" required>

                        <label for="edit_quantity">Quantity</label>
                        <input type="number" id="edit_quantity" name="quantity" required min="1">

                        <label for="edit_remarks">Type/Remarks</label>
                        <input type="text" id="edit_remarks" name="remarks">

                        <label for="edit_issue">Issue</label>
                        <input type="text" id="edit_issue" name="issue">

                        <label for="edit_conditions">Condition</label>
                        <select id="edit_conditions" name="conditions" required>
                            <option value="Available">Available</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Under Repair">Under Repair</option>
                        </select>
                    </div>

                    <!-- Right Column -->
                    <div class="modal-right">
                        <label for="edit_room">Room</label>
                        <input type="text" id="edit_room" name="room">

                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description"></textarea>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Update Item</button>
            </form>
        </div>
    </div>



    <script src="../js/additemmodal.js"></script>
</body>

</html>