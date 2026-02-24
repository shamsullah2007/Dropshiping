<?php
/**
 * View Order with Tracking Info
 * 
 * Enhanced order details page with prominent tracking display
 * 
 * @package CustomWoocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$order = wc_get_order($post->ID);

if (!$order) {
    return;
}

$tracking_info = cw_cj_get_order_tracking_info($order->get_id());
$user_can_read_order = is_user_logged_in() && ( $order->get_user_id() === get_current_user_id() || current_user_can('manage_woocommerce') );

if (!$user_can_read_order) {
    return;
}
?>

<div class="woocommerce-account">
    <!-- Order Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e5e5e5;">
        <div>
            <h1 style="margin: 0; color: #333;"><?php esc_html_e('Order Details', 'woocommerce'); ?></h1>
            <p style="margin: 5px 0 0 0; color: #666;">
                <?php printf(esc_html__('Order #%d placed on %s', 'woocommerce'), $order->get_order_number(), wc_format_datetime($order->get_date_created())); ?>
            </p>
        </div>
        <div style="text-align: right;">
            <span class="woocommerce-order-status status-<?php echo esc_attr($order->get_status()); ?>">
                <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
            </span>
        </div>
    </div>

    <!-- Tracking Information (Prominent Display) -->
    <?php if ($tracking_info['has_tracking']): ?>
        <div class="cj-order-tracking-box">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px; display: flex; align-items: center;">
                <span style="font-size: 24px; margin-right: 10px;">📦</span>
                <?php esc_html_e('Real-Time Tracking Information', 'custom-woocommerce'); ?>
            </h3>

            <!-- Tracking Details Grid -->
            <div class="cj-order-tracking-grid">
                <div class="cj-tracking-item">
                    <label><?php esc_html_e('Tracking Number', 'custom-woocommerce'); ?></label>
                    <code style="margin: 0; padding: 8px; background: white; border-radius: 4px; display: block; border: 1px solid #e5e5e5;">
                        <?php echo esc_html($tracking_info['tracking_number']); ?>
                    </code>
                </div>

                <div class="cj-tracking-item">
                    <label><?php esc_html_e('Carrier', 'custom-woocommerce'); ?></label>
                    <p><?php echo esc_html($tracking_info['carrier'] ?? __('Standard Shipping', 'custom-woocommerce')); ?></p>
                </div>
            </div>

            <!-- Status Progress -->
            <div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0; border: 1px solid #e5e5e5;">
                <label style="display: block; color: #666; font-size: 12px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase;"><?php esc_html_e('Shipping Status', 'custom-woocommerce'); ?></label>
                
                <?php
                $status_map = [
                    'pending' => __('Order Received', 'custom-woocommerce'),
                    'processing' => __('Processing Your Order', 'custom-woocommerce'),
                    'shipped' => __('In Transit', 'custom-woocommerce'),
                    'completed' => __('Delivered', 'custom-woocommerce'),
                    'delivered' => __('Delivered', 'custom-woocommerce'),
                ];
                $current_status = $tracking_info['status'] ?? $order->get_status();
                $status_label = $status_map[$current_status] ?? ucfirst(str_replace('-', ' ', $current_status));
                ?>
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        background: #ff4d4f;
                        color: white;
                        font-weight: 600;
                        font-size: 16px;
                    ">✓</span>
                    <span style="color: #333; font-weight: 600; font-size: 15px;"><?php echo esc_html($status_label); ?></span>
                </div>
            </div>

            <!-- Tracking Button -->
            <?php if ($tracking_info['tracking_url']): ?>
                <a href="<?php echo esc_url($tracking_info['tracking_url']); ?>" target="_blank" rel="noopener noreferrer" class="cj-tracking-btn">
                    <?php esc_html_e('Track Package on Carrier Website →', 'custom-woocommerce'); ?>
                </a>
            <?php endif; ?>

            <!-- Info Message -->
            <p style="margin: 15px 0 0 0; font-size: 13px; color: #666;">
                <?php esc_html_e('Tracking updates are automatically sent to your email when your package changes status.', 'custom-woocommerce'); ?>
            </p>
        </div>
    <?php else: ?>
        <div style="background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 4px; padding: 15px; margin-bottom: 30px; color: #666;">
            <p style="margin: 0;">
                <span style="font-size: 16px; margin-right: 8px;">⏳</span>
                <?php esc_html_e('Your tracking information will be available soon. We will send you an email as soon as your order ships!', 'custom-woocommerce'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Order Timeline -->
    <div class="cj-order-timeline">
        <h4><?php esc_html_e('Order Timeline', 'custom-woocommerce'); ?></h4>
        <?php
        $timeline_steps = [
            ['status' => 'pending', 'label' => __('Received', 'custom-woocommerce'), 'icon' => '✓'],
            ['status' => 'processing', 'label' => __('Processing', 'custom-woocommerce'), 'icon' => '⚙'],
            ['status' => ['shipped', 'on-hold'], 'label' => __('Shipped', 'custom-woocommerce'), 'icon' => '📦'],
            ['status' => ['completed', 'delivered'], 'label' => __('Delivered', 'custom-woocommerce'), 'icon' => '✓'],
        ];

        $order_status = $order->get_status();
        $completed_steps = 0;

        // Determine how many steps are completed
        foreach ($timeline_steps as $index => $step) {
            $step_statuses = is_array($step['status']) ? $step['status'] : [$step['status']];
            if (in_array($order_status, $step_statuses)) {
                $completed_steps = $index + 1;
                break;
            }
        }
        ?>

        <div class="cj-timeline-container">
            <?php foreach ($timeline_steps as $index => $step): ?>
                <?php $is_completed = $index < $completed_steps; ?>
                <div class="cj-timeline-step">
                    <div class="cj-timeline-icon <?php echo $is_completed ? 'active' : 'inactive'; ?>">
                        <?php echo esc_html($step['icon']); ?>
                    </div>
                    <div class="cj-timeline-label <?php echo $is_completed ? 'active' : ''; ?>">
                        <?php echo esc_html($step['label']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div style="margin-top: 30px;">
        <h3><?php esc_html_e('Order Items', 'woocommerce'); ?></h3>
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <tr>
                    <th class="woocommerce-table__product-name product-name"><?php esc_html_e('Product', 'woocommerce'); ?></th>
                    <th class="woocommerce-table__product-table product-quantity"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
                    <th class="woocommerce-table__product-table product-subtotal"><?php esc_html_e('Total', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($order->get_items() as $item_id => $item) {
                    $product = $item->get_product();
                    ?>
                    <tr class="woocommerce-table__product-row order_item">
                        <td class="woocommerce-table__product-name product-name">
                            <?php
                            if ($product) {
                                echo wp_kses_post($product->get_formatted_name());
                            } else {
                                echo wp_kses_post($item->get_name());
                            }
                            ?>
                        </td>
                        <td class="woocommerce-table__product-table product-quantity">
                            <?php echo esc_html($item->get_quantity()); ?>
                        </td>
                        <td class="woocommerce-table__product-table product-subtotal">
                            <?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
        
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th colspan="2"><?php esc_html_e('Order Summary', 'woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Subtotal:', 'woocommerce'); ?></td>
                    <td><?php echo wp_kses_post($order->get_subtotal_to_display()); ?></td>
                </tr>
                <?php
                foreach ($order->get_items('shipping') as $item_id => $item) {
                    ?>
                    <tr>
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
                    </tr>
                    <?php
                }
                ?>
                <?php
                foreach ($order->get_items('fee') as $item_id => $item) {
                    ?>
                    <tr>
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td><?php esc_html_e('Total:', 'woocommerce'); ?></td>
                    <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Shipping Address -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        <div>
            <h3><?php esc_html_e('Shipping Address', 'woocommerce'); ?></h3>
            <p style="margin: 0;">
                <?php
                echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '<br>';
                echo esc_html($order->get_billing_address_1()) . '<br>';
                if ($order->get_billing_address_2()) {
                    echo esc_html($order->get_billing_address_2()) . '<br>';
                }
                echo esc_html($order->get_billing_city() . ', ' . $order->get_billing_state() . ' ' . $order->get_billing_postcode()) . '<br>';
                echo esc_html(WC()->countries->countries[$order->get_billing_country()] ?? '');
                ?>
            </p>
        </div>
        <div>
            <h3><?php esc_html_e('Billing Address', 'woocommerce'); ?></h3>
            <p style="margin: 0;">
                <?php
                echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '<br>';
                echo esc_html($order->get_billing_address_1()) . '<br>';
                if ($order->get_billing_address_2()) {
                    echo esc_html($order->get_billing_address_2()) . '<br>';
                }
                echo esc_html($order->get_billing_city() . ', ' . $order->get_billing_state() . ' ' . $order->get_billing_postcode()) . '<br>';
                echo esc_html(WC()->countries->countries[$order->get_billing_country()] ?? '');
                ?>
            </p>
        </div>
    </div>

    <!-- Order Notes -->
    <?php
    $notes = $order->get_customer_order_notes();
    if (!empty($notes)):
        ?>
        <div style="margin-top: 30px; border-top: 1px solid #e5e5e5; padding-top: 20px;">
            <h3><?php esc_html_e('Order Notes', 'woocommerce'); ?></h3>
            <?php
            foreach ($notes as $note):
                ?>
                <div style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-left: 3px solid #ff4d4f; border-radius: 4px;">
                    <p style="margin: 0 0 5px 0; font-size: 12px; color: #999;">
                        <?php echo wp_kses_post(wp_format_datetime($note->comment_date)); ?>
                    </p>
                    <p style="margin: 0; color: #333;">
                        <?php echo wp_kses_post($note->comment_content); ?>
                    </p>
                </div>
                <?php
            endforeach;
            ?>
        </div>
    <?php endif; ?>

    <!-- Back Link -->
    <div style="margin-top: 30px;">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" style="color: #ff4d4f; text-decoration: none; font-weight: 600;">
            ← <?php esc_html_e('Back to Orders', 'woocommerce'); ?>
        </a>
    </div>
</div>
