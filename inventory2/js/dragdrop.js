/**
 * Drag and Drop Functionality for Item Assignment
 * SHJCS Inventory System
 */

(function () {
    'use strict';

    // Modal elements
    const quantityModal = document.getElementById('quantityModal');
    const modalClose = document.getElementById('modalClose');
    const modalSubmit = document.getElementById('modalSubmit');
    const modalItemName = document.getElementById('modalItemName');
    const modalItemQty = document.getElementById('modalItemQty');
    const modalAssignQty = document.getElementById('modalAssignQty');

    // Store current drag data
    let draggedItem = null;
    let targetRoom = null;

    /*
    |--------------------------------------------------------------------------
    | Drag Event Handlers
    |--------------------------------------------------------------------------
    */

    /**
     * Attach drag listeners to all item cards
     */
    function attachDragListeners() {
        const itemCards = document.querySelectorAll('.itemCard');

        itemCards.forEach(card => {
            // Drag start
            card.addEventListener('dragstart', function (e) {
                draggedItem = {
                    id: this.dataset.itemId,
                    name: this.dataset.itemName,
                    quantity: parseInt(this.dataset.itemQty)
                };

                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            });

            // Drag end
            card.addEventListener('dragend', function (e) {
                this.classList.remove('dragging');
            });
        });
    }

    /**
     * Attach drop listeners to all room cards
     */
    function attachDropListeners() {
        const roomCards = document.querySelectorAll('.roomCard');

        roomCards.forEach(card => {
            // Drag over
            card.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });

            // Drag enter
            card.addEventListener('dragenter', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            // Drag leave
            card.addEventListener('dragleave', function (e) {
                this.classList.remove('drag-over');
            });

            // Drop
            card.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (draggedItem) {
                    targetRoom = {
                        id: this.dataset.roomId,
                        name: this.querySelector('h3').textContent
                    };

                    openQuantityModal();
                }
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Open quantity selection modal
     */
    function openQuantityModal() {
        if (!draggedItem || !targetRoom) {
            return;
        }

        // Populate modal
        modalItemName.textContent = draggedItem.name;
        modalItemQty.textContent = draggedItem.quantity;
        modalAssignQty.value = 1;
        modalAssignQty.max = draggedItem.quantity;

        // Show modal
        quantityModal.style.display = 'flex';
        modalAssignQty.focus();
    }

    /**
     * Close quantity modal
     */
    function closeQuantityModal() {
        quantityModal.style.display = 'none';
        draggedItem = null;
        targetRoom = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment Function
    |--------------------------------------------------------------------------
    */

    /**
     * Assign item to room
     */
    function assignItemToRoom() {
        if (!draggedItem || !targetRoom) {
            showAlert('error', 'Invalid item or room selection');
            return;
        }

        const quantity = parseInt(modalAssignQty.value);

        // Validate quantity
        if (!quantity || quantity < 1) {
            showAlert('error', 'Please enter a valid quantity');
            return;
        }

        if (quantity > draggedItem.quantity) {
            showAlert('error', `Only ${draggedItem.quantity} available`);
            return;
        }

        // Disable submit button
        modalSubmit.disabled = true;
        modalSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';

        // Prepare form data
        const formData = new FormData();
        formData.append('item_id', draggedItem.id);
        formData.append('room_id', targetRoom.id);
        formData.append('quantity', quantity);

        // Send request
        fetch('../config/assignItem.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                if (data.includes('Success')) {
                    showAlert('success', data);
                    closeQuantityModal();

                    // Reload page after 1.5 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('error', data);
                    // Re-enable button
                    modalSubmit.disabled = false;
                    modalSubmit.innerHTML = '<i class="fas fa-check"></i> Assign Item';
                }
            })
            .catch(error => {
                console.error('Assignment error:', error);
                showAlert('error', 'Failed to assign item. Please try again.');
                // Re-enable button
                modalSubmit.disabled = false;
                modalSubmit.innerHTML = '<i class="fas fa-check"></i> Assign Item';
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    */

    // Close modal button
    if (modalClose) {
        modalClose.addEventListener('click', closeQuantityModal);
    }

    // Submit button
    if (modalSubmit) {
        modalSubmit.addEventListener('click', assignItemToRoom);
    }

    // Close modal on outside click
    window.addEventListener('click', function (event) {
        if (event.target === quantityModal) {
            closeQuantityModal();
        }
    });

    // Submit on Enter key in quantity input
    if (modalAssignQty) {
        modalAssignQty.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                assignItemToRoom();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */

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

        // Insert alert
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

    /*
    |--------------------------------------------------------------------------
    | Initialize
    |--------------------------------------------------------------------------
    */

    // Attach listeners on page load
    document.addEventListener('DOMContentLoaded', function () {
        attachDragListeners();
        attachDropListeners();
    });

    // Make function available globally for search results
    window.attachDragListeners = attachDragListeners;

})();