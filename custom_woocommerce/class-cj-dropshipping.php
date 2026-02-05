<?php
/**
 * CJ Dropshipping Custom Integration Module
 * 
 * Provides complete dropshipping workflow without external plugins:
 * - Product import and management
 * - Order creation and syncing
 * - Payment deduction from CJ balance
 * - Tracking updates via webhooks
 * 
 * @package CustomWoocommerce
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CJ_Dropshipping {
    
    // API Configuration
    const API_BASE_URL = 'https://developers.cjdropshipping.com/api2.0/v1';
    const TOKEN_EXPIRY = 15 * DAY_IN_SECONDS; // 15 days
    const OPTION_PREFIX = 'cw_cj_';
    
    // API Endpoints
    const ENDPOINTS = [
        // Authentication
        'get_token' => '/authentication/getAccessToken',
        
        // Products
        'get_categories' => '/product/getCategory',
        'list_products_v2' => '/product/listV2',
        'list_products' => '/product/list',
        'get_product_details' => '/product/query',
        'get_variants' => '/product/variant/query',
        'get_variant_by_id' => '/product/variant/queryByVid',
        'add_to_my_products' => '/product/addToMyProduct',
        'my_products' => '/product/myProduct/query',
        
        // Inventory
        'inventory_by_vid' => '/product/stock/queryByVid',
        'inventory_by_sku' => '/product/stock/queryBySku',
        'inventory_by_pid' => '/product/stock/getInventoryByPid',
        
        // Orders
        'create_order_v2' => '/shopping/order/createOrderV2',
        'create_order_v3' => '/shopping/order/createOrderV3',
        'add_cart' => '/shopping/order/addCart',
        'add_cart_confirm' => '/shopping/order/addCartConfirm',
        'save_parent_order' => '/shopping/order/saveGenerateParentOrder',
        'list_orders' => '/shopping/order/list',
        'get_order' => '/shopping/order/getOrderDetail',
        'delete_order' => '/shopping/order/deleteOrder',
        'confirm_order' => '/shopping/order/confirmOrder',
        'change_warehouse' => '/shopping/order/changeWarehouse',
        
        // Payment
        'get_balance' => '/shopping/pay/getBalance',
        'pay_balance' => '/shopping/pay/payBalance',
        'pay_balance_v2' => '/shopping/pay/payBalanceV2',
        
        // Shipping
        'upload_waybill' => '/shopping/order/uploadWaybillInfo',
        'update_waybill' => '/shopping/order/updateWaybillInfo',
    ];
    
    // Error Code Map
    const ERROR_CODES = [
        1600100 => 'Parameter error',
        1600200 => 'Request timeout',
        1600300 => 'Item not found',
        1603001 => 'Order confirm failed',
        200 => 'Success',
    ];
    
    private $access_token = null;
    private $api_key = '';
    private $platform_token = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_credentials();
    }
    
    /**
     * Load API credentials from WordPress options
     */
    private function load_credentials() {
        $this->api_key = get_option(self::OPTION_PREFIX . 'api_key', '');
        $this->platform_token = get_option(self::OPTION_PREFIX . 'platform_token', '');
        $this->access_token = $this->get_valid_token();
    }
    
    /**
     * Set API credentials
     * 
     * @param string $api_key CJ API Key (only credential needed)
     * @param string $platform_token Optional platform token
     */
    public function set_credentials($api_key, $platform_token = '') {
        $this->api_key = sanitize_text_field($api_key);
        $this->platform_token = sanitize_text_field($platform_token);
        
        // Save to options
        update_option(self::OPTION_PREFIX . 'api_key', $this->api_key);
        update_option(self::OPTION_PREFIX . 'platform_token', $this->platform_token);
        
        // Reset token to force refresh
        delete_option(self::OPTION_PREFIX . 'access_token');
        delete_option(self::OPTION_PREFIX . 'token_expiry');
        delete_option(self::OPTION_PREFIX . 'refresh_token');
        
        return $this->refresh_access_token();
    }
    
    /**
     * Get valid access token (refresh if expired)
     * 
     * @return string Access token
     */
    private function get_valid_token() {
        $token = get_option(self::OPTION_PREFIX . 'access_token', '');
        $expiry = get_option(self::OPTION_PREFIX . 'token_expiry', 0);
        
        // Check if token exists and is still valid
        if ($token && time() < $expiry) {
            return $token;
        }
        
        // Token expired or doesn't exist, get new one
        return $this->refresh_access_token();
    }
    
    /**
     * Refresh access token using API key
     * 
     * @return string New access token
     */
    public function refresh_access_token() {
        if (empty($this->api_key)) {
            return false;
        }
        
        $url = self::API_BASE_URL . self::ENDPOINTS['get_token'];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'apiKey' => $this->api_key,
            ]),
            'timeout' => 15,
        ]);
        
        if (is_wp_error($response)) {
            error_log('CJ API Token Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['data']['accessToken'])) {
            $token = $data['data']['accessToken'];
            $refresh_token = $data['data']['refreshToken'] ?? '';
            
            // Store token with expiry
            update_option(self::OPTION_PREFIX . 'access_token', $token);
            update_option(self::OPTION_PREFIX . 'token_expiry', time() + self::TOKEN_EXPIRY);
            
            // Store refresh token (180 day expiry)
            if (!empty($refresh_token)) {
                update_option(self::OPTION_PREFIX . 'refresh_token', $refresh_token);
            }
            
            $this->access_token = $token;
            return $token;
        }
        
        error_log('CJ API Token Response: ' . $body);
        return false;
    }
    
    /**
     * Make API request to CJ
     * 
     * @param string $endpoint Endpoint key from ENDPOINTS
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param array $params Request parameters
     * @param array $headers Additional headers
     * @return array|WP_Error Response data or error
     */
    private function request($endpoint, $method = 'GET', $params = [], $headers = []) {
        if (!$this->access_token) {
            return new WP_Error('no_token', 'CJ access token not available');
        }
        
        if (!isset(self::ENDPOINTS[$endpoint])) {
            return new WP_Error('invalid_endpoint', 'Invalid endpoint: ' . $endpoint);
        }
        
        $url = self::API_BASE_URL . self::ENDPOINTS[$endpoint];
        
        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $request_args = [
            'method' => $method,
            'headers' => array_merge([
                'Content-Type' => 'application/json',
                'CJ-Access-Token' => $this->access_token,
            ], $headers),
            'timeout' => 15,
        ];
        
        // Add body for POST/PATCH/DELETE
        if (in_array($method, ['POST', 'PATCH', 'DELETE']) && !empty($params)) {
            $request_args['body'] = wp_json_encode($params);
        }
        
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            error_log('CJ API Request Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Log failures
        if (isset($data['result']) && !$data['result']) {
            error_log('CJ API Error [' . $endpoint . ']: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    // ==================== PRODUCT METHODS ====================
    
    /**
     * Get product categories
     * 
     * @return array Category hierarchy
     */
    public function get_categories() {
        $response = $this->request('get_categories');
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * List products with search and filters
     * 
     * @param array $args Search parameters
     * @return array Product list with pagination
     */
    public function list_products($args = []) {
        $defaults = [
            'keyWord' => '',
            'page' => 1,
            'size' => 20,
            'categoryId' => '',
            'countryCode' => 'US',
            'startSellPrice' => '',
            'endSellPrice' => '',
            'productType' => '',
        ];
        
        $params = wp_parse_args($args, $defaults);
        
        // Use V2 for better performance
        $response = $this->request('list_products_v2', 'GET', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Get product details with variants
     * 
     * @param string $pid Product ID
     * @param bool $include_inventory Include inventory info
     * @return array Product details
     */
    public function get_product_details($pid, $include_inventory = true) {
        $params = [
            'pid' => $pid,
        ];
        
        if ($include_inventory) {
            $params['features'] = ['enable_inventory', 'enable_video', 'enable_description'];
        }
        
        $response = $this->request('get_product_details', 'GET', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Get product variants
     * 
     * @param string $pid Product ID
     * @param string $country_code Filter by country
     * @return array Variants list
     */
    public function get_variants($pid, $country_code = '') {
        $params = ['pid' => $pid];
        
        if (!empty($country_code)) {
            $params['countryCode'] = $country_code;
        }
        
        $response = $this->request('get_variants', 'GET', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    // ==================== INVENTORY METHODS ====================
    
    /**
     * Query inventory by variant ID
     * 
     * @param string $vid Variant ID
     * @return array Inventory data by warehouse
     */
    public function get_inventory_by_vid($vid) {
        $response = $this->request('inventory_by_vid', 'GET', ['vid' => $vid]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Query inventory by SKU
     * 
     * @param string $sku Product SKU
     * @return array Inventory data by warehouse
     */
    public function get_inventory_by_sku($sku) {
        $response = $this->request('inventory_by_sku', 'GET', ['sku' => $sku]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    // ==================== ORDER METHODS ====================
    
    /**
     * Create CJ order
     * 
     * @param array $order_data WooCommerce order data
     * @param array $products Products array with vid/sku and quantity
     * @param int $pay_type Payment type (2=balance, 3=no balance)
     * @return array|WP_Error Order response with orderId
     */
    public function create_order($order_data, $products, $pay_type = 3) {
        $params = [
            'orderNumber' => $order_data['order_number'],
            'shippingCountryCode' => $order_data['country_code'],
            'shippingCountry' => $order_data['country'],
            'shippingProvince' => $order_data['state'],
            'shippingCity' => $order_data['city'],
            'shippingPhone' => $order_data['phone'],
            'shippingCustomerName' => $order_data['first_name'] . ' ' . $order_data['last_name'],
            'shippingAddress' => $order_data['address_1'],
            'shippingAddress2' => $order_data['address_2'] ?? '',
            'shippingZip' => $order_data['postcode'],
            'email' => $order_data['email'] ?? '',
            'remark' => $order_data['remark'] ?? '',
            'logisticName' => $order_data['shipping_method'] ?? 'CJPacket',
            'fromCountryCode' => 'CN',
            'platform' => 'woocommerce',
            'payType' => $pay_type,
            'products' => $products,
        ];
        
        return $this->request('create_order_v2', 'POST', $params);
    }
    
    /**
     * Add order to cart
     * 
     * @param array $cj_order_ids CJ order IDs
     * @return array Response
     */
    public function add_to_cart($cj_order_ids) {
        $params = ['cjOrderIdList' => (array) $cj_order_ids];
        return $this->request('add_cart', 'POST', $params);
    }
    
    /**
     * Confirm cart
     * 
     * @param array $cj_order_ids CJ order IDs
     * @return array Response
     */
    public function confirm_cart($cj_order_ids) {
        $params = ['cjOrderIdList' => (array) $cj_order_ids];
        return $this->request('add_cart_confirm', 'POST', $params);
    }
    
    /**
     * Generate parent order for payment
     * 
     * @param string $shipment_order_id Shipment order ID
     * @return array Payment information
     */
    public function generate_parent_order($shipment_order_id) {
        $params = ['shipmentOrderId' => $shipment_order_id];
        $response = $this->request('save_parent_order', 'POST', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * List orders
     * 
     * @param array $args Query parameters
     * @return array Orders list
     */
    public function list_orders($args = []) {
        $defaults = [
            'pageNum' => 1,
            'pageSize' => 20,
            'status' => 'CREATED',
        ];
        
        $params = wp_parse_args($args, $defaults);
        $response = $this->request('list_orders', 'GET', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Get order details
     * 
     * @param string $order_id CJ order ID
     * @param array $features Optional features
     * @return array Order details
     */
    public function get_order($order_id, $features = []) {
        $params = ['orderId' => $order_id];
        
        if (!empty($features)) {
            $params['features'] = $features;
        }
        
        $response = $this->request('get_order', 'GET', $params);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        return isset($response['data']) ? $response['data'] : [];
    }
    
    /**
     * Confirm order
     * 
     * @param string $order_id Order ID
     * @return bool Success
     */
    public function confirm_order($order_id) {
        $response = $this->request('confirm_order', 'PATCH', ['orderId' => $order_id]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return isset($response['result']) && $response['result'];
    }
    
    /**
     * Delete order
     * 
     * @param string $order_id Order ID
     * @return bool Success
     */
    public function delete_order($order_id) {
        $response = $this->request('delete_order', 'DELETE', ['orderId' => $order_id]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return isset($response['result']) && $response['result'];
    }
    
    // ==================== PAYMENT METHODS ====================
    
    /**
     * Get account balance
     * 
     * @return float Account balance in USD
     */
    public function get_balance() {
        $response = $this->request('get_balance');
        
        // Debug logging to understand API response format
        error_log('CJ Balance Response: ' . json_encode($response, JSON_PRETTY_PRINT));
        
        if (is_wp_error($response)) {
            error_log('CJ Balance Error: ' . $response->get_error_message());
            return 0;
        }
        
        // Try different response formats
        if (isset($response['data']['amount'])) {
            return (float) $response['data']['amount'];
        }
        
        // Sometimes API returns directly at root level
        if (isset($response['amount'])) {
            return (float) $response['amount'];
        }
        
        // Or in data object
        if (isset($response['data']) && is_array($response['data'])) {
            // Try to find any numeric field that could be balance
            foreach ($response['data'] as $key => $value) {
                if (in_array($key, ['amount', 'balance', 'accountBalance', 'totalAmount'])) {
                    return (float) $value;
                }
            }
        }
        
        error_log('CJ Balance: No recognizable balance field found in response');
        return 0;
    }
    
    /**
     * Pay from balance (for single order)
     * 
     * @param string $order_id Order ID
     * @return bool Success
     */
    public function pay_balance($order_id) {
        $response = $this->request('pay_balance', 'POST', ['orderId' => $order_id]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return isset($response['result']) && $response['result'];
    }
    
    /**
     * Pay from balance V2 (for shipment order)
     * 
     * @param string $shipment_order_id Shipment order ID
     * @param string $pay_id Payment ID
     * @return bool Success
     */
    public function pay_balance_v2($shipment_order_id, $pay_id) {
        $params = [
            'shipmentOrderId' => $shipment_order_id,
            'payId' => $pay_id,
        ];
        
        $response = $this->request('pay_balance_v2', 'POST', $params);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return isset($response['result']) && $response['result'];
    }
    
    // ==================== SHIPPING METHODS ====================
    
    /**
     * Upload waybill/shipping label
     * 
     * @param string $order_id Order ID
     * @param string $cj_order_id CJ Order ID
     * @param string $shipping_company Shipping company name
     * @param string $track_number Tracking number
     * @param string $file_path Path to waybill file (PDF)
     * @return bool Success
     */
    public function upload_waybill($order_id, $cj_order_id, $shipping_company, $track_number, $file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Use WP HTTP with multipart form data
        $boundary = wp_generate_password(24);
        
        $body = '';
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="orderId"' . "\r\n\r\n";
        $body .= $order_id . "\r\n";
        
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="cjOrderId"' . "\r\n\r\n";
        $body .= $cj_order_id . "\r\n";
        
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="cjShippingCompanyName"' . "\r\n\r\n";
        $body .= $shipping_company . "\r\n";
        
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="trackNumber"' . "\r\n\r\n";
        $body .= $track_number . "\r\n";
        
        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="waybillFile"; filename="' . basename($file_path) . '"' . "\r\n";
        $body .= 'Content-Type: application/pdf' . "\r\n\r\n";
        $body .= file_get_contents($file_path) . "\r\n";
        $body .= '--' . $boundary . '--';
        
        $url = self::API_BASE_URL . self::ENDPOINTS['upload_waybill'];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'CJ-Access-Token' => $this->access_token,
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body' => $body,
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return isset($data['result']) && $data['result'];
    }
    
    // ==================== UTILITY METHODS ====================
    
    /**
     * Get error message from error code
     * 
     * @param int $code Error code
     * @return string Error message
     */
    public static function get_error_message($code) {
        return isset(self::ERROR_CODES[$code]) ? self::ERROR_CODES[$code] : 'Unknown error';
    }
    
    /**
     * Check if current user has CJ credentials
     * 
     * @return bool
     */
    public static function has_credentials() {
        return !empty(get_option(self::OPTION_PREFIX . 'api_key'));
    }
    
    /**
     * Store CJ order mapping in WordPress
     * 
     * @param int $woo_order_id WooCommerce order ID
     * @param string $cj_order_id CJ order ID
     */
    public static function map_woo_to_cj_order($woo_order_id, $cj_order_id) {
        $order = wc_get_order($woo_order_id);
        if ($order) {
            $order->update_meta_data('_cj_order_id', sanitize_text_field($cj_order_id));
            $order->save();
        }
    }
    
    /**
     * Get mapped CJ order ID from WooCommerce order
     * 
     * @param int $woo_order_id WooCommerce order ID
     * @return string|false CJ order ID or false
     */
    public static function get_cj_order_id($woo_order_id) {
        $order = wc_get_order($woo_order_id);
        if ($order) {
            return $order->get_meta('_cj_order_id');
        }
        return false;
    }
}

// Initialize singleton instance
function cw_cj_dropshipping() {
    static $instance = null;
    
    if (null === $instance) {
        $instance = new CJ_Dropshipping();
    }
    
    return $instance;
}

// Instantiate on init
add_action('init', function() {
    cw_cj_dropshipping();
});
