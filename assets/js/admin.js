(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('Admin script loaded');
        console.log('NPSData:', typeof NPSData !== 'undefined' ? NPSData : 'NOT FOUND');
        
        // ===== SELECT LOGO FUNCTIONALITY =====
        var logoFrame;
        
        $('#nps-select-logo').on('click', function(e) {
            e.preventDefault();
            console.log('Select Logo button clicked');
            
            // Check if wp.media exists
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('WordPress media library is not loaded. Please refresh the page.');
                console.error('wp.media is not defined');
                return false;
            }
            
            // If frame already exists, just open it
            if (logoFrame) {
                console.log('Opening existing frame');
                logoFrame.open();
                return false;
            }
            
            console.log('Creating new media frame');
            
            // Create media frame
            logoFrame = wp.media({
                title: 'Select Logo Image',
                button: {
                    text: 'Use This Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When image is selected
            logoFrame.on('select', function() {
                console.log('Image selected from media library');
                
                var attachment = logoFrame.state().get('selection').first().toJSON();
                console.log('Attachment data:', attachment);
                
                // Get logo dimensions
                var width = parseInt($('#nps-logo-width').val()) || 50;
                var height = parseInt($('#nps-logo-height').val()) || 50;
                
                console.log('Sending AJAX request to save logo');
                
                // Send AJAX request
                $.ajax({
                    url: NPSData.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'nps_select_logo',
                        nonce: NPSData.nonce,
                        logo_id: attachment.id,
                        logo_size: JSON.stringify({
                            width: width,
                            height: height
                        })
                    },
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        
                        if (response.success) {
                            var logoUrl = response.data.logo_url;
                            console.log('Logo URL:', logoUrl);
                            
                            // Update preview
                            $('#nps-logo-preview').html(
                                '<img src="' + logoUrl + '" style="max-width: 200px; max-height: 200px; object-fit: contain; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">'
                            );
                            
                            showMessage('✓ Logo selected successfully!', 'success');
                        } else {
                            showMessage('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        console.error('Response:', xhr.responseText);
                        showMessage('✗ Error: ' + error, 'error');
                    }
                });
            });
            
            // Open the frame
            console.log('Opening media frame');
            logoFrame.open();
            
            return false;
        });
        
        // ===== SAVE SETTINGS FUNCTIONALITY =====
        $('#nps-save-settings').on('click', function(e) {
            e.preventDefault();
            console.log('Save settings clicked');
            
            var companyInfo = $('#nps-company-info').val();
            var recipientAddress = $('#nps-recipient-address').val();
            var customHtml = $('#nps-custom-html').val();
            
            console.log('Sending settings to server');
            
            $.ajax({
                url: NPSData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nps_save_settings',
                    nonce: NPSData.nonce,
                    company_info: companyInfo,
                    recipient_address: recipientAddress,
                    custom_html: customHtml
                },
                success: function(response) {
                    console.log('Settings save response:', response);
                    
                    if (response.success) {
                        showMessage('✓ All settings saved successfully!', 'success');
                    } else {
                        showMessage('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    showMessage('✗ Error saving settings: ' + error, 'error');
                }
            });
        });
        
        // ===== FILTER ORDERS FUNCTIONALITY =====
        $('#nps-filter-orders').on('click', function(e) {
            e.preventDefault();
            console.log('Filter orders clicked');
            
            var status = $('#nps-order-status').val();
            console.log('Selected status:', status);
            
            $.ajax({
                url: NPSData.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nps_filter_orders',
                    nonce: NPSData.nonce,
                    status: status
                },
                success: function(response) {
                    console.log('Filter response:', response);
                    
                    if (response.success) {
                        updateOrdersTable(response.data.orders);
                        showMessage('✓ Orders filtered successfully! (' + response.data.count + ' orders found)', 'success');
                    } else {
                        showMessage('✗ Error: ' + (response.data ? response.data.message : 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    showMessage('✗ Error filtering orders: ' + error, 'error');
                }
            });
        });
        
        // ===== SELECT ALL ORDERS =====
        $('#nps-select-all').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.nps-order-checkbox').prop('checked', isChecked);
        });
        
        // ===== UNCHECK SELECT ALL IF ANY ORDER IS UNCHECKED =====
        $(document).on('change', '.nps-order-checkbox', function() {
            var totalCheckboxes = $('.nps-order-checkbox').length;
            var checkedCheckboxes = $('.nps-order-checkbox:checked').length;
            
            if (totalCheckboxes === checkedCheckboxes) {
                $('#nps-select-all').prop('checked', true);
            } else {
                $('#nps-select-all').prop('checked', false);
            }
        });
        
        // ===== PRINT PDF FUNCTIONALITY =====
        $('#nps-print-pdf').on('click', function(e) {
            e.preventDefault();
            console.log('Print PDF clicked');
            
            var selectedOrders = [];
            $('.nps-order-checkbox:checked').each(function() {
                try {
                    var data = $(this).val();
                    console.log('Order data type:', typeof data);
                    
                    if (data) {
                        // Try to decode base64
                        try {
                            var decoded = atob(data);
                            var orderObj = JSON.parse(decoded);
                            selectedOrders.push(orderObj);
                            console.log('Decoded order:', orderObj);
                        } catch(e) {
                            // If base64 fails, try direct JSON
                            var orderObj = JSON.parse(data);
                            selectedOrders.push(orderObj);
                            console.log('Direct JSON order:', orderObj);
                        }
                    }
                } catch(e) {
                    console.error('Error parsing order data:', e);
                }
            });
            
            console.log('Selected orders:', selectedOrders);
            console.log('Total selected:', selectedOrders.length);
            
            if (selectedOrders.length === 0) {
                alert('Please select at least one order');
                return false;
            }
            
            console.log('Sending AJAX request with orders:', JSON.stringify(selectedOrders));
            
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
                    $('#nps-print-pdf').prop('disabled', true).text('Generating PDF...');
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    
                    if (response.success) {
                        showMessage('✓ PDF generated successfully!', 'success');
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        var message = response.data ? (response.data.message || 'Unknown error') : 'Unknown error';
                        showMessage('✗ Error: ' + message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response:', xhr.responseText);
                    showMessage('✗ Error generating PDF: ' + error, 'error');
                },
                complete: function() {
                    $('#nps-print-pdf').prop('disabled', false).text('Print PDF Packing Slips');
                }
            });
            
            return false;
        });
        
        // ===== CHANGE ITEMS PER PAGE =====
        $('#nps-per-page-select').on('change', function() {
            var perPage = $(this).val();
            var currentUrl = window.location.href;
            var url = new URL(currentUrl);
            url.searchParams.set('nps_per_page', perPage);
            url.searchParams.set('nps_page', 1);
            window.location.href = url.toString();
        });
        
        // ===== HELPER FUNCTIONS =====
        
        /**
         * Show notification message
         */
        function showMessage(message, type) {
            var messageHtml = '<div class="notice notice-' + type + ' is-dismissible" style="margin: 20px 0;"><p>' + message + '</p></div>';
            $('.nps-filter-section').after(messageHtml);
            
            setTimeout(function() {
                $('.notice').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        /**
         * Update orders table with new data
         */
        function updateOrdersTable(orders) {
            var html = '';
            
            if (orders && orders.length > 0) {
                orders.forEach(function(order) {
                    html += '<tr>';
                    html += '<td><input type="checkbox" class="nps-order-checkbox" value="' + btoa(JSON.stringify(order)) + '" /></td>';
                    html += '<td><strong>' + escapeHtml(order.order_number) + '</strong></td>';
                    html += '<td>' + escapeHtml(order.customer_name) + '</td>';
                    html += '<td><span class="order-status status-' + order.status + '">' + escapeHtml(capitalizeStatus(order.status)) + '</span></td>';
                    html += '<td>' + escapeHtml(order.date) + '</td>';
                    html += '<td>' + order.total + '</td>';
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="6" style="text-align: center; padding: 20px;">No orders found.</td></tr>';
            }
            
            $('#nps-orders-list').html(html);
        }
        
        /**
         * Escape HTML special characters
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        /**
         * Capitalize status text
         */
        function capitalizeStatus(status) {
            return status.charAt(0).toUpperCase() + status.slice(1);
        }
    });
    
})(jQuery);