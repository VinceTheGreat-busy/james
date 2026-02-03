/**
 * Drag-and-Drop & Assignment – plain XHR, no JSON
 * SHJCS Inventory System
 *
 * 1. Makes every .itemCard draggable.  (window.attachDragListeners is called
 *    by search.js after every render so freshly-created cards are included.)
 * 2. Makes every .roomCard a drop target.
 * 3. On drop   → opens the quantity modal.
 * 4. On submit → POSTs to assignItem.php, reads the plain-text response,
 *    shows a toast, invalidates the search cache, and reloads.
 */

(function () {
    'use strict';

    /* ----------------------------------------------------------
     * DOM – modal
     * ---------------------------------------------------------- */
    var quantityModal = document.getElementById('quantityModal');
    var modalClose = document.getElementById('modalClose');
    var modalSubmit = document.getElementById('modalSubmit');
    var modalItemName = document.getElementById('modalItemName');
    var modalItemQty = document.getElementById('modalItemQty');
    var modalAssignQty = document.getElementById('modalAssignQty');

    /* ----------------------------------------------------------
     * Transient state
     * ---------------------------------------------------------- */
    var draggedItem = null;   // { id, name, quantity }
    var targetRoom = null;   // { id, name }

    /* ==============================================================
     * DRAG  (source – item cards)
     * ============================================================== */

    /**
     * Wire dragstart/dragend on every .itemCard currently in the DOM.
     * Exported on window so search.js can re-wire after rendering.
     */
    function attachDragListeners() {
        var cards = document.querySelectorAll('.itemCard');
        for (var i = 0; i < cards.length; i++) {
            cards[i].ondragstart = onDragStart;
            cards[i].ondragend = onDragEnd;
        }
    }

    function onDragStart(e) {
        var card = e.currentTarget;

        draggedItem = {
            id: card.dataset.itemId,
            name: card.dataset.itemName,
            quantity: parseInt(card.dataset.itemQty, 10)
        };

        card.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.itemId);
    }

    function onDragEnd(e) {
        e.currentTarget.classList.remove('dragging');
    }

    /* ==============================================================
     * DROP  (target – room cards)
     * ============================================================== */

    /**
     * Wire all four drag-target events on every .roomCard.
     * Room cards are server-rendered and don't change, so this only
     * needs to run once.
     */
    function attachDropListeners() {
        var rooms = document.querySelectorAll('.roomCard');
        for (var i = 0; i < rooms.length; i++) {
            rooms[i].ondragover = onDragOver;
            rooms[i].ondragenter = onDragEnter;
            rooms[i].ondragleave = onDragLeave;
            rooms[i].ondrop = onDrop;
        }
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function onDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }

    function onDragLeave(e) {
        // Ignore leave events that fire when moving into a child node
        if (!this.contains(e.relatedTarget)) {
            this.classList.remove('drag-over');
        }
    }

    function onDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        if (!draggedItem) return;

        targetRoom = {
            id: this.dataset.roomId,
            name: (this.querySelector('h3') || {}).textContent || ''
        };

        openQuantityModal();
    }

    /* ==============================================================
     * MODAL
     * ============================================================== */

    function openQuantityModal() {
        if (!draggedItem || !targetRoom || !quantityModal) return;

        modalItemName.textContent = draggedItem.name;
        modalItemQty.textContent = draggedItem.quantity;
        modalAssignQty.value = 1;
        modalAssignQty.max = draggedItem.quantity;
        modalAssignQty.min = 1;

        quantityModal.style.display = 'flex';
        requestAnimationFrame(function () { modalAssignQty.focus(); });
    }

    function closeQuantityModal() {
        if (quantityModal) quantityModal.style.display = 'none';
        draggedItem = null;
        targetRoom = null;
    }

    /* ==============================================================
     * ASSIGNMENT  (plain XHR POST)
     * ============================================================== */

    function assignItemToRoom() {
        if (!draggedItem || !targetRoom) {
            showAlert('error', 'Invalid item or room selection.');
            return;
        }

        var quantity = parseInt(modalAssignQty.value, 10);

        // --- client-side validation ---
        if (!quantity || quantity < 1 || isNaN(quantity)) {
            showAlert('error', 'Please enter a valid quantity (≥ 1).');
            return;
        }
        if (quantity > draggedItem.quantity) {
            showAlert('error', 'Only ' + draggedItem.quantity + ' available.');
            return;
        }

        // --- disable button while in-flight ---
        modalSubmit.disabled = true;
        modalSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning…';

        // --- build form body manually (no FormData needed) ---
        var body = 'item_id=' + encodeURIComponent(draggedItem.id)
            + '&room_id=' + encodeURIComponent(targetRoom.id)
            + '&quantity=' + encodeURIComponent(quantity);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../config/assignItem.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            if (xhr.status === 401) {
                window.location.href = '../config/login.php';
                return;
            }

            var msg = xhr.responseText.trim();

            // assignItem.php echoes a plain-text message.
            // Success messages contain the word "Success".
            if (msg.indexOf('Success') !== -1) {
                showAlert('success', msg);
                closeQuantityModal();

                // Wipe the search cache so the next search fetches fresh
                // quantities from the server
                if (typeof window.searchFunctions === 'object' &&
                    typeof window.searchFunctions.invalidateCache === 'function') {
                    window.searchFunctions.invalidateCache();
                }

                // Let the user read the toast, then reload
                setTimeout(function () { window.location.reload(); }, 1500);
            } else {
                showAlert('error', msg || 'Assignment failed.');
                resetSubmitButton();
            }
        };

        xhr.onerror = function () {
            showAlert('error', 'Failed to assign item. Please try again.');
            resetSubmitButton();
        };

        xhr.send(body);
    }

    function resetSubmitButton() {
        if (modalSubmit) {
            modalSubmit.disabled = false;
            modalSubmit.innerHTML = '<i class="fas fa-check"></i> Assign Item';
        }
    }

    /* ==============================================================
     * ALERT  (top-of-page toast)
     * ============================================================== */

    function showAlert(type, message) {
        // Remove any previous toasts
        var existing = document.querySelectorAll('.alert');
        for (var i = 0; i < existing.length; i++) existing[i].remove();

        var alert = document.createElement('div');
        alert.className = 'alert ' + type;
        alert.innerHTML =
            '<span>' + escapeHtml(message) + '</span>'
            + '<button class="alert-close" onclick="this.parentElement.remove()">'
            + '<i class="fas fa-times"></i></button>';

        var container = document.querySelector('.main-content') || document.body;
        container.insertBefore(alert, container.firstChild);

        // Auto-dismiss with a short fade
        setTimeout(function () {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 350);
        }, 5000);
    }

    /* ==============================================================
     * HELPERS
     * ============================================================== */

    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        var el = document.createElement('div');
        el.textContent = String(text);
        return el.innerHTML;
    }

    /* ==============================================================
     * EVENT WIRING
     * ============================================================== */

    if (modalClose) modalClose.addEventListener('click', closeQuantityModal);
    if (modalSubmit) modalSubmit.addEventListener('click', assignItemToRoom);

    // Click on the backdrop → close
    window.addEventListener('click', function (e) {
        if (e.target === quantityModal) closeQuantityModal();
    });

    // Enter in quantity input → submit
    if (modalAssignQty) {
        modalAssignQty.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                assignItemToRoom();
            }
        });
    }

    /* ----------------------------------------------------------
     * Init
     * ---------------------------------------------------------- */
    function init() {
        attachDragListeners();
        attachDropListeners();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ----------------------------------------------------------
     * Public API
     * ---------------------------------------------------------- */
    window.attachDragListeners = attachDragListeners;

})();