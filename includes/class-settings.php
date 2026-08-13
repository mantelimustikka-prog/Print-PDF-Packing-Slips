<?php
/**
 * Settings Class
 * Handles plugin settings stored in network options
 */

if (!defined('ABSPATH')) {
    exit;
}

class Network_Packing_Slip_Settings {
    
    /**
     * Get logo URL
     */
    public function get_logo_url() {
        return get_network_option(null, 'nps_logo_url', '');
    }
    
    /**
     * Get logo ID
     */
    public function get_logo_id() {
        return get_network_option(null, 'nps_logo_id', 0);
    }
    
    /**
     * Get logo size
     */
    public function get_logo_size() {
        $size = get_network_option(null, 'nps_logo_size', array());
        if (empty($size) || !is_array($size)) {
            $size = array('width' => 50, 'height' => 50);
        }
        return $size;
    }
    
    /**
     * Get company info
     */
    public function get_company_info() {
        return get_network_option(null, 'nps_company_info', '');
    }
    
    /**
     * Get recipient address template
     */
    public function get_recipient_address() {
        return get_network_option(null, 'nps_recipient_address', '');
    }
    
    /**
     * Get custom HTML
     */
    public function get_custom_html() {
        return get_network_option(null, 'nps_custom_html', '');
    }
    
    /**
     * Get empty space after 1st slip (in cm)
     */
    public function get_empty_space_after_slip() {
        $space = get_network_option(null, 'nps_empty_space_after_slip');
        
        if ($space === false) {
            $space = 1;
        }
        
        $space = floatval($space);
        
        if ($space < 0) {
            $space = 0;
        }
        if ($space > 10) {
            $space = 10;
        }
        
        return $space;
    }
    
    /**
     * Save logo
     */
    public function save_logo($logo_id, $logo_url, $logo_size) {
        update_network_option(null, 'nps_logo_id', $logo_id);
        update_network_option(null, 'nps_logo_url', $logo_url);
        update_network_option(null, 'nps_logo_size', $logo_size);
    }
    
    /**
     * Save company info
     */
    public function save_company_info($company_info) {
        return update_network_option(null, 'nps_company_info', $company_info);
    }
    
    /**
     * Save recipient address
     */
    public function save_recipient_address($recipient_address) {
        return update_network_option(null, 'nps_recipient_address', $recipient_address);
    }
    
    /**
     * Save custom HTML
     */
    public function save_custom_html($custom_html) {
        return update_network_option(null, 'nps_custom_html', $custom_html);
    }
    
    /**
     * Save empty space after slip
     */
    public function save_empty_space_after_slip($space) {
        // Remove wp_cache to ensure fresh data
        wp_cache_delete('nps_empty_space_after_slip', 'network-options');
        
        $space = floatval($space);
        if ($space < 0) {
            $space = 0;
        }
        if ($space > 10) {
            $space = 10;
        }
        
        $result = update_network_option(null, 'nps_empty_space_after_slip', $space);
        
        // Clear cache again after update
        wp_cache_delete('nps_empty_space_after_slip', 'network-options');
        
        error_log('NPS: save_empty_space_after_slip - Saved value: ' . $space . ', Result: ' . ($result ? 'true' : 'false'));
        
        return $result;
    }
}
?>