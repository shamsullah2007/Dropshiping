<?php
/**
 * CJ Dropshipping Customer Tracking Features
 * 
 * Provides customer-facing tracking features:
 * - Real-time order status tracking
 * - Carrier tracking links (USPS, UPS, FedEx, DHL, etc)
 * - Email notifications when orders ship
 * - Dashboard tracking display
 * 
 * @package CustomWoocommerce
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==================== CARRIER TRACKING URLS ====================

/**
 * Get tracking URL for a carrier
 * 
 * @param string $carrier Carrier name (USPS, UPS, FedEx, DHL, etc)
 * @param string $tracking_number Tracking number
 * @return string Full tracking URL
 */
function cw_cj_get_carrier_tracking_url($carrier, $tracking_number) {
    $carrier = strtoupper(trim($carrier));
    $tracking_number = urlencode(trim($tracking_number));
    
    $urls = [
        'USPS' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . $tracking_number,
        'UPS' => 'https://www.ups.com/track?tracknum=' . $tracking_number,
        'FEDEX' => 'https://tracking.fedex.com/en/tracking/' . $tracking_number,
        'FedEx' => 'https://tracking.fedex.com/en/tracking/' . $tracking_number,
        'DHL' => 'https://www.dhl.com/en/en/express/tracking.html?AWB=' . $tracking_number,
        'AMAZON' => 'https://www.amazon.com/gp/Help/customer/display.html?nodeId=G7CT7QV532CHSXPX',
        'AMAZON_SHIPPING' => 'https://tracking.amazon.com/' . $tracking_number,
        'YANWEN' => 'https://www.yanwenexpress.com/TrackingDetail?number=' . $tracking_number,
        'GITT' => 'https://www.gitx.com.hk/Tracking/tracking.aspx?billcode=' . $tracking_number,
        'APC' => 'https://www.apc.fr/parcel/en',
        'S.F.EXPRESS' => 'https://www.sf-express.com/en/sc/home',
        'ZTO' => 'http://www.zto.com/',
    ];
    
    return $urls[$carrier] ?? '';
}

/**
 * Get tracking info for an order
 * 
 * @param int $order_id WooCommerce order ID
 * @return array Tracking info with carrier and number
 */
function cw_cj_get_order_tracking_info($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return [
            'has_tracking' => false,
            'cj_order_id' => null,
            'tracking_number' => null,
            'carrier' => null,
            'status' => null,
            'tracking_url' => null,
        ];
    }
    
    $cj_order_id = get_post_meta($order_id, '_cj_order_id', true);
    $tracking_number = get_post_meta($order_id, '_shipping_tracking_number', true);
    
    // Try to get tracking number from WooCommerce shipping
    if (!$tracking_number) {
        $shipping_items = $order->get_shipping_methods();
        foreach ($shipping_items as $item) {
            $tracking = $item->get_meta('tracking_number');
            if ($tracking) {
                $tracking_number = $tracking;
                break;
            }
        }
    }
    
    // Try CJ API if we have CJ order ID but no tracking yet
    $carrier = null;
    if ($cj_order_id && !$tracking_number) {
        $cj = cw_cj_dropshipping();
        $cj_order = $cj->get_order($cj_order_id);
        
        if (!is_wp_error($cj_order) && isset($cj_order['trackNumber'])) {
            $tracking_number = $cj_order['trackNumber'];
            $carrier = $cj_order['waybillCode'] ?? null;
        }
    }
    
    $has_tracking = !empty($tracking_number);
    $tracking_url = $has_tracking && $carrier ? cw_cj_get_carrier_tracking_url($carrier, $tracking_number) : '';
    
    return [
        'has_tracking' => $has_tracking,
        'cj_order_id' => $cj_order_id,
        'tracking_number' => $tracking_number,
        'carrier' => $carrier,
        'status' => $has_tracking ? 'shipped' : $order->get_status(),
        'tracking_url' => $tracking_url,
    ];
}

// ==================== CUSTOMER DASHBOARD INTEGRATION ====================

/**
 * Display tracking info on customer order details page
 */
add_action('woocommerce_view_order', 'cw_cj_display_order_tracking_info', 5);
function cw_cj_display_order_tracking_info($order_id) {
    $tracking_info = cw_cj_get_order_tracking_info($order_id);
    
    if (!$tracking_info['has_tracking']) {
        return;
    }
    
    echo cw_cj_render_tracking_display($tracking_info);
}

/**
 * Display tracking info on customer dashboard/orders list
 */
add_action('woocommerce_account_my-orders_column_order-number', 'cw_cj_add_tracking_to_order_list', 10, 1);
function cw_cj_add_tracking_to_order_list($order) {
    $tracking_info = cw_cj_get_order_tracking_info($order->get_id());
    
    if (!$tracking_info['has_tracking']) {
        return;
    }
    
    echo '<small style="display: block; margin-top: 8px; color: #666;">';
    echo esc_html('Tracking: ' . $tracking_info['tracking_number']);
    if ($tracking_info['tracking_url']) {
        echo ' <a href="' . esc_url($tracking_info['tracking_url']) . '" target="_blank" rel="noopener noreferrer" style="color: #ff4d4f;">View</a>';
    }
    echo '</small>';
}

// ==================== TRACKING DISPLAY TEMPLATE ====================

/**
 * Render tracking display HTML
 * 
 * @param array $tracking_info Tracking information
 * @return string HTML tracking display
 */
function cw_cj_render_tracking_display($tracking_info) {
    if (!$tracking_info['has_tracking']) {
        return '';
    }
    
    $status_labels = [
        'pending' => 'Order Received',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    
    $status = $tracking_info['status'];
    $status_label = $status_labels[$status] ?? ucfirst($status);
    $tracking_number = esc_html($tracking_info['tracking_number']);
    $carrier = esc_html($tracking_info['carrier'] ?? 'Unknown Carrier');
    
    $html = '<div class="cj-order-tracking-box" style="
        background: #f5f5f5;
        border: 2px solid #ff4d4f;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        font-family: Arial, sans-serif;
    ">';
    
    // Tracking header
    $html .= '<div style="margin-bottom: 15px;">';
    $html .= '<h3 style="margin: 0 0 5px 0; color: #333; font-size: 16px;">📦 Tracking Information</h3>';
    $html .= '<p style="margin: 0; color: #666; font-size: 14px;">Your package is ' . $status_label . '</p>';
    $html .= '</div>';
    
    // Tracking details
    $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">';
    
    $html .= '<div>';
    $html .= '<label style="display: block; color: #666; font-size: 12px; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Tracking Number</label>';
    $html .= '<code style="background: white; padding: 8px 12px; border-radius: 4px; display: block; font-family: monospace; font-size: 14px; font-weight: 600; color: #333;">' . $tracking_number . '</code>';
    $html .= '</div>';
    
    $html .= '<div>';
    $html .= '<label style="display: block; color: #666; font-size: 12px; font-weight: 600; margin-bottom: 5px; text-transform: uppercase;">Carrier</label>';
    $html .= '<p style="margin: 0; padding: 8px 12px; background: white; border-radius: 4px; color: #333; font-size: 14px;">' . $carrier . '</p>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Tracking button
    if ($tracking_info['tracking_url']) {
        $html .= '<a href="' . esc_url($tracking_info['tracking_url']) . '" target="_blank" rel="noopener noreferrer" style="
            display: inline-block;
            background: #ff4d4f;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        " onmouseover="this.style.background=\'#d9393f\'" onmouseout="this.style.background=\'#ff4d4f\'">
            Track Your Package →
        </a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// ==================== TRACKING TIMELINE ====================

/**
 * Display order status timeline
 * 
 * @param int $order_id WooCommerce order ID
 */
function cw_cj_display_order_timeline($order_id) {
    $order = wc_get_order($order_id);
    $tracking_info = cw_cj_get_order_tracking_info($order_id);
    
    if (!$order) {
        return;
    }
    
    $statuses = [
        'processing' => ['label' => 'Order Received', 'icon' => '✓'],
        'shipped' => ['label' => 'Shipped', 'icon' => '📦'],
        'completed' => ['label' => 'Delivered', 'icon' => '✓'],
    ];
    
    $current_status = $order->get_status();
    
    echo '<div class="cj-order-timeline">';
    echo '<h4 style="margin-top: 0; color: #333;">Order Status</h4>';
    echo '<div style="display: flex; align-items: center; gap: 20px;">';
    
    foreach ($statuses as $status => $data) {
        $is_active = in_array($current_status, array_keys(array_slice($statuses, 0, array_key_first(array_filter($statuses, fn($s) => $s === $status)) + 1)));
        
        echo '<div style="text-align: center; flex: 1;">';
        echo '<div style="
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: ' . ($is_active ? '#ff4d4f' : '#e5e5e5') . ';
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 600;
        ">' . $data['icon'] . '</div>';
        echo '<p style="margin: 0; font-size: 13px; color: ' . ($is_active ? '#333' : '#999') . '; font-weight: ' . ($is_active ? '600' : '400') . ';">' . $data['label'] . '</p>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// ==================== EMAIL NOTIFICATIONS ====================

/**
 * Send tracking notification email to customer
 * 
 * @param int $order_id WooCommerce order ID
 * @param string $tracking_number Tracking number
 * @param string $carrier Carrier name
 */
function cw_cj_send_tracking_email($order_id, $tracking_number, $carrier = '') {
    $order = wc_get_order($order_id);
    
    if (!$order || !$order->get_billing_email()) {
        return false;
    }
    
    $tracking_url = cw_cj_get_carrier_tracking_url($carrier, $tracking_number);
    $customer_email = $order->get_billing_email();
    $order_number = $order->get_order_number();
    $site_name = get_bloginfo('name');
    
    $subject = sprintf(__('Your Order #%s Has Shipped - Track It Now!', 'custom-woocommerce'), $order_number);
    
    $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">';
    $message .= '<h2 style="color: #ff4d4f; border-bottom: 2px solid #ff4d4f; padding-bottom: 10px;">📦 Your Package is on the Way!</h2>';
    
    $message .= '<p>Hi ' . esc_html($order->get_billing_first_name()) . ',</p>';
    
    $message .= '<p>Great news! Your order <strong>#' . esc_html($order_number) . '</strong> has been shipped! Here are your tracking details:</p>';
    
    $message .= '<div style="background: #f5f5f5; border-left: 4px solid #ff4d4f; padding: 15px; margin: 20px 0; border-radius: 4px;">';
    $message .= '<p style="margin: 0 0 10px 0;"><strong>Tracking Number:</strong></p>';
    $message .= '<code style="background: white; padding: 10px; display: block; font-family: monospace; font-size: 14px; font-weight: 600; border-radius: 4px; word-break: break-all;">' . esc_html($tracking_number) . '</code>';
    
    if ($carrier) {
        $message .= '<p style="margin: 10px 0 0 0;"><strong>Carrier:</strong> ' . esc_html($carrier) . '</p>';
    }
    $message .= '</div>';
    
    if ($tracking_url) {
        $message .= '<p style="text-align: center; margin: 20px 0;">';
        $message .= '<a href="' . esc_url($tracking_url) . '" style="display: inline-block; background: #ff4d4f; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: 600;">Track Your Package</a>';
        $message .= '</p>';
    }
    
    $message .= '<p>You can also view your order details here:<br>';
    $message .= '<a href="' . esc_url($order->get_view_order_url()) . '" style="color: #ff4d4f;">' . esc_url($order->get_view_order_url()) . '</a></p>';
    
    $message .= '<hr style="border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;">';
    $message .= '<p style="font-size: 12px; color: #666;">Questions? Visit our <a href="' . esc_url(home_url()) . '" style="color: #ff4d4f;">store</a> or contact our support team.</p>';
    $message .= '<p style="font-size: 12px; color: #999; margin: 0;">© ' . date('Y') . ' ' . esc_html($site_name) . '. All rights reserved.</p>';
    $message .= '</div>';
    
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
    ];
    
    return wp_mail($customer_email, $subject, $message, $headers);
}

/**
 * Hook into CJ webhook to send email when tracking updates
 */
add_action('cj_webhook_received', 'cw_cj_webhook_send_tracking_email', 15);
function cw_cj_webhook_send_tracking_email($webhook_data) {
    $event_type = $webhook_data['eventType'] ?? '';
    
    // Only send on logistics/tracking updates
    if ($event_type !== 'LOGISTICS') {
        return;
    }
    
    $cj_order_id = $webhook_data['data']['cjOrderId'] ?? null;
    $tracking_number = $webhook_data['data']['trackingNumber'] ?? null;
    
    if (!$cj_order_id || !$tracking_number) {
        return;
    }
    
    // Find WooCommerce order by CJ order ID
    $args = [
        'meta_query' => [
            [
                'key' => '_cj_order_id',
                'value' => $cj_order_id,
            ]
        ],
        'limit' => 1,
    ];
    
    $orders = wc_get_orders($args);
    
    if (empty($orders)) {
        return;
    }
    
    $order_id = $orders[0]->get_id();
    
    // Check if email already sent
    $email_sent = get_post_meta($order_id, '_tracking_email_sent', true);
    
    if ($email_sent) {
        return; // Already sent
    }
    
    $carrier = $webhook_data['data']['carrier'] ?? $webhook_data['data']['waybillCode'] ?? '';
    
    // Send email
    if (cw_cj_send_tracking_email($order_id, $tracking_number, $carrier)) {
        // Mark as sent
        update_post_meta($order_id, '_tracking_email_sent', true);
        error_log('CJ Tracking Email: Sent for order ' . $order_id . ', tracking: ' . $tracking_number);
    }
}

// ==================== ADMIN NOTIFICATION ====================

/**
 * Add tracking info to WooCommerce admin order emails
 */
add_filter('woocommerce_email_order_meta_fields', 'cw_cj_add_tracking_to_admin_email', 10, 1);
function cw_cj_add_tracking_to_admin_email($fields) {
    // This is for admin emails
    return $fields;
}

/**
 * Display tracking in customer completed email
 */
add_action('woocommerce_email_after_order_table', 'cw_cj_email_tracking_info', 10, 4);
function cw_cj_email_tracking_info($order, $sent_to_admin, $plain_text, $email) {
    // Only add to customer completed/shipped emails
    if ($sent_to_admin || !in_array($email->id, ['customer_completed_order', 'customer_processing_order'])) {
        return;
    }
    
    $tracking_info = cw_cj_get_order_tracking_info($order->get_id());
    
    if (!$tracking_info['has_tracking']) {
        return;
    }
    
    if ($plain_text) {
        echo "\n==========================================\n";
        echo "TRACKING INFORMATION\n";
        echo "==========================================\n";
        echo "Tracking Number: " . $tracking_info['tracking_number'] . "\n";
        if ($tracking_info['carrier']) {
            echo "Carrier: " . $tracking_info['carrier'] . "\n";
        }
        if ($tracking_info['tracking_url']) {
            echo "Track: " . $tracking_info['tracking_url'] . "\n";
        }
    } else {
        echo '<div style="border: 1px solid #ddd; padding: 12px; background: #f9f9f9; border-radius: 4px; margin: 12px 0;">';
        echo '<h3 style="margin-top: 0; color: #ff4d4f;">📦 Tracking Information</h3>';
        echo '<p><strong>Tracking Number:</strong> ' . esc_html($tracking_info['tracking_number']) . '</p>';
        if ($tracking_info['carrier']) {
            echo '<p><strong>Carrier:</strong> ' . esc_html($tracking_info['carrier']) . '</p>';
        }
        if ($tracking_info['tracking_url']) {
            echo '<p><a href="' . esc_url($tracking_info['tracking_url']) . '" target="_blank" style="color: #ff4d4f; text-decoration: none; font-weight: 600;">View Tracking →</a></p>';
        }
        echo '</div>';
    }
}

// ==================== SHORTCODE ====================

/**
 * Shortcode to display tracking info anywhere
 * Usage: [cj_tracking order_id="123"]
 */
add_shortcode('cj_tracking', 'cw_cj_tracking_shortcode');
function cw_cj_tracking_shortcode($atts) {
    $atts = shortcode_atts([
        'order_id' => get_the_ID(),
    ], $atts);
    
    $order_id = intval($atts['order_id']);
    
    if (!$order_id) {
        return '';
    }
    
    $tracking_info = cw_cj_get_order_tracking_info($order_id);
    
    if (!$tracking_info['has_tracking']) {
        return '<p>No tracking information available yet.</p>';
    }
    
    return cw_cj_render_tracking_display($tracking_info);
}

// ==================== ADMIN NOTES ====================

/**
 * Add tracking info to WooCommerce order notes automatically
 */
add_action('cj_webhook_received', 'cw_cj_add_tracking_order_note', 10, 1);
function cw_cj_add_tracking_order_note($webhook_data) {
    $event_type = $webhook_data['eventType'] ?? '';
    
    if ($event_type !== 'LOGISTICS') {
        return;
    }
    
    $cj_order_id = $webhook_data['data']['cjOrderId'] ?? null;
    $tracking_number = $webhook_data['data']['trackingNumber'] ?? null;
    $carrier = $webhook_data['data']['carrier'] ?? $webhook_data['data']['waybillCode'] ?? '';
    $status = $webhook_data['data']['status'] ?? '';
    
    if (!$cj_order_id) {
        return;
    }
    
    // Find WooCommerce order
    $args = [
        'meta_query' => [
            [
                'key' => '_cj_order_id',
                'value' => $cj_order_id,
            ]
        ],
        'limit' => 1,
    ];
    
    $orders = wc_get_orders($args);
    
    if (empty($orders)) {
        return;
    }
    
    $order = $orders[0];
    
    $note = 'CJ Tracking Update: ';
    if ($carrier) {
        $note .= 'Carrier=' . $carrier . ' ';
    }
    if ($tracking_number) {
        $note .= 'Tracking=' . $tracking_number . ' ';
    }
    if ($status) {
        $note .= 'Status=' . $status;
    }
    
    $order->add_order_note(trim($note), false); // Don't notify customer, we'll send dedicated email
}
