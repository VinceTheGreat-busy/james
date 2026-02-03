/**
 * Client-side item search – no network requests
 * SHJCS Inventory System
 *
 * Reads the full item list from window.__ITEMS__ (rendered by dashboard.php).
 * On page load  → renders every item, sorted A-Z (already sorted by the query).
 * On input      → filters that list to names that contain the typed text
 *                 (case-insensitive), re-renders instantly.
 * On Esc        → clears the input and shows all items again.
 * Ctrl/Cmd + K  → focuses the search box from anywhere on the page.
 *
 * After rendering, calls window.attachDragListeners() (provided by dragdrop.js)
 * so every card is immediately draggable.
 */

(function () {
    'use strict';

    /* ----------------------------------------------------------
     * DOM
     * ---------------------------------------------------------- */
    var searchInput = document.getElementById('itemSearch');
    var itemCardsContainer = document.getElementById('itemCards');

    if (!searchInput || !itemCardsContainer) {
        console.warn('search.js – required elements not found');
        return;
    }

    /* ----------------------------------------------------------
     * Data  – injected by dashboard.php before this script runs
     * ---------------------------------------------------------- */
    var allItems = window.__ITEMS__ || [];

    /* ----------------------------------------------------------
     * State
     * ---------------------------------------------------------- */
    var debounceTimer = null;

    /* ----------------------------------------------------------
     * Bootstrap – show everything on first load
     * ---------------------------------------------------------- */
    renderItems(allItems);

    searchInput.addEventListener('input', onInput);
    searchInput.addEventListener('keydown', onKeydown);
    document.addEventListener('keydown', onGlobalKeydown);

    /* ----------------------------------------------------------
     * Input handlers
     * ---------------------------------------------------------- */
    function onInput(e) {
        clearTimeout(debounceTimer);
        var query = e.target.value.trim();

        debounceTimer = setTimeout(function () {
            if (query.length === 0) {
                renderItems(allItems);
            } else {
                renderItems(filterItems(query));
            }
        }, 150);
    }

    function onKeydown(e) {
        if (e.key === 'Escape') {
            searchInput.value = '';
            clearTimeout(debounceTimer);
            renderItems(allItems);
            searchInput.blur();
        }
    }

    function onGlobalKeydown(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    }

    /* ----------------------------------------------------------
     * Filter  – case-insensitive substring match on name
     * ---------------------------------------------------------- */
    function filterItems(query) {
        var lower = query.toLowerCase();
        var out = [];
        for (var i = 0; i < allItems.length; i++) {
            if (allItems[i].name.toLowerCase().indexOf(lower) !== -1) {
                out.push(allItems[i]);
            }
        }
        return out;
    }

    /* ----------------------------------------------------------
     * Render
     * ---------------------------------------------------------- */
    function renderItems(items) {
        if (items.length === 0) {
            var q = searchInput.value.trim();
            itemCardsContainer.innerHTML = q.length > 0 ? noResultsHTML(q) : emptyHTML();
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            html += cardHTML(items[i]);
        }
        itemCardsContainer.innerHTML = html;

        // Hand off drag wiring to dragdrop.js
        if (typeof window.attachDragListeners === 'function') {
            window.attachDragListeners();
        }
    }

    /* ----------------------------------------------------------
     * Card template  – includes drag handle, Edit, Delete
     * ---------------------------------------------------------- */
    function cardHTML(item) {
        var condClass = (item.condition || '').toLowerCase().replace(/\s+/g, '_');

        return '<div class="itemCard" draggable="true"'
            + ' data-item-id="' + item.id + '"'
            + ' data-item-name="' + escapeAttr(item.name) + '"'
            + ' data-item-qty="' + item.quantity + '">'

            // ── main content ──
            + '<div class="item-content">'
            + '<h3>' + escapeHtml(item.name) + '</h3>'
            + '<div class="item-info">'
            + '<p><strong>Quantity:</strong> <span class="item-quantity">' + item.quantity + '</span></p>'
            + '<p><strong>Condition:</strong> '
            + '<span class="condition-badge ' + condClass + '">' + escapeHtml(item.condition) + '</span>'
            + '</p>'
            + '<p><strong>Remarks:</strong> ' + escapeHtml(item.type) + '</p>'
            + (item.description
                ? '<p class="item-description"><i class="fas fa-info-circle"></i> ' + escapeHtml(item.description) + '</p>'
                : '')
            + '</div>'
            + '</div>'

            // ── action row: drag hint + edit + delete ──
            + '<div class="item-actions">'
            + '<div class="item-btn-group">'
            + '<button class="btn-edit" onclick="openEditModal(' + item.id + ')" title="Edit item">'
            + '<i class="fas fa-edit"></i>'
            + '</button>'
            + '<button class="btn-delete" onclick="deleteItem(' + item.id + ', \'' + escapeAttr(item.name) + '\')" title="Delete item">'
            + '<i class="fas fa-trash"></i>'
            + '</button>'
            + '</div>'
            + '</div>'

            + '</div>';
    }

    /* ----------------------------------------------------------
     * State-screen templates
     * ---------------------------------------------------------- */
    function emptyHTML() {
        return '<div class="empty-state">'
            + '<i class="fas fa-inbox"></i>'
            + '<p>No items available.</p>'
            + '</div>';
    }

    function noResultsHTML(query) {
        return '<div class="empty-state">'
            + '<i class="fas fa-inbox"></i>'
            + '<p>No items found for "' + escapeHtml(query) + '"</p>'
            + '<small>Try a different search term</small>'
            + '</div>';
    }

    /* ----------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------- */
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        var el = document.createElement('div');
        el.textContent = String(text);
        return el.innerHTML;
    }

    function escapeAttr(text) {
        if (!text && text !== 0) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /* ----------------------------------------------------------
     * Public API
     * ---------------------------------------------------------- */
    window.searchFunctions = {
        invalidateCache: function () { /* page reloads after assignment */ },
        clearSearch: function () {
            searchInput.value = '';
            renderItems(allItems);
        },
        focusSearch: function () {
            searchInput.focus();
            searchInput.select();
        }
    };

})();