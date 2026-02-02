/**
 * Modal Functionality for Add/Edit Items
 * SHJCS Inventory System
 */

(function () {
    'use strict';

    // Add Item Modal Elements
    const addItemBtn = document.getElementById('addItem');
    const itemModal = document.getElementById('itemModal');
    const itemModalClose = document.getElementById('itemModalClose');
    const addItemForm = document.getElementById('addItemForm');
    const addItemSubmit = document.getElementById('addItemSubmit');

    // Edit Item Modal Elements
    const editItemModal = document.getElementById('editItemModal');
    const editItemModalClose = document.getElementById('editItemModalClose');
    const editItemForm = document.getElementById('editItemForm');
    const editItemSubmit = document.getElementById('editItemSubmit');

    /*
    |--------------------------------------------------------------------------
    | ADD ITEM MODAL
    |--------------------------------------------------------------------------
    */

    // Open Add Item Modal
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function () {
            itemModal.style.display = 'flex';
            // Reset form
            if (addItemForm) {
                addItemForm.reset();
            }
        });
    }

    // Close Add Item Modal
    if (itemModalClose) {
        itemModalClose.addEventListener('click', function () {
            itemModal.style.display = 'none';
        });
    }

    // Submit Add Item Form
    if (addItemSubmit && addItemForm) {
        addItemSubmit.addEventListener('click', function (e) {
            e.preventDefault();

            // Validate form
            if (!addItemForm.checkValidity()) {
                addItemForm.reportValidity();
                return;
            }

            // Disable button to prevent double submission
            addItemSubmit.disabled = true;
            addItemSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            // Submit form
            addItemForm.submit();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT ITEM MODAL
    |--------------------------------------------------------------------------
    */

    // Close Edit Item Modal
    if (editItemModalClose) {
        editItemModalClose.addEventListener('click', function () {
            editItemModal.style.display = 'none';
        });
    }

    // Submit Edit Item Form
    if (editItemSubmit && editItemForm) {
        editItemSubmit.addEventListener('click', function (e) {
            e.preventDefault();

            // Validate form
            if (!editItemForm.checkValidity()) {
                editItemForm.reportValidity();
                return;
            }

            // Disable button
            editItemSubmit.disabled = true;
            editItemSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            // Get form data
            const formData = new FormData(editItemForm);

            // Submit via AJAX
            fetch('../config/editItem.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', data.message);
                        editItemModal.style.display = 'none';

                        // Refresh the page after 1 second
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', data.message);
                        // Re-enable button
                        editItemSubmit.disabled = false;
                        editItemSubmit.innerHTML = '<i class="fas fa-check"></i> Save Changes';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to update item. Please try again.');
                    // Re-enable button
                    editItemSubmit.disabled = false;
                    editItemSubmit.innerHTML = '<i class="fas fa-check"></i> Save Changes';
                });
        });
    }

    // Close modals when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target === itemModal) {
            itemModal.style.display = 'none';
        }
        if (event.target === editItemModal) {
            editItemModal.style.display = 'none';
        }
    });

    /*
    |--------------------------------------------------------------------------
    | GLOBAL FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Open Edit Modal (called from item cards)
     */
    window.openEditModal = function (itemId) {
        // Fetch item details
        fetch(`../config/getItem.php?id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.item) {
                    const item = data.item;

                    // Populate form fields
                    document.getElementById('editItemId').value = item.id;
                    document.getElementById('editItemName').value = item.name;
                    document.getElementById('editQuantity').value = item.quantity;
                    document.getElementById('editCondition').value = item.conditions;
                    document.getElementById('editType').value = item.type;
                    document.getElementById('editDescription').value = item.description || '';

                    // Show modal
                    editItemModal.style.display = 'flex';
                } else {
                    showAlert('error', 'Failed to load item details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to load item details.');
            });
    };

    /**
     * Delete Item (called from item cards)
     */
    window.deleteItem = function (itemId, itemName) {
        if (!confirm(`Are you sure you want to delete "${itemName}"?`)) {
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.append('item_id', itemId);

        // Submit delete request
        fetch('../config/delete.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message);

                    // Refresh the page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to delete item. Please try again.');
            });
    };

    /**
     * Show alert message
     */
    function showAlert(type, message) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert ${type}`;
        alert.innerHTML = `
            <span>${escapeHtml(message)}</span>
            <button class="alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Insert alert at the top of main content
        const mainContent = document.querySelector('.main-content') || document.body;
        mainContent.insertBefore(alert, mainContent.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();