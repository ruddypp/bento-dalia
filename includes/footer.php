<?php if(isset($_SESSION['user_id'])): ?>
        </div> <!-- end content -->
    </div> <!-- end flex container -->
<?php endif; ?>

<!-- Custom JavaScript -->
<script src="assets/js/script.js"></script>

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
                }
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

// Mobile sidebar toggle
document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('hidden');
});

// Check if current page is in a submenu and show that submenu
document.addEventListener('DOMContentLoaded', function() {
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