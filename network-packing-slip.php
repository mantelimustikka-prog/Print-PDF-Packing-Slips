<?php
/**
 * Plugin Name: Network Packing Slip
 * Plugin URI: https://example.com
 * Description: Generate PDF packing slips across WooCommerce network sites
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: print-pdf-packing-slips
 * Domain Path: /languages
 * Network: true
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NETWORK_PACKING_SLIP_VERSION', '1.0.0');
define('NETWORK_PACKING_SLIP_DIR', plugin_dir_path(__FILE__));
define('NETWORK_PACKING_SLIP_URL', plugin_dir_url(__FILE__));
define('NETWORK_PACKING_SLIP_INCLUDES', NETWORK_PACKING_SLIP_DIR . 'includes/');

// Load plugin files
require_once NETWORK_PACKING_SLIP_INCLUDES . 'class-settings.php';
require_once NETWORK_PACKING_SLIP_INCLUDES . 'class-print-slip.php';
require_once NETWORK_PACKING_SLIP_INCLUDES . 'class-network-packing-slip.php';

// Plugin activation and deactivation hooks
register_activation_hook(__FILE__, array('Network_Packing_Slip', 'activate'));
register_deactivation_hook(__FILE__, array('Network_Packing_Slip', 'deactivate'));

// Initialize the plugin
add_action('plugins_loaded', function() {
    Network_Packing_Slip::get_instance();
});
?>