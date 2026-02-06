# CJ Dropshipping Order Sync - Troubleshooting Guide

## Problem
Orders are being created in WooCommerce but not synced to CJ Dropshipping.

## Root Cause
The order sync is triggered when an order reaches **"processing"** status. If your orders stay in "pending" or don't reach "processing" automatically, they won't be synced.

---

## Quick Fixes

### 1. **Check Your Debug Log** (First Step)
```
File: /wp-content/debug.log
```

**What to look for:**
- Search for "CJ Order" messages
- Check if orders trigger the processing hook
- Look for any API errors or credential issues

**To enable debug logging** in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

### 2. **Use the Debug Tool**
Visit in your browser:
```
https://your-site.com/wp-content/themes/custom_woocommerce/debug-cj-orders.php
```

This will check:
- ✓ CJ API credentials configuration
- ✓ Account balance and API connection
- ✓ Recent orders and their CJ sync status
- ✓ Products have CJ IDs set
- ✓ WordPress error log entries

---

### 3. **Manual Order Sync**
Two methods:

#### Method A: WooCommerce Order Page (Easiest)
1. Go to **WooCommerce > Orders**
2. Open any "processing" status order
3. Click the **"Sync to CJ Dropshipping"** button
4. Order will sync immediately

#### Method B: Database Query
```sql
-- Get orders that haven't been synced
SELECT ID, post_modified 
FROM wp_posts p
WHERE post_type = 'shop_order'
AND post_status = 'wc-processing'
AND ID NOT IN (
    SELECT post_id FROM wp_postmeta 
    WHERE meta_key = '_cj_order_id'
)
ORDER BY post_modified DESC;
```

---

### 4. **Check If Products Have CJ IDs**
In WooCommerce, edit a product used in an order:

1. Go to **Products**
2. Edit the product
3. Scroll to **"CJ Product ID"** and **"CJ Variant ID"** fields
4. If empty, you need to import products from CJ catalog first

**To import products:**
1. Go to **WooCommerce > CJ Dropshipping**
2. Search for products from CJ catalog
3. Import them with variants
4. Verify CJ IDs are populated

---

### 5. **Check Order Status**
Your orders must be in "processing" for auto-sync to work.

```php
// From WordPress admin PHP, check order status:
$order = wc_get_order(123); // Replace 123 with order ID
echo $order->get_status(); // Should print "processing"
```

**Common issues:**
- Orders stuck in **"pending"** → Payment gateway not updating status
- Orders in **"on-hold"** → Manual review required before processing

**Solution:**
- Change order status to "processing` manually in admin
- Use the manual sync button once status is updated

---

### 6. **Verify CJ API Credentials**
1. Go to **WooCommerce > CJ Dropshipping** admin page
2. Verify the API Key is entered correctly
3. Test the connection (if test button exists)
4. Check your CJ account at https://developers.cjdropshipping.com

**To get API key:**
1. Log in to CJ Dropshipping account
2. Go to **Settings > API Key**
3. Copy your API Key
4. Paste into WooCommerce > CJ Dropshipping settings

---

### 7. **Check for Errors in Order Note**
After you manually set an order to processing or click sync:

1. Open the order in WooCommerce
2. Look at **Order Notes** section
3. Check for messages like:
   - ❌ **"No CJ products found"** → Products don't have CJ IDs set
   - ❌ **"CJ order creation failed"** → Check debug log for API error
   - ✓ **"CJ Order Created & Paid"** → Success!

---

## Advanced Troubleshooting

### Check WooCommerce Payment Method
```php
// In wp-admin-page or custom code:
$order = wc_get_order(123);
echo $order->get_payment_method(); // Should show payment method
```

**Common payment methods that auto-transition to processing:**
- `woocommerce_payments` (WC Payments)
- `stripe` (Stripe)
- `paypal_commerce` (PayPal)

**Methods that DON'T auto-process:**
- `cod` (Cash on Delivery) → Order stays "pending" until manually marked
- `bacs` (Bank Transfer) → Requires manual payment confirmation
- `check` (Check Payment) → Requires manual verification

**Fix for non-auto payment methods:**
```php
// Add this to functions.php to auto-process certain payment methods:
add_action('woocommerce_thankyou', function($order_id) {
    $order = wc_get_order($order_id);
    if ($order && $order->get_payment_method() === 'cod') {
        if ($order->get_status() === 'pending') {
            $order->set_status('processing');
            $order->save();
        }
    }
});
```

---

### Manually Trigger Sync via Code
```php
// From admin or scheduled action:
do_action('cw_cj_manual_sync_order', 123); // 123 = order ID
```

Or with WordPress CLI:
```bash
wp eval 'do_action("cw_cj_manual_sync_order", 123);'
```

---

### Enable Extended Logging
Add this to your theme's `functions.php`:

```php
// Log all CJ interactions
add_action('cw_cj_manual_sync_order', function($order_id) {
    error_log('CJ MANUAL SYNC TRIGGERED: Order ' . $order_id);
});

// Log payment complete
add_action('woocommerce_payment_complete', function($order_id) {
    error_log('PAYMENT COMPLETE: Order ' . $order_id);
});
```

---

## Checklist

- [ ] CJ Dropshipping plugin/theme is active
- [ ] CJ API Key is entered in **WooCommerce > CJ Dropshipping**
- [ ] API connection test passes (in debug tool)
- [ ] Products are imported from CJ catalog with CJ IDs set
- [ ] Orders reach "processing" status (check manually or auto-transition)
- [ ] debug.log shows "CJ Order: Created successfully" message
- [ ] User has WooCommerce "manage_options" capability (admin user)

---

## Getting Help

1. **Check the debug tool** at `/wp-content/themes/custom_woocommerce/debug-cj-orders.php`
2. **Review WordPress error log** at `/wp-content/debug.log`
3. **Check order notes** in WooCommerce order details
4. **Verify product CJ IDs** are set before creating test orders
5. **Test with manual sync button** on order page

---

## Contact CJ Dropshipping Support

If the API connection fails:
1. Visit https://developers.cjdropshipping.com/help
2. Verify your API Key hasn't expired
3. Check your API request limits
4. Confirm your account has sufficient balance
