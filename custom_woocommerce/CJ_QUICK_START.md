# CJ Dropshipping Integration - Quick Start

## What's New?

Your WordPress theme now has a **complete custom CJ Dropshipping integration** built-in, without any external plugins!

### Files Added:
1. `class-cj-dropshipping.php` - Core API client (800+ lines)
2. `cj-integration-hooks.php` - WordPress integration & admin pages (400+ lines)
3. `CJ_INTEGRATION_README.md` - Full documentation
4. Modified `functions.php` - Loads the integration

## Get Started in 3 Minutes

### Step 1: Add Your CJ Credentials

1. WordPress Admin → **WooCommerce > CJ Dropshipping**
2. Enter your CJ API Key (from [CJ Developer Account](https://developer.cjdropshipping.com/account/info))
3. Enter your CJ API Secret
4. Click **Save CJ Credentials**

You should see your account balance displayed!

### Step 2: Set Up Webhook Notifications

1. Go to [CJ Developer > Webhooks](https://developer.cjdropshipping.com/settings/webhook)
2. Add this URL:
   ```
   https://your-site.com/wp-json/cj-dropshipping/v1/webhook
   ```
3. Enable: **LOGISTICS** and **ORDER** notifications
4. Save

Now your orders will automatically get tracking numbers!

### Step 3: Link Your Products to CJ (Optional)

When products are linked to CJ catalog items, orders will auto-create:

```php
// In product setup or bulk import
cw_cj_save_product_mapping(
    $woo_product_id,
    'CJ_PRODUCT_ID',
    'CJ_VARIANT_ID'
);
```

## How It Works

**When customer orders on your site:**
1. ✓ WooCommerce order created
2. ✓ Auto-creates CJ order with shipping address
3. ✓ Auto-confirms order in CJ
4. ✓ Auto-deducts cost from your CJ balance
5. ✓ Receives tracking number via webhook
6. ✓ Updates WooCommerce order with tracking

**All automated!** No manual steps needed.

## Essential Functions

```php
// Initialize CJ integration
$cj = cw_cj_dropshipping();

// Search CJ catalog
$products = $cj->list_products(['keyWord' => 'hoodie']);

// Get product details with inventory
$product = $cj->get_product_details($product_id);

// Check live inventory
$inventory = $cj->get_inventory_by_vid($variant_id);

// Get account balance
$balance = $cj->get_balance();

// Get/link CJ order in WooCommerce
$cj_order_id = CJ_Dropshipping::get_cj_order_id($woo_order_id);
```

## What Can You Do Now?

✓ **Automatic Order Processing** - Orders flow to CJ automatically  
✓ **Real-Time Inventory** - Check stock before selling  
✓ **Auto Tracking** - Customers get tracking numbers automatically  
✓ **Balance Management** - See your CJ account balance in WordPress  
✓ **Product Catalog** - Browse and import CJ products  
✓ **Order History** - View all orders and statuses  

## Admin Page Features

Go to: **WooCommerce > CJ Dropshipping**

- 📊 Account balance display
- 🔐 Credential management
- 🔗 Webhook URL (copy to CJ account)
- ✅ Connection status verification
- 📝 Integration features list

## Testing Your Setup

Click this link in your browser (while logged in as admin):
```
https://your-site.com/wp-admin/admin-ajax.php?action=cw_cj_test
```

Should see:
```json
{
  "success": true,
  "message": "Connected to CJ API",
  "balance": 1234.56
}
```

## Troubleshooting

**"Failed to verify credentials"**
- Copy full API Key from CJ account (including all characters)
- No extra spaces!
- Check API Secret is correct

**"No orders auto-creating"**
- Make sure products have `_cj_variant_id` meta set
- Enable debug logging: `define('WP_DEBUG_LOG', true);`
- Check `/wp-content/debug.log` for errors

**"Not getting tracking updates"**
- Verify webhook URL is publicly accessible
- Test URL in browser - should return empty (that's expected)
- Check CJ webhook delivery logs

## Next Steps

1. ✓ Add CJ credentials
2. ✓ Configure webhook URL
3. ✓ Link your products to CJ catalog
4. ✓ Test with a sample order
5. ✓ Monitor first few orders in admin

## Need Help?

**For CJ Issues:**
- [CJ Developer Documentation](https://developer.cjdropshipping.com)
- [CJ Support Center](https://support.cjdropshipping.com)

**For WordPress Integration:**
- Check `wp-content/debug.log` for errors
- Check WooCommerce order notes for status updates
- Review webhook delivery logs in CJ account

## API Costs

✓ **FREE!** This integration uses no external services  
✓ Only CJ API usage (first 1,000 requests free/day)  
✓ No plugin subscriptions  
✓ **Full control** - no 3rd party middleman  

---

**Version:** 1.0.0  
**Status:** Production Ready  
**Last Updated:** 2025-02-06
