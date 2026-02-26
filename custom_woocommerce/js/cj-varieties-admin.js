/**
 * CJ Product Varieties Admin - Save via AJAX
 * 
 * Provides reliable saving mechanism for delivery charges and ETA fields
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Safety check - ensure localization data exists
    if (typeof cwCJVarietiesAdmin === 'undefined') {
        console.warn('⚠️ cwCJVarietiesAdmin not found - auto-save disabled');
        return;
    }

    const productId = cwCJVarietiesAdmin.product_id;
    const nonce = cwCJVarietiesAdmin.nonce;
    const ajaxUrl = cwCJVarietiesAdmin.ajax_url;

    let saveTimeout = null;

    // Monitor changes to delivery fields
    $(document).on('change input', 'input[name="cw_cj_delivery_charges"], input[name="cw_cj_delivery_eta"]', function () {

        // Clear any pending save
        clearTimeout(saveTimeout);

        // Auto-save after 2 seconds of no changes
        saveTimeout = setTimeout(function () {
            saveDeliveryDetailsAjax();
        }, 2000);
    });

    /**
     * Save delivery details via AJAX
     */
    function saveDeliveryDetailsAjax() {
        const deliveryCharges = $('input[name="cw_cj_delivery_charges"]').val() || '';
        const deliveryEta = $('input[name="cw_cj_delivery_eta"]').val() || '';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'cw_save_delivery_details_admin',
                nonce: nonce,
                product_id: productId,
                delivery_charges: deliveryCharges,
                delivery_eta: deliveryEta,
            },
            success: function (response) {
                if (response.success) {
                    console.log('✓ Auto-saved:', response.data);
                    showNotice('Changes saved automatically', 'success');
                } else {
                    console.error('Save error:', response.data);
                    showNotice('Error saving changes', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotice('Connection error', 'error');
            }
        });
    }

    /**
     * Show temporary notice
     */
    function showNotice(message, type) {
        // Remove existing notice
        $('.cw-delivery-auto-notice').remove();

        const noticeColor = type === 'success' ? '#4CAF50' : '#f44336';
        const noticeHtml = $(`
            <div class="cw-delivery-auto-notice" style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${noticeColor};
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 9999;
                font-size: 14px;
            ">
                ${message}
            </div>
        `);

        $('body').append(noticeHtml);

        // Auto-dismiss after 3 seconds
        setTimeout(function () {
            noticeHtml.fadeOut(300, function () {
                $(this).remove();
            });
        }, 3000);
    }

    console.log('✓ CJ Varieties Admin Auto-Save Initialized');
});

