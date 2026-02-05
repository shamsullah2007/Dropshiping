<?php
/**
 * Beautiful CJ Dropshipping Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

function cw_cj_admin_page() {
    // Handle form submission
    if (isset($_POST['submit']) && check_admin_referer('cw_cj_settings')) {
        $cj = cw_cj_dropshipping();
        $result = $cj->set_credentials(
            $_POST['cj_api_key'] ?? '',
            $_POST['cj_platform_token'] ?? ''
        );
        
        if ($result) {
            echo '<div class="cj-notice cj-notice-success"><p>✓ CJ credentials saved and verified!</p></div>';
        } else {
            echo '<div class="cj-notice cj-notice-error"><p>✗ Failed to verify credentials. Check your API Key.</p></div>';
        }
    }
    
    // Get current credentials
    $api_key = get_option('cw_cj_api_key', '');
    $platform_token = get_option('cw_cj_platform_token', '');
    $balance = CJ_Dropshipping::has_credentials() ? cw_cj_dropshipping()->get_balance() : 0;
    ?>
    <style>
        .cj-dashboard {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .cj-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.25);
        }
        
        .cj-header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .cj-header p {
            margin: 8px 0 0 0;
            opacity: 0.95;
            font-size: 16px;
        }
        
        .cj-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .cj-card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .cj-card:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }
        
        .cj-card h3 {
            margin: 0 0 12px 0;
            color: #667eea;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cj-stat {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin: 10px 0;
        }
        
        .cj-stat-label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        
        .cj-balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .cj-balance h3,
        .cj-balance .cj-stat-label {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .cj-balance .cj-stat {
            color: white;
        }
        
        .cj-section {
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .cj-section h2 {
            margin-top: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .cj-form-group {
            margin-bottom: 24px;
        }
        
        .cj-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        
        .cj-form-group input[type="text"],
        .cj-form-group input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
            background: white;
        }
        
        .cj-form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #f9fafb;
        }
        
        .cj-description {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
            line-height: 1.5;
        }
        
        .cj-description a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .cj-description a:hover {
            color: #764ba2;
        }
        
        .cj-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 13px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cj-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.35);
        }
        
        .cj-button:active {
            transform: translateY(0);
        }
        
        .cj-import-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .cj-notice {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .cj-notice-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        
        .cj-notice-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .cj-import-status {
            display: none;
            padding: 24px;
            background: linear-gradient(135deg, #f3f4f6 0%, #f9fafb 100%);
            border-radius: 10px;
            text-align: center;
            margin-top: 25px;
            border: 2px solid #e5e7eb;
        }
        
        .cj-spinner {
            display: inline-block;
            width: 28px;
            height: 28px;
            border: 3px solid #d1d5db;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 12px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .cj-import-form .cj-form-group {
            margin-bottom: 0;
        }
    </style>
    
    <div class="cj-dashboard">
        <div class="cj-header">
            <h1>🚀 CJ Dropshipping Hub</h1>
            <p>Manage your dropshipping integration - Import products, check balance, monitor performance</p>
        </div>
        
        <?php if ($balance > 0): ?>
            <div class="cj-cards">
                <div class="cj-card cj-balance">
                    <h3>Account Balance</h3>
                    <div class="cj-stat">${<?php echo number_format($balance, 2); ?></div>
                    <p class="cj-stat-label">Ready to fulfill orders</p>
                </div>
                
                <div class="cj-card">
                    <h3>✓ Status</h3>
                    <p style="color: #10b981; font-weight: 700; margin: 8px 0;">Connected</p>
                    <p class="cj-stat-label">Ready to import products</p>
                </div>
                
                <div class="cj-card">
                    <h3>📦 Products</h3>
                    <p style="margin: 8px 0;">View your imported products in WooCommerce Products section</p>
                    <p class="cj-stat-label">Auto-linked to CJ</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- API Key Configuration -->
        <div class="cj-section">
            <h2>🔑 API Configuration</h2>
            <form method="post" class="cj-form">
                <?php wp_nonce_field('cw_cj_settings'); ?>
                
                <div class="cj-form-group">
                    <label for="cj_api_key">CJ API Key</label>
                    <input type="text" 
                           id="cj_api_key" 
                           name="cj_api_key" 
                           value="<?php echo esc_attr($api_key); ?>" 
                           placeholder="CJUserNum@api@xxxxxxxx..."
                           required>
                    <div class="cj-description">
                        Get from <a href="https://developer.cjdropshipping.com/account/info" target="_blank">CJ Developer Account ↗</a><br>
                        Format: <code>CJUserNum@api@xxxxxxxxxxxxxxxx...</code>
                    </div>
                </div>
                
                <div class="cj-form-group">
                    <label for="cj_platform_token">Platform Token <span style="font-weight: 400; color: #9ca3af;">(Optional)</span></label>
                    <input type="text" 
                           id="cj_platform_token" 
                           name="cj_platform_token" 
                           value="<?php echo esc_attr($platform_token); ?>" 
                           placeholder="Leave empty for standard use">
                    <div class="cj-description">For multi-platform orders</div>
                </div>
                
                <button type="submit" class="cj-button" name="submit">Save CJ Credentials</button>
            </form>
        </div>
        
        <!-- Product Import -->
        <div class="cj-section">
            <h2>📥 Import Products</h2>
            <p style="color: #6b7280; margin-bottom: 20px; line-height: 1.6;">
                Automatically import CJ products with titles, descriptions, and pricing. Products are instantly linked to CJ variants for automatic order creation.
            </p>
            
            <form id="cj-import-form" class="cj-import-form">
                <?php wp_nonce_field('cw_cj_import', 'cw_cj_import_nonce'); ?>
                
                <div class="cj-form-group">
                    <label for="import_search">Search Products</label>
                    <input type="text" 
                           id="import_search" 
                           name="import_search" 
                           placeholder="hoodie, mug, shirt...">
                    <div class="cj-description">Leave empty to import all</div>
                </div>
                
                <div class="cj-form-group">
                    <label for="import_markup">Price Markup (%)</label>
                    <input type="number" 
                           id="import_markup" 
                           name="import_markup" 
                           value="50" 
                           min="0" 
                           max="500">
                    <div class="cj-description">50 = 50% markup</div>
                </div>
                
                <div class="cj-form-group">
                    <label for="import_limit">Max Products</label>
                    <input type="number" 
                           id="import_limit" 
                           name="import_limit" 
                           value="10" 
                           min="1" 
                           max="500">
                    <div class="cj-description">Start with 10</div>
                </div>
                
                <button type="submit" class="cj-button">
                    <span id="cj-import-btn-text">Start Import</span>
                </button>
            </form>
            
            <div class="cj-import-status" id="cj-import-status">
                <div class="cj-spinner"></div>
                <strong>Importing...</strong> <span id="cj-import-count">0</span> products
            </div>
            
            <div id="cj-import-results" style="display: none; margin-top: 20px;">
                <div class="cj-notice cj-notice-success">
                    <p id="cj-import-success-msg"></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(function($) {
        $('#cj-import-form').on('submit', function(e) {
            e.preventDefault();
            
            const search = $('#import_search').val();
            const markup = $('#import_markup').val();
            const limit = $('#import_limit').val();
            const nonce = $('input[name="cw_cj_import_nonce"]').val();
            
            $('#cj-import-status').show();
            $('#cj-import-results').hide();
            $('#cj-import-btn-text').text('Processing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cw_cj_import_ajax',
                    cw_cj_import_nonce: nonce,
                    search: search,
                    markup: markup,
                    limit: limit
                },
                success: function(response) {
                    $('#cj-import-status').hide();
                    $('#cj-import-btn-text').text('Start Import');
                    
                    if (response.success) {
                        $('#cj-import-success-msg').html('✓ ' + response.data.message);
                        $('#cj-import-results').show();
                    } else {
                        alert('❌ ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    $('#cj-import-status').hide();
                    $('#cj-import-btn-text').text('Start Import');
                    const message = xhr?.responseJSON?.data?.message || xhr?.responseText || 'Request failed.';
                    alert('❌ ' + message);
                }
            });
        });
    });
    </script>
    <?php
}
