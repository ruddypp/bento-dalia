<?php if(isset($_SESSION['user_id'])): ?>
        </div> <!-- end content -->
    </div> <!-- end flex container -->
<?php endif; ?>

<!-- Custom JavaScript -->
<script src="assets/js/script.js"></script>
<script src="assets/js/responsive.js"></script>

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
    $('.select2').select2();
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

// Create overlay div for sidebar
function createSidebarOverlay() {
    const overlayExists = document.getElementById('sidebar-overlay');
    if (!overlayExists) {
        const overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.classList.add('sidebar-overlay');
        document.body.appendChild(overlay);
        
        // Close sidebar when overlay is clicked
        overlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    return document.getElementById('sidebar-overlay');
}

// Function to open sidebar
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const overlay = createSidebarOverlay();
    
    // Force styles to ensure visibility
    sidebar.style.display = 'block';
    sidebar.style.visibility = 'visible';
    sidebar.style.transform = 'translateX(0)';
    sidebar.style.zIndex = '1000';
    
    sidebar.classList.add('active');
    sidebarToggle.classList.add('active');
    overlay.classList.add('active');
    
    // Store sidebar state
    localStorage.setItem('sidebarActive', 'true');
}

// Function to close sidebar
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar && sidebarToggle) {
        sidebar.classList.remove('active');
        sidebar.style.transform = 'translateX(-100%)';
        sidebarToggle.classList.remove('active');
        
        if (overlay) {
            overlay.classList.remove('active');
        }
        
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
    const overlay = document.getElementById('sidebar-overlay');
    const isMobile = window.innerWidth < 1024;
    
    if (isMobile) {
        // On mobile devices, check localStorage for sidebar state
        const sidebarActive = localStorage.getItem('sidebarActive') === 'true';
        if (sidebarActive) {
            sidebar.classList.add('active');
            sidebarToggle.classList.add('active');
            overlay.classList.add('active');
        }
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