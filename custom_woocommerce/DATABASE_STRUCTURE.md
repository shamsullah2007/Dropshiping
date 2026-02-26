# Database Tables for Delivery Charges & ETA

## Quick Answer

Your delivery charges and ETA data is stored in **ONE table**: `wp_postmeta`

---

## The Tables

### Table 1: `wp_posts` (Stores Product Information)
```
┌─────────────────────────────────────────────────────┐
│ Table: wp_posts                                     │
├─────────────────────────────────────────────────────┤
│ ID          │ post_title      │ post_type           │
├─────────────────────────────────────────────────────┤
│ 123         │ Red Shirt       │ product             │
│ 124         │ Blue Shirt      │ product             │
│ 125         │ Green Shirt     │ product             │
└─────────────────────────────────────────────────────┘
```
- Stores: Product name, type, content
- **Does NOT store** delivery charges or ETA

---

## Table 2: `wp_postmeta` (Stores Custom Fields)
```
┌──────────────────────────────────────────────────────────────────┐
│ Table: wp_postmeta                                               │
├──────────┬──────────┬──────────────────────┬────────────────────┤
│ meta_id  │ post_id  │ meta_key             │ meta_value         │
├──────────┼──────────┼──────────────────────┼────────────────────┤
│ 1001     │ 123      │ _cj_delivery_charges │ $10.00             │
│ 1002     │ 123      │ _cj_delivery_eta     │ 5-7 business days  │
│ 1003     │ 124      │ _cj_delivery_charges │ Free Shipping      │
│ 1004     │ 124      │ _cj_delivery_eta     │ 3-5 business days  │
│ 1005     │ 123      │ _price               │ 29.99              │
│ 1006     │ 123      │ _regular_price       │ 29.99              │
└──────────┴──────────┴──────────────────────┴────────────────────┘
```

**YOUR DELIVERY DATA IS HERE! ☝️**

---

## Column Descriptions

| Column Name | Data Type | Purpose |
|---|---|---|
| `meta_id` | INT | Unique ID for each record |
| `post_id` | INT | Which product this belongs to |
| `meta_key` | VARCHAR(255) | The field name |
| `meta_value` | LONGTEXT | The actual value |

---

## Data Storage Keys

The system stores delivery data using these **meta_key** values:

| Meta Key | Stores | Example |
|---|---|---|
| `_cj_delivery_charges` | Delivery cost | `$10.00` or `Free Shipping` |
| `_cj_delivery_eta` | Estimated arrival time | `5-7 business days` or `3-5 days` |

**The underscore `_` at the beginning** = "hidden field" in WordPress (not shown by default in admin)

---

## SQL Queries to Check Your Data

### Query 1: See All Delivery Data
```sql
SELECT 
    p.ID as product_id,
    p.post_title as product_name,
    MAX(CASE WHEN pm.meta_key = '_cj_delivery_charges' THEN pm.meta_value END) as delivery_charges,
    MAX(CASE WHEN pm.meta_key = '_cj_delivery_eta' THEN pm.meta_value END) as delivery_eta
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
GROUP BY p.ID
LIMIT 50;
```

### Query 2: Check Specific Product
```sql
SELECT *
FROM wp_postmeta
WHERE post_id = 123  -- Replace 123 with your product ID
AND meta_key IN ('_cj_delivery_charges', '_cj_delivery_eta');
```

### Query 3: Count How Many Products Have Delivery Data
```sql
SELECT 
    COUNT(DISTINCT CASE WHEN meta_key = '_cj_delivery_charges' THEN post_id END) as products_with_charges,
    COUNT(DISTINCT CASE WHEN meta_key = '_cj_delivery_eta' THEN post_id END) as products_with_eta
FROM wp_postmeta;
```

### Query 4: Add Test Data to a Product
```sql
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
VALUES (123, '_cj_delivery_charges', '$15.00');

INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
VALUES (123, '_cj_delivery_eta', '7-10 business days');
```

---

## WordPress Meta System

WordPress uses a flexible "key-value" system for storing custom data:

```
One Product can have MANY metadata records:
  
Product ID 123
├── meta_key: _price → meta_value: 29.99
├── meta_key: _regular_price → meta_value: 29.99
├── meta_key: _cj_delivery_charges → meta_value: $10.00 ✓ OUR DATA
├── meta_key: _cj_delivery_eta → meta_value: 5-7 business days ✓ OUR DATA
├── meta_key: _cj_varieties → meta_value: [JSON with variant info]
└── meta_key: _product_attributes → meta_value: [...]
```

---

## How Data Gets Saved

```
1. User edits product in WordPress Admin
2. User fills in "Delivery Charges" and "ETA" fields
3. User clicks "Update Product"
4. WordPress calls save_post_product hook
5. Our code runs: cw_cj_save_varieties()
6. Code calls: update_post_meta($product_id, '_cj_delivery_charges', $value)
7. WordPress saves to wp_postmeta table
8. Done! ✓
```

---

## Table Structure Details

### `wp_postmeta` Full Schema
```sql
CREATE TABLE wp_postmeta (
  meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id bigint(20) UNSIGNED NOT NULL,
  meta_key varchar(255),
  meta_value longtext,
  PRIMARY KEY (meta_id),
  KEY post_id (post_id),
  KEY meta_key (meta_key(191))
);
```

- **meta_id**: Auto-incrementing primary key
- **post_id**: References wp_posts.ID (which product)
- **meta_key**: The field name (up to 255 characters)
- **meta_value**: The value (up to 4GB of text!)
- **Indexed**: post_id and meta_key are indexed for fast lookups

---

## Checking If Data Exists

### Via phpMyAdmin:
1. Go to phpMyAdmin
2. Select your WordPress database
3. Click on `wp_postmeta` table
4. Search for:
   - `meta_key = '_cj_delivery_charges'`
   - `meta_key = '_cj_delivery_eta'`

### Via WordPress PHP:
```php
// Get delivery charges for product 123
$charges = get_post_meta(123, '_cj_delivery_charges', true);
echo $charges; // Output: $10.00

// Get ETA for product 123
$eta = get_post_meta(123, '_cj_delivery_eta', true);
echo $eta; // Output: 5-7 business days
```

### Via SQL:
```sql
SELECT meta_value 
FROM wp_postmeta 
WHERE post_id = 123 
AND meta_key = '_cj_delivery_charges';
```

---

## Related Tables

While your data is in `wp_postmeta`, here are other important tables:

| Table | Purpose |
|---|---|
| `wp_posts` | Product main info |
| `wp_postmeta` | Product custom fields **← YOUR DATA IS HERE** |
| `wp_term_relationships` | Product categories/tags |
| `wp_woocommerce_product_meta_lookup` | WooCommerce optimized lookups |

---

## Troubleshooting

### Problem: Data Not Saving to Database

**Check these conditions:**

1. ✓ Product exists in `wp_posts` table?
   ```sql
   SELECT * FROM wp_posts WHERE ID = YOUR_PRODUCT_ID;
   ```

2. ✓ Nonce is valid?
   - Check wp-content/debug.log

3. ✓ User has permission to edit posts?
   - Current user must have `edit_post` capability

4. ✓ No PHP errors?
   - Check wp-content/debug.log

5. ✓ meta_key is exactly: `_cj_delivery_charges` and `_cj_delivery_eta`?
   - Case sensitive!
   - Must have underscore at start!

---

## Test Database Save Directly

To prove the database works, use this PHP code:

```php
<?php
// Save test data
update_post_meta(123, '_cj_delivery_charges', 'TEST: $99.99');
update_post_meta(123, '_cj_delivery_eta', 'TEST: 1 day delivery');

// Read it back
$charges = get_post_meta(123, '_cj_delivery_charges', true);
$eta = get_post_meta(123, '_cj_delivery_eta', true);

echo "Charges: " . $charges; // Should output: TEST: $99.99
echo "ETA: " . $eta; // Should output: TEST: 1 day delivery
```

If this works, the database is fine. If not, there's a WordPress configuration issue.

---

## Summary

- **Table**: `wp_postmeta`
- **Columns**: `meta_id`, `post_id`, `meta_key`, `meta_value`
- **Your Keys**: `_cj_delivery_charges`, `_cj_delivery_eta`
- **One product can have many records** (one per meta key)
- **Check via SQL or use WordPress functions**: `get_post_meta()`, `update_post_meta()`
