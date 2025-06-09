/**
 * Responsive enhancement script for Bento Kopi Inventory System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Wrap all tables with responsive container
    const tables = document.querySelectorAll('table:not(.dataTable)');
    tables.forEach(table => {
        // Skip if table is already wrapped
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.classList.add('table-responsive');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Add responsive classes to all form groups
    const formGroups = document.querySelectorAll('.form-group, .form-row');
    formGroups.forEach(group => {
        group.classList.add('responsive-form-group');
    });
    
    // Make all buttons more touch-friendly on mobile
    const buttons = document.querySelectorAll('.btn, button');
    buttons.forEach(button => {
        if (!button.classList.contains('responsive-button')) {
            button.classList.add('responsive-button');
        }
    });
    
    // Make action button groups responsive
    const actionGroups = document.querySelectorAll('.btn-group, .action-buttons');
    actionGroups.forEach(group => {
        group.classList.add('action-buttons');
    });
    
    // Enhance Select2 responsiveness
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').on('select2:open', function() {
            document.querySelector('.select2-search__field').focus();
        });
    }
    
    // Fix modal dialogs for mobile
    const modals = document.querySelectorAll('.modal-dialog, .modal-content');
    modals.forEach(modal => {
        modal.classList.add('responsive-modal');
    });
    
    // Enhance DataTables responsive behavior
    if (typeof $.fn.DataTable !== 'undefined') {
        // Already handled in initDataTables function
    }
    
    // Make sidebar submenu more touch-friendly on mobile
    const isMobile = window.innerWidth < 1024;
    if (isMobile) {
        // Close sidebar when clicking on non-submenu links on mobile
        const sidebarLinks = document.querySelectorAll('.sidebar a:not([onclick*="toggleSubmenu"])');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Don't close sidebar if clicking within a submenu toggle
                if (!this.closest('ul').classList.contains('hidden')) {
                    if (typeof closeSidebar === 'function') {
                        // Small delay to allow the click to register
                        setTimeout(() => {
                            closeSidebar();
                        }, 100);
                    }
                }
            });
        });
        // Add touch-friendly padding to submenu items
        const submenuItems = document.querySelectorAll('.sidebar a');
        submenuItems.forEach(item => {
            item.style.padding = '0.75rem 1rem';
        });
        
        // Make submenu parent items easier to tap
        const submenuParents = document.querySelectorAll('.sidebar a[onclick*="toggleSubmenu"]');
        submenuParents.forEach(parent => {
            // Make the entire parent item clickable
            parent.style.position = 'relative';
            
            // Add a slightly larger tap target for the toggle icon
            const chevron = parent.querySelector('.fas.fa-chevron-down, .fas.fa-chevron-up');
            if (chevron) {
                chevron.style.padding = '10px';
                chevron.style.margin = '-10px';
            }
        });
    }
});