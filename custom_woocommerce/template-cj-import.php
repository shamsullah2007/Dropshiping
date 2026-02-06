<?php
/**
 * Template Name: CJ Product Import
 * Description: Admin-only page for importing CJ products
 * 
 * Usage: Create a page with this template to get the import dashboard
 */

if (!is_user_logged_in()) {
    wp_die('<div style="padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; color: #991b1b; margin: 20px;"><strong>⚠️ Login Required:</strong> Please log in as an administrator to access the import dashboard.</div>');
}

if (!current_user_can('manage_options')) {
    wp_die('<div style="padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; color: #991b1b; margin: 20px;"><strong>❌ Access Denied:</strong> Only administrators can access the import dashboard.</div>');
}

wp_enqueue_script('jquery');
get_header();
?>

<style>
    .cj-frontend-dashboard {
        max-width: 1200px;
        margin: 20px auto;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        padding: 20px;
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
    .cj-form-group input[type="number"],
    .cj-form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
        background: white;
        font-family: inherit;
    }
    
    .cj-form-group input:focus,
    .cj-form-group textarea:focus {
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
    
    .cj-button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(102, 126, 234, 0.35);
    }
    
    .cj-button:active {
        transform: translateY(0);
    }
    
    .cj-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
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

<div class="cj-frontend-dashboard">
    <div class="cj-header">
        <h1>🚀 CJ Product Import Dashboard</h1>
        <p>Import products from CJ Dropshipping catalog using keywords or direct product links</p>
    </div>
    
    <div class="cj-section">
        <h2>📥 Import Products</h2>
        <p style="color: #6b7280; margin-bottom: 20px; line-height: 1.6;">
            Automatically import CJ products with titles, descriptions, and pricing. Products are instantly linked to CJ variants for automatic order creation.
        </p>
        
        <!-- Search Import Form -->
        <h3 style="color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Method 1: Search by Keyword</h3>
        <form id="cj-import-form-search-frontend" class="cj-import-form" style="margin-bottom: 40px;">
            <?php wp_nonce_field('cw_cj_import', 'cw_cj_import_nonce'); ?>
            
            <div class="cj-form-group">
                <label for="import_search_fe">Search Products</label>
                <input type="text" 
                       id="import_search_fe" 
                       name="import_search" 
                       placeholder="hoodie, mug, shirt...">
                <div class="cj-description">Leave empty to import all</div>
            </div>
            
            <div class="cj-form-group">
                <label for="import_markup_fe">Price Markup (%)</label>
                <input type="number" 
                       id="import_markup_fe" 
                       name="import_markup" 
                       value="50" 
                       min="0" 
                       max="500">
                <div class="cj-description">50 = 50% markup</div>
            </div>
            
            <div class="cj-form-group">
                <label for="import_limit_fe">Max Products</label>
                <input type="number" 
                       id="import_limit_fe" 
                       name="import_limit" 
                       value="10" 
                       min="1" 
                       max="500">
                <div class="cj-description">Start with 10</div>
            </div>
            
            <div class="cj-form-group">
                <label style="display: flex; align-items: center; cursor: pointer; text-transform: none;">
                    <input type="checkbox" 
                           id="skip_images_search_fe" 
                           name="skip_images" 
                           value="1"
                           style="width: auto; margin-right: 8px;">
                    Skip Images (Faster Import)
                </label>
                <div class="cj-description">Import products without images for 3-5x faster speed. You can add images later.</div>
            </div>
            
            <button type="submit" class="cj-button" id="cj-import-btn-search-fe">
                <span id="cj-import-btn-text-search-fe">Start Search Import</span>
            </button>
        </form>
        
        <!-- Link Import Form -->
        <h3 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px;">Method 2: Import by Product Links</h3>
        <form id="cj-import-form-links-frontend" style="margin-bottom: 20px;">
            <?php wp_nonce_field('cw_cj_import', 'cw_cj_import_nonce'); ?>
            
            <div class="cj-form-group">
                <label for="import_single_link_fe">Single Product Link</label>
                <input type="text" 
                       id="import_single_link_fe" 
                       name="import_single_link" 
                       placeholder="https://cjdropshipping.com/product/...">
                <div class="cj-description">Paste a single CJ product link to import one product</div>
            </div>
            
            <div class="cj-form-group">
                <label for="import_bulk_links_fe">Bulk Product Links</label>
                <textarea id="import_bulk_links_fe" 
                          name="import_bulk_links" 
                          placeholder="Paste multiple CJ product links (one per line)&#10;https://cjdropshipping.com/product/...&#10;https://cjdropshipping.com/product/..."
                          style="min-height: 120px;"></textarea>
                <div class="cj-description">One link per line. Single link will be imported first if provided.</div>
            </div>
            
            <div style="display: grid; grid-template-columns: auto auto; gap: 20px; align-items: end;">
                <div class="cj-form-group">
                    <label for="import_link_markup_fe">Price Markup (%)</label>
                    <input type="number" 
                           id="import_link_markup_fe" 
                           name="import_link_markup" 
                           value="50" 
                           min="0" 
                           max="500">
                    <div class="cj-description">50 = 50% markup</div>
                </div>
                
                <div class="cj-form-group">
                    <label style="display: flex; align-items: center; cursor: pointer; text-transform: none;">
                        <input type="checkbox" 
                               id="skip_images_links_fe" 
                               name="skip_images" 
                               value="1"
                               style="width: auto; margin-right: 8px;">
                        Skip Images (Faster)
                    </label>
                    <div class="cj-description">3-5x faster speed</div>
                </div>
            </div>
            <button type="submit" class="cj-button" id="cj-import-btn-links-fe" style="margin-top: 10px;">
                <span id="cj-import-btn-text-links-fe">Start Link Import</span>
            </button>
        </form>
        
        <div class="cj-import-status" id="cj-import-status-fe">
            <div class="cj-spinner"></div>
            <strong>Importing...</strong> <span id="cj-import-count-fe">0</span> products
        </div>
        
        <div id="cj-import-results-fe" style="display: none; margin-top: 20px;">
            <div class="cj-notice cj-notice-success">
                <p id="cj-import-success-msg-fe"></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Handle Search Import
    $('#cj-import-form-search-frontend').on('submit', function(e) {
        e.preventDefault();
        
        const search = $('#import_search_fe').val();
        const markup = $('#import_markup_fe').val();
        const limit = $('#import_limit_fe').val();
        const skip_images = $('#skip_images_search_fe').is(':checked');
        const nonce = $('input[name="cw_cj_import_nonce"]').val();
        
        $('#cj-import-status-fe').show();
        $('#cj-import-results-fe').hide();
        $('#cj-import-btn-text-search-fe').text('Processing...');
        $('#cj-import-btn-search-fe').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cw_cj_import_ajax',
                cw_cj_import_nonce: nonce,
                mode: 'search',
                search: search,
                markup: markup,
                limit: limit,
                skip_images: skip_images ? 'true' : 'false'
            },
            success: function(response) {
                $('#cj-import-status-fe').hide();
                $('#cj-import-btn-text-search-fe').text('Start Search Import');
                $('#cj-import-btn-search-fe').prop('disabled', false);
                
                if (response.success) {
                    $('#cj-import-success-msg-fe').html('✓ ' + response.data.message);
                    $('#cj-import-results-fe').show();
                } else {
                    alert('❌ ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                $('#cj-import-status-fe').hide();
                $('#cj-import-btn-text-search-fe').text('Start Search Import');
                $('#cj-import-btn-search-fe').prop('disabled', false);
                const message = xhr?.responseJSON?.data?.message || xhr?.responseText || 'Request failed.';
                alert('❌ ' + message);
            }
        });
    });
    
    // Handle Link Import
    $('#cj-import-form-links-frontend').on('submit', function(e) {
        e.preventDefault();
        
        // Extract CJ product ID from URL
        function extractProductId(url) {
            url = url.trim();
            let match = url.match(/-p-(\d+)\.html/i);
            if (match && match[1]) return match[1];
            match = url.match(/\/product\/([a-zA-Z0-9]+)/i);
            if (match && match[1]) return match[1];
            match = url.match(/[?&](?:id|pid|productId|product_id)=([a-zA-Z0-9]+)/i);
            if (match && match[1]) return match[1];
            match = url.match(/#.*id=([a-zA-Z0-9]+)/i);
            if (match && match[1]) return match[1];
            return null;
        }
        
        const singleLink = $('#import_single_link_fe').val().trim();
        const bulkLinks = $('#import_bulk_links_fe').val().trim();
        const markup = $('#import_link_markup_fe').val();
        const skip_images = $('#skip_images_links_fe').is(':checked');
        const nonce = $('input[name="cw_cj_import_nonce"]').val();
        
        let productIds = [];
        
        if (singleLink) {
            const id = extractProductId(singleLink);
            if (id) {
                productIds.push(id);
            } else {
                alert('❌ Invalid single product link. Please check the URL format.');
                return;
            }
        }
        
        if (bulkLinks) {
            const links = bulkLinks.split('\n');
            for (let i = 0; i < links.length; i++) {
                const link = links[i].trim();
                if (link) {
                    const id = extractProductId(link);
                    if (id) {
                        if (!productIds.includes(id)) {
                            productIds.push(id);
                        }
                    } else {
                        alert('❌ Invalid product link at line ' + (i + 1) + ': ' + link);
                        return;
                    }
                }
            }
        }
        
        if (productIds.length === 0) {
            alert('❌ Please provide at least one product link');
            return;
        }
        
        $('#cj-import-status-fe').show();
        $('#cj-import-results-fe').hide();
        $('#cj-import-btn-text-links-fe').text('Processing...');
        $('#cj-import-btn-links-fe').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $.param({
                action: 'cw_cj_import_ajax',
                cw_cj_import_nonce: nonce,
                mode: 'links',
                product_ids: productIds,
                markup: markup
            }, true),
            success: function(response) {
                $('#cj-import-status-fe').hide();
                $('#cj-import-btn-text-links-fe').text('Start Link Import');
                $('#cj-import-btn-links-fe').prop('disabled', false);
                
                if (response.success) {
                    $('#cj-import-success-msg-fe').html('✓ ' + response.data.message);
                    $('#cj-import-results-fe').show();
                    $('#import_single_link_fe').val('');
                    $('#import_bulk_links_fe').val('');
                } else {
                    alert('❌ ' + (response.data?.message || 'Unknown error'));
                }
            },
            error: function(xhr) {
                $('#cj-import-status-fe').hide();
                $('#cj-import-btn-text-links-fe').text('Start Link Import');
                $('#cj-import-btn-links-fe').prop('disabled', false);
                const message = xhr?.responseJSON?.data?.message || xhr?.responseText || 'Request failed.';
                alert('❌ ' + message);
            }
        });
    });
});
</script>

<?php
get_footer();
