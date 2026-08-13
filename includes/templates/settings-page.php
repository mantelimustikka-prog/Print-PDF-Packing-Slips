<?php
/**
 * Settings Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = Network_Packing_Slip::get_instance()->get_settings();
$logo_url = $settings->get_logo_url();
$logo_id = $settings->get_logo_id();
$logo_size = $settings->get_logo_size();
$logo_width = isset($logo_size['width']) ? intval($logo_size['width']) : 50;
$logo_height = isset($logo_size['height']) ? intval($logo_size['height']) : 50;
$company_info = $settings->get_company_info();
$recipient_address = $settings->get_recipient_address();
$custom_html = $settings->get_custom_html();
$empty_space = $settings->get_empty_space_after_slip();

error_log('NPS Settings Page: empty_space value = ' . $empty_space);
?>

<div class="wrap">
    <h1><?php esc_html_e('PDF Packing Slips Settings', 'print-pdf-packing-slips'); ?></h1>
    
    <div id="nps-message-container"></div>
    
    <div class="nps-settings-section" style="max-width: 1200px;">
        
        <!-- Global Help Section -->
        <div class="nps-settings-box" style="background: #f0f7ff; border: 2px solid #0073aa;">
            <h2 style="color: #0073aa; margin-top: 0;">
                <?php esc_html_e('📚 Available Variables & Shortcodes', 'print-pdf-packing-slips'); ?>
            </h2>
            <p style="color: #555; font-size: 13px; margin: 0 0 20px 0; font-style: italic;">
                <?php esc_html_e('Use these in all three content boxes below. You can also use any HTML tags (p, strong, em, ul, li, table, etc.) for formatting.', 'print-pdf-packing-slips'); ?>
            </p>
            
            <!-- Global Instructions Tabs -->
            <div class="nps-instructions-tabs" style="margin-bottom: 0;">
                <button type="button" class="nps-tab-button active" data-tab="global-variables"><?php esc_html_e('Variables', 'print-pdf-packing-slips'); ?></button>
                <button type="button" class="nps-tab-button" data-tab="global-shortcodes"><?php esc_html_e('Shortcodes', 'print-pdf-packing-slips'); ?></button>
            </div>
            
            <!-- Variables Tab -->
            <div id="global-variables" class="nps-tab-content active" style="background: #fff; padding: 20px; border-radius: 0 0 4px 4px; margin-bottom: 0; font-size: 12px; border: 1px solid #0073aa; border-top: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Billing Variables Column -->
                    <div>
                        <h4 style="color: #0073aa; margin-top: 0; margin-bottom: 10px; font-size: 14px;">
                            <?php esc_html_e('📋 Customer/Billing Variables', 'print-pdf-packing-slips'); ?>
                        </h4>
                        <code style="display: block; background: #f9f9f9; padding: 12px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 11px; line-height: 1.8;">
{customer_first_name}<br>
{customer_last_name}<br>
{customer_email}<br>
{customer_phone}<br><br>
{billing_first_name}<br>
{billing_last_name}<br>
{billing_company}<br>
{billing_address_1}<br>
{billing_address_2}<br>
{billing_city}<br>
{billing_postcode}<br>
{billing_state}<br>
{billing_country}<br>
{billing_country_name}<br>
{billing_email}<br>
{billing_phone}
                        </code>
                    </div>
                    
                    <!-- Shipping Variables Column -->
                    <div>
                        <h4 style="color: #0073aa; margin-top: 0; margin-bottom: 10px; font-size: 14px;">
                            <?php esc_html_e('🚚 Shipping & Order Variables', 'print-pdf-packing-slips'); ?>
                        </h4>
                        <code style="display: block; background: #f9f9f9; padding: 12px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 11px; line-height: 1.8;">
{shipping_first_name}<br>
{shipping_last_name}<br>
{shipping_company}<br>
{shipping_address_1}<br>
{shipping_address_2}<br>
{shipping_city}<br>
{shipping_postcode}<br>
{shipping_state}<br>
{shipping_country}<br>
{shipping_country_name}<br>
{shipping_email}<br>
{shipping_phone}<br>
{shipping_method}<br><br>
{order_number}<br>
{order_date}<br>
{order_total}
                        </code>
                    </div>
                </div>
            </div>
            
            <!-- Shortcodes Tab -->
            <div id="global-shortcodes" class="nps-tab-content" style="background: #fff; padding: 20px; border-radius: 0 0 4px 4px; margin-bottom: 0; font-size: 12px; display: none; border: 1px solid #0073aa; border-top: none;">
                <h4 style="color: #0073aa; margin-top: 0; margin-bottom: 15px; font-size: 14px;">
                    <?php esc_html_e('📌 WordPress Shortcodes', 'print-pdf-packing-slips'); ?>
                </h4>
                <p style="margin: 0 0 15px 0; color: #555;">
                    <?php esc_html_e('Use any registered WordPress shortcodes. Examples:', 'print-pdf-packing-slips'); ?>
                </p>
                <code style="display: block; background: #f9f9f9; padding: 12px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 11px; line-height: 1.8; margin-bottom: 15px;">
[site_url] - <?php esc_html_e('Website URL', 'print-pdf-packing-slips'); ?><br>
[bloginfo] - <?php esc_html_e('Blog information', 'print-pdf-packing-slips'); ?><br>
[date format="Y-m-d"] - <?php esc_html_e('Current date', 'print-pdf-packing-slips'); ?><br>
[year] - <?php esc_html_e('Current year', 'print-pdf-packing-slips'); ?><br>
<?php esc_html_e('Any custom shortcodes from your plugins', 'print-pdf-packing-slips'); ?>
                </code>
                
                <h4 style="color: #0073aa; margin: 20px 0 10px 0; font-size: 14px;">
                    <?php esc_html_e('Example:', 'print-pdf-packing-slips'); ?>
                </h4>
                <code style="display: block; background: #f9f9f9; padding: 12px; border: 1px solid #e0e0e0; border-radius: 4px; font-size: 11px;">
&lt;p&gt;Generated on [date format="d/m/Y"] at [site_url]&lt;/p&gt;
                </code>
            </div>
        </div>
        
        <!-- SENDER Section -->
        <div class="nps-settings-box">
            <h2><?php esc_html_e('SENDER:', 'print-pdf-packing-slips'); ?></h2>
            <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                <?php esc_html_e('Displayed in top-left box (30% width). Supports variables, shortcodes, and HTML formatting (p, strong, em, ul, li, table, br, hr, etc.).', 'print-pdf-packing-slips'); ?>
            </p>
            
            <textarea 
                id="nps-company-info" 
                rows="6" 
                style="width: 100%; border: 1px solid #ddd; padding: 10px; font-family: monospace; border-radius: 4px; font-size: 12px;"
                placeholder="Example: &lt;p&gt;&lt;strong&gt;{billing_company}&lt;/strong&gt;&lt;br&gt;{billing_address_1}&lt;br&gt;{billing_city}, {billing_postcode}&lt;/p&gt;"
            ><?php echo esc_textarea($company_info); ?></textarea>
        </div>
        
        <!-- RECIPIENT - Delivery Address Section -->
        <div class="nps-settings-box">
            <h2><?php esc_html_e('RECIPIENT - Delivery Address:', 'print-pdf-packing-slips'); ?></h2>
            <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                <?php esc_html_e('Displayed in full-width box below sender. Supports variables, shortcodes, and HTML formatting (p, strong, em, ul, li, table, br, hr, etc.).', 'print-pdf-packing-slips'); ?>
            </p>
            
            <textarea 
                id="nps-recipient-address" 
                rows="6" 
                style="width: 100%; border: 1px solid #ddd; padding: 10px; font-family: monospace; border-radius: 4px; font-size: 12px;"
                placeholder="Example: &lt;p&gt;&lt;strong&gt;Ship To:&lt;/strong&gt;&lt;br&gt;{shipping_first_name} {shipping_last_name}&lt;br&gt;{shipping_address_1}&lt;br&gt;{shipping_city}, {shipping_postcode}&lt;/p&gt;"
            ><?php echo esc_textarea($recipient_address); ?></textarea>
        </div>
        
        <!-- Packing Slip Bottom Section -->
        <div class="nps-settings-box">
            <h2><?php esc_html_e('Packing Slip Bottom:', 'print-pdf-packing-slips'); ?></h2>
            <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                <?php esc_html_e('Additional content below order information. Supports variables, shortcodes, and HTML formatting (p, strong, em, ul, li, table, br, hr, etc.).', 'print-pdf-packing-slips'); ?>
            </p>
            
            <textarea 
                id="nps-custom-html" 
                rows="8" 
                style="width: 100%; border: 1px solid #ddd; padding: 10px; font-family: monospace; border-radius: 4px; font-size: 12px;"
                placeholder="Example: &lt;p&gt;Thank you for your order!&lt;br&gt;Order: {order_number}&lt;br&gt;Total: {order_total}&lt;/p&gt;"
            ><?php echo esc_textarea($custom_html); ?></textarea>
        </div>
        
        <!-- Empty Space Section -->
        <div class="nps-settings-box">
            <h2><?php esc_html_e('Empty Space After 1st Slip', 'print-pdf-packing-slips'); ?></h2>
            <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                <?php esc_html_e('Set the spacing between first and second order on each A4 page.', 'print-pdf-packing-slips'); ?>
            </p>
            
            <div style="display: flex; align-items: center; gap: 15px;">
                <input 
                    type="number" 
                    id="nps-empty-space" 
                    min="0" 
                    max="10" 
                    step="0.5"
                    value="<?php echo esc_attr($empty_space); ?>"
                    style="width: 120px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                />
                <span style="color: #666;"><?php esc_html_e('cm (0 - 10 cm)', 'print-pdf-packing-slips'); ?></span>
            </div>
        </div>
        
        <!-- Logo Section -->
        <div class="nps-settings-box">
            <h2><?php esc_html_e('Logo', 'print-pdf-packing-slips'); ?></h2>
            
            <div id="nps-logo-preview" style="margin-bottom: 20px;">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" style="max-width: 200px; max-height: 100px; object-fit: contain; border: 1px solid #ddd; padding: 10px; border-radius: 4px;" alt="Logo Preview" />
                <?php else: ?>
                    <p style="color: #666;"><?php esc_html_e('No logo selected', 'print-pdf-packing-slips'); ?></p>
                <?php endif; ?>
            </div>
            
            <button type="button" id="nps-select-logo" class="button button-primary"><?php esc_html_e('Select Logo', 'print-pdf-packing-slips'); ?></button>
            
            <!-- Logo Size Settings -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h4><?php esc_html_e('Logo Size', 'print-pdf-packing-slips'); ?></h4>
                <p style="color: #666; font-size: 12px; margin-bottom: 15px;">
                    <?php esc_html_e('Set the width and height of the logo in millimeters (mm) in the PDF.', 'print-pdf-packing-slips'); ?>
                </p>
                
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <!-- Width Field -->
                    <div style="flex: 1; min-width: 200px;">
                        <label for="nps-logo-width" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            <?php esc_html_e('Logo Width (mm)', 'print-pdf-packing-slips'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="nps-logo-width" 
                            min="10" 
                            max="200" 
                            step="1"
                            value="<?php echo esc_attr($logo_width); ?>"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                        />
                        <small style="display: block; color: #666; margin-top: 5px;">
                            <?php esc_html_e('Recommended: 30-80 mm', 'print-pdf-packing-slips'); ?>
                        </small>
                    </div>
                    
                    <!-- Height Field -->
                    <div style="flex: 1; min-width: 200px;">
                        <label for="nps-logo-height" style="display: block; margin-bottom: 5px; font-weight: bold;">
                            <?php esc_html_e('Logo Height (mm)', 'print-pdf-packing-slips'); ?>
                        </label>
                        <input 
                            type="number" 
                            id="nps-logo-height" 
                            min="10" 
                            max="200" 
                            step="1"
                            value="<?php echo esc_attr($logo_height); ?>"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                        />
                        <small style="display: block; color: #666; margin-top: 5px;">
                            <?php esc_html_e('Recommended: 20-50 mm', 'print-pdf-packing-slips'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="nps-settings-box" style="text-align: center; background: #f9f9f9;">
            <button type="button" id="nps-save-settings" class="button button-primary button-large" style="padding: 12px 40px; font-size: 16px;">
                <?php esc_html_e('💾 Save All Settings', 'print-pdf-packing-slips'); ?>
            </button>
        </div>
    </div>
    
    <style>
        .nps-settings-box {
            background: white;
            border: 1px solid #ddd;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .nps-settings-box h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #23282d;
        }
        .nps-settings-box h4 {
            margin: 15px 0 10px 0;
            font-size: 13px;
            color: #555;
            font-weight: 600;
        }
        .nps-settings-box textarea {
            resize: vertical;
            font-size: 12px !important;
        }
        .nps-settings-box textarea:focus {
            border-color: #0073aa !important;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.1) !important;
        }
        .notice {
            margin: 20px 0 !important;
        }
        .nps-instructions-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #0073aa;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        .nps-tab-button {
            background: #f5f5f5;
            border: 2px solid #0073aa;
            border-bottom: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 13px;
            color: #0073aa;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            margin-right: 5px;
            margin-bottom: -2px;
            border-radius: 4px 4px 0 0;
            font-weight: 600;
        }
        .nps-tab-button:hover {
            background: #efefef;
        }
        .nps-tab-button.active {
            background: #fff;
            color: #0073aa;
            border-bottom-color: #fff;
        }
        .nps-tab-content {
            display: none;
        }
        .nps-tab-content.active {
            display: block;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        #nps-save-settings {
            background: #0073aa;
            border-color: #005a87;
            box-shadow: 0 2px 5px rgba(0,115,170,0.2);
            transition: all 0.2s ease;
        }
        #nps-save-settings:hover {
            background: #005a87;
            box-shadow: 0 4px 8px rgba(0,115,170,0.3);
        }
    </style>
</div>

<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Tab switching functionality
        $('.nps-tab-button').on('click', function(e) {
            e.preventDefault();
            
            var tabName = $(this).data('tab');
            var container = $(this).closest('.nps-settings-box');
            
            // Hide all tabs in this container
            container.find('.nps-tab-content').removeClass('active');
            container.find('.nps-tab-button').removeClass('active');
            
            // Show selected tab
            container.find('#' + tabName).addClass('active');
            $(this).addClass('active');
        });
        
        // Save settings
        $('#nps-save-settings').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            button.prop('disabled', true).text('⏳ Saving...');
            
            var companyInfo = $('#nps-company-info').val();
            var recipientAddress = $('#nps-recipient-address').val();
            var customHtml = $('#nps-custom-html').val();
            var emptySpace = $('#nps-empty-space').val();
            var logoWidth = $('#nps-logo-width').val();
            var logoHeight = $('#nps-logo-height').val();
            
            console.log('Saving empty space: ' + emptySpace);
            
            $.ajax({
                url: NPSData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nps_save_settings',
                    nonce: NPSData.nonce,
                    company_info: companyInfo,
                    recipient_address: recipientAddress,
                    custom_html: customHtml,
                    empty_space: emptySpace,
                    logo_width: logoWidth,
                    logo_height: logoHeight
                },
                success: function(response) {
                    console.log('Save response:', response);
                    button.prop('disabled', false).text(originalText);
                    if (response.success) {
                        showMessage('✓ All settings saved successfully!', 'success');
                    } else {
                        showMessage('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Save error:', error);
                    button.prop('disabled', false).text(originalText);
                    showMessage('✗ Error saving settings: ' + error, 'error');
                }
            });
        });
        
        // Show message
        function showMessage(message, type) {
            var messageHtml = '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
            $('#nps-message-container').html(messageHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('#nps-message-container').fadeOut('slow', function() {
                    $(this).html('').show();
                });
            }, 5000);
        }
    });
    
})(jQuery);
</script>