/**
 * Item Search Functionality
 * SHJCS Inventory System
 */

(function() {
    'use strict';

    const searchInput = document.getElementById('itemSearch');
    const itemCardsContainer = document.getElementById('itemCards');
    let searchTimeout;

    if (!searchInput || !itemCardsContainer) {
        console.warn('Search elements not found on this page');
        return;
    }

    // Debounced search function
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        const query = this.value.trim();
        
        // Clear results if search is empty
        if (query.length === 0) {
            showEmptyState();
            return;
        }

        // Wait 300ms after user stops typing
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    /**
     * Perform the search
     */
    function performSearch(query) {
        // Show loading state
        itemCardsContainer.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Searching for "${escapeHtml(query)}"...</p>
            </div>
        `;

        // Fetch results
        fetch(`../config/searchItems.php?search=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    displayResults(data.items, query);
                } else {
                    showError(data.message || 'Search failed');
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Failed to search items. Please try again.');
            });
    }

    /**
     * Display search results
     */
    function displayResults(items, query) {
        if (items.length === 0) {
            itemCardsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No items found for "${escapeHtml(query)}"</p>
                    <small>Try a different search term</small>
                </div>
            `;
            return;
        }

        let html = '';
        items.forEach(item => {
            html += createItemCard(item);
        });

        itemCardsContainer.innerHTML = html;

        // Re-attach drag event listeners if drag-drop is available
        if (typeof attachDragListeners === 'function') {
            attachDragListeners();
        }
    }

    /**
     * Create item card HTML
     */
    function createItemCard(item) {
        const conditionClass = getConditionClass(item.condition);
        
        return `
            <div class="itemCard" 
                 draggable="true" 
                 data-item-id="${item.id}" 
                 data-item-name="${escapeHtml(item.name)}" 
                 data-item-qty="${item.quantity}">
                
                <div class="item-header">
                    <h3>${escapeHtml(item.name)}</h3>
                    <span class="condition-badge ${conditionClass}">
                        ${escapeHtml(item.condition)}
                    </span>
                </div>
                
                <div class="item-details">
                    <p class="item-quantity">
                        <i class="fas fa-boxes"></i>
                        <strong>Quantity:</strong> ${item.quantity}
                    </p>
                    <p class="item-type">
                        <i class="fas fa-tag"></i>
                        <strong>Type:</strong> ${escapeHtml(item.type)}
                    </p>
                    ${item.description ? `
                        <p class="item-description">
                            <i class="fas fa-info-circle"></i>
                            ${escapeHtml(item.description)}
                        </p>
                    ` : ''}
                </div>
                
                <div class="item-actions">
                    <button class="btn-edit" onclick="openEditModal(${item.id})" title="Edit Item">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete" onclick="deleteItem(${item.id}, '${escapeHtml(item.name)}')" title="Delete Item">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Get condition CSS class
     */
    function getConditionClass(condition) {
        const conditionMap = {
            'Available': 'condition-available',
            'Damaged': 'condition-damaged',
            'For Replacement': 'condition-replacement'
        };
        return conditionMap[condition] || 'condition-default';
    }

    /**
     * Show empty state
     */
    function showEmptyState() {
        itemCardsContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>Type to search for items...</p>
            </div>
        `;
    }

    /**
     * Show error message
     */
    function showError(message) {
        itemCardsContainer.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Focus search input on page load
    if (searchInput) {
        searchInput.focus();
    }

})();