<?php if(isset($_SESSION['user_id'])): ?>
        </div> <!-- end content -->
    </div> <!-- end flex container -->
<?php endif; ?>

<!-- Custom JavaScript -->
<script src="assets/js/script.js"></script>
<script src="assets/js/responsive.js"></script>
<script src="assets/js/modal-responsive.js"></script>
<script src="assets/js/modal-desktop-fixes.js"></script>

<script>
// Global script to safely initialize DataTables
function initDataTables(selector) {
    const tables = document.querySelectorAll(selector);
    tables.forEach(table => {
        // Check if this table is already a DataTable
        if (!$.fn.DataTable.isDataTable(table)) {
            $(table).DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                responsive: true,
                scrollX: true,
                autoWidth: false
            });
        }
    });
}

// Initialize any data-tables on page load
$(document).ready(function() {
    initDataTables('.data-table');
    
    // Initialize Select2
    $('.select2').select2({
        width: '100%' // Force 100% width
    });
    
    // Fix modal heights and positions
    $('.modal').on('shown.bs.modal', function() {
        const $modal = $(this);
        const $modalBody = $modal.find('.modal-body');
        const $modalDialog = $modal.find('.modal-dialog');
        const $modalContent = $modal.find('.modal-content');
        const $modalHeader = $modal.find('.modal-header');
        const $modalFooter = $modal.find('.modal-footer');
        
        // Calculate heights
        const viewportHeight = window.innerHeight;
        const headerHeight = $modalHeader.outerHeight() || 0;
        const footerHeight = $modalFooter.outerHeight() || 0;
        const maxBodyHeight = Math.floor(viewportHeight * 0.9) - headerHeight - footerHeight - 30;
        
        // Apply max height to body
        $modalBody.css({
            'max-height': maxBodyHeight + 'px',
            'overflow-y': 'auto'
        });
        
        // Center modal if smaller than viewport
        const modalHeight = $modalContent.outerHeight();
        if (modalHeight < viewportHeight) {
            $modalDialog.css('margin-top', Math.max(10, (viewportHeight - modalHeight) / 2) + 'px');
        } else {
            $modalDialog.css('margin-top', '10px');
        }
    });
    
    // Fix form fields in modals
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('select, input, textarea').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).next('.select2-container').css('width', '100%');
            } else {
                $(this).css('max-width', '100%');
            }
        });
        
        // Make tables responsive inside modals
        $(this).find('table').wrap('<div class="table-responsive"></div>');
    });
    
    // Handle window resize for open modals
    $(window).on('resize', function() {
        $('.modal.show').each(function() {
            const $modal = $(this);
            const $modalBody = $modal.find('.modal-body');
            const $modalDialog = $modal.find('.modal-dialog');
            const $modalContent = $modal.find('.modal-content');
            const $modalHeader = $modal.find('.modal-header');
            const $modalFooter = $modal.find('.modal-footer');
            
            // Recalculate heights
            const viewportHeight = window.innerHeight;
            const headerHeight = $modalHeader.outerHeight() || 0;
            const footerHeight = $modalFooter.outerHeight() || 0;
            const maxBodyHeight = Math.floor(viewportHeight * 0.9) - headerHeight - footerHeight - 30;
            
            // Apply max height to body
            $modalBody.css({
                'max-height': maxBodyHeight + 'px',
                'overflow-y': 'auto'
            });
            
            // Adjust modal width based on screen size
            if (window.innerWidth < 768) {
                $modalDialog.css({
                    'margin': '0.5rem',
                    'max-width': 'calc(100% - 1rem)'
                });
            } else {
                $modalDialog.css({
                    'margin': '1.75rem auto'
                });
                
                // Reset to default sizes based on modal size
                if ($modalDialog.parent('.modal-lg').length) {
                    $modalDialog.css('max-width', '800px');
                } else if ($modalDialog.parent('.modal-xl').length) {
                    $modalDialog.css('max-width', '1140px');
                } else if ($modalDialog.parent('.modal-sm').length) {
                    $modalDialog.css('max-width', '300px');
                } else {
                    $modalDialog.css('max-width', '500px');
                }
                
                // Desktop-specific fixes for precision
                if (window.innerWidth >= 992) {
                    // Ensure modal content doesn't overflow
                    const modalWidth = $modalDialog.width();
                    
                    // Fix form layout
                    $modal.find('.form-row').css({
                        'display': 'flex',
                        'flex-wrap': 'wrap',
                        'margin-right': '-5px',
                        'margin-left': '-5px'
                    });
                    
                    // Fix form columns
                    $modal.find('.form-row > [class*="col-"]').css({
                        'padding-right': '5px',
                        'padding-left': '5px'
                    });
                    
                    // Fix tables in modals
                    $modal.find('table').each(function() {
                        const $table = $(this);
                        
                        // Ensure table is responsive
                        if (!$table.parent().hasClass('table-responsive')) {
                            $table.wrap('<div class="table-responsive"></div>');
                        }
                        
                        // Set table width to match container
                        $table.css('width', '100%');
                        
                        // Fix column widths for better text display
                        const colCount = $table.find('thead th').length;
                        if (colCount > 0) {
                            const colWidth = Math.floor(100 / colCount);
                            $table.find('th, td').css('width', colWidth + '%');
                        }
                    });
                }
            }
        });
    });
});

// Toggle sidebar submenu function
function toggleSubmenu(submenuId) {
    const submenu = document.getElementById(submenuId);
    if (submenu) {
        submenu.classList.toggle('hidden');
        const parentLink = submenu.previousElementSibling;
        const icon = parentLink.querySelector('.fa-chevron-down, .fa-chevron-up');
        if (icon) {
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        }
        parentLink.setAttribute('aria-expanded', submenu.classList.contains('hidden') ? 'false' : 'true');
    }
}

// No longer need overlay function
function createSidebarOverlay() {
    // Function kept for compatibility but does nothing
    return null;
}

// Function to open sidebar
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    
    // Force styles to ensure visibility
    sidebar.style.display = 'block';
    sidebar.style.visibility = 'visible';
    sidebar.style.transform = 'translateX(0)';
    sidebar.style.zIndex = '1000';
    
    sidebar.classList.add('active');
    sidebarToggle.classList.add('active');
    
    // Store sidebar state
    localStorage.setItem('sidebarActive', 'true');
}

// Function to close sidebar
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    
    if (sidebar && sidebarToggle) {
        sidebar.classList.remove('active');
        sidebar.style.transform = 'translateX(-100%)';
        sidebarToggle.classList.remove('active');
        
        // Store sidebar state
        localStorage.setItem('sidebarActive', 'false');
    }
}

// Mobile sidebar toggle - using direct click handler
const toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    
    if (!sidebar) return;
    
    if (sidebar.classList.contains('active')) {
        closeSidebar();
    } else {
        openSidebar();
    }
};

// Ensure toggle button is clickable
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn) {
        // Remove any existing listeners
        toggleBtn.replaceWith(toggleBtn.cloneNode(true));
        
        // Add fresh click handler
        document.getElementById('sidebar-toggle').addEventListener('click', toggleSidebar);
    }
});

// Sidebar close button
document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.getElementById('sidebar-close');
    if (closeBtn) {
        // Remove any existing listeners
        closeBtn.replaceWith(closeBtn.cloneNode(true));
        
        // Add fresh click handler
        document.getElementById('sidebar-close').addEventListener('click', closeSidebar);
    }
});

// Check if current page is in a submenu and show that submenu
document.addEventListener('DOMContentLoaded', function() {
    // Create sidebar overlay
    createSidebarOverlay();
    
    // Initialize responsive sidebar state
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const isMobile = window.innerWidth < 1024;
    
    if (isMobile) {
        // Always ensure sidebar is closed on page load for mobile
        sidebar.classList.remove('active');
        if (sidebarToggle) sidebarToggle.classList.remove('active');
        localStorage.setItem('sidebarActive', 'false');
    }
    
    // Add resize listener to handle responsive changes
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            // On desktop, ensure sidebar is visible and reset mobile changes
            sidebar.classList.remove('active');
            sidebarToggle.classList.remove('active');
            overlay.classList.remove('active');
            sidebar.removeAttribute('style');
        } else {
            // On mobile, ensure sidebar toggle is visible
            sidebarToggle.style.display = 'flex';
        }
    });
    
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    
    // Check if current page is in menu submenu
    if (filename === 'menu_makanan.php' || filename === 'menu_minuman.php') {
        const menuSubmenu = document.getElementById('menu-submenu');
        if (menuSubmenu && menuSubmenu.classList.contains('hidden')) {
            toggleSubmenu('menu-submenu');
        }
    }
    
    // Check if current page is in laporan submenu
    if (filename === 'laporan_masuk.php' || filename === 'laporan_keluar.php') {
        const laporanSubmenu = document.getElementById('laporan-submenu');
        if (laporanSubmenu && laporanSubmenu.classList.contains('hidden')) {
            toggleSubmenu('laporan-submenu');
        }
    }
    
    // Check if current page is in inventaris submenu
    if (filename === 'barang.php' || filename === 'bahan_baku.php') {
        const inventarisSubmenu = document.getElementById('inventaris-submenu');
        if (inventarisSubmenu && inventarisSubmenu.classList.contains('hidden')) {
            toggleSubmenu('inventaris-submenu');
        }
    }
});
</script>

</body>
</html>
<?php
// If the output buffer is still active, flush it
if (ob_get_length()) {
    ob_end_flush();
}
?> 