<?php
/**
 * Direct PDF Generation Test
 * This bypasses AJAX to isolate the problem
 */

// Load WordPress
$wp_load = dirname(__FILE__) . '/../../wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = dirname(__FILE__) . '/../../../wp-load.php';
}

if (!file_exists($wp_load)) {
    die('Cannot find wp-load.php');
}

require_once $wp_load;

// Must be network admin
if (!is_user_logged_in() || !current_user_can('manage_network')) {
    wp_die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct PDF Test</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .box { background: #f0f0f0; padding: 20px; border-radius: 5px; margin: 20px 0; }
        pre { background: white; padding: 15px; overflow-x: auto; border-left: 3px solid #0073aa; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        button { padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>🔧 Direct PDF Generation Diagnostic</h1>
    
    <div class="box">
        <h2>Test 1: Get First Order</h2>
        <pre><?php
            
// Get first order from any site
$sites = get_sites();
$test_order = null;

foreach ($sites as $site) {
    switch_to_blog($site->blog_id);
    
    if (class_exists('WooCommerce')) {
        $orders = wc_get_orders(array('limit' => 1, 'return' => 'objects'));
        
        if (!empty($orders)) {
            $order = $orders[0];
            $test_order = array(
                'id' => $order->get_id(),
                'site_id' => $site->blog_id,
                'order_number' => $order->get_order_number(),
                'customer' => $order->get_formatted_billing_full_name()
            );
            restore_current_blog();
            break;
        }
    }
    
    restore_current_blog();
}

if ($test_order) {
    echo "✓ Found test order:\n";
    echo "  Order ID: " . $test_order['id'] . "\n";
    echo "  Site ID: " . $test_order['site_id'] . "\n";
    echo "  Order #: " . $test_order['order_number'] . "\n";
    echo "  Customer: " . $test_order['customer'] . "\n";
} else {
    echo "✗ No orders found. Create an order first.";
    exit;
}

        ?></pre>
    </div>
    
    <div class="box">
        <h2>Test 2: Try PDF Generation</h2>
        <pre><?php
            
if ($test_order) {
    try {
        // Load classes
        require_once dirname(__FILE__) . '/includes/class-settings.php';
        require_once dirname(__FILE__) . '/includes/class-print-slip.php';
        
        echo "✓ Classes loaded\n";
        
        // Initialize
        $print_slip = new Network_Packing_Slip_Print();
        
        echo "✓ Print_Slip object created\n";
        
        // Check mPDF
        if (!$print_slip->is_mpdf_available()) {
            echo "✗ mPDF not available: " . $print_slip->get_mpdf_error() . "\n";
            exit;
        }
        
        echo "✓ mPDF is available\n";
        
        // Try to generate PDF
        echo "\nGenerating PDF...\n";
        
        $order_ids = array(
            array(
                'order_id' => $test_order['id'],
                'site_id' => $test_order['site_id']
            )
        );
        
        $pdf_url = $print_slip->generate_pdf($order_ids);
        
        if ($pdf_url) {
            echo "✓ PDF generated successfully!\n";
            echo "  URL: " . $pdf_url . "\n";
            echo "\n<a href=\"" . $pdf_url . "\" target=\"_blank\" style=\"color: #0073aa; text-decoration: none;\">📥 Download PDF</a>";
        } else {
            echo "✗ PDF generation failed\n";
        }
        
    } catch (Throwable $e) {
        echo "✗ ERROR: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
}
        
        ?></pre>
    </div>
    
    <div class="box">
        <h2>Debug Log Output</h2>
        <p>Check your server's error log at: <code>/wp-content/debug.log</code></p>
        <p>or run: <code>tail -30 wp-content/debug.log</code></p>
    </div>
    
</body>
</html>