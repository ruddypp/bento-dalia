/**
 * Modal Responsive Enhancement
 * Script untuk meningkatkan responsivitas modal di semua ukuran layar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix modal heights on open
    const fixModalOnShow = function() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Check if modal already has event listener
            if (!modal.dataset.responsiveFixed) {
                modal.dataset.responsiveFixed = 'true';
                
                // Use MutationObserver to detect when modal becomes visible
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.attributeName === 'class' && 
                            modal.classList.contains('show')) {
                            adjustModalHeight(modal);
                        }
                    });
                });
                
                observer.observe(modal, { attributes: true });
                
                // Also handle jQuery modal events if jQuery is available
                if (typeof jQuery !== 'undefined') {
                    jQuery(modal).on('shown.bs.modal', function() {
                        adjustModalHeight(modal);
                    });
                    
                    // Adjust on window resize while modal is open
                    jQuery(window).on('resize', function() {
                        if (jQuery(modal).hasClass('show')) {
                            adjustModalHeight(modal);
                        }
                    });
                }
            }
        });
    };
    
    // Function to adjust modal height based on viewport
    const adjustModalHeight = function(modal) {
        const modalDialog = modal.querySelector('.modal-dialog');
        const modalContent = modal.querySelector('.modal-content');
        const modalHeader = modal.querySelector('.modal-header');
        const modalBody = modal.querySelector('.modal-body');
        const modalFooter = modal.querySelector('.modal-footer');
        
        if (!modalDialog || !modalContent || !modalBody) return;
        
        // Reset any previous inline styles
        modalBody.style.maxHeight = '';
        
        // Get viewport height and width
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        
        // Calculate header and footer heights
        const headerHeight = modalHeader ? modalHeader.offsetHeight : 0;
        const footerHeight = modalFooter ? modalFooter.offsetHeight : 0;
        
        // Different calculations based on device size
        let maxBodyHeight;
        if (viewportWidth >= 992) {
            // Desktop - more space and precision
            maxBodyHeight = Math.floor(viewportHeight * 0.85) - headerHeight - footerHeight - 40; // 40px for padding
            
            // Ensure modal width is appropriate for desktop
            if (modal.classList.contains('modal-lg')) {
                modalDialog.style.maxWidth = '800px';
            } else if (modal.classList.contains('modal-xl')) {
                modalDialog.style.maxWidth = '1140px';
            } else if (modal.classList.contains('modal-sm')) {
                modalDialog.style.maxWidth = '300px';
            } else {
                modalDialog.style.maxWidth = '500px';
            }
            
            // Ensure modal dialog doesn't exceed viewport height
            modalDialog.style.maxHeight = (viewportHeight * 0.95) + 'px';
        } else {
            // Mobile/tablet - tighter space
            maxBodyHeight = Math.floor(viewportHeight * 0.9) - headerHeight - footerHeight - 30; // 30px for padding
            
            // Adjust modal width for smaller screens
            if (viewportWidth < 576) {
                modalDialog.style.margin = '0.5rem';
                modalDialog.style.maxWidth = 'calc(100% - 1rem)';
            }
        }
        
        // Apply max height to modal body
        modalBody.style.maxHeight = maxBodyHeight + 'px';
        modalBody.style.overflowY = 'auto';
        
        // Ensure modal is centered in viewport
        const modalHeight = modalContent.offsetHeight;
        if (modalHeight < viewportHeight) {
            modalDialog.style.marginTop = Math.max(10, (viewportHeight - modalHeight) / 2) + 'px';
        } else {
            modalDialog.style.marginTop = '10px';
        }
        
        // Ensure content inside modal body doesn't overflow
        const tables = modalBody.querySelectorAll('table');
        tables.forEach(table => {
            // Ensure tables have responsive wrapper
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
        
        // Fix form field widths to prevent overflow
        const formFields = modalBody.querySelectorAll('input, select, textarea');
        formFields.forEach(field => {
            field.style.maxWidth = '100%';
        });
    };
    
    // Fix Select2 dropdowns in modals
    const fixSelect2InModals = function() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            // Fix Select2 search box focus
            jQuery(document).on('select2:open', () => {
                document.querySelector('.select2-search__field').focus();
            });
            
            // Fix Select2 inside modals
            jQuery(document).on('shown.bs.modal', '.modal', function() {
                const $modal = jQuery(this);
                const $select2Elements = $modal.find('select.select2');
                
                // Fix container width
                $modal.find('.select2-container').css('width', '100%');
                
                // Refresh Select2 elements
                $select2Elements.each(function() {
                    const $select = jQuery(this);
                    
                    // Destroy and reinitialize for proper rendering
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    
                    // Desktop-specific settings
                    if (window.innerWidth >= 992) {
                        $select.select2({
                            width: '100%',
                            dropdownParent: $modal,
                            dropdownAutoWidth: true,
                            dropdownPosition: 'below',
                            minimumResultsForSearch: 10,
                            templateResult: function(data) {
                                if (!data.id) return data.text;
                                
                                // Add ellipsis for long text
                                const $result = jQuery('<div></div>');
                                $result.text(data.text);
                                $result.css({
                                    'text-overflow': 'ellipsis',
                                    'overflow': 'hidden',
                                    'white-space': 'nowrap',
                                    'max-width': '100%'
                                });
                                
                                return $result;
                            }
                        });
                    } else {
                        // Mobile settings
                        $select.select2({
                            width: '100%',
                            dropdownParent: $modal
                        });
                    }
                });
                
                // Fix dropdown positioning
                jQuery(window).trigger('resize.select2');
            });
        }
    };
    
    // Fix DataTables in modals
    const fixDataTablesInModals = function() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
            jQuery(document).on('shown.bs.modal', '.modal', function() {
                const tables = jQuery(this).find('table.dataTable');
                if (tables.length > 0) {
                    tables.each(function() {
                        if (jQuery.fn.DataTable.isDataTable(this)) {
                            jQuery(this).DataTable().columns.adjust();
                        }
                    });
                }
            });
        }
    };
    
    // Fix form inputs in modals for iOS
    const fixIOSInputs = function() {
        // Fix for iOS input zoom
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        if (isIOS) {
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.style.fontSize = '16px';
            });
        }
    };
    
    // Make modals draggable if jQuery UI is available
    const makeModalsDraggable = function() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.draggable !== 'undefined') {
            jQuery(document).on('shown.bs.modal', '.modal', function() {
                jQuery(this).find('.modal-dialog').draggable({
                    handle: '.modal-header',
                    containment: 'document'
                });
            });
        }
    };
    
    // Fix modals on mobile devices
    const fixMobileModals = function() {
        // Fix for modals on mobile
        if (window.innerWidth < 768) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const modalDialog = modal.querySelector('.modal-dialog');
                if (modalDialog) {
                    modalDialog.style.margin = '0.5rem';
                    modalDialog.style.maxWidth = 'calc(100% - 1rem)';
                }
            });
        }
    };
    
    // Initialize all fixes
    const initModalFixes = function() {
        fixModalOnShow();
        fixSelect2InModals();
        fixDataTablesInModals();
        fixIOSInputs();
        makeModalsDraggable();
        fixMobileModals();
        
        // Also fix on window resize
        window.addEventListener('resize', function() {
            fixMobileModals();
            
            // Adjust any open modals
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                adjustModalHeight(modal);
            });
        });
    };
    
    // Run initialization
    initModalFixes();
}); 