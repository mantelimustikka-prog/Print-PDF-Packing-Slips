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
     * Clear cached network option values.
     */
    private function clear_network_option_cache($option_name) {
        wp_cache_delete($option_name, 'network-options');
        wp_cache_delete($option_name, 'site-options');
    }
    
    /**
     * Sanitize logo size values.
     */
    private function sanitize_logo_size($logo_size) {
        $width = isset($logo_size['width']) ? intval($logo_size['width']) : 50;
        $height = isset($logo_size['height']) ? intval($logo_size['height']) : 50;
        
        $width = max(10, min(200, $width));
        $height = max(10, min(200, $height));
        
        return array(
            'width' => $width,
            'height' => $height,
        );
    }
    
    /**
     * Sanitize empty space after slip.
     */
    private function sanitize_empty_space($space) {
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
     * Persist multiple settings in one verified save sequence.
     */
    private function persist_settings($options) {
        $results = array();
        
        foreach ($options as $option_name => $option_value) {
            $this->clear_network_option_cache($option_name);
            $updated = update_network_option(null, $option_name, $option_value);
            $this->clear_network_option_cache($option_name);
            
            $stored_value = get_network_option(null, $option_name, null);
            $verified = maybe_serialize($stored_value) === maybe_serialize($option_value);
            $results[$option_name] = $verified;
            
            error_log(
                'NPS: persist_settings - ' . $option_name .
                ' updated=' . ($updated ? 'true' : 'false') .
                ' verified=' . ($verified ? 'true' : 'false') .
                ' value=' . wp_json_encode($option_value)
            );
        }
        
        return array(
            'success' => !in_array(false, $results, true),
            'results' => $results,
            'values' => $options,
        );
    }
    
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
        return $this->sanitize_logo_size($size);
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
        
        return $this->sanitize_empty_space($space);
    }
    
    /**
     * Save logo
     */
    public function save_logo($logo_id, $logo_url, $logo_size) {
        return $this->persist_settings(array(
            'nps_logo_id' => intval($logo_id),
            'nps_logo_url' => esc_url_raw($logo_url),
            'nps_logo_size' => $this->sanitize_logo_size($logo_size),
        ));
    }
    
    /**
     * Save company info
     */
    public function save_company_info($company_info) {
        $result = $this->persist_settings(array(
            'nps_company_info' => $company_info,
        ));
        
        return $result['success'];
    }
    
    /**
     * Save recipient address
     */
    public function save_recipient_address($recipient_address) {
        $result = $this->persist_settings(array(
            'nps_recipient_address' => $recipient_address,
        ));
        
        return $result['success'];
    }
    
    /**
     * Save custom HTML
     */
    public function save_custom_html($custom_html) {
        $result = $this->persist_settings(array(
            'nps_custom_html' => $custom_html,
        ));
        
        return $result['success'];
    }
    
    /**
     * Save all editable settings together.
     */
    public function save_all_settings($settings) {
        return $this->persist_settings(array(
            'nps_company_info' => isset($settings['company_info']) ? $settings['company_info'] : '',
            'nps_recipient_address' => isset($settings['recipient_address']) ? $settings['recipient_address'] : '',
            'nps_custom_html' => isset($settings['custom_html']) ? $settings['custom_html'] : '',
            'nps_logo_size' => $this->sanitize_logo_size(isset($settings['logo_size']) ? $settings['logo_size'] : array()),
            'nps_empty_space_after_slip' => $this->sanitize_empty_space(isset($settings['empty_space']) ? $settings['empty_space'] : 1),
        ));
    }
    
    /**
     * Save empty space after slip
     */
    public function save_empty_space_after_slip($space) {
        $result = $this->persist_settings(array(
            'nps_empty_space_after_slip' => $this->sanitize_empty_space($space),
        ));
        
        return $result['success'];
    }
}
?>