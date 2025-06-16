// Toggle sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-hidden');
            content.classList.toggle('content-full');
        });
    }
    
    // Close alert
    const alertCloses = document.querySelectorAll('.alert-close');
    alertCloses.forEach(function(alertClose) {
        alertClose.addEventListener('click', function() {
            this.parentElement.parentElement.remove();
        });
    });
    
    // Datatables initialization
    if (typeof $.fn.DataTable !== 'undefined' && document.querySelector('.data-table')) {
        $('.data-table').DataTable({
            responsive: true,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ entri",
                zeroRecords: "Data tidak ditemukan",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                infoFiltered: "(difilter dari _MAX_ total entri)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    }
    
    // Select2 initialization
    if (typeof $.fn.select2 !== 'undefined' && document.querySelector('.select2')) {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Pilih opsi'
        });
    }
    
    // Date picker
    if (typeof $.fn.datepicker !== 'undefined' && document.querySelector('.datepicker')) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            language: 'id'
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Dynamic form fields for item entries
    if (document.getElementById('add-item-row')) {
        document.getElementById('add-item-row').addEventListener('click', function() {
            const template = document.getElementById('item-row-template');
            const container = document.getElementById('items-container');
            const clone = template.content.cloneNode(true);
            const rowCount = container.querySelectorAll('.item-row').length + 1;
            
            const inputs = clone.querySelectorAll('[name]');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                input.setAttribute('name', name.replace('index', rowCount));
                input.setAttribute('id', name.replace('index', rowCount));
            });
            
            container.appendChild(clone);
            
            // Re-initialize select2 for new row
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    width: '100%',
                    placeholder: 'Pilih opsi'
                });
            }
            
            // Add event listener to remove button
            const removeButtons = container.querySelectorAll('.remove-item-row');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.item-row').remove();
                });
            });
        });
    }
    
    // Print functionality
    if (document.getElementById('print-button')) {
        document.getElementById('print-button').addEventListener('click', function() {
            window.print();
        });
    }
}); 