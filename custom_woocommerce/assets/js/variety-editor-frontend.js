/**
 * Front-End Variety Editor
 * Allows admins to edit product varieties from the Product Manager page
 */

(function ($) {
    'use strict';

    const UI = {
        modal: $('#cwVarietyEditorModal'),
        list: $('#cwVarietiesList'),
        addBtn: $('#cwAddVarietyBtn'),
        closeButtons: $('.cw-close-editor'),
        autoSaveNotice: $('.cw-auto-save-notice'),
        deliveryCharges: $('#cwDeliveryCharges'),
        deliveryEta: $('#cwDeliveryEta'),
        saveDeliveryBtn: $('#cwSaveDeliveryDetails'),
    };

    let currentProductId = null;
    let currentVarieties = [];

    // Edit product button click
    $(document).on('click', '.edit-product-btn', function () {
        const productId = $(this).data('product-id');
        loadVarietiesForEditing(productId);
        UI.modal.show();
    });

    // Close modal
    UI.closeButtons.on('click', function (e) {
        e.preventDefault();
        UI.modal.hide();
    });

    // Close modal when clicking outside
    UI.modal.on('click', function (e) {
        if ($(e.target).is('#cwVarietyEditorModal')) {
            UI.modal.hide();
        }
    });

    // Add new variety row
    UI.addBtn.on('click', function () {
        addVarietyRow();
    });

    // Save delivery details
    UI.saveDeliveryBtn.on('click', function () {
        if (!currentProductId) {
            showError('No product selected');
            return;
        }

        const deliveryCharges = UI.deliveryCharges.val().trim();
        const deliveryEta = UI.deliveryEta.val().trim();

        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: cwVarietyEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'cw_save_delivery_details_frontend',
                product_id: currentProductId,
                delivery_charges: deliveryCharges,
                delivery_eta: deliveryEta,
                nonce: cwVarietyEditor.nonce,
            },
            success: function (response) {
                if (response.success) {
                    showSuccess('Delivery details saved successfully!');
                } else {
                    showError(response.data || 'Failed to save delivery details');
                }
            },
            error: function () {
                showError('Error saving delivery details');
            },
            complete: function () {
                UI.saveDeliveryBtn.prop('disabled', false).text('Save Delivery Details');
            }
        });
    });

    /**
     * Load varieties for editing
     */
    function loadVarietiesForEditing(productId) {
        currentProductId = productId;

        $.ajax({
            url: cwVarietyEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'cw_get_product_varieties_edit',
                product_id: productId,
                nonce: cwVarietyEditor.nonce,
            },
            success: function (response) {
                if (response.success) {
                    currentVarieties = response.data.varieties;

                    // Update modal title
                    $('#cwVarietyEditorModal .cw-variety-editor-header h2').text(
                        'Edit Varieties: ' + response.data.product_name
                    );

                    // Render varieties
                    renderVarieties(currentVarieties);

                    if (UI.deliveryCharges.length) {
                        UI.deliveryCharges.val(response.data.delivery_charges || '');
                    }
                    if (UI.deliveryEta.length) {
                        UI.deliveryEta.val(response.data.delivery_eta || '');
                    }
                } else {
                    showError(response.data || 'Failed to load varieties');
                }
            },
            error: function () {
                showError('Error loading varieties. Please try again.');
            }
        });
    }

    /**
     * Render varieties list
     */
    function renderVarieties(varieties) {
        UI.list.empty();

        if (varieties.length === 0) {
            UI.list.html('<p style="color: #666; padding: 20px; text-align: center;">' +
                'No varieties added yet. Click "Add Variety" to create one.' +
                '</p>');
            return;
        }

        varieties.forEach((variety, index) => {
            addVarietyRow(index, variety);
        });
    }

    /**
     * Add or render a variety row
     */
    function addVarietyRow(index = null, variety = null) {
        const isNew = index === null;
        const idx = isNew ? currentVarieties.length : index;

        const imageSrc = variety && variety.image_id ?
            cwVarietyEditor.imageUrl || '' :
            'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="80" height="80"%3E%3Crect fill="%23ddd" width="80" height="80"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-size="12"%3ENo Image%3C/text%3E%3C/svg%3E';

        const rowHtml = `
            <div class="cw-variety-row" data-index="${idx}">
                <div class="cw-variety-field">
                    <label>Variety Image</label>
                    <div class="cw-image-preview">
                        <img src="${imageSrc}" alt="Variety Image" class="variety-image-preview">
                        <input type="hidden" class="variety-image-id" value="${variety?.image_id || ''}">
                    </div>
                    <button type="button" class="button button-secondary cw-upload-image">
                        Upload Image
                    </button>
                </div>
                
                <div class="cw-variety-field">
                    <label>Color/Name <span style="color: red;">*</span></label>
                    <input type="text" class="variety-color-name" value="${variety?.color_name || ''}" 
                           placeholder="e.g., Black, Large, M" required>
                </div>
                
                <div class="cw-variety-field">
                    <label>Price</label>
                    <input type="number" class="variety-price" value="${variety?.price || ''}" 
                           placeholder="Optional price override" 
                           step="0.01" min="0">
                </div>
                
                <div class="cw-variety-actions">
                    <button type="button" class="button button-small cw-save-variety">
                        Save
                    </button>
                    <button type="button" class="button button-small button-link-delete cw-delete-variety">
                        Delete
                    </button>
                </div>
            </div>
        `;

        if (isNew) {
            UI.list.append(rowHtml);
        } else {
            $(`[data-index="${idx}"]`).replaceWith(rowHtml);
        }
    }

    /**
     * Upload image for variety
     */
    $(document).on('click', '.cw-upload-image', function (e) {
        e.preventDefault();
        const frame = wp.media({
            title: 'Select Variety Image',
            button: {
                text: 'Use Image',
            },
            multiple: false,
        });

        const $row = $(this).closest('.cw-variety-row');

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $row.find('.variety-image-id').val(attachment.id);
            $row.find('.variety-image-preview').attr('src', attachment.url);
        });

        frame.open();
    });

    /**
     * Save variety
     */
    $(document).on('click', '.cw-save-variety', function (e) {
        e.preventDefault();

        const $row = $(this).closest('.cw-variety-row');
        const index = $row.data('index');
        const colorName = $row.find('.variety-color-name').val().trim();
        const price = $row.find('.variety-price').val() || 0;
        const imageId = $row.find('.variety-image-id').val() || 0;

        if (!colorName) {
            showError('Please enter a color/name');
            return;
        }

        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: cwVarietyEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'cw_save_variety_frontend',
                product_id: currentProductId,
                variety_index: index,
                color_name: colorName,
                price: price,
                image_id: imageId,
                nonce: cwVarietyEditor.nonce,
            },
            success: function (response) {
                if (response.success) {
                    currentVarieties = response.data.varieties;
                    showSuccess('Variety saved successfully!');
                    renderVarieties(currentVarieties);
                } else {
                    showError(response.data || 'Failed to save variety');
                }
            },
            error: function () {
                showError('Error saving variety');
            },
            complete: function () {
                $row.find('.cw-save-variety').prop('disabled', false).text('Save');
            }
        });
    });

    /**
     * Delete variety
     */
    $(document).on('click', '.cw-delete-variety', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this variety?')) {
            return;
        }

        const $row = $(this).closest('.cw-variety-row');
        const index = $row.data('index');

        $(this).prop('disabled', true);

        $.ajax({
            url: cwVarietyEditor.ajax_url,
            type: 'POST',
            data: {
                action: 'cw_delete_variety_frontend',
                product_id: currentProductId,
                variety_index: index,
                nonce: cwVarietyEditor.nonce,
            },
            success: function (response) {
                if (response.success) {
                    currentVarieties = response.data.varieties;
                    showSuccess('Variety deleted successfully!');
                    renderVarieties(currentVarieties);
                } else {
                    showError(response.data || 'Failed to delete variety');
                }
            },
            error: function () {
                showError('Error deleting variety');
            },
            complete: function () {
                $row.find('.cw-delete-variety').prop('disabled', false);
            }
        });
    });

    /**
     * Show success message
     */
    function showSuccess(message) {
        UI.autoSaveNotice.text('✓ ' + message).show();
        setTimeout(() => {
            UI.autoSaveNotice.fadeOut();
        }, 3000);
    }

    /**
     * Show error message
     */
    function showError(message) {
        alert('Error: ' + message);
    }

})(jQuery);
