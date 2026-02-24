# ✅ Manual CJ Order Workflow - Quick Start

## What Changed
- ❌ **NO automatic CJ order creation** when customer buys
- ✅ **MANUAL process**: You control when to place orders in CJ
- ✅ **Tracking still works automatically** once you link CJ Order ID

---

## The Flow (3 Steps)

### Step 1: Customer Buys on Your Site
```
Customer → Your Website → Order Created in WooCommerce
           (Status: Processing/Pending)
           ✅ NO CJ order created yet
```

### Step 2: You Create Order in CJ
```
You → CJ Dropshipping Website → Create Order Manually
      (Same products from customer's WooCommerce order)
      Copy the CJ Order ID
```

### Step 3: Link CJ ID to WooCommerce Order
```
WooCommerce Admin Order Page
    ↓
Find "📦 CJ Dropshipping Order" section
    ↓
Paste CJ Order ID
    ↓
Click "Save ID"
    ↓
✅ Tracking automatically updates & emails sent to customer
```

---

## Where to Add CJ Order ID

**In WordPress Admin:**
1. Go to **WooCommerce → Orders**
2. Click the customer's order
3. **Scroll down** to find section labeled:
   ```
   📦 CJ Dropshipping Order
   ```
4. Enter CJ Order ID and click **"Save ID"**

---

## Example

**Customer Order #5432:**
- Product: Pink T-Shirt (Size M) × 1
- Price: $29.99
- Customer: John Doe

**What you do:**
1. Go to CJ Dropshipping website
2. Create order manually with: Pink T-Shirt (Size M) × 1
3. CJ confirms: Order ID = `1234567890`
4. Copy `1234567890`
5. Go back to WooCommerce Order #5432
6. Paste ID in CJ Order field
7. Save
8. ✅ Done! Customer sees tracking automatically

---

## Important

After linking CJ Order ID:
- ⏱️ Wait 5-15 minutes for tracking to appear
- 📧 Customer gets automatic email when order ships
- 📍 Tracking link appears on customer's order page
- 🔄 Updates happen automatically from CJ webhooks

---

## What You Control

✅ When to place order in CJ (not automatic)
✅ Which variant/size to order (not forced)
✅ Can review product details first
✅ Can batch multiple customer orders in CJ if needed
✅ Only place orders you want

---

## Troubleshooting

**Tracking not appearing?**
- Wait 15 minutes
- Check CJ order has shipping info
- Refresh the page

**Can't find CJ Order field?**
- Scroll down on order page
- Look for blue box with "📦" icon

**Need to change CJ Order ID?**
- Click "Change ID" link
- Enter new ID
- Save again

---

## Files Modified

✏️ **`cj-integration-hooks.php`**
- Disabled automatic order sync hooks
- Added manual CJ Order ID form on order page
- New AJAX handler for saving CJ ID manually

📝 **New Documentation**
- `CJ_MANUAL_ORDER_WORKFLOW.md` - Complete guide with examples

---

## Test It Now

1. **Create test order** on your site
2. **Go to CJ Dropshipping** and manually create matching order
3. **Copy CJ Order ID**
4. **Go to your WooCommerce order** (WooCommerce → Orders → Select order)
5. **Scroll to CJ section** and paste the ID
6. **Wait 5-15 minutes** and check if tracking appears
7. ✅ If tracking shows up, you're all set!

---

**Ready to use!** Start placing manual orders in CJ and linking them back to your WordPress orders.
