<?php
require_once '../config/db.php';
require_once '../config/auth.php';

requireLogin();
setSecurityHeaders();

// ── room ID – clean redirect on failure, no debug output ──
$roomId = sanitizeId($_GET['room_id'] ?? null);
if (!$roomId) {
    header('Location: dashboard.php');
    exit;
}

// ── room details ──
$room = fetchOne(
    $conn,
    "SELECT r.id, r.rn, r.name, r.teacher, f.floor_number
     FROM rooms r
     LEFT JOIN floors f ON r.floor_id = f.id
     WHERE r.id = ?",
    [$roomId],
    'i'
);

if (!$room) {
    header('Location: dashboard.php');
    exit;
}

// ── items already assigned to this room ──
$itemsArray = fetchAll(
    $conn,
    "SELECT
        i.id,
        i.name,
        i.conditions,
        i.description,
        i.type,
        ri.quantity AS assigned_quantity,
        ri.assigned_at,
        u.username AS assigned_by
     FROM room_items ri
     JOIN  items i  ON ri.item_id = i.id
     LEFT JOIN users u ON ri.assigned_by = u.id
     WHERE ri.room_id = ?
     ORDER BY ri.assigned_at DESC",
    [$roomId],
    'i'
);

$totalItems = count($itemsArray);
$totalQuantity = array_sum(array_column($itemsArray, 'assigned_quantity'));

// ── ALL items with stock > 0 for the assign-modal (same pattern as dashboard) ──
$allItems = fetchAll(
    $conn,
    "SELECT id, name, quantity, conditions, type, description
     FROM items WHERE quantity > 0 ORDER BY name ASC"
);

$currentUser = getCurrentUser();

// JS-safe string escaper – defined once, reused for both item loops
$esc = function (string $v): string {
    return str_replace(["\r\n", "\r", "\n"], ' ', addslashes($v));
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room <?= htmlspecialchars($room['rn']); ?> – SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/universal.css">
    <link rel="stylesheet" href="../style/dashboards.css">
    <link rel="stylesheet" href="../style/rooms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="layout">
        <!-- Mobile Menu Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar – identical structure to dashboard & reports -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </div>
            <nav>
                <div class="logo">
                    <h1><i class="fas fa-boxes"></i> SHJCS</h1>
                    <p class="user-greeting"><?= htmlspecialchars($currentUser['username']); ?></p>
                </div>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <li><a href="../config/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-wrapper">
            <!-- Header -->
            <header>
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>
                        <i class="fas fa-door-open"></i>
                        <?= htmlspecialchars($room['rn']); ?> – <?= htmlspecialchars($room['name']); ?>
                    </h1>
                </div>
            </header>

            <div class="main-content" style="display:block;">

                <!-- Back link -->
                <a href="dashboard.php" class="back-btn no-print"
                    style="display:inline-flex;align-items:center;gap:6px;margin-bottom:18px;text-decoration:none;color:#4a7cf7;font-weight:600;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>

                <!-- Room meta row -->
                <div class="room-header"
                    style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
                    <p style="margin:0;color:#ffff;">
                        <i class="fas fa-building"></i> Floor
                        <?= htmlspecialchars($room['floor_number'] ?: 'N/A'); ?> &ndash;
                        <?= htmlspecialchars($room['teacher'] ?: 'N/A'); ?>
                    </p>
                    <div class="no-print" style="display:flex;gap:10px;">
                        <button onclick="window.print()" class="btn btn-secondary"
                            style="display:inline-flex;align-items:center;gap:6px;">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button id="openAssignModal" class="btn btn-primary"
                            style="display:inline-flex;align-items:center;gap:6px;">
                            <i class="fas fa-plus"></i> Assign Item
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-cards" style="display:flex;gap:16px;margin-bottom:24px;flex-wrap:wrap;">
                    <div class="stat-card">
                        <h3><?= $totalItems; ?></h3>
                        <p><i class="fas fa-layer-group"></i> Unique Items</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $totalQuantity; ?></h3>
                        <p><i class="fas fa-boxes"></i> Total Quantity</p>
                    </div>
                </div>

                <!-- Item grid -->
                <?php if ($totalItems > 0): ?>
                    <div class="items-grid">
                        <?php foreach ($itemsArray as $item): ?>
                            <div class="item-card">
                                <span class="card-badge <?= htmlspecialchars($item['conditions']); ?>">
                                    <?= htmlspecialchars($item['conditions']); ?>
                                </span>

                                <h3><?= htmlspecialchars($item['name']); ?></h3>

                                <div class="item-info">
                                    <label>Quantity</label>
                                    <span class="quantity-badge"><?= (int) $item['assigned_quantity']; ?></span>
                                </div>

                                <?php if ($item['type']): ?>
                                    <div class="item-info">
                                        <label>Remarks</label>
                                        <span><?= htmlspecialchars($item['type']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($item['description']): ?>
                                    <div class="item-description">
                                        <?= htmlspecialchars($item['description']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="assigned-info">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($item['assigned_by'] ?? 'Unknown'); ?>
                                    <span style="margin-left:10px;">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= date('M d, Y', strtotime($item['assigned_at'])); ?>
                                    </span>
                                </div>

                                <button class="remove-btn no-print"
                                    onclick="removeItem(<?= (int) $item['id']; ?>, <?= (int) $roomId; ?>, '<?= $esc($item['name']); ?>')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Items Assigned</h3>
                        <p>This room has no items yet. Click <strong>Assign Item</strong> above to add one.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Assign-Item Modal ──────────────────────────── -->
    <div class="modal" id="assignModal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Assign Item to <?= htmlspecialchars($room['rn']); ?></h2>
                <span id="assignModalClose" class="close">&times;</span>
            </div>

            <div class="modal-body">
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="assignSearch" placeholder="Search items…" autocomplete="off">
                </div>

                <div class="search-results" id="assignResults"></div>

                <div class="qty-picker" id="qtyPicker">
                    <p class="selected-name">Selected: <strong id="pickedName"></strong></p>
                    <div class="qty-row">
                        <label>Qty</label>
                        <input type="number" id="pickedQty" min="1" value="1">
                        <span style="color:#6b7280;font-size:.82rem;" id="pickedMax"></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button id="assignSubmit" class="btn btn-primary" disabled>
                    <i class="fas fa-check"></i> Assign
                </button>
            </div>
        </div>
    </div>

    <!-- ── Toast ──────────────────────────────────────── -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle toast-icon" id="toastIcon"></i>
        <span id="toastMsg"></span>
    </div>

    <!-- ── Item data for assign-modal client-side search ── -->
    <script>
        window.__ITEMS__ = [
            <?php
            $first = true;
            foreach ($allItems as $row) {
                if (!$first)
                    echo ",\n";
                $first = false;

                echo "{"
                    . "'id':" . (int) $row['id'] . ","
                    . "'name':'" . $esc($row['name']) . "',"
                    . "'quantity':" . (int) $row['quantity'] . ","
                    . "'condition':'" . $esc($row['conditions'] ?? '') . "',"
                    . "'type':'" . $esc($row['type'] ?? '') . "',"
                    . "'description':'" . $esc($row['description'] ?? '') . "'"
                    . "}";
            }
            ?>
        ];
    </script>

    <!-- ── Scripts ────────────────────────────────────── -->
    <script src="../js/side.js"></script>

    <script>
        (function () {
            'use strict';

            /* ── Toast helper ──────────────────────────── */
            var toastTimer = null;
            function showToast(msg, type) {
                type = type || 'success';
                var toast = document.getElementById('toast');
                var icon = document.getElementById('toastIcon');
                document.getElementById('toastMsg').textContent = msg;

                toast.className = 'toast ' + type + ' show';
                icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + ' toast-icon';

                if (toastTimer) clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 3200);
            }

            /* ── Modal open / close ────────────────────── */
            var assignModal = document.getElementById('assignModal');
            var assignSearch = document.getElementById('assignSearch');
            var assignResults = document.getElementById('assignResults');
            var qtyPicker = document.getElementById('qtyPicker');
            var pickedNameEl = document.getElementById('pickedName');
            var pickedQtyInput = document.getElementById('pickedQty');
            var pickedMaxEl = document.getElementById('pickedMax');
            var assignSubmitBtn = document.getElementById('assignSubmit');

            var pickedItem = null;   // { id, name, quantity }

            document.getElementById('openAssignModal').addEventListener('click', function () {
                assignModal.style.display = 'flex';
                assignSearch.value = '';
                assignResults.innerHTML = '';
                qtyPicker.classList.remove('visible');
                assignSubmitBtn.disabled = true;
                pickedItem = null;
                setTimeout(function () { assignSearch.focus(); }, 80);
            });

            function closeAssignModal() {
                assignModal.style.display = 'none';
            }
            document.getElementById('assignModalClose').addEventListener('click', closeAssignModal);
            assignModal.addEventListener('click', function (e) {
                if (e.target === assignModal) closeAssignModal();
            });

            /* ── Client-side search (filters window.__ITEMS__) ── */
            var allItems = window.__ITEMS__ || [];
            var debounce = null;

            assignSearch.addEventListener('input', function (e) {
                clearTimeout(debounce);
                var q = e.target.value.trim();

                if (!q) {
                    assignResults.innerHTML = '';
                    qtyPicker.classList.remove('visible');
                    assignSubmitBtn.disabled = true;
                    pickedItem = null;
                    return;
                }

                debounce = setTimeout(function () { filterAndRender(q); }, 150);
            });

            function filterAndRender(q) {
                var lower = q.toLowerCase();
                var matches = [];
                for (var i = 0; i < allItems.length; i++) {
                    if (allItems[i].name.toLowerCase().indexOf(lower) !== -1) {
                        matches.push(allItems[i]);
                    }
                }

                assignResults.innerHTML = '';

                if (matches.length === 0) {
                    assignResults.innerHTML =
                        '<div class="result-row" style="color:#6b7280;padding:10px 0;font-size:.88rem;">'
                        + 'No items found.</div>';
                    return;
                }

                for (var j = 0; j < matches.length; j++) {
                    (function (item) {
                        var row = document.createElement('div');
                        row.className = 'result-row';
                        row.innerHTML =
                            '<div>'
                            + '<div class="r-name">' + escapeHtml(item.name) + '</div>'
                            + '<div class="r-meta">' + escapeHtml(item.type || '') + '</div>'
                            + '</div>'
                            + '<div class="r-qty">' + item.quantity + ' available</div>';

                        // Pass the row element explicitly – never rely on bare `event`
                        row.addEventListener('click', function () { selectItem(item, row); });
                        assignResults.appendChild(row);
                    })(matches[j]);
                }
            }

            /* ── Item selected ─────────────────────────── */
            function selectItem(item, clickedRow) {
                pickedItem = item;
                pickedNameEl.textContent = item.name;
                pickedMaxEl.textContent = '/ ' + item.quantity + ' available';
                pickedQtyInput.value = 1;
                pickedQtyInput.max = item.quantity;
                qtyPicker.classList.add('visible');
                assignSubmitBtn.disabled = false;

                // Highlight selected row, clear others
                var rows = assignResults.querySelectorAll('.result-row');
                for (var i = 0; i < rows.length; i++) rows[i].style.background = '';
                clickedRow.style.background = '#e0eaff';
            }

            /* ── Submit assign (plain XHR POST) ────────── */
            assignSubmitBtn.addEventListener('click', function () {
                if (!pickedItem) return;

                var qty = parseInt(pickedQtyInput.value, 10);
                if (isNaN(qty) || qty < 1 || qty > pickedItem.quantity) {
                    showToast('Enter a valid quantity (1–' + pickedItem.quantity + ').', 'error');
                    return;
                }

                assignSubmitBtn.disabled = true;
                assignSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning…';

                var body = 'item_id=' + encodeURIComponent(pickedItem.id)
                    + '&room_id=' + encodeURIComponent(<?= (int) $roomId; ?>)
                    + '&quantity=' + encodeURIComponent(qty);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../config/assignItem.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function () {
                    if (xhr.status === 401) { window.location.href = '../config/login.php'; return; }

                    var msg = xhr.responseText.trim();
                    if (msg.indexOf('Success') !== -1) {
                        showToast(qty + ' unit(s) of "' + pickedItem.name + '" assigned successfully.');
                        closeAssignModal();
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showToast(msg || 'Assignment failed.', 'error');
                        assignSubmitBtn.disabled = false;
                        assignSubmitBtn.innerHTML = '<i class="fas fa-check"></i> Assign';
                    }
                };

                xhr.onerror = function () {
                    showToast('Network error. Please try again.', 'error');
                    assignSubmitBtn.disabled = false;
                    assignSubmitBtn.innerHTML = '<i class="fas fa-check"></i> Assign';
                };

                xhr.send(body);
            });

            /* ── Remove item (plain XHR POST) ──────────── */
            window.removeItem = function (itemId, roomId, name) {
                if (!confirm('Remove "' + name + '" from this room?')) return;

                var body = 'item_id=' + encodeURIComponent(itemId)
                    + '&room_id=' + encodeURIComponent(roomId);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../config/removeItem.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function () {
                    if (xhr.status === 401) { window.location.href = '../config/login.php'; return; }

                    var msg = xhr.responseText.trim();
                    if (msg.indexOf('Success') !== -1) {
                        showToast('"' + name + '" removed and returned to inventory.');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showToast(msg || 'Failed to remove item.', 'error');
                    }
                };

                xhr.onerror = function () {
                    showToast('Network error. Please try again.', 'error');
                };

                xhr.send(body);
            };

            /* ── Escape helper ─────────────────────────── */
            function escapeHtml(text) {
                if (!text && text !== 0) return '';
                var el = document.createElement('div');
                el.textContent = String(text);
                return el.innerHTML;
            }

            /* ── Mobile sidebar ────────────────────────── */
            var menuToggle = document.getElementById('menuToggle');
            var sidebar = document.getElementById('sidebar');
            var sidebarOverlay = document.getElementById('sidebarOverlay');
            var sidebarClose = document.getElementById('sidebarClose');

            function openSidebar() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (menuToggle) menuToggle.addEventListener('click', openSidebar);
            if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
            if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);

            if (window.innerWidth <= 768 && sidebar) {
                var links = sidebar.querySelectorAll('a');
                for (var i = 0; i < links.length; i++) {
                    links[i].addEventListener('click', closeSidebar);
                }
            }

        })();
    </script>
</body>

</html>