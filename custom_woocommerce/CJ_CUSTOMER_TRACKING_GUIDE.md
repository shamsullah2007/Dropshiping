# CJ Dropshipping Customer Tracking Features

## Overview

Your theme now includes complete **real-time order tracking** features for customers. Orders automatically show:

- ✅ **Real-time tracking numbers** from CJ Dropshipping
- ✅ **Carrier tracking links** (USPS, UPS, FedEx, DHL, and more)
- ✅ **Email notifications** when orders ship
- ✅ **Order timeline** showing shipping progress
- ✅ **Tracking displayed on customer account page**

---

## What Customers See

### 1. **Order Details Page** (My Account)
When customers view their order, they see:

- 📦 **Tracking Information Box**
  - Tracking number (copyable)
  - Carrier name
  - Real-time shipping status
  - "Track Your Package" button (links to carrier website)

- **Order Timeline**
  - Visual progress: Received → Processing → Shipped → Delivered
  - Current order status

- **Order Summary**
  - Items ordered
  - Shipping address
  - Total cost
  - Order notes

### 2. **Email Notifications**
When an order ships, customers receive an email with:
- Tracking number
- Carrier information
- Direct link to carrier tracking
- Order summary

### 3. **Customer Dashboard**
Orders list shows tracking info:
- Quick view of tracking number
- View link for detailed tracking

---

## Automatic Features (No Setup Needed)

✅ **Automatic Email Alerts**
- Sent automatically when CJ webhook receives tracking number
- Only sent once per order (no duplicates)
- Includes direct carrier tracking link

✅ **Automatic Tracking Display**
- Shows on order details page
- Shows on customer dashboard
- Shows in WooCommerce emails

✅ **Automatic Carrier Links**
- Detects carrier type from CJ (USPS, UPS, FedEx, DHL, etc)
- Creates direct tracking links to carrier websites
- Works for all major carriers

✅ **Automatic Order Status**
- Updates with each CJ webhook notification
- Syncs carrier status to WooCommerce order status
- Adds order notes for record-keeping

---

## Supported Carriers

The system automatically creates tracking links for:

| Carrier | Website |
|---------|---------|
| **USPS** | www.usps.com |
| **UPS** | www.ups.com |
| **FedEx** | tracking.fedex.com |
| **DHL** | www.dhl.com |
| **Amazon Shipping** | tracking.amazon.com |
| **Yanwen Express** | yanwenexpress.com |
| **S.F. Express** | sf-express.com |
| **ZTO Express** | zto.com |
| And more... | |

---

## How It Works Behind the Scenes

### 1. **CJ Webhook → WooCommerce**
```
CJ Dropshipping webhook arrives
    ↓
Captures: tracking number, carrier, status
    ↓
Updates WooCommerce order
    ↓
Sends email to customer
    ↓
Updates order status & timeline
```

### 2. **Files Involved**

**Main tracking file:**
- `cj-customer-tracking.php` - All tracking functions

**Templates:**
- `woocommerce/myaccount/view-order.php` - Order details page with tracking display

**Styles:**
- `assets/css/cj-customer-tracking.css` - Tracking box styling

**Integration:**
- Hooks into existing CJ webhook system
- No external plugins needed
- No configuration required

---

## Testing Tracking Features

### Test 1: Check Order Details Page
1. Go to customer account → My Orders
2. Click any order with tracking number
3. You should see: **Tracking Information Box** at top

### Test 2: Check Email Notification
1. Place a test order
2. Wait for CJ webhook (or manually upload tracking via admin)
3. Customer should receive email with:
   - Subject: "Your Order #123 Has Shipped - Track It Now!"
   - Tracking number
   - Carrier name
   - Track button

### Test 3: Check Carrier Link
1. View order with tracking
2. Click "Track Your Package →" button
3. Should open carrier's tracking page

---

## Customer Touchpoints

### Where Customers See Tracking:

1. **Email**: Automated shipping notification
2. **Order Details Page**: Full tracking info
3. **Orders List**: Quick tracking preview
4. **Order Emails**: In WooCommerce transactional emails

---

## Developer Info

### Key Functions

**Get tracking info:**
```php
$tracking_info = cw_cj_get_order_tracking_info($order_id);

// Returns:
[
    'has_tracking' => true,
    'cj_order_id' => 'abc123',
    'tracking_number' => '1Z999AA10123456784',
    'carrier' => 'UPS',
    'status' => 'shipped',
    'tracking_url' => 'https://www.ups.com/track?tracknum=...'
]
```

**Get carrier tracking URL:**
```php
$url = cw_cj_get_carrier_tracking_url('USPS', '9400111899223456789012');
// Returns: https://tools.usps.com/go/TrackConfirmAction?tLabels=...
```

**Display tracking anywhere:**
```php
echo cw_cj_render_tracking_display($tracking_info);
```

**Use shortcode:**
```
[cj_tracking order_id="123"]
```

**Send email manually:**
```php
cw_cj_send_tracking_email($order_id, $tracking_number, $carrier);
```

### Hooks

**When tracking email is sent:**
```php
do_action('cj_tracking_email_sent', $order_id, $tracking_number);
```

**Filter tracking URL:**
```php
apply_filters('cj_tracking_url', $url, $carrier, $tracking_number);
```

---

## Customization

### Change Email Template
Edit function `cw_cj_send_tracking_email()` in `cj-customer-tracking.php`

### Change Tracking Display Style
Edit `assets/css/cj-customer-tracking.css`

### Add More Carriers
In `cw_cj_get_carrier_tracking_url()`, add to `$urls` array:
```php
'YOUR_CARRIER' => 'https://your-carrier.com/track?number=',
```

### Change Order Timeline Steps
Edit `view-order.php` timeline HTML section

---

## Troubleshooting

### Tracking Not Showing
1. ✅ Verify CJ webhook is configured in CJ account
2. ✅ Check webhook URL: `https://your-site.com/wp-json/cj-dropshipping/v1/webhook`
3. ✅ Check WooCommerce order meta: `_cj_order_id` should exist
4. ✅ Check error log: `/wp-content/debug.log`
5. ✅ Manually upload tracking number in admin for testing

### Email Not Sent
1. ✅ Check email settings in WordPress
2. ✅ Check order email address is correct
3. ✅ Check `_tracking_email_sent` meta (should be `1` after send)
4. ✅ Check spam folder
5. ✅ Enable debug: `define('WP_DEBUG_LOG', true);`

### Wrong Carrier Link
1. ✅ Edit carrier in `cw_cj_get_carrier_tracking_url()`
2. ✅ Verify carrier code from CJ matches array key
3. ✅ Test URL format is correct

---

## What's New vs Original System

| Feature | Before | Now |
|---------|--------|-----|
| Show tracking | Only in admin | ✅ Customers see it |
| Email alerts | Manual only | ✅ Automatic |
| Carrier links | None | ✅ Direct links |
| Timeline view | None | ✅ Visual progress |
| Dashboard | No tracking | ✅ Quick preview |
| Customizable | No | ✅ Easy to edit |

---

## Version Info

- **Version**: 1.0.0
- **Status**: Production Ready
- **Created**: February 2026
- **No external plugins required**
- **No additional configuration needed**

---

## Questions?

For CJ Dropshipping issues:
- [CJ Developer Docs](https://developer.cjdropshipping.com)
- [CJ Support Center](https://support.cjdropshipping.com)

For WordPress theme issues:
- Check `/wp-content/debug.log`
- Review order notes for sync status
