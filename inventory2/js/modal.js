/**
 * Modal Functionality – Add / Edit / Delete Items
 * SHJCS Inventory System
 *
 * openEditModal  – reads item data from window.__ITEMS__ (no network call).
 * editItem POST  – plain XHR POST to editItem.php, reads JSON response.
 * deleteItem     – plain XHR POST to delete.php, reads JSON response.
 */

(function () {
    'use strict';

    /* ----------------------------------------------------------
     * ADD ITEM modal
     * ---------------------------------------------------------- */
    var addItemBtn = document.getElementById('addItem');
    var itemModal = document.getElementById('itemModal');
    var itemModalClose = document.getElementById('itemModalClose');
    var addItemForm = document.getElementById('addItemForm');
    var addItemSubmit = document.getElementById('addItemSubmit');

    if (addItemBtn && itemModal) {
        addItemBtn.addEventListener('click', function () {
            itemModal.style.display = 'flex';
            if (addItemForm) addItemForm.reset();
        });
    }

    if (itemModalClose && itemModal) {
        itemModalClose.addEventListener('click', function () {
            itemModal.style.display = 'none';
        });
    }

    // Add-Item form submits as a normal POST (the <form action> does the work).
    // Button just validates and disables itself to prevent double-click.
    if (addItemSubmit && addItemForm) {
        addItemSubmit.addEventListener('click', function (e) {
            e.preventDefault();
            if (!addItemForm.checkValidity()) {
                addItemForm.reportValidity();
                return;
            }
            addItemSubmit.disabled = true;
            addItemSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding…';
            addItemForm.submit();
        });
    }

    /* ----------------------------------------------------------
     * EDIT ITEM modal
     * ---------------------------------------------------------- */
    var editItemModal = document.getElementById('editItemModal');
    var editItemModalClose = document.getElementById('editItemModalClose');
    var editItemForm = document.getElementById('editItemForm');
    var editItemSubmit = document.getElementById('editItemSubmit');

    if (editItemModalClose && editItemModal) {
        editItemModalClose.addEventListener('click', function () {
            editItemModal.style.display = 'none';
        });
    }

    // Edit-Item submit – plain XHR POST, editItem.php returns JSON
    if (editItemSubmit && editItemForm) {
        editItemSubmit.addEventListener('click', function (e) {
            e.preventDefault();
            if (!editItemForm.checkValidity()) {
                editItemForm.reportValidity();
                return;
            }

            editItemSubmit.disabled = true;
            editItemSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

            var body = 'item_id=' + encodeURIComponent(document.getElementById('editItemId').value)
                + '&itemName=' + encodeURIComponent(document.getElementById('editItemName').value)
                + '&quantity=' + encodeURIComponent(document.getElementById('editQuantity').value)
                + '&condition=' + encodeURIComponent(document.getElementById('editCondition').value)
                + '&type=' + encodeURIComponent(document.getElementById('editType').value)
                + '&description=' + encodeURIComponent(document.getElementById('editDescription').value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../config/editItem.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function () {
                var data;
                try { data = JSON.parse(xhr.responseText); } catch (ex) { data = null; }

                if (data && data.status === 'success') {
                    showAlert('success', data.message);
                    editItemModal.style.display = 'none';
                    setTimeout(function () { window.location.reload(); }, 1000);
                } else {
                    showAlert('error', (data && data.message) || 'Failed to update item.');
                    editItemSubmit.disabled = false;
                    editItemSubmit.innerHTML = '<i class="fas fa-check"></i> Save Changes';
                }
            };

            xhr.onerror = function () {
                showAlert('error', 'Network error. Please try again.');
                editItemSubmit.disabled = false;
                editItemSubmit.innerHTML = '<i class="fas fa-check"></i> Save Changes';
            };

            xhr.send(body);
        });
    }

    /* ----------------------------------------------------------
     * Close modals on backdrop click
     * ---------------------------------------------------------- */
    window.addEventListener('click', function (e) {
        if (itemModal && e.target === itemModal) itemModal.style.display = 'none';
        if (editItemModal && e.target === editItemModal) editItemModal.style.display = 'none';
    });

    /* ----------------------------------------------------------
     * GLOBAL: openEditModal(itemId)
     * Reads data from window.__ITEMS__ – no network call.
     * ---------------------------------------------------------- */
    window.openEditModal = function (itemId) {
        var items = window.__ITEMS__ || [];
        var item = null;

        for (var i = 0; i < items.length; i++) {
            if (items[i].id === itemId) {
                item = items[i];
                break;
            }
        }

        if (!item) {
            showAlert('error', 'Item not found. Please refresh and try again.');
            return;
        }

        document.getElementById('editItemId').value = item.id;
        document.getElementById('editItemName').value = item.name;
        document.getElementById('editQuantity').value = item.quantity;
        document.getElementById('editCondition').value = item.condition;   // 'condition' key in __ITEMS__
        document.getElementById('editType').value = item.type;
        document.getElementById('editDescription').value = item.description || '';

        if (editItemModal) editItemModal.style.display = 'flex';
    };

    /* ----------------------------------------------------------
     * GLOBAL: deleteItem(itemId, itemName)
     * Plain XHR POST; delete.php returns JSON.
     * ---------------------------------------------------------- */
    window.deleteItem = function (itemId, itemName) {
        if (!confirm('Are you sure you want to delete "' + itemName + '"?')) return;

        var body = 'item_id=' + encodeURIComponent(itemId);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../config/delete.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            var data;
            try { data = JSON.parse(xhr.responseText); } catch (ex) { data = null; }

            if (data && data.status === 'success') {
                showAlert('success', data.message);
                setTimeout(function () { window.location.reload(); }, 1000);
            } else {
                showAlert('error', (data && data.message) || 'Failed to delete item.');
            }
        };

        xhr.onerror = function () {
            showAlert('error', 'Network error. Please try again.');
        };

        xhr.send(body);
    };

    /* ----------------------------------------------------------
     * Alert helper (top-of-page toast, shared by all modals)
     * ---------------------------------------------------------- */
    function showAlert(type, message) {
        var old = document.querySelectorAll('.alert');
        for (var i = 0; i < old.length; i++) old[i].remove();

        var alert = document.createElement('div');
        alert.className = 'alert ' + type;
        alert.innerHTML =
            '<span>' + escapeHtml(message) + '</span>'
            + '<button class="alert-close" onclick="this.parentElement.remove()">'
            + '<i class="fas fa-times"></i></button>';

        var container = document.querySelector('.main-content') || document.body;
        container.insertBefore(alert, container.firstChild);

        setTimeout(function () {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 350);
        }, 5000);
    }

    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        var el = document.createElement('div');
        el.textContent = String(text);
        return el.innerHTML;
    }

})();