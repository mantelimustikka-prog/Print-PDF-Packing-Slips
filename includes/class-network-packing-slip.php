<?php
/**
 * Main Plugin Class
 * Handles plugin initialization, admin menus, and AJAX requests
 */

if (!defined('ABSPATH')) {
    exit;
}

class Network_Packing_Slip {
    
    private static $instance = null;
    private $settings = null;
    private $print_slip = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('NPS: Network_Packing_Slip class instantiated');
        
        // Load settings class
        $this->settings = new Network_Packing_Slip_Settings();
        error_log('NPS: Settings class loaded');
        
        // Load print class
        $this->print_slip = new Network_Packing_Slip_Print();
        error_log('NPS: Print class loaded');
        
        // Add network admin menus
        add_action('network_admin_menu', array($this, 'add_network_admin_menus'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Handle AJAX requests
        add_action('wp_ajax_nps_select_logo', array($this, 'ajax_select_logo'));
        add_action('wp_ajax_nps_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_nps_filter_orders', array($this, 'ajax_filter_orders'));
        add_action('wp_ajax_nps_print_pdf', array($this, 'ajax_print_pdf'));
        
        error_log('NPS: All hooks registered');
    }
    
    /**
     * Add network admin menus
     */
    public function add_network_admin_menus() {
        error_log('NPS: Adding network admin menus');
        
        // Menu configuration
        $menu_name = 'Print PDF Packing Slips'; // Change this to your desired menu name
        $page_title = 'Print PDF Packing Slips'; // Change this to your desired page title
        
        // ICON OPTIONS (choose one):
        // Option 1: Built-in Dashicon (recommended)
        $menu_icon = 'dashicons-printer'; // PDF icon
        
        // Option 2: Other useful Dashicons:
        // $menu_icon = 'dashicons-media-document'; // Document icon
        // $menu_icon = 'dashicons-images-alt'; // Image icon
        // $menu_icon = 'dashicons-printer'; // Printer icon
        // $menu_icon = 'dashicons-format-aside'; // Document icon
        // $menu_icon = 'dashicons-text-page'; // Text page icon
        
        // Option 3: Custom icon URL (use your own image)
        // $menu_icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6z"/></svg>');
        
        // Option 4: Custom URL to image file
        // $menu_icon = NETWORK_PACKING_SLIP_URL . 'assets/images/icon.png';
        
        // Main menu - Print Packing Slips
        add_menu_page(
            $page_title,
            $menu_name,
            'manage_network',
            'network-packing-slip',
            array($this, 'render_print_page'),
            $menu_icon, // The menu icon
            80 // Position in menu
        );
        
        // Settings submenu
        add_submenu_page(
            'network-packing-slip',
            __('PDF Packing Slips Settings', 'print-pdf-packing-slips'),
            __('Settings', 'print-pdf-packing-slips'),
            'manage_network',
            'network-packing-slip-settings',
            array($this, 'render_settings_page')
        );
        
        error_log('NPS: Network admin menus added');
    }
    
    /**
     * Render print/filter page
     */
    public function render_print_page() {
        if (!current_user_can('manage_network')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'print-pdf-packing-slips'));
        }
        include NETWORK_PACKING_SLIP_INCLUDES . 'templates/print-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_network')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'print-pdf-packing-slips'));
        }
        include NETWORK_PACKING_SLIP_INCLUDES . 'templates/settings-page.php';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        error_log('NPS: enqueue_admin_assets called for hook: ' . $hook);
        
        // Only enqueue on our plugin pages
        if (strpos($hook, 'network-packing-slip') === false) {
            error_log('NPS: Not a network-packing-slip page, skipping enqueue');
            return;
        }
        
        error_log('NPS: Enqueueing assets');
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        error_log('NPS: Media uploader enqueued');
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Enqueue WordPress admin styles
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('colors');
        wp_enqueue_style('media');
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'network-packing-slip-admin',
            NETWORK_PACKING_SLIP_URL . 'assets/css/admin.css',
            array('wp-admin'),
            NETWORK_PACKING_SLIP_VERSION
        );
        error_log('NPS: Admin CSS enqueued');
        
        // Enqueue custom admin scripts
        wp_enqueue_script(
            'network-packing-slip-admin',
            NETWORK_PACKING_SLIP_URL . 'assets/js/admin.js',
            array('jquery'),
            NETWORK_PACKING_SLIP_VERSION,
            true
        );
        error_log('NPS: Admin JS enqueued');
        
        // Localize script with AJAX data
        $localization = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('network-packing-slip-nonce'),
            'pluginUrl' => NETWORK_PACKING_SLIP_URL
        );
        
        wp_localize_script('network-packing-slip-admin', 'NPSData', $localization);
        error_log('NPS: Script localized with data');
    }
    
    /**
     * AJAX: Select Logo
     */
    public function ajax_select_logo() {
        error_log('NPS AJAX: select_logo called');
        
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            error_log('NPS AJAX: No nonce in POST');
            wp_send_json_error(array('message' => 'No nonce provided'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'network-packing-slip-nonce')) {
            error_log('NPS AJAX: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed - nonce invalid'));
        }
        
        error_log('NPS AJAX: Nonce verified');
        
        // Check capabilities
        if (!current_user_can('manage_network')) {
            error_log('NPS AJAX: User does not have manage_network capability');
            wp_send_json_error(array('message' => 'Permission denied - manage_network capability required'));
        }
        
        error_log('NPS AJAX: User has manage_network capability');
        
        // Validate and sanitize input
        $logo_id = isset($_POST['logo_id']) ? intval($_POST['logo_id']) : 0;
        $logo_size_json = isset($_POST['logo_size']) ? sanitize_text_field($_POST['logo_size']) : '';
        
        error_log('NPS AJAX: logo_id = ' . $logo_id);
        
        // Parse logo size
        $logo_size = array('width' => 50, 'height' => 50);
        if (!empty($logo_size_json)) {
            $parsed_size = json_decode(stripslashes($logo_size_json), true);
            if (is_array($parsed_size)) {
                $logo_size = array(
                    'width' => isset($parsed_size['width']) ? intval($parsed_size['width']) : 50,
                    'height' => isset($parsed_size['height']) ? intval($parsed_size['height']) : 50
                );
            }
        }
        
        // Validate logo ID
        if ($logo_id <= 0) {
            error_log('NPS AJAX: Invalid logo ID');
            wp_send_json_error(array('message' => 'Invalid logo ID - must be greater than 0'));
        }
        
        // Get attachment and validate it exists
        $attachment = get_post($logo_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            error_log('NPS AJAX: Attachment not found or not valid. ID: ' . $logo_id);
            wp_send_json_error(array('message' => 'Attachment not found or invalid type'));
        }
        
        error_log('NPS AJAX: Attachment found - post_type: ' . $attachment->post_type);
        
        // Get logo URL
        $logo_url = wp_get_attachment_url($logo_id);
        if (!$logo_url) {
            error_log('NPS AJAX: Unable to get attachment URL for ID: ' . $logo_id);
            wp_send_json_error(array('message' => 'Unable to get attachment URL'));
        }
        
        error_log('NPS AJAX: logo_url = ' . $logo_url);
        
        $save_result = $this->settings->save_logo($logo_id, $logo_url, $logo_size);
        
        if (!$save_result['success']) {
            error_log('NPS AJAX: Failed to persist logo settings - ' . wp_json_encode($save_result['results']));
            wp_send_json_error(array('message' => 'Failed to save logo settings'));
        }
        
        error_log('NPS AJAX: Network options updated - ' . wp_json_encode($save_result['results']));
        
        // Return success with logo URL
        wp_send_json_success(array(
            'logo_url' => $logo_url,
            'logo_id' => $logo_id,
            'logo_size' => $save_result['values']['nps_logo_size'],
            'message' => 'Logo selected and saved successfully'
        ));
    }
    
    /**
     * AJAX: Save Settings
     */
    public function ajax_save_settings() {
        error_log('NPS AJAX: save_settings called');
        
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            error_log('NPS AJAX: No nonce in POST');
            wp_send_json_error(array('message' => 'No nonce provided'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'network-packing-slip-nonce')) {
            error_log('NPS AJAX: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed - nonce invalid'));
        }
        
        error_log('NPS AJAX: Nonce verified');
        
        // Check capabilities
        if (!current_user_can('manage_network')) {
            error_log('NPS AJAX: User does not have manage_network capability');
            wp_send_json_error(array('message' => 'Permission denied - manage_network capability required'));
        }
        
        error_log('NPS AJAX: User has manage_network capability');
        
        // Sanitize and validate input
        $company_info = isset($_POST['company_info']) ? wp_kses_post($_POST['company_info']) : '';
        $recipient_address = isset($_POST['recipient_address']) ? wp_kses_post($_POST['recipient_address']) : '';
        $custom_html = isset($_POST['custom_html']) ? wp_kses_post($_POST['custom_html']) : '';
        $empty_space = isset($_POST['empty_space']) ? $_POST['empty_space'] : 1;
        $logo_width = isset($_POST['logo_width']) ? intval($_POST['logo_width']) : 50;
        $logo_height = isset($_POST['logo_height']) ? intval($_POST['logo_height']) : 50;
        
        error_log('NPS AJAX: empty_space = ' . $empty_space);
        
        $save_result = $this->settings->save_all_settings(array(
            'company_info' => $company_info,
            'recipient_address' => $recipient_address,
            'custom_html' => $custom_html,
            'empty_space' => $empty_space,
            'logo_size' => array(
                'width' => $logo_width,
                'height' => $logo_height,
            ),
        ));
        
        if (!$save_result['success']) {
            error_log('NPS AJAX: Failed to persist settings - ' . wp_json_encode($save_result['results']));
            wp_send_json_error(array(
                'message' => 'Failed to save one or more settings',
                'results' => $save_result['results'],
            ));
        }
        
        error_log('NPS AJAX: All settings saved - ' . wp_json_encode($save_result['values']));
        
        wp_send_json_success(array(
            'message' => 'All settings saved successfully',
            'values' => $save_result['values'],
        ));
    }
    
    /**
     * AJAX: Filter Orders
     */
    public function ajax_filter_orders() {
        error_log('NPS AJAX: filter_orders called');
        
        // Verify nonce
        if (!isset($_POST['nonce'])) {
            error_log('NPS AJAX: No nonce in POST');
            wp_send_json_error(array('message' => 'No nonce provided'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'network-packing-slip-nonce')) {
            error_log('NPS AJAX: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed - nonce invalid'));
        }
        
        error_log('NPS AJAX: Nonce verified');
        
        // Check capabilities
        if (!current_user_can('manage_network')) {
            error_log('NPS AJAX: User does not have manage_network capability');
            wp_send_json_error(array('message' => 'Permission denied - manage_network capability required'));
        }
        
        error_log('NPS AJAX: User has manage_network capability');
        
        // Get and sanitize status filter
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        error_log('NPS AJAX: Filtering orders by status: ' . (!empty($status) ? $status : 'all'));
        
        // Get filtered orders from print class
        $orders = $this->print_slip->get_network_orders($status);
        
        error_log('NPS AJAX: Retrieved ' . count($orders) . ' orders');
        
        if (is_array($orders)) {
            error_log('NPS AJAX: Sending ' . count($orders) . ' orders to client');
            wp_send_json_success(array(
                'orders' => $orders,
                'count' => count($orders),
                'message' => 'Orders retrieved successfully'
            ));
        } else {
            error_log('NPS AJAX: Failed to retrieve orders - not an array');
            wp_send_json_error(array('message' => 'Failed to retrieve orders'));
        }
    }
    
    /**
     * AJAX: Print PDF
     */
    public function ajax_print_pdf() {
        // Start buffering to catch any output
        ob_start();
        
        try {
            error_log('NPS AJAX print_pdf: ===== START =====');
            
            // Check for nonce first
            if (!isset($_POST['nonce'])) {
                error_log('NPS AJAX: ERROR - No nonce in POST');
                ob_end_clean();
                wp_send_json_error(array('message' => 'No nonce provided'));
                return;
            }
            
            error_log('NPS AJAX: Nonce found');
            
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'network-packing-slip-nonce')) {
                error_log('NPS AJAX: ERROR - Nonce verification failed');
                ob_end_clean();
                wp_send_json_error(array('message' => 'Security check failed'));
                return;
            }
            
            error_log('NPS AJAX: Nonce verified successfully');
            
            // Check user capability
            if (!current_user_can('manage_network')) {
                error_log('NPS AJAX: ERROR - User does not have manage_network capability');
                ob_end_clean();
                wp_send_json_error(array('message' => 'Permission denied'));
                return;
            }
            
            error_log('NPS AJAX: User has manage_network capability');
            
            // Get order data
            if (!isset($_POST['order_ids'])) {
                error_log('NPS AJAX: ERROR - No order_ids in POST');
                ob_end_clean();
                wp_send_json_error(array('message' => 'No orders provided'));
                return;
            }
            
            $order_data = $_POST['order_ids'];
            error_log('NPS AJAX: order_ids received');
            
            if (empty($order_data)) {
                error_log('NPS AJAX: ERROR - order_ids is empty');
                ob_end_clean();
                wp_send_json_error(array('message' => 'No orders selected'));
                return;
            }
            
            // Process order data
            $order_ids = array();
            
            if (is_array($order_data)) {
                foreach ($order_data as $item) {
                    try {
                        $order_info = null;
                        
                        // If it's already an array
                        if (is_array($item)) {
                            $order_info = $item;
                        }
                        // If it's a string, try to decode it
                        elseif (is_string($item)) {
                            $decoded = @base64_decode($item, true);
                            if ($decoded) {
                                $order_info = @json_decode($decoded, true);
                            } else {
                                $order_info = @json_decode($item, true);
                            }
                        }
                        
                        if (is_array($order_info) && isset($order_info['id'])) {
                            $order_ids[] = array(
                                'order_id' => intval($order_info['id']),
                                'site_id' => intval($order_info['site_id'] ?? 1)
                            );
                        }
                    } catch (Exception $e) {
                        error_log('NPS AJAX: Item processing error - ' . $e->getMessage());
                    }
                }
            }
            
            error_log('NPS AJAX: Processed ' . count($order_ids) . ' valid orders');
            
            if (empty($order_ids)) {
                error_log('NPS AJAX: ERROR - No valid orders after processing');
                ob_end_clean();
                wp_send_json_error(array('message' => 'No valid orders'));
                return;
            }
            
            // Call generate_pdf
            error_log('NPS AJAX: Calling generate_pdf...');
            
            $pdf_url = $this->print_slip->generate_pdf($order_ids);
            
            error_log('NPS AJAX: generate_pdf returned');
            error_log('NPS AJAX: PDF URL: ' . ($pdf_url ? $pdf_url : 'FALSE'));
            
            if ($pdf_url) {
                error_log('NPS AJAX: SUCCESS - Sending PDF URL');
                ob_end_clean();
                wp_send_json_success(array(
                    'pdf_url' => $pdf_url,
                    'message' => 'PDF generated successfully'
                ));
                return;
            } else {
                error_log('NPS AJAX: ERROR - PDF generation failed');
                ob_end_clean();
                wp_send_json_error(array('message' => 'Failed to generate PDF'));
                return;
            }
            
        } catch (Exception $e) {
            error_log('NPS AJAX: EXCEPTION CAUGHT - ' . $e->getMessage());
            $output = ob_get_clean();
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
            return;
        }
        
        error_log('NPS AJAX print_pdf: ===== END =====');
        ob_end_clean();
    }
    
    /**
     * Plugin activation hook
     */
    public static function activate() {
        error_log('NPS: Plugin activation started');
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('NPS: WooCommerce not active');
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('Network Packing Slip plugin requires WooCommerce to be installed and activated.', 'print-pdf-packing-slips'),
                esc_html__('Plugin Activation Error', 'print-pdf-packing-slips'),
                array('back_link' => true)
            );
        }
        
        error_log('NPS: WooCommerce is active');
        
        // Initialize default network options
        if (!get_network_option(null, 'nps_company_info')) {
            update_network_option(null, 'nps_company_info', '');
            error_log('NPS: Initialized nps_company_info');
        }
        
        if (!get_network_option(null, 'nps_recipient_address')) {
            update_network_option(null, 'nps_recipient_address', '');
            error_log('NPS: Initialized nps_recipient_address');
        }
        
        if (!get_network_option(null, 'nps_custom_html')) {
            update_network_option(null, 'nps_custom_html', '');
            error_log('NPS: Initialized nps_custom_html');
        }
        
        if (!get_network_option(null, 'nps_logo_id')) {
            update_network_option(null, 'nps_logo_id', 0);
            error_log('NPS: Initialized nps_logo_id');
        }
        
        if (!get_network_option(null, 'nps_logo_url')) {
            update_network_option(null, 'nps_logo_url', '');
            error_log('NPS: Initialized nps_logo_url');
        }
        
        if (!get_network_option(null, 'nps_logo_size')) {
            update_network_option(null, 'nps_logo_size', array('width' => 50, 'height' => 50));
            error_log('NPS: Initialized nps_logo_size');
        }
        
        if (!get_network_option(null, 'nps_empty_space_after_slip')) {
            update_network_option(null, 'nps_empty_space_after_slip', 1);
            error_log('NPS: Initialized nps_empty_space_after_slip');
        }
        
        // Create packing-slips directory
        $upload_dir = wp_upload_dir();
        $packing_slips_dir = $upload_dir['basedir'] . '/packing-slips';
        
        if (!is_dir($packing_slips_dir)) {
            if (wp_mkdir_p($packing_slips_dir)) {
                error_log('NPS: Created packing-slips directory: ' . $packing_slips_dir);
            } else {
                error_log('NPS: Failed to create packing-slips directory: ' . $packing_slips_dir);
            }
        } else {
            error_log('NPS: packing-slips directory already exists');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('NPS: Plugin activation completed successfully');
    }
    
    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        error_log('NPS: Plugin deactivation started');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('NPS: Plugin deactivation completed');
    }
    
    /**
     * Get settings instance
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get print slip instance
     */
    public function get_print_slip() {
        return $this->print_slip;
    }
}
?>
