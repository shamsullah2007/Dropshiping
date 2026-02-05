# CJ Dropshipping Custom Integration Guide

## Overview

This custom integration module provides complete CJ Dropshipping functionality without external plugins:

- ✓ **Product Management**: Import categories, products, variants, and real-time inventory
- ✓ **Order Processing**: Automatic order creation when WooCommerce order is placed
- ✓ **Payment Integration**: Automatic deduction from CJ account balance
- ✓ **Tracking Updates**: Webhook receiver for automatic tracking number sync
- ✓ **Order Mapping**: Bidirectional sync between WooCommerce and CJ orders

## Architecture

### Files Structure

```
custom_woocommerce/
├── class-cj-dropshipping.php      # Main API client class
├── cj-integration-hooks.php       # WordPress hooks & admin pages
└── functions.php                  # Theme core (includes above)
```

### Core Components

#### 1. **CJ_Dropshipping Class** (`class-cj-dropshipping.php`)

Main API client with methods for:

**Authentication**
```php
$cj = cw_cj_dropshipping();
$cj->set_credentials($api_key, $api_secret, $platform_token);
$cj->refresh_access_token();
```

**Products**
```php
$categories = $cj->get_categories();
$products = $cj->list_products(['keyWord' => 'hoodie', 'page' => 1]);
$product = $cj->get_product_details($pid, $include_inventory = true);
$variants = $cj->get_variants($pid);
```

**Inventory**
```php
$inventory = $cj->get_inventory_by_vid($vid);
$inventory = $cj->get_inventory_by_sku($sku);
```

**Orders**
```php
// Create order
$order_data = [
    'order_number' => '12345',
    'country_code' => 'US',
    'country' => 'United States',
    'state' => 'CA',
    'city' => 'Los Angeles',
    'phone' => '5551234567',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'address_1' => '123 Main St',
    'postcode' => '90001',
    'email' => 'john@example.com',
];

$products = [
    [
        'vid' => '92511400-C758-4474-93CA-66D442F5F787',
        'quantity' => 2,
        'storeLineItemId' => 'lineitem-123',
    ]
];

$result = $cj->create_order($order_data, $products, 2); // payType=2 for balance payment
$cj_order_id = $result['data']['orderId'];

// Confirm and pay
$cj->add_to_cart($cj_order_id);
$cj->confirm_cart($cj_order_id);
$shipment = $cj->generate_parent_order($cj_order_id);
$cj->pay_balance_v2($cj_order_id, $shipment['payId']);
```

**Payment**
```php
$balance = $cj->get_balance();
```

**Shipping**
```php
$cj->upload_waybill($order_id, $cj_order_id, 'USPS', 'tracking123', '/path/to/label.pdf');
```

#### 2. **Integration Hooks** (`cj-integration-hooks.php`)

**Admin Settings Page**
- Location: WooCommerce > CJ Dropshipping
- Features:
  - Add/update API credentials
  - Display account balance
  - Webhook URL configuration
  - Integration status

**Automatic Order Processing**
- Hook: `woocommerce_order_status_processing`
- Automatically creates CJ order when WooCommerce order moves to Processing
- Maps WooCommerce order to CJ order ID
- Auto-pays from CJ balance

**Webhook Receiver**
- REST Endpoint: `/wp-json/cj-dropshipping/v1/webhook`
- Receives notifications for:
  - **LOGISTICS**: Tracking number updates
  - **ORDER**: Order status changes
- Auto-updates WooCommerce order with tracking numbers

## Setup Instructions

### Step 1: Get CJ API Credentials

1. Go to [CJ Developer Account](https://developer.cjdropshipping.com/account/info)
2. Copy your **Access Key** (API Key)
3. Copy your **Secret Key** (API Secret)
4. (Optional) Get **Platform Token** if using multi-platform integration

### Step 2: Configure in WordPress

1. Go to **WooCommerce > CJ Dropshipping**
2. Paste API Key and API Secret
3. (Optional) Add Platform Token
4. Click **Save CJ Credentials**
5. Wait for verification - you should see account balance displayed

### Step 3: Configure CJ Webhook

1. Go to your [CJ Developer Dashboard](https://developer.cjdropshipping.com/settings/webhook)
2. Add new webhook with URL:
   ```
   https://your-site.com/wp-json/cj-dropshipping/v1/webhook
   ```
3. Select notification types:
   - ✓ LOGISTICS (for tracking updates)
   - ✓ ORDER (for order status changes)
4. Save

### Step 4: Map Products to CJ Catalog (Optional)

When creating WooCommerce products, associate them with CJ products:

```php
cw_cj_save_product_mapping(
    $woo_product_id,
    $cj_product_id,
    $cj_variant_id
);
```

Store these in product meta:
- `_cj_product_id: string` - CJ product ID
- `_cj_variant_id: string` - CJ variant ID (required for orders)

## Usage Examples

### Example 1: Manual Product Import

```php
$cj = cw_cj_dropshipping();

// Get all products
$page = 1;
while ($page <= 10) {
    $products = $cj->list_products([
        'page' => $page,
        'size' => 50,
        'countryCode' => 'US',
    ]);
    
    foreach ($products['content'] as $item) {
        foreach ($item['productList'] as $product) {
            // Create WooCommerce product
            $woo_id = wp_insert_post([
                'post_type' => 'product',
                'post_title' => $product['nameEn'],
                'post_content' => $product['description'],
            ]);
            
            // Map to CJ
            cw_cj_save_product_mapping($woo_id, $product['id'], null);
        }
    }
    
    $page++;
}
```

### Example 2: Check Live Inventory

```php
// Display live inventory for a product
$cj_variant_id = get_post_meta($product_id, '_cj_variant_id', true);
if ($cj_variant_id) {
    $stock = cw_cj_get_live_inventory($cj_variant_id);
    echo "Stock: " . $stock . " units";
}
```

### Example 3: Programmatic Order Creation

```php
add_action('woocommerce_checkout_order_processed', function($order_id) {
    $order = wc_get_order($order_id);
    $cj = cw_cj_dropshipping();
    
    // Check if order has CJ products
    $products = [];
    foreach ($order->get_items() as $item) {
        $cj_variant_id = get_post_meta($item->get_product_id(), '_cj_variant_id', true);
        if ($cj_variant_id) {
            $products[] = [
                'vid' => $cj_variant_id,
                'quantity' => $item->get_quantity(),
            ];
        }
    }
    
    if (empty($products)) return;
    
    // Create CJ order
    $result = $cj->create_order([
        'order_number' => $order->get_order_number(),
        'country_code' => $order->get_billing_country(),
        'country' => WC()->countries->countries[$order->get_billing_country()],
        'state' => $order->get_billing_state(),
        'city' => $order->get_billing_city(),
        'phone' => $order->get_billing_phone(),
        'first_name' => $order->get_billing_first_name(),
        'last_name' => $order->get_billing_last_name(),
        'address_1' => $order->get_billing_address_1(),
        'postcode' => $order->get_billing_postcode(),
        'email' => $order->get_billing_email(),
    ], $products);
    
    if (isset($result['data']['orderId'])) {
        CJ_Dropshipping::map_woo_to_cj_order($order_id, $result['data']['orderId']);
        $order->add_order_note('CJ Order Created: ' . $result['data']['orderId']);
    }
}, 10, 1);
```

### Example 4: Get CJ Order from WooCommerce

```php
$woo_order_id = 123;
$cj_order_id = CJ_Dropshipping::get_cj_order_id($woo_order_id);

if ($cj_order_id) {
    $cj = cw_cj_dropshipping();
    $cj_order = $cj->get_order($cj_order_id);
    
    echo "CJ Order Status: " . $cj_order['orderStatus'];
    echo "Tracking: " . $cj_order['trackNumber'];
}
```

## Database Storage

The integration stores data in the following locations:

**WordPress Options (Settings)**
```
cw_cj_api_key          # API Key (hashed)
cw_cj_api_secret       # API Secret (hashed)
cw_cj_platform_token   # Platform Token
cw_cj_access_token     # Current access token
cw_cj_token_expiry     # Token expiration timestamp
```

**WooCommerce Order Meta**
```
_cj_order_id           # Mapped CJ order ID
_shipping_tracking_number  # Tracking number from CJ
```

**WooCommerce Product Meta**
```
_cj_product_id         # CJ product ID
_cj_variant_id         # CJ variant ID (required for orders)
```

## Error Handling

All API methods handle errors gracefully:

```php
$result = $cj->create_order($order_data, $products);

if (is_wp_error($result)) {
    error_log('Error: ' . $result->get_error_message());
} else if (isset($result['result']) && !$result['result']) {
    error_log('API Error [' . $result['code'] . ']: ' . $result['message']);
} else {
    // Success
    $cj_order_id = $result['data']['orderId'];
}
```

Error codes reference:
- `1600100` - Parameter error
- `1600200` - Request timeout
- `1600300` - Item not found
- `1603001` - Order confirm failed
- `200` - Success

Full error code list available in CJ API documentation.

## Webhook Payload Examples

**Logistics/Tracking Update**
```json
{
    "eventType": "LOGISTICS",
    "data": {
        "cjOrderId": "SD25080109282603297001",
        "trackingNumber": "1234567890",
        "status": "SHIPPED"
    }
}
```

**Order Status Update**
```json
{
    "eventType": "ORDER",
    "data": {
        "cjOrderId": "SD25080109282603297001",
        "status": "UNSHIPPED"
    }
}
```

## Testing the Integration

Test connection in WordPress admin using:

```php
$test = cw_cj_test_connection();
if ($test['success']) {
    echo "Connected! Balance: $" . $test['balance'];
}
```

Or access via URL:
```
https://your-site.com/wp-admin/admin-ajax.php?action=cw_cj_test
```

## Troubleshooting

### "Failed to verify CJ credentials"
- Check API Key and Secret are correct
- Verify credentials don't have extra spaces
- Ensure CJ account has API access enabled

### Orders not auto-creating
- Verify CJ credentials are saved
- Check WooCommerce products have `_cj_variant_id` meta
- Check error logs: `/wp-content/debug.log`
- Verify products have CJ variant IDs assigned

### Webhook not receiving updates
- Check webhook URL is publicly accessible
- Verify webhook URL configured correctly in CJ account
- Check with CJ support for webhook logs

### Tracking numbers not updating
- Verify webhook endpoint is receiving POST requests
- Check order note in WooCommerce for webhook errors
- Test webhook manually from CJ console

## Performance Considerations

- **Token Caching**: Tokens cached for 15 days (CJ max)
- **Inventory Queries**: Each inventory check makes 1 API call
- **Batch Operations**: Use list endpoints with pagination to minimize API calls
- **Webhook Delivery**: Use background tasks for processing to avoid timeout

## API Rate Limits

CJ API has the following rate limits:
- Free users: 1,000 requests/day
- Requests are shared across all API endpoints

Optimize by:
- Caching product/category data
- Batching orders
- Using pagination efficiently

## Security Notes

- API credentials stored encrypted in WordPress options
- Never commit credentials to version control
- Use environment variables or secure config for production
- Webhook endpoint has no auth (CJ doesn't support it) - protect via IP whitelist if needed
- All API calls use HTTPS

## Support

For CJ API issues:
- [CJ Developer Docs](https://developer.cjdropshipping.com)
- [CJ Support](https://cjdropshipping.com/contact)

For integration issues:
- Check WordPress error logs
- Review CJ webhook delivery logs
- Test manually via REST client (Postman)
