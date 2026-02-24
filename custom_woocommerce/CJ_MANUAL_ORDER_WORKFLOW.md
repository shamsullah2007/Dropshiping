# CJ Dropshipping Manual Order Workflow

## Overview
**Changed from AUTOMATIC to MANUAL order creation** - You now have full control over when and how orders are placed in CJ Dropshipping.

---

## How It Works

### Step 1: Customer Places Order
- Customer adds products to cart and checks out on your website
- WooCommerce order is created (status: **Pending Payment** or **Processing**)
- ✅ **NO automatic CJ order is created** - you have full control

### Step 2: Admin Manually Creates CJ Order
- Go to **CJ Dropshipping website** (https://www.cjdropshipping.com)
- Manually create an order with the same products from the customer's WooCommerce order
- Copy the **CJ Order ID** from CJ (usually shown as confirmation or in Orders list)

### Step 3: Link CJ Order to WooCommerce Order
- Go to **WooCommerce → Orders** in your admin
- Click the customer's order
- Scroll down to section: **"📦 CJ Dropshipping Order"**
- Paste the CJ Order ID you copied
- Click **"Save ID"** button

### Step 4: Automatic Tracking
- ✅ Tracking updates automatically
- ✅ Customer sees tracking on their order page
- ✅ Email notifications sent automatically
- ✅ Timeline updates as shipment progresses

---

## Visual Guide

### Admin Order Page - CJ Order Section

```
┌─────────────────────────────────────────────┐
│ 📦 CJ Dropshipping Order                   │
│                                             │
│ Steps:                                     │
│ 1. Go to CJ Dropshipping website           │
│ 2. Create an order manually with products  │
│ 3. Copy the CJ Order ID and paste it below │
│ 4. Save and tracking will update auto      │
│                                             │
│ [Enter CJ Order ID...........] [Save ID]   │
│                                             │
│ ✓ Success! CJ Order ID saved.             │
│   Tracking will update automatically.      │
└─────────────────────────────────────────────┘
```

### After CJ Order ID is Linked

```
┌─────────────────────────────────────────────┐
│ 📦 CJ Dropshipping Order                   │
│                                             │
│ ✓ CJ Order ID: 1234567890                 │
│                                             │
│ • Status: Out for Delivery                │
│ • Tracking: USPS Track123456              │
│ • Last Update: Today 2:30 PM              │
│                                             │
│ [Change ID]                                │
└─────────────────────────────────────────────┘
```

---

## Admin Workflow Step-by-Step

### Scenario: Customer Ordered 1x Pink T-Shirt (Size M)

**In WooCommerce:**
1. Navigate to **WooCommerce → Orders**
2. Find the customer's order (e.g., #Order 5432)
3. Scroll down to **"📦 CJ Dropshipping Order"** section
4. See the instructions:
   ```
   Steps:
   1. Go to CJ Dropshipping
   2. Create an order manually with this order's products
   3. Copy the CJ Order ID and paste it below
   4. Save and tracking will automatically update
   ```

**In CJ Dropshipping (parallel window):**
1. Log in to https://www.cjdropshipping.com
2. Click **"My Orders"** or **"Order Management"**
3. Click **"Create New Order"** or **"Place Order"**
4. Add the same products:
   - Product: Pink T-Shirt (Size M)
   - Quantity: 1
5. Customer shipping address (from customer's order)
6. Click **"Place Order"** or **"Confirm"**
7. CJ shows order confirmation with **Order ID** (e.g., `1234567890`)
8. Copy that ID

**Back in WooCommerce:**
1. In the **CJ Order ID** input field, paste: `1234567890`
2. Click **"Save ID"** button
3. Wait for confirmation: "✓ Success! CJ Order ID saved. Tracking will update automatically."
4. Page reloads showing the linked order

**Result:**
- ✅ Order is now linked
- ✅ Customer sees tracking on their order page
- ✅ Automatic email notifications when status changes
- ✅ Carrier tracking links (USPS, UPS, FedEx, etc.)

---

## Benefits of Manual Workflow

| Aspect | Automatic (Old) | Manual (New) |
|--------|-----------------|-------------|
| Control | ❌ No control, auto creates | ✅ Full admin control |
| Customization | ❌ Fixed mapping | ✅ Can customize before placing |
| Multiple Variants | ❌ May place wrong size/color | ✅ Pick exact variant |
| Bulk Orders | ❌ One by one | ✅ Can batch orders in CJ |
| Failed Orders | ❌ Auto creates, might fail | ✅ Only place successful orders |
| Cost Control | ❌ Immediate charge | ✅ Review before placing |
| Tracking | ✅ Works | ✅ Works (same) |

---

## Important Notes

### What Happens to Customer Orders?

1. **Customer sees order in WooCommerce** - Yes, immediately after purchase
2. **Order status** - Set to Processing/Pending initially
3. **Customer tracking page** - Shows empty until CJ Order ID is added
4. **Can customer cancel?** - Yes (you handle refunds manually)
5. **Guest order support** - Yes, works with guest checkout too

### Manual Order ID Entry

- **Can change later?** - Yes, click "Change ID" to update
- **Format** - Enter just the CJ Order ID number
- **Multiple products?** - Create separate CJ orders, or one CJ order with multiple items
- **Variant variations?** - You control which variants go in CJ order

### Tracking Activation Timeline

| Action | Time |
|--------|------|
| Add CJ Order ID | Immediate |
| Tracking appears | 5-15 minutes |
| First update | When CJ ships (1-5 days) |
| Customer email | Within 2 hours of update |
| Carrier link appears | When CJ provides tracking number |

---

## Common Questions

**Q: What if I forget to add the CJ Order ID?**
A: The WooCommerce order will show as Processing, but customer won't see tracking. Just add the CJ Order ID anytime and tracking will appear.

**Q: Can I change the CJ Order ID after saving?**
A: Yes! Click "Change ID" and enter the new ID. Previous tracking data resets.

**Q: What happens if CJ order fails?**
A: No automatic charge. You only add the CJ Order ID if it succeeds. If failed, don't add the ID, and work with customer for refund/resend.

**Q: Can I place one CJ order with multiple customer orders?**
A: Technically yes, but NOT recommended. Each customer order should map to one CJ Order ID for clean tracking.

**Q: Does customer see anything while waiting for CJ Order ID?**
A: Customer sees the order in their account with "Processing" status. On the Order Details page, tracking section shows "No tracking yet" or similar message.

**Q: What about SKUs?**
A: Just as you described - you copy product SKUs from your WooCommerce and use them to find products in CJ Dropshipping.

---

## Troubleshooting

### "CJ Order ID not appearing in order"
- Refresh the page
- Make sure you're logged in as admin
- Check browser console for errors (F12 → Console tab)

### "Tracking not updating after adding CJ Order ID"
- Wait 5-15 minutes for webhook to sync
- Check if CJ order has actual tracking info (in CJ dashboard)
- Verify webhook endpoint is configured in CJ settings

### "Can't change CJ Order ID"
- Click "Change ID" link
- Clear the field and enter new ID
- Click "Save ID" again

### "Customer doesn't see tracking"
- Verify CJ Order ID is saved (green checkmark)
- Check if CJ order has tracking number yet
- Customer may need to refresh their order page

---

## System Architecture

### How Tracking Works (After CJ Order ID is Saved)

```
CJ Dropshipping                WooCommerce
┌──────────────────┐          ┌──────────────────┐
│ CJ Order Created │          │ WC Order with    │
│ ID: 1234567890   │          │ CJ Order ID set  │
└────────┬─────────┘          └────────┬─────────┘
         │                             │
         ├─ Order Shipped ────────────│
         │    ↓                        │
         ├─ Webhook: LOGISTICS ──────→│ Stripe tracker
         │    ↓                        │ into WC order
         │ Tracking Number ────────────→│ Email to Customer
         │  Updates every 6 hours       │ Show on Order Page
         │    ↓                        │
         └─ Order Delivered ──────────→└─ Mark Completed
```

### REST Webhook Endpoint

- **URL**: `https://yoursite.com/wp-json/cj-dropshipping/v1/webhook`
- **Triggered by**: CJ Dropshipping when order status changes
- **Updates**: Tracking number, order status, carrier info
- **No setup needed**: Already configured in theme

---

## Implementation Summary

### What Changed

✅ **Removed automatic order creation** on purchase
✅ **Added manual CJ Order ID input form** on order page
✅ **Kept all tracking functionality** - works same as before
✅ **Admin has full control** - decide when to place orders
✅ **Webhooks still work** - tracking auto-updates

### What Stayed the Same

✅ Tracking updates work
✅ Email notifications work
✅ Carrier links work
✅ Webhook receiver works
✅ Customer sees tracking on order page
✅ All admin features still available

---

## Quick Reference

| Need | Solution |
|------|----------|
| Add CJ Order ID | Go to WooCommerce order → Scroll to CJ section → Paste ID → Save |
| Change CJ Order ID | Click "Change ID" → Clear field → Paste new ID → Save |
| View tracking status | Check order details page in customer account |
| Debug tracking issues | Check CJ dashboard for correct order status |
| Test the flow | Create test order, manually add CJ ID, verify tracking appears |

---

## Next Steps

1. **Test it**: Create a test WooCommerce order
2. **Create CJ order**: Go to CJ Dropshipping, create matching order manually
3. **Link it**: Get CJ Order ID and paste in WooCommerce order
4. **Verify**: Check that tracking appears after 5-15 minutes
5. **Monitor**: Check emails and order page to see tracking updates

---

**Need help?** Check your debug log at `/wp-content/debug.log` for detailed operation logs.
