/**
 * Modal Desktop Precision Fixes
 * Script untuk memperbaiki tampilan modal pada desktop khususnya di halaman bahan_baku.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk memperbaiki modal pada desktop
    const fixDesktopModals = function() {
        // Hanya jalankan pada desktop
        if (window.innerWidth < 992) return;
        
        // Perbaikan untuk modal bahan baku
        const fixBahanBakuModals = function() {
            const modalIds = ['#addBahanBakuModal', '#editBahanBakuModal', '#viewBahanBakuModal', '#returBahanBakuModal'];
            
            modalIds.forEach(modalId => {
                const modal = document.querySelector(modalId);
                if (!modal) return;
                
                // Perbaiki posisi modal
                const modalContent = modal.querySelector('.relative');
                if (modalContent) {
                    modalContent.style.top = '0';
                    modalContent.style.marginTop = '2rem';
                    modalContent.style.width = '800px';
                    modalContent.style.maxWidth = '90%';
                }
                
                // Perbaiki tinggi modal
                const modalBody = modal.querySelector('.mt-3');
                if (modalBody) {
                    modalBody.style.maxHeight = 'calc(90vh - 4rem)';
                    modalBody.style.overflow = 'auto';
                }
                
                // Tambahkan event listener untuk modal
                if (!modal.dataset.desktopFixed) {
                    modal.dataset.desktopFixed = 'true';
                    
                    // Perbaiki modal saat ditampilkan
                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.attributeName === 'class' && 
                                !modal.classList.contains('hidden')) {
                                fixModalPosition(modal);
                            }
                        });
                    });
                    
                    observer.observe(modal, { attributes: true });
                }
            });
        };
        
        // Fungsi untuk memperbaiki posisi modal
        const fixModalPosition = function(modal) {
            const modalContent = modal.querySelector('.relative');
            if (!modalContent) return;
            
            // Reset posisi
            modalContent.style.top = '0';
            modalContent.style.marginTop = '2rem';
            
            // Pastikan modal tidak terlalu tinggi
            const viewportHeight = window.innerHeight;
            const modalHeight = modalContent.offsetHeight;
            
            if (modalHeight > viewportHeight * 0.9) {
                modalContent.style.height = (viewportHeight * 0.9) + 'px';
                
                // Pastikan body modal bisa di-scroll
                const modalBody = modalContent.querySelector('.mt-3');
                if (modalBody) {
                    const headerHeight = modalContent.querySelector('.flex.justify-between.items-center') ? 
                        modalContent.querySelector('.flex.justify-between.items-center').offsetHeight : 0;
                    
                    modalBody.style.maxHeight = `calc(${viewportHeight * 0.9}px - ${headerHeight}px - 2rem)`;
                    modalBody.style.overflowY = 'auto';
                }
            }
            
            // Perbaiki lebar untuk modal spesifik
            if (modal.id === 'addBahanBakuModal') {
                modalContent.style.width = '800px';
                modalContent.style.maxWidth = '90%';
            }
        };
        
        // Perbaikan untuk tabel dalam modal
        const fixTablesInModals = function() {
            const tables = document.querySelectorAll('#addBahanBakuModal table, #editBahanBakuModal table, #viewBahanBakuModal table, #returBahanBakuModal table');
            
            tables.forEach(table => {
                table.style.width = '100%';
                table.style.tableLayout = 'fixed';
                
                // Perbaiki lebar kolom
                const headerCells = table.querySelectorAll('th');
                if (headerCells.length > 0) {
                    const equalWidth = 100 / headerCells.length;
                    headerCells.forEach(cell => {
                        cell.style.width = equalWidth + '%';
                    });
                }
            });
        };
        
        // Perbaikan untuk Select2 dalam modal
        const fixSelect2InModals = function() {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
                jQuery(document).on('shown.bs.modal', '.modal', function() {
                    const $modal = jQuery(this);
                    const $select2Elements = $modal.find('select.select2');
                    
                    // Refresh Select2 elements
                    $select2Elements.each(function() {
                        const $select = jQuery(this);
                        
                        // Destroy and reinitialize for proper rendering
                        if ($select.data('select2')) {
                            $select.select2('destroy');
                        }
                        
                        $select.select2({
                            width: '100%',
                            dropdownParent: $modal,
                            dropdownAutoWidth: true
                        });
                    });
                });
            }
        };
        
        // Jalankan semua perbaikan
        fixBahanBakuModals();
        fixTablesInModals();
        fixSelect2InModals();
        
        // Perbaiki juga saat window di-resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                fixBahanBakuModals();
                fixTablesInModals();
            }
        });
    };
    
    // Jalankan perbaikan untuk desktop
    fixDesktopModals();
}); 