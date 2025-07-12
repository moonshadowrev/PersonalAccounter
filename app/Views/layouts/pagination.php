<?php
if ($total_pages > 1):
    // Build query string for sorting and searching
    $query_params = [];
    if (isset($sort)) $query_params['sort'] = $sort;
    if (isset($order)) $query_params['order'] = $order;
    if (isset($search) && !empty($search)) $query_params['search'] = $search;
    $query_string = http_build_query($query_params);
    $query_string = $query_string ? '?' . $query_string : '';

    $window = 2;
?>
<nav class="mt-3">
    <ul class="pagination">
        <?php if ($current_page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url; ?>/page/<?php echo $current_page - 1; ?><?php echo $query_string; ?>">Previous</a>
            </li>
        <?php endif; ?>

        <?php if ($current_page > $window + 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url; ?>/page/1<?php echo $query_string; ?>">1</a>
            </li>
            <?php if ($current_page > $window + 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = max(1, $current_page - $window); $i <= min($total_pages, $current_page + $window); $i++): ?>
            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo $base_url; ?>/page/<?php echo $i; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages - $window): ?>
            <?php if ($current_page < $total_pages - $window - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url; ?>/page/<?php echo $total_pages; ?><?php echo $query_string; ?>"><?php echo $total_pages; ?></a>
            </li>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo $base_url; ?>/page/<?php echo $current_page + 1; ?><?php echo $query_string; ?>">Next</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?> 