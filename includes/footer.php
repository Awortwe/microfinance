<?php
/**
 * Common Footer Template
 * Included at the bottom of all authenticated pages
 */
?>
    </div><!-- End of main-content -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/js/main.js"></script>
    <script src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/js/validation.js"></script>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.querySelector('.sidebar-backdrop');
            sidebar.classList.toggle('show');
            if (backdrop) {
                backdrop.classList.toggle('show');
            }
        }

        // Close sidebar when clicking backdrop
        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.querySelector('.sidebar-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
        });

        // Initialize DataTables
        $(document).ready(function() {
            // Check if DataTable is already initialized to avoid duplication
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable('.datatable')) {
                $('.datatable').DataTable({
                    "pageLength": 25,
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                           '<"row"<"col-sm-12"tr>>' +
                           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search records...",
                        "lengthMenu": "Show _MENU_ records per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ records",
                        "infoEmpty": "No records found",
                        "infoFiltered": "(filtered from _MAX_ total records)",
                        "zeroRecords": "No matching records found",
                        "paginate": {
                            "first": "<i class='bi bi-chevron-double-left'></i>",
                            "last": "<i class='bi bi-chevron-double-right'></i>",
                            "next": "<i class='bi bi-chevron-right'></i>",
                            "previous": "<i class='bi bi-chevron-left'></i>"
                        }
                    },
                    "order": [[0, "asc"]],
                    "columnDefs": [
                        { "orderable": false, "targets": -1 } // Last column (actions) not sortable
                    ],
                    "drawCallback": function() {
                        // Re-initialize Bootstrap tooltips after table redraw
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl);
                        });
                    }
                });
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            });
        }, 5000);

        // Confirm delete dialog
        function confirmDelete(message = 'Are you sure you want to delete this record?') {
            return confirm(message);
        }

        // Confirm action with custom message
        function confirmAction(message = 'Are you sure?') {
            return confirm(message);
        }

        // Format number inputs - prevent negative values
        document.addEventListener('DOMContentLoaded', function() {
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
                
                // Prevent non-numeric input
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'e' || e.key === 'E' || e.key === '-') {
                        e.preventDefault();
                    }
                });
            });

            // Initialize all tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize all popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });

        // Print page function
        function printPage() {
            window.print();
        }

        // Go back function
        function goBack() {
            window.history.back();
        }

        // Format currency display
        function formatCurrency(amount, currency = 'GHS') {
            return currency + ' ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    </script>

    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php echo $page_scripts; ?>
    <?php endif; ?>

    <!-- Loading Spinner (hidden by default) -->
    <div id="loadingSpinner" class="spinner-overlay" style="display: none;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Please wait...</p>
        </div>
    </div>

    <script>
        // Show loading spinner on form submit
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form:not(.no-spinner)');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const spinner = document.getElementById('loadingSpinner');
                    if (spinner) {
                        spinner.style.display = 'flex';
                    }
                });
            });
        });

        // Hide loading spinner when page loads
        window.addEventListener('load', function() {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>