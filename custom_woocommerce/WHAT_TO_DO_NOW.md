# 🚀 CJ Integration - What You Can Do NOW

## ✅ Status: Integration Installed & Ready

All code is installed in your theme. Here's your action plan:

---

## 🎯 IMMEDIATE ACTIONS (Do This First)

### Action 1: Get Your CJ API Key (2 minutes)

1. **Go to:** https://developer.cjdropshipping.com/account/info
2. **Log in** with your CJ account
3. **Copy your API Key** (looks like: `CJUserNum@api@xxxxxxxxxxxxxxxxxxxxxxxx`)
4. **That's all you need!** 🎉

> ⚠️ **Don't have a CJ account?**  
> Sign up at: https://cjdropshipping.com/register

---

### Action 2: Add API Key to WordPress (1 minute)

1. **Go to WordPress Admin:** http://localhost/wordpress/wp-admin
2. **Navigate to:** WooCommerce > **CJ Dropshipping** (new menu item)
3. **Paste your API Key:**
   - `CJUserNum@api@xxxxxxxxxxxxxxxxxxxxxxxx`
4. **Click:** Save CJ Credentials
5. **Success!** You should see your account balance displayed

**That's it! No secret key needed!** ✅

**Screenshot of what you'll see:**
```
┌─────────────────────────────────────────┐
│ CJ Account Balance: $1,234.56           │
└─────────────────────────────────────────┘

API Key: [CJUserNum@api@xxxxxxxxxxxxxxxx]
Platform Token: [optional]

[Save CJ Credentials]
```

---

### Action 3: Test the Connection (1 minute)

**Open this URL in your browser:**
```
http://localhost/wordpress/wp-admin/admin-ajax.php?action=cw_cj_test
```

**Expected response:**
```json
{
  "success": true,
  "message": "Connected to CJ API",
  "balance": 1234.56
}
```

✅ **If you see this = you're connected!**

---

## 🛠️ NEXT ACTIONS (Setup Automation)

### Action 4: Enable Webhook for Auto-Tracking (3 minutes)

When CJ ships orders, tracking numbers will automatically appear in WooCommerce.

**Setup:**
1. **Go to:** https://developer.cjdropshipping.com/settings/webhook
2. **Click:** Add Webhook
3. **Paste this URL:**
   ```
   https://your-ngrok-url/wordpress/wp-json/cj-dropshipping/v1/webhook
   ```
   (Replace with your actual domain when live)
4. **Enable these event types:**
   - ✅ LOGISTICS (tracking numbers)
   - ✅ ORDER (order status)
5. **Save**

**What this does:**
- Order ships → CJ sends tracking number → WordPress automatically updates order
- Customer gets tracking email automatically
- No manual work!

---

## 📦 PRODUCT SETUP OPTIONS

You have 3 ways to work with CJ products:

### Option A: Manual Dropshipping (Simplest)

**How it works:**
1. Customer orders on your site
2. You manually create order in CJ
3. You manually add tracking to WooCommerce

**Best for:** Testing, low volume

**No setup needed!** Just sell and fulfill manually.

---

### Option B: Automatic Order Creation (Recommended)

**How it works:**
1. Customer orders on your site
2. **Order automatically goes to CJ** ✨
3. **Automatically paid from CJ balance** ✨
4. **Tracking automatically synced** ✨

**Setup Required:** Link your products to CJ catalog

**To enable:**

When adding products to WooCommerce, add this custom field:

```
Field Name: _cj_variant_id
Value: [CJ variant ID from catalog]
```

**How to get CJ variant IDs:**

**Method 1 - Via WordPress (easiest):**
```php
// In WordPress, open Tools > Theme Functions Editor
// Or add to your child theme's functions.php

add_action('admin_init', function() {
    if (isset($_GET['search_cj'])) {
        $cj = cw_cj_dropshipping();
        $products = $cj->list_products([
            'keyWord' => $_GET['search_cj'],
            'page' => 1,
            'size' => 20
        ]);
        
        echo '<pre>';
        print_r($products);
        echo '</pre>';
        die;
    }
});
```

Then visit:
```
http://localhost/wordpress/wp-admin/?search_cj=hoodie
```

**Method 2 - Via Code:**
```php
$cj = cw_cj_dropshipping();

// Search CJ catalog
$products = $cj->list_products([
    'keyWord' => 'hoodie',
    'page' => 1,
    'size' => 20
]);

// Shows all products with variant IDs
foreach ($products['content'][0]['productList'] as $product) {
    echo $product['nameEn'] . ": " . $product['id'] . "\n";
}
```

**Method 3 - CJ Website:**
1. Browse https://cjdropshipping.com/products
2. Click product
3. Copy product ID from URL or page

---

### Option C: Full Catalog Import (Advanced)

Import entire CJ catalog into WooCommerce.

**Run this code once** (in WordPress > Tools > Theme Functions Editor):

```php
add_action('wp_ajax_import_cj_products', function() {
    $cj = cw_cj_dropshipping();
    
    // Get products from CJ
    $result = $cj->list_products([
        'keyWord' => '',  // Empty = all products
        'page' => 1,
        'size' => 50,
        'categoryId' => '', // Filter by category
        'countryCode' => 'US', // Products with US inventory
    ]);
    
    $imported = 0;
    
    foreach ($result['content'] as $item) {
        foreach ($item['productList'] as $product) {
            // Create WooCommerce product
            $post_id = wp_insert_post([
                'post_type' => 'product',
                'post_title' => $product['nameEn'],
                'post_status' => 'publish',
                'post_content' => $product['description'] ?? '',
            ]);
            
            // Set price
            update_post_meta($post_id, '_regular_price', $product['sellPrice']);
            update_post_meta($post_id, '_price', $product['sellPrice']);
            
            // Link to CJ
            update_post_meta($post_id, '_cj_product_id', $product['id']);
            
            // Get first variant
            $variants = $cj->get_variants($product['id']);
            if (!empty($variants)) {
                update_post_meta($post_id, '_cj_variant_id', $variants[0]['vid']);
            }
            
            $imported++;
        }
    }
    
    echo "Imported: $imported products";
    wp_die();
});
```

Then visit:
```
http://localhost/wordpress/wp-admin/admin-ajax.php?action=import_cj_products
```

---

## 💰 PRICING STRATEGIES

### Strategy 1: Fixed Markup
Add 50% to CJ price:
```php
$cj_price = 10.00;
$your_price = $cj_price * 1.5; // $15.00
```

### Strategy 2: Tiered Markup
```php
if ($cj_price < 10) {
    $your_price = $cj_price * 2.0; // 100% markup
} else if ($cj_price < 50) {
    $your_price = $cj_price * 1.5; // 50% markup
} else {
    $your_price = $cj_price * 1.3; // 30% markup
}
```

### Strategy 3: Fixed Amount
Add $5 to every product:
```php
$your_price = $cj_price + 5;
```

---

## 🎬 SAMPLE WORKFLOW (Full Automation)

This is what happens when everything is set up:

1. **Customer browses your site**
   - Sees products (from CJ catalog or custom)
   - Adds to cart
   - Checks out

2. **Order placed** → WooCommerce order created

3. **Automatic (no action needed):**
   - ✓ Order sent to CJ
   - ✓ Paid from your CJ balance
   - ✓ CJ processes order
   - ✓ CJ ships order

4. **Webhook fires:**
   - ✓ Tracking number added to WooCommerce
   - ✓ Customer gets tracking email
   - ✓ Order marked as shipped

5. **You profit!** 💰

**Your only job:** Marketing and customer service

---

## 🔍 TESTING WORKFLOW

Before going live, test the system:

### Test 1: Check Balance
```php
$cj = cw_cj_dropshipping();
$balance = $cj->get_balance();
echo "Balance: $" . $balance;
```

### Test 2: Search Products
```php
$cj = cw_cj_dropshipping();
$products = $cj->list_products(['keyWord' => 'mug']);
print_r($products);
```

### Test 3: Check Inventory
```php
$cj = cw_cj_dropshipping();
$stock = $cj->get_inventory_by_sku('CJWJWJYZ02543');
print_r($stock);
```

### Test 4: Manual Order Creation
```php
$cj = cw_cj_dropshipping();

$order_data = [
    'order_number' => 'TEST-001',
    'country_code' => 'US',
    'country' => 'United States',
    'state' => 'CA',
    'city' => 'Los Angeles',
    'phone' => '5551234567',
    'first_name' => 'Test',
    'last_name' => 'Customer',
    'address_1' => '123 Test St',
    'postcode' => '90001',
    'email' => 'test@example.com',
];

$products = [
    [
        'vid' => 'YOUR-VARIANT-ID-HERE',
        'quantity' => 1,
    ]
];

$result = $cj->create_order($order_data, $products, 3); // payType=3 (no payment)
print_r($result);
```

---

## 📊 MONITORING & MANAGEMENT

### Check Order Status

**In WordPress:**
```php
$woo_order_id = 123;
$cj_order_id = CJ_Dropshipping::get_cj_order_id($woo_order_id);

if ($cj_order_id) {
    $cj = cw_cj_dropshipping();
    $order = $cj->get_order($cj_order_id);
    
    echo "Status: " . $order['orderStatus'];
    echo "Tracking: " . $order['trackNumber'];
}
```

**Order Statuses:**
- `CREATED` - Just created
- `IN_CART` - In cart
- `UNPAID` - Confirmed but not paid
- `UNSHIPPED` - Paid, waiting to ship
- `SHIPPED` - In transit (has tracking)
- `DELIVERED` - Delivered to customer
- `CANCELLED` - Cancelled

### Check Sync Issues

Look at WooCommerce order notes:
```
WooCommerce > Orders > [Select Order] > Order Notes
```

You'll see:
- "CJ Order Created: SD12345..."
- "CJ Tracking Update: Status=SHIPPED..."
- Any errors or issues

---

## 🐛 TROUBLESHOOTING

### Issue: "Failed to verify credentials"

**Solution:**
1. Copy your API Key again from CJ (full string)
2. Remove any spaces
3. Try pasting in a text editor first to verify
4. Format should be: `CJUserNum@api@xxxxxxxxxxxxxxxxxxxxxxxx`

### Issue: "Orders not auto-creating"

**Checklist:**
- ✓ CJ credentials saved?
- ✓ Products have `_cj_variant_id` meta?
- ✓ WooCommerce order reached "Processing" status?

**Debug:**
Enable logging:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check:
```
/wp-content/debug.log
```

### Issue: "No tracking numbers"

**Checklist:**
- ✓ Webhook URL configured in CJ?
- ✓ Webhook URL publicly accessible?
- ✓ Order actually shipped by CJ?

**Test webhook manually:**
```bash
curl -X POST http://localhost/wordpress/wp-json/cj-dropshipping/v1/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "eventType": "LOGISTICS",
    "data": {
      "cjOrderId": "YOUR-CJ-ORDER-ID",
      "trackingNumber": "TEST123",
      "status": "SHIPPED"
    }
  }'
```

---

## 📚 USEFUL CODE SNIPPETS

### Get CJ Categories
```php
$cj = cw_cj_dropshipping();
$categories = $cj->get_categories();
print_r($categories);
```

### Get Product Details
```php
$cj = cw_cj_dropshipping();
$product = $cj->get_product_details('PRODUCT-ID-HERE');
echo $product['productNameEn'];
echo $product['sellPrice'];
print_r($product['variants']);
```

### Check Real-Time Inventory
```php
$cj = cw_cj_dropshipping();
$inventory = $cj->get_inventory_by_vid('VARIANT-ID-HERE');

foreach ($inventory as $warehouse) {
    echo $warehouse['areaEn'] . ": " . $warehouse['totalInventoryNum'] . "\n";
}
```

### List All Your Orders
```php
$cj = cw_cj_dropshipping();
$orders = $cj->list_orders([
    'pageNum' => 1,
    'pageSize' => 50,
    'status' => 'SHIPPED', // or 'CREATED', 'UNSHIPPED', etc.
]);

print_r($orders);
```

---

## 🎓 LEARNING PATH

**Day 1-2:** Setup
- ✓ Get your API Key from CJ
- ✓ Add to WordPress
- ✓ Test connection

**Day 3-5:** Product Setup
- ✓ Add 5-10 test products
- ✓ Link to CJ variants
- ✓ Set pricing

**Day 6-7:** Order Testing
- ✓ Make test order
- ✓ Verify CJ order created
- ✓ Check payment deducted

**Week 2:** Automation
- ✓ Configure webhook
- ✓ Test tracking sync
- ✓ Monitor automatic flow

**Week 3+:** Scale
- ✓ Import more products
- ✓ Optimize pricing
- ✓ Monitor margins

---

## 💡 PRO TIPS

### Tip 1: Where to Get API Key
```
https://developer.cjdropshipping.com/account/info
→ Click "Generate" to create new API Key
→ Copy the key (format: CJUserNum@api@xxxxxxxx...)
```

That's all you need! ✅
- Shipping variations
- Returns/refunds
- Marketing costs
- Platform fees

### Tip 2: Stock Monitoring
Check inventory before marketing:
```php
$stock = cw_cj_get_live_inventory($variant_id);
if ($stock < 10) {
    // Don't run ads for this product
}
```

### Tip 3: Order Notes
Always check WooCommerce order notes - they contain:
- CJ order ID
- Payment status
- Tracking updates
- Any errors

### Tip 4: Balance Alerts
Set up daily balance check:
```php
add_action('wp', function() {
    if (date('H') === '09') { // 9 AM
        $balance = cw_cj_dropshipping()->get_balance();
        if ($balance < 100) {
            wp_mail('you@example.com', 'Low CJ Balance', "Balance: $$balance");
        }
    }
});
```

---

## 🚀 GO LIVE CHECKLIST

Before launching to real customers:

- [ ] CJ API Key configured and verified
- [ ] Webhook URL configured (with real domain, not localhost)
- [ ] Connection test passes (shows balance)
- [ ] 10+ products tested
- [ ] Test order placed and fulfilled
- [ ] Tracking number received
- [ ] Balance sufficient ($500+ recommended)
- [ ] Email notifications working
- [ ] Pricing strategy confirmed
- [ ] Backup plan for out-of-stock items

---

## 📞 SUPPORT RESOURCES

**CJ Support:**
- Docs: https://developer.cjdropshipping.com
- Support: https://support.cjdropshipping.com
- Live Chat: https://cjdropshipping.com (bottom right)

**WordPress Logs:**
- Error Log: `/wp-content/debug.log`
- Order Notes: WooCommerce > Orders > [Order] > Notes

**Integration Files:**
- Settings: `WooCommerce > CJ Dropshipping`
- Code: `/wp-content/themes/builtin_themes/custom_woocommerce/`
  - `class-cj-dropshipping.php` - API client
  - `cj-integration-hooks.php` - WordPress integration
  - `CJ_INTEGRATION_README.md` - Full docs

---

## ⚡ QUICK REFERENCE

**Start integration:**
```php
$cj = cw_cj_dropshipping();
```

**Check if configured:**
```php
if (CJ_Dropshipping::has_credentials()) {
    // Ready to use
}
```

**Most used functions:**
```php
$balance = $cj->get_balance();
$products = $cj->list_products(['keyWord' => 'search']);
$product = $cj->get_product_details($pid);
$inventory = $cj->get_inventory_by_vid($vid);
$result = $cj->create_order($order_data, $products);
```

**Get WooCommerce → CJ mapping:**
```php
$cj_order_id = CJ_Dropshipping::get_cj_order_id($woo_order_id);
```

---

**Version:** 1.0.0  
**Date:** February 6, 2026  
**Status:** ✅ Ready for Production

🎉 **You're all set! Grab your API Key and add it to WordPress!** 🎉
