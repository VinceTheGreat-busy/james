/**
 * Sidebar Toggle Functionality
 * SHJCS Inventory System
 */

(function () {
    'use strict';

    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('aside');
    const layout = document.querySelector('.layout');

    if (!toggleButton || !sidebar) {
        console.warn('Sidebar elements not found');
        return;
    }

    // Check if sidebar state is saved in localStorage
    const sidebarState = localStorage.getItem('sidebarCollapsed');

    if (sidebarState === 'true') {
        layout.classList.add('sidebar-collapsed');
    }

    // Toggle sidebar on button click
    toggleButton.addEventListener('click', function () {
        layout.classList.toggle('sidebar-collapsed');

        // Save state to localStorage
        const isCollapsed = layout.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function (event) {
        const isMobile = window.innerWidth <= 768;

        if (isMobile && !sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
            layout.classList.add('sidebar-collapsed');
        }
    });

    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            // Auto-collapse on mobile
            if (window.innerWidth <= 768) {
                layout.classList.add('sidebar-collapsed');
            }
        }, 250);
    });

})();