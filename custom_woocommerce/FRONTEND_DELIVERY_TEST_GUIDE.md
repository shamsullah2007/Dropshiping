# Frontend Delivery Implementation - Quick Test Guide

## Quick Test: Add Product Form

### Step 1: Create a New Product
1. Go to your Product Manager page (usually `/product-manager/`)
2. Click the **"Add Single Item"** tab
3. You should see the form with these sections:
   - Product Title
   - Price
   - SKU
   - Category
   - Description
   - **NEW: Delivery Information** (with Delivery Charges and ETA fields)
   - Product Varieties

### Step 2: Test Auto-Save (Add Form)
1. Fill in product details:
   - Title: "Test Product"
   - Price: "50"
   - Description: "Test description"
   
2. Find the **Delivery Information** section
3. In the "Delivery Charges" field, type: `$10`
4. Observe the field border turns **YELLOW** (change detected)
5. Wait 2 seconds - the field border should turn **GREEN** (saved)
6. A green notification should appear saying "Delivery information saved"

7. In the "ETA" field, type: `3-7 days`
8. Again observe YELLOW then GREEN border after 2 seconds

9. Click **"Create Product"** button

### Step 3: Verify Data Persisted
1. Go back to the Product Manager
2. In the "All Products" section, find your newly created product
3. Click the **"Edit"** button
4. A modal should open showing product details
5. You should see the delivery charges and ETA fields POPULATED with your values:
   - Delivery Charges: `$10`
   - ETA: `3-7 days`

## Test: Edit Product Form

### Step 1: Open Edit Modal
1. In Product Manager, click **"Edit"** on any existing product
2. The variety editor modal should open
3. Look for **Delivery Information** section at the top with:
   - Delivery Charges input field
   - ETA input field
   - "Save Delivery Details" button

### Step 2: Edit Delivery Information
1. Change the Delivery Charges field to: `$15.99`
2. Change the ETA field to: `5-10 business days`
3. Click the **"Save Delivery Details"** button
4. You should see a success message

### Step 3: Verify Persistence
1. Refresh the page or close and re-open the modal
2. The fields should still show:
   - Delivery Charges: `$15.99`
   - ETA: `5-10 business days`

## Verification Checklist

### Visual Elements
- [ ] Delivery Information section appears on add product form
- [ ] Delivery Charges input field is visible and editable
- [ ] ETA input field is visible and editable
- [ ] Fields have proper styling and layout

### Auto-Save (Add Form)
- [ ] Field border turns yellow when typing
- [ ] Field border turns green after 2 seconds
- [ ] Green success notification appears
- [ ] Notification disappears after ~4 seconds

### Data Persistence
- [ ] Data is saved when form is submitted
- [ ] Data persists after page refresh
- [ ] Data displays correctly in edit modal
- [ ] Data saves when edited in edit modal

### Edit Modal
- [ ] Edit modal opens with product data
- [ ] Delivery fields are populated with existing data
- [ ] "Save Delivery Details" button works
- [ ] Changes persist after refresh

## Troubleshooting

### Auto-Save Not Working
1. Check browser console for JavaScript errors (F12)
2. Check WordPress debug.log: `/wp-content/debug.log`
3. Verify nonce is being sent in AJAX requests
4. Check that user has `manage_woocommerce` capability

### Fields Not Saving
1. Verify form submission completes without errors
2. Check browser console for AJAX errors
3. Check WordPress debug.log for PHP errors
4. Ensure user has `edit_product` capability

### Data Not Persisting
1. Check database: Query `wp_postmeta` table
   ```sql
   SELECT * FROM wp_postmeta WHERE post_id = [PRODUCT_ID] AND meta_key LIKE '%delivery%';
   ```
2. Verify meta keys are: `_cj_delivery_charges` and `_cj_delivery_eta`
3. Check for capitalization in field names

## Database Verification

To manually check if data is being saved:

### Via WordPress Admin
1. Go to Tools → Delivery Diagnosis
2. Scroll down to see products with delivery charges
3. Verify your test product appears with correct values

### Via MySQL
```sql
-- View all delivery data for products
SELECT 
    p.ID,
    p.post_title,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_cj_delivery_charges') as delivery_charges,
    (SELECT meta_value FROM wp_postmeta WHERE post_id = p.ID AND meta_key = '_cj_delivery_eta') as delivery_eta
FROM wp_posts p
WHERE p.post_type = 'product'
ORDER BY p.ID DESC
LIMIT 10;
```

## Next Steps After Testing

Once you've confirmed everything works:

1. **Display delivery info on product pages** (optional):
   - Create/update single product template to show delivery charges and ETA
   - Consider adding to product page template or single-product.php

2. **Add to customer email notifications** (optional):
   - Include delivery charges in order confirmation emails
   - Show ETA to customers when order is placed

3. **Bulk edit** (optional):
   - Add ability to bulk edit delivery charges for multiple products

## Support & Debugging

### Enable Debug Logging
Add to `wp-config.php` if not already enabled:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check AJAX Calls
1. Open browser Developer Tools (F12)
2. Go to Network tab
3. Create/edit a product
4. Look for requests to `admin-ajax.php`
5. Click on AJAX request and check Response tab
6. Should see `"success":true` in JSON response

### Review Implementation
- [FRONTEND_DELIVERY_IMPLEMENTATION.md](FRONTEND_DELIVERY_IMPLEMENTATION.md) - Full technical details
- [DATABASE_STRUCTURE.md](DATABASE_STRUCTURE.md) - Database schema reference
