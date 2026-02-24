# 🔧 Admin Panel Guide - CJ Dropshipping & Tracking

## Overview

Your WordPress admin dashboard has several powerful features for managing CJ Dropshipping orders, products, and customer tracking. Here's what administrators can do:

---

## 1. **CJ Dropshipping Settings Page** ⚙️

### Location
**WordPress Admin → WooCommerce → CJ Dropshipping**

### What You Can Do

#### **API Credentials Configuration**
- Add CJ API Key
- Add Platform Token (optional)
- Test connection to CJ account
- View account balance in real-time
- Save credentials securely

#### **Dashboard Cards**
Shows at a glance:
- 💰 **Account Balance** - How much credit you have on your CJ account
- ✓ **Status** - Connected/Not Connected
- 📦 **Products** - Link to imported products section

#### **Webhook Configuration**
- See your webhook URL
- Instructions to add to CJ account
- Webhook receiver for tracking updates

---

## 2. **Order Management** 📋

### Location
**WordPress Admin → WooCommerce → Orders**

### Admin Features on Order Page

#### **Manual Order Sync Button**
When viewing an order that's in "processing" status:
- Button: **"Sync to CJ Dropshipping"**
- Syncs order to CJ immediately
- Auto-charges from CJ balance
- Shows success/error message
- One-click operation

#### **Order Notes Display**
Shows:
- CJ order creation notes
- CJ order ID (if synced)
- Payment status
- Any sync errors

#### **Order Meta Information**
Visible in order meta:
- `_cj_order_id` - Linked CJ order
- `_shipping_tracking_number` - Tracking number
- `_tracking_email_sent` - Email status

#### **Tracking Information**
When tracking is available:
- Tracking number displayed
- Carrier name
- Order status (in shipping section)
- Customer view link

---

## 3. **Product Management** 📦

### Location
**WordPress Admin → Products**

### Admin Features on Product Page

#### **CJ Product Information Box**
Shows when product is linked to CJ:
- CJ Product ID
- Button: **"Refresh Variants from CJ"**
- Updates variant images and prices
- One-click refresh
- Keeps product but updates data

#### **Product Linking**
- Add CJ Product ID to product meta
- Add CJ Variant IDs to variations
- Automatic linking tools
- Bulk import helpers

---

## 4. **Product Import Tools** 📥

### Location
**WordPress Admin → WooCommerce → CJ Dropshipping (Admin Page v2)**

### Import Methods

#### **Method 1: Search by Keyword**
Import products by searching CJ catalog:
- Search term (e.g., "hoodie", "mug")
- Price markup percentage (0-500%)
- Max products to import (limit)
- Option to skip images (faster)
- Real-time progress indicator

**Steps:**
1. Enter search term (or leave blank for all)
2. Set price markup (e.g., 50% = double CJ price)
3. Set limit (start with 10-20)
4. Optional: Check "Skip Images" for faster import
5. Click "Start Search Import"
6. Watch import progress
7. Products auto-created in WooCommerce
8. Linked to CJ automatically

#### **Method 2: Import by Product Links**
Import specific products from CJ URLs:
- Paste single CJ product link
- Or paste multiple links (one per line)
- Set price markup
- Option to skip images
- Bulk import multiple products

**Steps:**
1. Find product(s) on CJ website
2. Copy URL from browser
3. Paste in "Single Product Link" OR "Bulk Product Links" area
4. Set price markup
5. Click "Start Link Import"
6. Products created with CJ data
7. Automatically linked

### Import Status Indicators
- ✓ Success message with product count
- ❌ Error messages with details
- Progress spinner during import
- Auto-linking happens automatically

---

## 5. **Debug & Troubleshooting Tools** 🔍

### Location
Various endpoints and pages

#### **Debug Log Access**
- File: `/wp-content/debug.log`
- Enable in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### **What Gets Logged**
- ✓ Order sync attempts
- ✓ CJ API requests/responses
- ✓ Webhook receipts
- ✓ Tracking updates
- ✓ Email sending
- ✓ Error/warnings

#### **Debug Endpoints**
Test webhook receiver:
```
https://your-site.com/wp-json/cj-dropshipping/v1/webhook
```
Should return: `{"success":true}`

---

## 6. **Email Notifications** 📧

### Admin Features

#### **Track Email Sending**
In order notes, you can see:
- ✓ Email sent notification
- Tracking number that was sent
- Date/time sent
- No duplicates (marked with flag)

#### **Test Email Sending**
In debug code, admins can:
- Test tracking email function
- Verify email settings
- Check SMTP configuration

---

## 7. **Database & Meta Information** 💾

### Accessible Via Admin

#### **Order Meta Keys**
View in WooCommerce **Order Details → Order Meta**:
- `_cj_order_id` - CJ Order ID (if synced)
- `_shipping_tracking_number` - Tracking number
- `_tracking_email_sent` - Whether email was sent

#### **Product Meta Keys**
View in Product page:
- `_cj_product_id` - CJ Product ID
- `_cj_variant_id` - CJ Variant ID
- `_product_video_ids` - Video attachments

#### **Settings Options**
Stored in WordPress Options:
- `cw_cj_api_key` - API Key
- `cw_cj_platform_token` - Platform Token
- `cw_cj_access_token` - Current auth token
- `cw_cj_token_expiry` - Token expiration time
- `cw_cj_refresh_token` - Refresh token (optional)

---

## 8. **Workflow Examples** 📊

### Scenario 1: Customer Places Order
```
Admin View:
1. Order appears in WooCommerce Orders
2. Status: Pending
3. Click order details
4. See "Sync to CJ Dropshipping" button (if processing status)
5. Click button
6. Order syncs to CJ
7. Auto-charged from CJ balance
8. Order notes updated with CJ Order ID
```

### Scenario 2: Order Ships
```
Admin View:
1. CJ webhook sends tracking update
2. Order automatically updated in WordPress
3. Order status changes to "Shipped"
4. Tracking number added to order meta
5. Order notes updated with carrier info
6. Email marked as sent
7. Admin can see all details in order
```

### Scenario 3: Admin Imports Products
```
Admin View:
1. Go to WooCommerce → CJ Dropshipping
2. Enter search term "hoodie"
3. Set markup to 50%
4. Set limit to 20
5. Click "Start Search Import"
6. Watch progress indicator
7. See success: "✓ Imported 15 products"
8. Products appear in Products list
9. Tagged with CJ metadata
10. Ready to sell immediately
```

---

## 9. **Permission Requirements** 🔐

### Required User Capabilities
- `manage_options` - Access CJ settings page
- `manage_woocommerce` - View/edit orders
- `manage_woocommerce_orders` - Manual order sync
- `edit_products` - Manage products/imports

### Access Control
- Only admins see CJ settings
- Only shop managers see order tools
- Only product editors can import
- All protected with nonces (security)

---

## 10. **Quick Admin Checklist** ✅

### Initial Setup
- [ ] Add CJ API Key to settings
- [ ] Verify connection (see balance)
- [ ] Add webhook URL to CJ account
- [ ] Test webhook endpoint
- [ ] Import first 10-20 products

### Ongoing Management
- [ ] Check debug log regularly
- [ ] Monitor order syncs
- [ ] Verify tracking emails send
- [ ] Refresh product variants if needed
- [ ] Update prices/inventory as needed

### Troubleshooting
- [ ] Check order has CJ product IDs
- [ ] Verify API key hasn't expired
- [ ] Check debug log for errors
- [ ] Test webhook URL is accessible
- [ ] Verify email settings in WordPress

---

## 11. **Common Admin Tasks** 🎯

### Task 1: View Order Tracking
**Admin Check:**
1. Orders → Find order
2. Scroll down to "Shipping" section
3. See tracking number
4. See carrier name
5. See "View Tracking" link if available

### Task 2: Manually Sync Order to CJ
**Admin Action:**
1. Orders → Open order
2. If status is "Processing":
   - Click "Sync to CJ Dropshipping" button
   - See spinner while syncing
   - Get success message with CJ Order ID
   - Order notes updated
3. Check debug log if fails

### Task 3: Import Products Faster
**Admin Tip:**
- Check "Skip Images" for 3-5x faster import
- Import in batches (20-50 at a time)
- Can add images/videos later manually
- Use link import for specific products

### Task 4: Fix Product Sync Issues
**Admin Action:**
1. Products → Edit product
2. Scroll to "CJ Dropshipping" box
3. Check CJ Product ID is set
4. Click "Refresh Variants from CJ"
5. Updates prices/images/variants
6. Keeps product data intact

### Task 5: Monitor Webhook
**Admin Check:**
1. Check `wp-content/debug.log`
2. Search for "CJ Tracking"
3. See webhook events received
4. See tracking updates logged
5. Watch for any errors

---

## 12. **Admin Notifications** 🔔

### What Admins See

#### **In Admin Dashboard**
- Order status changes
- New sync requests
- Import completion messages
- Error alerts

#### **In Debug Log**
```
[DATE TIME] CJ Order: Creating CJ order with 2 products
[DATE TIME] CJ Order: Created successfully - CJ Order ID: abc123
[DATE TIME] CJ Order: Mapped WC Order 456 to CJ Order abc123
[DATE TIME] CJ Order: Payment successful! Amount: $45.99
[DATE TIME] CJ Tracking Email: Sent for order 456
```

#### **In Order Notes**
- CJ Order Created & Paid: Order ID=abc123, Amount=$45.99
- CJ Tracking Update: Status=SHIPPED, Tracking=1Z999AA10123
- CJ Order Status: DELIVERED (from webhook)

---

## 13. **Security Features** 🔒

### Admin Security
- ✓ Nonce verification on all forms
- ✓ Permission checks on AJAX handlers
- ✓ API key encrypted in database
- ✓ Secure webhook validation
- ✓ Debug log excluded from public access
- ✓ Admin-only pages protected

### Data Safety
- ✓ Original products never deleted on refresh
- ✓ Variants updated non-destructively
- ✓ All changes logged
- ✓ Database backups recommended
- ✓ No data loss on failed imports

---

## 14. **Admin Settings for Customization** ⚙️

### Can Be Changed
- CJ API Key
- Platform Token
- Price markup during import
- Product limit during import
- Skip images option

### Cannot Change (Hardcoded)
- Webhook URL (use domain settings)
- Import method algorithms
- Order sync process
- Email templates (edit in code)

---

## 15. **Admin Troubleshooting Guide** 🆘

### "Connection Failed"
1. Check API Key is complete (copy full string)
2. No spaces at beginning/end
3. Check in CJ account it's the right key
4. Check internet connection

### "Order Won't Sync"
1. Check order status is "Processing"
2. Check products have `_cj_variant_id` set
3. Check CJ balance > order total
4. Check debug log for error message
5. See `wp-content/debug.log`

### "Tracking Not Showing"
1. Check order has `_cj_order_id` meta
2. Check order has `_shipping_tracking_number` meta
3. Verify webhook is configured in CJ account
4. Check webhook URL is publicly accessible
5. Test webhook: `https://your-site.com/wp-json/cj-dropshipping/v1/webhook`

### "Email Not Sending"
1. Check SMTP settings in `wp-config.php`
2. Check order email address is valid
3. Check spam folder
4. See `/wp-content/debug.log` for errors
5. Test with test email function

---

## Summary

**Admin Dashboard Features:**
| Feature | Location | Action |
|---------|----------|--------|
| CJ Settings | WooCommerce → CJ Dropshipping | Add credentials, view balance |
| Order Sync | Orders → Order Details | 1-click sync button |
| Product Import | WooCommerce → CJ Dropshipping | Search or link import |
| Product Refresh | Products → Edit Product | Refresh variants box |
| Debug Log | `/wp-content/debug.log` | View all activity logs |
| Tracking Info | Orders → Order Details | See auto-updated tracking |
| Email Status | Order Notes | See tracking email status |

**All features are designed to make admin management easy with minimal clicks!** ✅
