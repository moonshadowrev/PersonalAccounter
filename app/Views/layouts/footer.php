<?php if (isset($_SESSION['user'])): ?>
    </div> <!-- /main-content -->
</div> <!-- /app-container -->
<?php else: ?>
    </div> <!-- /login-wrapper -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($view_data['load_datatable']) && $view_data['load_datatable']): ?>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.js"></script>
<script>
    $(document).ready(function () {
        $('<?php echo isset($view_data['datatable_target']) ? $view_data['datatable_target'] : '#dataTable'; ?>').DataTable({
            responsive: true,
            destroy: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[0, 'desc']], // Sort by ID descending by default
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });
</script>
<?php endif; ?>
<script src="/js/main.js"></script>
</body>
</html> 