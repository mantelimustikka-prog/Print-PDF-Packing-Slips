<?php
if (!defined('ABSPATH')) {
    exit;
}

$print_slip = new Network_Packing_Slip_Print();

// Get pagination parameters
$current_page = isset($_GET['nps_page']) ? max(1, intval($_GET['nps_page'])) : 1;
$per_page = isset($_GET['nps_per_page']) ? intval($_GET['nps_per_page']) : 20;
$status_filter = isset($_GET['nps_status']) ? sanitize_text_field($_GET['nps_status']) : '';

// Validate per_page value
$allowed_per_page = array(20, 30, 40, 50, 60);
if (!in_array($per_page, $allowed_per_page)) {
    $per_page = 20;
}

// Get order status for WooCommerce
$order_statuses = wc_get_order_statuses();

// Get all orders (without pagination first to get total count)
$all_orders = $print_slip->get_network_orders($status_filter);
$total_orders = count($all_orders);

// Calculate pagination
$total_pages = ceil($total_orders / $per_page);
$current_page = min($current_page, max(1, $total_pages));

// Get orders for current page
$offset = ($current_page - 1) * $per_page;
$paginated_orders = array_slice($all_orders, $offset, $per_page);

// Build current URL for pagination
$current_url = admin_url('admin.php?page=network-packing-slip');
?>

<div class="wrap">
    <h1><?php esc_html_e('Print PDF Packing Slips', 'network-packing-slip'); ?></h1>
    
    <div class="nps-print-container">
        
        <!-- Filter Section -->
        <div class="nps-filter-section">
            <h2><?php esc_html_e('Filter Orders', 'network-packing-slip'); ?></h2>
            
            <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label for="nps-order-status"><?php esc_html_e('Order Status', 'network-packing-slip'); ?></label>
                    <select id="nps-order-status" name="nps_status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php esc_html_e('All Statuses', 'network-packing-slip'); ?></option>
                        <?php foreach ($order_statuses as $status => $label): 
                            $status = str_replace('wc-', '', $status);
                            $selected = ($status_filter === $status) ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php echo esc_attr($selected); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="button" class="button button-primary" id="nps-filter-orders">
                    <?php esc_html_e('Filter Orders', 'network-packing-slip'); ?>
                </button>
                
                <button type="button" class="button button-success" id="nps-print-pdf" style="background-color: #28a745; color: white; border-color: #28a745;">
                    <?php esc_html_e('Print PDF Packing Slips', 'network-packing-slip'); ?>
                </button>
            </div>
        </div>
        
        <!-- Orders Section with Pagination -->
        <div class="nps-orders-section" style="margin-top: 30px;">
            <h2><?php esc_html_e('Orders', 'network-packing-slip'); ?></h2>
            
            <!-- Per Page Selector and Info -->
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="nps-per-page-select" style="margin-bottom: 0; font-weight: 600;">
                        <?php esc_html_e('Orders per page:', 'network-packing-slip'); ?>
                    </label>
                    <select id="nps-per-page-select" name="nps_per_page" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: auto;">
                        <?php foreach ($allowed_per_page as $value): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($per_page, $value); ?>>
                                <?php echo esc_html($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <span style="color: #666; font-size: 14px;">
                    <?php 
                    if ($total_orders > 0) {
                        printf(
                            esc_html__('Showing %1$d to %2$d of %3$d orders', 'network-packing-slip'),
                            $offset + 1,
                            min($offset + $per_page, $total_orders),
                            $total_orders
                        );
                    } else {
                        esc_html_e('No orders found', 'network-packing-slip');
                    }
                    ?>
                </span>
            </div>
            
            <!-- Orders Table -->
            <div class="nps-orders-table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="nps-select-all" title="<?php esc_attr_e('Select all orders on this page', 'network-packing-slip'); ?>" />
                            </th>
                            <th style="width: 80px;"><?php esc_html_e('Order #', 'network-packing-slip'); ?></th>
                            <th><?php esc_html_e('Customer', 'network-packing-slip'); ?></th>
                            <th style="width: 130px;"><?php esc_html_e('Status', 'network-packing-slip'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Date', 'network-packing-slip'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Total', 'network-packing-slip'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="nps-orders-list">
                        <?php if (!empty($paginated_orders)): ?>
                            <?php foreach ($paginated_orders as $order): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="nps-order-checkbox" value="<?php echo esc_attr(base64_encode(json_encode($order))); ?>" title="<?php esc_attr_e('Select this order', 'network-packing-slip'); ?>" />
                                    </td>
                                    <td><strong><?php echo esc_html($order['order_number']); ?></strong></td>
                                    <td><?php echo esc_html($order['customer_name']); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo esc_attr($order['status']); ?>">
                                            <?php echo esc_html(wc_get_order_status_name($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($order['date']); ?></td>
                                    <td><?php echo wp_kses_post($order['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 20px;">
                                    <p style="margin: 0; color: #666; font-size: 14px;">
                                        <?php esc_html_e('No orders found.', 'network-packing-slip'); ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="nps-pagination" style="margin-top: 30px; display: flex; align-items: center; gap: 5px; justify-content: center; flex-wrap: wrap;">
                    
                    <?php
                    // Build pagination URLs
                    $first_page_url = add_query_arg(array('nps_page' => 1, 'nps_per_page' => $per_page, 'nps_status' => $status_filter), $current_url);
                    $prev_page_url = add_query_arg(array('nps_page' => $current_page - 1, 'nps_per_page' => $per_page, 'nps_status' => $status_filter), $current_url);
                    $next_page_url = add_query_arg(array('nps_page' => $current_page + 1, 'nps_per_page' => $per_page, 'nps_status' => $status_filter), $current_url);
                    $last_page_url = add_query_arg(array('nps_page' => $total_pages, 'nps_per_page' => $per_page, 'nps_status' => $status_filter), $current_url);
                    ?>
                    
                    <!-- First Page Button -->
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo esc_url($first_page_url); ?>" class="button" title="<?php esc_attr_e('Go to first page', 'network-packing-slip'); ?>">
                            <span aria-hidden="true">«</span> <?php esc_html_e('First', 'network-packing-slip'); ?>
                        </a>
                    <?php else: ?>
                        <button class="button" disabled title="<?php esc_attr_e('Already on first page', 'network-packing-slip'); ?>">
                            <span aria-hidden="true">«</span> <?php esc_html_e('First', 'network-packing-slip'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <!-- Previous Page Button -->
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo esc_url($prev_page_url); ?>" class="button" title="<?php esc_attr_e('Go to previous page', 'network-packing-slip'); ?>">
                            <span aria-hidden="true">‹</span> <?php esc_html_e('Prev', 'network-packing-slip'); ?>
                        </a>
                    <?php else: ?>
                        <button class="button" disabled title="<?php esc_attr_e('Already on first page', 'network-packing-slip'); ?>">
                            <span aria-hidden="true">‹</span> <?php esc_html_e('Prev', 'network-packing-slip'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <span class="nps-page-info" style="margin: 0 10px; font-weight: 600; color: #333; min-width: 100px; text-align: center;">
                        <?php printf(
                            esc_html__('Page %1$d of %2$d', 'network-packing-slip'),
                            $current_page,
                            $total_pages
                        ); ?>
                    </span>
                    
                    <!-- Next Page Button -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo esc_url($next_page_url); ?>" class="button" title="<?php esc_attr_e('Go to next page', 'network-packing-slip'); ?>">
                            <?php esc_html_e('Next', 'network-packing-slip'); ?> <span aria-hidden="true">›</span>
                        </a>
                    <?php else: ?>
                        <button class="button" disabled title="<?php esc_attr_e('Already on last page', 'network-packing-slip'); ?>">
                            <?php esc_html_e('Next', 'network-packing-slip'); ?> <span aria-hidden="true">›</span>
                        </button>
                    <?php endif; ?>
                    
                    <!-- Last Page Button -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo esc_url($last_page_url); ?>" class="button" title="<?php esc_attr_e('Go to last page', 'network-packing-slip'); ?>">
                            <?php esc_html_e('Last', 'network-packing-slip'); ?> <span aria-hidden="true">»</span>
                        </a>
                    <?php else: ?>
                        <button class="button" disabled title="<?php esc_attr_e('Already on last page', 'network-packing-slip'); ?>">
                            <?php esc_html_e('Last', 'network-packing-slip'); ?> <span aria-hidden="true">»</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
    .nps-print-container {
        max-width: 1400px;
        margin: 20px 0;
    }
    
    .nps-filter-section {
        background: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        margin-bottom: 30px;
    }
    
    .nps-filter-section h2 {
        margin-top: 0;
        color: #23282d;
        font-size: 18px;
    }
    
    .nps-orders-section {
        background: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    }
    
    .nps-orders-section h2 {
        margin-top: 0;
        color: #23282d;
        font-size: 18px;
    }
    
    .nps-orders-table-wrapper {
        overflow-x: auto;
        margin: 20px 0;
    }
    
    .wp-list-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .wp-list-table thead {
        background-color: #f9f9f9;
        border-bottom: 2px solid #ddd;
    }
    
    .wp-list-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #23282d;
        border: 1px solid #ddd;
    }
    
    .wp-list-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .wp-list-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .order-status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }
    
    .status-completed {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    
    .status-on-hold {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    
    .status-processing {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .status-failed {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .status-refunded {
        background-color: #e7e7e7;
        color: #333;
        border: 1px solid #ccc;
    }
    
    /* Button styles */
    .button {
        display: inline-block;
        padding: 8px 16px;
        background-color: #f1f1f1;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
        line-height: 1.4;
    }
    
    .button:hover:not(:disabled) {
        background-color: #e5e5e5;
        border-color: #999;
    }
    
    .button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .button-primary {
        background-color: #0073aa;
        border-color: #0073aa;
        color: #fff;
    }
    
    .button-primary:hover:not(:disabled) {
        background-color: #005a87;
        border-color: #005a87;
    }
    
    .button-success {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
    }
    
    .button-success:hover:not(:disabled) {
        background-color: #218838;
        border-color: #218838;
    }
    
    /* Checkbox styles */
    input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
        margin: 0;
    }
    
    input[type="checkbox"]:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
    
    /* Pagination styles */
    .nps-pagination {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .nps-pagination .button {
        margin: 5px;
    }
    
    /* Responsive styles */
    @media (max-width: 768px) {
        .nps-filter-section {
            padding: 15px;
        }
        
        .nps-orders-section {
            padding: 15px;
        }
        
        .wp-list-table th,
        .wp-list-table td {
            padding: 8px;
            font-size: 12px;
        }
        
        .order-status {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .button {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .nps-pagination {
            padding: 10px;
        }
        
        .nps-pagination .button {
            margin: 2px;
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .nps-page-info {
            min-width: 80px !important;
            font-size: 12px;
        }
        
        #nps-per-page-select {
            font-size: 12px;
            padding: 6px;
        }
    }
    
    @media (max-width: 480px) {
        .nps-filter-section > div {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .nps-filter-section > div > div {
            width: 100% !important;
        }
        
        .nps-filter-section > div > button {
            width: 100%;
        }
        
        .nps-orders-table-wrapper {
            font-size: 12px;
        }
        
        .wp-list-table th,
        .wp-list-table td {
            padding: 6px;
        }
        
        .nps-pagination {
            justify-content: center;
            gap: 2px;
        }
        
        .nps-pagination .button {
            padding: 4px 8px;
            font-size: 11px;
            margin: 2px;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    
    // Select all orders on current page
    $('#nps-select-all').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.nps-order-checkbox').prop('checked', isChecked);
    });
    
    // Uncheck "Select All" if any order is unchecked
    $(document).on('change', '.nps-order-checkbox', function() {
        var totalCheckboxes = $('.nps-order-checkbox').length;
        var checkedCheckboxes = $('.nps-order-checkbox:checked').length;
        
        if (totalCheckboxes === checkedCheckboxes) {
            $('#nps-select-all').prop('checked', true);
        } else {
            $('#nps-select-all').prop('checked', false);
        }
    });
    
    // Change items per page
    $('#nps-per-page-select').on('change', function() {
        var perPage = $(this).val();
        var currentUrl = window.location.href;
        var url = new URL(currentUrl);
        url.searchParams.set('nps_per_page', perPage);
        url.searchParams.set('nps_page', 1); // Reset to page 1
        window.location.href = url.toString();
    });
    
    // Filter orders by status
    $('#nps-filter-orders').on('click', function(e) {
        e.preventDefault();
        var status = $('#nps-order-status').val();
        var currentUrl = window.location.href;
        var url = new URL(currentUrl);
        url.searchParams.set('nps_status', status);
        url.searchParams.set('nps_page', 1); // Reset to page 1
        window.location.href = url.toString();
    });
    
    // Print PDF
    $('#nps-print-pdf').on('click', function(e) {
        e.preventDefault();
        console.log('Print PDF clicked');
        
        var selectedOrders = [];
        $('.nps-order-checkbox:checked').each(function() {
            try {
                var data = $(this).val();
                if (data) {
                    // Decode base64 data
                    var decoded = atob(data);
                    selectedOrders.push(JSON.parse(decoded));
                }
            } catch(e) {
                console.error('Error parsing order data:', e);
            }
        });
        
        console.log('Selected orders:', selectedOrders);
        
        if (selectedOrders.length === 0) {
            alert('<?php esc_html_e('Please select at least one order', 'network-packing-slip'); ?>');
            return false;
        }
        
        $.ajax({
            url: NPSData.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'nps_print_pdf',
                nonce: NPSData.nonce,
                order_ids: selectedOrders
            },
            beforeSend: function() {
                $('#nps-print-pdf').prop('disabled', true).text('<?php esc_html_e('Generating PDF...', 'network-packing-slip'); ?>');
            },
            success: function(response) {
                console.log('PDF response:', response);
                
                if (response.success) {
                    showMessage('✓ <?php esc_html_e('PDF generated successfully!', 'network-packing-slip'); ?>', 'success');
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    showMessage('✗ <?php esc_html_e('Error:', 'network-packing-slip'); ?> ' + (response.data ? response.data.message : '<?php esc_html_e('Unknown error', 'network-packing-slip'); ?>'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                showMessage('✗ <?php esc_html_e('Error generating PDF:', 'network-packing-slip'); ?> ' + error, 'error');
            },
            complete: function() {
                $('#nps-print-pdf').prop('disabled', false).text('<?php esc_html_e('Print PDF Packing Slips', 'network-packing-slip'); ?>');
            }
        });
        
        return false;
    });
    
    // Helper function to show messages
    function showMessage(message, type) {
        var messageHtml = '<div class="notice notice-' + type + ' is-dismissible" style="margin: 20px 0;"><p>' + message + '</p></div>';
        $('.nps-filter-section').after(messageHtml);
        
        setTimeout(function() {
            $('.notice').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }
});
</script>