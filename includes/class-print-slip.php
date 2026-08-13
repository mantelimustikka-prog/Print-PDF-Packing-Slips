<?php
/**
 * Print Slip Class
 * Handles PDF generation and order retrieval across network sites
 */

if (!defined('ABSPATH')) {
    exit;
}

class Network_Packing_Slip_Print {
    
    private $settings;
    private $mpdf_available = false;
    private $mpdf_error = '';
    
    public function __construct() {
        error_log('NPS: Print Slip Class instantiated');
        $this->settings = new Network_Packing_Slip_Settings();
        $this->check_mpdf_availability();
    }
    
    /**
     * Check if mPDF is available
     */
    private function check_mpdf_availability() {
        $plugin_dir = NETWORK_PACKING_SLIP_DIR;
        $autoload_file = $plugin_dir . 'vendor/autoload.php';
        
        error_log('NPS: Checking mPDF availability');
        error_log('NPS: Plugin dir: ' . $plugin_dir);
        error_log('NPS: Autoload file: ' . $autoload_file);
        
        if (!file_exists($autoload_file)) {
            $this->mpdf_error = 'mPDF vendor autoload not found. Run: composer install';
            error_log('NPS: ' . $this->mpdf_error);
            $this->mpdf_available = false;
            return;
        }
        
        try {
            require_once $autoload_file;
            
            if (class_exists('Mpdf\Mpdf')) {
                $this->mpdf_available = true;
                error_log('NPS: mPDF is available and ready to use');
            } else {
                $this->mpdf_error = 'Mpdf\Mpdf class not found after loading autoload';
                error_log('NPS: ' . $this->mpdf_error);
                $this->mpdf_available = false;
            }
        } catch (Exception $e) {
            $this->mpdf_error = 'Error loading mPDF: ' . $e->getMessage();
            error_log('NPS: ' . $this->mpdf_error);
            $this->mpdf_available = false;
        }
    }
    
    /**
     * Get mPDF availability status
     */
    public function is_mpdf_available() {
        return $this->mpdf_available;
    }
    
    /**
     * Get mPDF error message
     */
    public function get_mpdf_error() {
        return $this->mpdf_error;
    }
    
    /**
     * Get all network orders from all sites
     */
    public function get_network_orders($status = '') {
        error_log('NPS: get_network_orders called with status: ' . ($status ? $status : 'all'));
        
        try {
            // Get all sites in the network
            $sites = get_sites(array(
                'limit' => 999,
                'archived' => 0,
                'deleted' => 0,
                'spam' => 0
            ));
            
            error_log('NPS: Found ' . count($sites) . ' sites in network');
            
            $orders = array();
            $site_count = 0;
            $order_count = 0;
            
            // Iterate through all sites
            foreach ($sites as $site) {
                error_log('NPS: Processing site ID: ' . $site->blog_id . ', URL: ' . $site->siteurl);
                
                // Switch to the site
                switch_to_blog($site->blog_id);
                
                // Check if WooCommerce is active on this site
                if (!class_exists('WooCommerce')) {
                    error_log('NPS: WooCommerce not active on site ID: ' . $site->blog_id);
                    restore_current_blog();
                    continue;
                }
                
                $site_count++;
                
                // Build WooCommerce order query arguments
                $args = array(
                    'limit' => 999,
                    'return' => 'objects',
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                
                // Add status filter if provided
                if (!empty($status)) {
                    $args['status'] = array($status);
                }
                
                // Get orders from this site
                try {
                    $site_orders = wc_get_orders($args);
                    error_log('NPS: Retrieved ' . count($site_orders) . ' orders from site ID: ' . $site->blog_id);
                    
                    // Process each order
                    foreach ($site_orders as $order) {
                        if (is_object($order) && method_exists($order, 'get_id')) {
                            $order_id = $order->get_id();
                            $order_count++;
                            
                            $orders[] = array(
                                'id' => $order_id,
                                'site_id' => $site->blog_id,
                                'site_name' => $site->blogname,
                                'order_number' => $order->get_order_number(),
                                'customer_name' => $order->get_formatted_billing_full_name(),
                                'customer_email' => $order->get_billing_email(),
                                'status' => $order->get_status(),
                                'date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                                'total' => $order->get_formatted_order_total(),
                            );
                        }
                    }
                } catch (Exception $e) {
                    error_log('NPS: Error retrieving orders from site ID ' . $site->blog_id . ': ' . $e->getMessage());
                }
                
                // Restore to current blog
                restore_current_blog();
            }
            
            // Sort all orders by date (newest first)
            usort($orders, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            error_log('NPS: Total - Sites: ' . $site_count . ', Orders: ' . $order_count . ', Returned: ' . count($orders));
            
            return $orders;
            
        } catch (Exception $e) {
            error_log('NPS: Error in get_network_orders: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Generate PDF with 2 packing slips per page (Portrait orientation)
     */
    public function generate_pdf($order_ids) {
        error_log('NPS: generate_pdf called with ' . count($order_ids) . ' orders');
        
        // Check if mPDF is available
        if (!$this->mpdf_available) {
            error_log('NPS: mPDF not available - ' . $this->mpdf_error);
            return false;
        }
        
        try {
            error_log('NPS: Creating mPDF instance');
            
            // Get empty space setting (convert cm to mm)
            $empty_space_cm = $this->settings->get_empty_space_after_slip();
            $empty_space_mm = $empty_space_cm * 10; // 1cm = 10mm
            $page_content_height_mm = 281;
            $slip_height_mm = max(20, ($page_content_height_mm - $empty_space_mm) / 2);
            $page_content_height_css = number_format($page_content_height_mm, 2, '.', '');
            $slip_height_css = number_format($slip_height_mm, 2, '.', '');
            error_log('NPS: Empty space after slip: ' . $empty_space_cm . 'cm (' . $empty_space_mm . 'mm)');
            
            // Prepare temp directory
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/mpdf-temp';
            if (!is_dir($temp_dir)) {
                wp_mkdir_p($temp_dir);
                error_log('NPS: Created temp directory: ' . $temp_dir);
            }
            
            // Create mPDF instance - PORTRAIT orientation (P)
            $mpdf = new \Mpdf\Mpdf(array(
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 8,
                'margin_bottom' => 8,
                'tempDir' => $temp_dir,
                'default_font' => 'Arial'
            ));
            
            error_log('NPS: mPDF instance created successfully - PORTRAIT orientation');
            
            // Define CSS for mPDF
            $css = '<style>
            * {
                box-sizing: border-box;
            }
            html, body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
            .page {
                width: 100%;
                height: ' . $page_content_height_css . 'mm;
                page-break-after: always;
                page-break-inside: avoid;
                margin: 0;
                padding: 0;
                overflow: hidden;
            }
            .page:last-of-type {
                page-break-after: avoid;
            }
            .slip-wrapper {
                width: 100%;
                height: ' . $slip_height_css . 'mm;
                margin: 0;
                padding: 0;
                page-break-inside: avoid;
                overflow: hidden;
            }
            .slip {
                width: 100%;
                height: 100%;
                padding: 5mm;
                margin: 0;
                overflow: hidden;
            }
            
            /* Header table with Logo (30%) and Sender (70%) */
            .header-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 5mm;
                page-break-inside: avoid;
            }
            
            .header-table td {
                padding: 0;
                vertical-align: top;
                border: none;
            }
            
            .logo-cell {
                width: 30%;
                text-align: left;
                padding-right: 3mm;
            }
            
            .logo-section img {
                object-fit: contain;
                max-width: 100%;
                height: auto;
            }
            
            .sender-cell {
                width: 70%;
                border: 1px solid #000;
                padding: 3mm;
            }
            
            .sender-content {
                font-size: 10px;
                line-height: 1.3;
            }
            
            .sender-content p {
                margin: 0 0 2mm 0;
                padding: 0;
            }
            
            .sender-content strong, .sender-content b {
                font-weight: bold;
            }
            
            .sender-content em, .sender-content i {
                font-style: italic;
            }
            
            .sender-content u {
                text-decoration: underline;
            }
            
            .sender-content ul, .sender-content ol {
                margin: 1mm 0 2mm 8mm;
                padding: 0;
            }
            
            .sender-content li {
                margin: 0.5mm 0;
            }
            
            /* Recipient section - full width below */
            .address-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 5mm;
                page-break-inside: avoid;
            }
            .address-table td {
                border: 1px solid #000;
                padding: 3mm;
                vertical-align: top;
                font-size: 10px;
                line-height: 1.3;
            }
            .address-recipient {
                width: 100%;
            }
            .address-content {
                font-size: 10px;
                line-height: 1.3;
            }
            .address-content p {
                margin: 0 0 2mm 0;
                padding: 0;
            }
            .address-content strong, .address-content b {
                font-weight: bold;
            }
            .address-content em, .address-content i {
                font-style: italic;
            }
            .address-content u {
                text-decoration: underline;
            }
            .address-content ul, .address-content ol {
                margin: 1mm 0 2mm 8mm;
                padding: 0;
            }
            .address-content li {
                margin: 0.5mm 0;
            }
            
            .separator-line {
                border-top: 2px solid #000;
                margin: 3mm 0;
                height: 0;
            }
            
            /* Custom section */
            .custom-section {
                font-size: 10px;
                line-height: 1.4;
                overflow: hidden;
            }
            .custom-section p {
                margin: 0 0 2mm 0;
                padding: 0;
            }
            .custom-section ul, .custom-section ol {
                margin: 1mm 0 2mm 12mm;
                padding: 0;
            }
            .custom-section li {
                margin: 0.5mm 0;
            }
            .custom-section strong, .custom-section b {
                font-weight: bold;
            }
            .custom-section em, .custom-section i {
                font-style: italic;
            }
            .custom-section u {
                text-decoration: underline;
            }
            .custom-section h1, .custom-section h2, .custom-section h3, .custom-section h4, .custom-section h5, .custom-section h6 {
                margin: 2mm 0 1mm 0;
                font-weight: bold;
            }
            .custom-section h1 { font-size: 14px; }
            .custom-section h2 { font-size: 12px; }
            .custom-section h3 { font-size: 11px; }
            .custom-section h4 { font-size: 11px; }
            .custom-section h5 { font-size: 10px; }
            .custom-section h6 { font-size: 10px; }
            .custom-section a {
                color: #0000FF;
                text-decoration: underline;
            }
            .custom-section table {
                width: 100%;
                border-collapse: collapse;
                margin: 2mm 0;
            }
            .custom-section table td, .custom-section table th {
                border: 1px solid #999;
                padding: 2mm;
                font-size: 10px;
            }
            .custom-section table th {
                background: #f0f0f0;
                font-weight: bold;
            }
            .custom-section br {
                line-height: 100%;
            }
            
            /* Products section - at the bottom */
            .products-section {
                font-size: 11px;
                font-weight: bold;
                margin: 3mm 0 0 0;
                padding-top: 3mm;
                border-top: 1px solid #ccc;
                word-wrap: break-word;
                overflow-wrap: break-word;
                overflow: hidden;
            }
            
            p {
                margin: 0;
                padding: 0;
            }
            </style>';
            
            // Start HTML
            $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
' . $css . '
</head>
<body>';
            
            $slip_count = 0;
            $page_slip_count = 0;
            $total_orders = count($order_ids);
            
            error_log('NPS: Total orders to process: ' . $total_orders);
            
            // Process each order
            foreach ($order_ids as $index => $order_data) {
                if (!is_array($order_data)) {
                    error_log('NPS: Skipping invalid order data (not an array)');
                    continue;
                }
                
                $order_id = isset($order_data['order_id']) ? intval($order_data['order_id']) : intval($order_data['id']);
                $site_id = isset($order_data['site_id']) ? intval($order_data['site_id']) : 1;
                
                error_log('NPS: Processing order ID: ' . $order_id . ' from site ID: ' . $site_id);
                
                // Open new page div every 2 slips
                if ($page_slip_count === 0) {
                    $html .= '<div class="page">';
                }
                
                // Switch to the correct site
                switch_to_blog($site_id);
                
                try {
                    $order = wc_get_order($order_id);
                    
                    if ($order) {
                        error_log('NPS: Order found - ' . $order->get_order_number());
                        
                        $slip_html = $this->generate_single_slip_html($order);
                        
                        $wrapper_style = ' style="height: ' . $slip_height_css . 'mm;';
                        
                        if ($page_slip_count === 0 && $empty_space_mm > 0) {
                            $wrapper_style .= ' margin-bottom: ' . $empty_space_mm . 'mm;';
                        }
                        
                        $wrapper_style .= '"';
                        
                        $html .= '<div class="slip-wrapper"' . $wrapper_style . '>';
                        $html .= '<div class="slip">';
                        $html .= $slip_html;
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        $slip_count++;
                        $page_slip_count++;
                        
                        // Close page div after 2 slips
                        if ($page_slip_count === 2) {
                            $html .= '</div>';
                            $page_slip_count = 0;
                        }
                    } else {
                        error_log('NPS: Order not found - ID: ' . $order_id . ' on site: ' . $site_id);
                    }
                } catch (Exception $e) {
                    error_log('NPS: Error processing order: ' . $e->getMessage());
                }
                
                // Restore to network admin
                restore_current_blog();
            }
            
            // Close last page if there are remaining slips
            if ($page_slip_count > 0 && $page_slip_count < 2) {
                $html .= '</div>';
            }
            
            // Strict closing tags
            $html .= '</body>
</html>';
            
            error_log('NPS: HTML prepared with ' . $slip_count . ' slips');
            
            // Write HTML to mPDF
            $mpdf->WriteHTML($html);
            error_log('NPS: HTML written to mPDF');
            
            // Create packing slips directory
            $packing_slips_dir = $upload_dir['basedir'] . '/packing-slips';
            if (!is_dir($packing_slips_dir)) {
                wp_mkdir_p($packing_slips_dir);
                error_log('NPS: Created packing-slips directory');
            }
            
            // Check if directory is writable
            if (!is_writable($packing_slips_dir)) {
                error_log('NPS: ERROR - packing-slips directory is not writable: ' . $packing_slips_dir);
                return false;
            }
            
            // Generate filename and save PDF
            $filename = 'packing-slips-' . time() . '.pdf';
            $file_path = $packing_slips_dir . '/' . $filename;
            
            error_log('NPS: Attempting to write PDF to: ' . $file_path);
            
            $mpdf->Output($file_path, 'F');
            
            // Verify file was created
            if (!file_exists($file_path)) {
                error_log('NPS: ERROR - PDF file was not created at: ' . $file_path);
                return false;
            }
            
            $file_size = filesize($file_path);
            error_log('NPS: PDF file created successfully, size: ' . $file_size . ' bytes');
            
            $pdf_url = $upload_dir['baseurl'] . '/packing-slips/' . $filename;
            
            error_log('NPS: PDF URL: ' . $pdf_url);
            
            return $pdf_url;
            
        } catch (Exception $e) {
            error_log('NPS: CRITICAL ERROR in generate_pdf');
            error_log('NPS: Message: ' . $e->getMessage());
            error_log('NPS: File: ' . $e->getFile());
            error_log('NPS: Line: ' . $e->getLine());
            return false;
        }
    }
    
    /**
     * Generate single slip HTML with SKU line moved to bottom
     */
    private function generate_single_slip_html($order) {
        $logo_url = $this->settings->get_logo_url();
        $logo_size = $this->settings->get_logo_size();
        $logo_width = isset($logo_size['width']) ? intval($logo_size['width']) : 50;
        $logo_height = isset($logo_size['height']) ? intval($logo_size['height']) : 50;
        
        $company_info = $this->settings->get_company_info();
        $recipient_template = $this->settings->get_recipient_address();
        $custom_html = $this->settings->get_custom_html();
        
        // Replace variables
        $recipient_address = $this->replace_order_variables($recipient_template, $order);
        $custom_html = $this->replace_order_variables($custom_html, $order);
        
        // Get products
        $products_line = $this->get_products_line($order);
        
        // Escape products line (plain text)
        $products_line = htmlspecialchars($products_line, ENT_QUOTES, 'UTF-8');
        
        // Define allowed HTML tags for all content boxes
        $allowed_html = array(
            'p' => array('style' => array(), 'class' => array()),
            'div' => array('style' => array(), 'class' => array()),
            'span' => array('style' => array(), 'class' => array()),
            'br' => array(),
            'hr' => array('style' => array()),
            'strong' => array('style' => array()),
            'b' => array('style' => array()),
            'em' => array('style' => array()),
            'i' => array('style' => array()),
            'u' => array('style' => array()),
            'del' => array('style' => array()),
            's' => array('style' => array()),
            'h1' => array('style' => array(), 'class' => array()),
            'h2' => array('style' => array(), 'class' => array()),
            'h3' => array('style' => array(), 'class' => array()),
            'h4' => array('style' => array(), 'class' => array()),
            'h5' => array('style' => array(), 'class' => array()),
            'h6' => array('style' => array(), 'class' => array()),
            'ul' => array('style' => array()),
            'ol' => array('style' => array(), 'start' => array()),
            'li' => array('style' => array()),
            'a' => array('href' => array(), 'style' => array(), 'target' => array(), 'rel' => array()),
            'table' => array('style' => array(), 'border' => array(), 'cellpadding' => array(), 'cellspacing' => array()),
            'tr' => array('style' => array()),
            'td' => array('style' => array(), 'colspan' => array(), 'rowspan' => array()),
            'th' => array('style' => array(), 'colspan' => array(), 'rowspan' => array()),
            'thead' => array(),
            'tbody' => array(),
            'tfoot' => array(),
            'blockquote' => array('style' => array()),
            'pre' => array('style' => array()),
            'code' => array('style' => array()),
        );
        
        // Process all HTML content with shortcodes and sanitize
        $company_info = do_shortcode($company_info);
        $company_info = wp_kses($company_info, $allowed_html);
        
        $recipient_address = do_shortcode($recipient_address);
        $recipient_address = wp_kses($recipient_address, $allowed_html);
        
        $custom_html = do_shortcode($custom_html);
        $custom_html = wp_kses($custom_html, $allowed_html);
        
        $html = '';
        
        // Header table: Logo (30%) and Sender (70%) side by side - NO HEADERS PRINTED
        $html .= '<table class="header-table" cellpadding="0" cellspacing="0">';
        $html .= '<tr>';
        
        // Logo cell (30% width)
        $html .= '<td class="logo-cell">';
        if ($logo_url) {
            $html .= '<div class="logo-section">';
            $html .= '<img src="' . esc_url($logo_url) . '" alt="Logo" style="width: ' . $logo_width . 'mm; height: ' . $logo_height . 'mm;" />';
            $html .= '</div>';
        }
        $html .= '</td>';
        
        // Sender cell (70% width) - NO "SENDER:" HEADER PRINTED
        $html .= '<td class="sender-cell">';
        $html .= '<div class="sender-content">';
        $html .= $company_info;
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '</tr>';
        $html .= '</table>';
        
        // Recipient Address table - full width below - NO "RECIPIENT - Delivery Address:" HEADER PRINTED
        $html .= '<table class="address-table" cellpadding="3" cellspacing="0">';
        $html .= '<tr>';
        
        $html .= '<td class="address-recipient">';
        $html .= '<div class="address-content">';
        $html .= $recipient_address;
        $html .= '</div>';
        $html .= '</td>';
        
        $html .= '</tr>';
        $html .= '</table>';
        
        // Separator line
        $html .= '<div class="separator-line"></div>';
        
        // Custom HTML content - Packing Slip Bottom (takes available space)
        $html .= '<div class="custom-section">';
        $html .= $custom_html;
        $html .= '</div>';
        
        // Products line - MOVED TO BOTTOM AS LAST ROW
        $html .= '<div class="products-section">';
        $html .= nl2br($products_line);
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get products line formatted as SKU(Quantity)
     */
    private function get_products_line($order) {
        $items = $order->get_items();
        $products = array();
        
        foreach ($items as $item) {
            $product = $item->get_product();
            $sku = $product ? $product->get_sku() : 'N/A';
            $qty = $item->get_quantity();
            $products[] = $sku . '(' . $qty . ')';
        }
        
        if (empty($products)) {
            return 'No products';
        }
        
        return implode(' / ', $products);
    }
    
    /**
     * Replace order variables
     */
    private function replace_order_variables($template, $order) {
        $replacements = array(
            // Customer/Billing Variables
            '{customer_first_name}' => $order->get_billing_first_name(),
            '{customer_last_name}' => $order->get_billing_last_name(),
            '{customer_email}' => $order->get_billing_email(),
            '{customer_phone}' => $order->get_billing_phone(),
            '{billing_first_name}' => $order->get_billing_first_name(),
            '{billing_last_name}' => $order->get_billing_last_name(),
            '{billing_company}' => $order->get_billing_company(),
            '{billing_address_1}' => $order->get_billing_address_1(),
            '{billing_address_2}' => $order->get_billing_address_2(),
            '{billing_city}' => $order->get_billing_city(),
            '{billing_postcode}' => $order->get_billing_postcode(),
            '{billing_state}' => $order->get_billing_state(),
            '{billing_country}' => $order->get_billing_country(),
            '{billing_country_name}' => $this->get_country_name($order->get_billing_country()),
            '{billing_email}' => $order->get_billing_email(),
            '{billing_phone}' => $order->get_billing_phone(),
            
            // Shipping Variables
            '{shipping_first_name}' => $order->get_shipping_first_name(),
            '{shipping_last_name}' => $order->get_shipping_last_name(),
            '{shipping_company}' => $order->get_shipping_company(),
            '{shipping_address_1}' => $order->get_shipping_address_1(),
            '{shipping_address_2}' => $order->get_shipping_address_2(),
            '{shipping_city}' => $order->get_shipping_city(),
            '{shipping_postcode}' => $order->get_shipping_postcode(),
            '{shipping_state}' => $order->get_shipping_state(),
            '{shipping_country}' => $order->get_shipping_country(),
            '{shipping_country_name}' => $this->get_country_name($order->get_shipping_country()),
            '{shipping_email}' => $order->get_billing_email(),
            '{shipping_phone}' => $order->get_billing_phone(),
            '{shipping_method}' => $this->get_shipping_method($order),
            
            // Order Variables
            '{order_number}' => $order->get_order_number(),
            '{order_date}' => $order->get_date_created()->format('Y-m-d H:i:s'),
            '{order_total}' => $order->get_formatted_order_total(),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Convert country code to country name
     */
    private function get_country_name($country_code) {
        // Get WooCommerce countries
        $countries = WC()->countries->get_countries();
        
        // Return country name if exists, otherwise return the code
        if (isset($countries[$country_code])) {
            return $countries[$country_code];
        }
        
        return $country_code;
    }
    
    /**
     * Get shipping method
     */
    private function get_shipping_method($order) {
        $shipping_methods = $order->get_shipping_methods();
        if (!empty($shipping_methods)) {
            $shipping_method = array_shift($shipping_methods);
            return $shipping_method->get_method_title();
        }
        return 'N/A';
    }
}
?>