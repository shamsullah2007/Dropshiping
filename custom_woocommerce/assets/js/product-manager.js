/**
 * Product Manager - Enhanced with Variety Editor
 */

(function ($) {
    'use strict';

    function injectDeliveryFields($form) {
        if (!$form || !$form.length) {
            return;
        }

        if ($form.find('input[name="cw_delivery_charges"]').length) {
            return;
        }

        const isEdit = $form.attr('id') === 'edit-product-form';
        const priceInput = isEdit ? $form.find('#edit-product-price') : $form.find('#cw-product-price');

        if (!priceInput.length) {
            return;
        }

        const fieldsHtml = `
            <label for="${isEdit ? 'edit-product-delivery-charges' : 'cw-delivery-charges'}">Delivery Charges</label>
            <input type="text" id="${isEdit ? 'edit-product-delivery-charges' : 'cw-delivery-charges'}" name="cw_delivery_charges" placeholder="e.g., $5.99 or Free">

            <label for="${isEdit ? 'edit-product-delivery-eta' : 'cw-delivery-eta'}">ETA</label>
            <input type="text" id="${isEdit ? 'edit-product-delivery-eta' : 'cw-delivery-eta'}" name="cw_delivery_eta" placeholder="e.g., 7-12 business days">
        `;

        $(fieldsHtml).insertAfter(priceInput);
    }

    function injectIntoVisibleForms() {
        injectDeliveryFields($('.cw-add-product-form'));
    }

    // Tab switching
    $('.pm-tab-btn').on('click', function () {
        const tabName = $(this).data('tab');

        $('.pm-tab-btn').removeClass('active');
        $('.pm-tab-content').removeClass('active');

        $(this).addClass('active');
        $('#' + tabName).addClass('active');

        if (tabName === 'add-single' || tabName === 'edit-product') {
            injectIntoVisibleForms();
        }
    });

    // Initial injection for Add Single Item tab
    $(document).ready(function () {
        injectIntoVisibleForms();

        const editContainer = document.getElementById('editProductContainer');
        if (editContainer) {
            const observer = new MutationObserver(() => {
                injectIntoVisibleForms();
            });

            observer.observe(editContainer, { childList: true, subtree: true });
        }
    });

    // Delete product
    $(document).on('click', '.delete-product-btn', function () {
        const productId = $(this).data('product-id');

        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        $(this).prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'cw_delete_product_frontend',
                product_id: productId,
                nonce: $('[name="cw_product_manager_nonce"]').val(),
            },
            success: function (response) {
                if (response.success) {
                    alert('Product deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Failed to delete product'));
                }
            },
            error: function () {
                alert('Error deleting product. Please try again.');
                location.reload();
            }
        });
    });

    /**
     * Edit product - Opens variety editor when Edit button is clicked
     * The variety editor modal will handle all editing
     */
    $(document).on('click', '.edit-product-btn', function (e) {
        e.preventDefault();

        // The variety editor will handle opening the modal
        // when this button is clicked (from variety-editor-frontend.js)
    });

    /**
     * Bulk operations
     */
    const bulkUpload = {
        files: [],
        forms: {},

        init() {
            $('#bulk-images').on('change', (e) => this.handleImageSelect(e));
        },

        handleImageSelect(e) {
            this.files = Array.from(e.target.files);
            this.renderPreviews();
            this.createForms();
        },

        renderPreviews() {
            const $preview = $('#bulkImagesPreview').empty();

            this.files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    $('<div class="bulk-image-preview">')
                        .append($('<img>').attr('src', e.target.result))
                        .appendTo($preview);
                };
                reader.readAsDataURL(file);
            });
        },

        createForms() {
            const $container = $('#bulkFormsContainer').empty();
            const $addBtn = $('#bulkAddAllBtn');

            this.files.forEach((file, index) => {
                const formHtml = `
                    <div class="bulk-product-form" data-index="${index}">
                        <h3>${file.name}</h3>
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" class="bulk-product-name" required>
                        </div>
                        <div class="form-group">
                            <label>Price *</label>
                            <input type="number" class="bulk-product-price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" class="bulk-product-sku">
                        </div>
                        <input type="hidden" class="bulk-product-image" data-file-index="${index}">
                    </div>
                `;
                $container.append(formHtml);
            });

            if (this.files.length > 0) {
                $addBtn.show();
            }
        }
    };

    bulkUpload.init();

    $('#bulkAddAllBtn').on('click', function () {
        if (bulkUpload.files.length === 0) {
            alert('Please select images first');
            return;
        }

        $(this).prop('disabled', true).text('Adding Products...');

        let completed = 0;
        const total = bulkUpload.files.length;

        bulkUpload.files.forEach((file, index) => {
            const $form = $(`.bulk-product-form[data-index="${index}"]`);
            const name = $form.find('.bulk-product-name').val();
            const price = $form.find('.bulk-product-price').val();
            const sku = $form.find('.bulk-product-sku').val();

            if (!name || !price) {
                alert(`Please fill in Name and Price for ${file.name}`);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'cw_add_bulk_product_frontend');
            formData.append('product_name', name);
            formData.append('product_price', price);
            formData.append('product_sku', sku);
            formData.append('product_image', file);
            formData.append('nonce', $('[name="cw_add_bulk_nonce"]').val());

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    completed++;
                    if (response.success) {
                        console.log(`✓ ${name} added`);
                    } else {
                        console.error(`✗ ${name} failed: ${response.data}`);
                    }

                    if (completed === total) {
                        alert('Batch upload complete! Reloading...');
                        location.reload();
                    }
                },
                error: function () {
                    completed++;
                    console.error(`✗ ${name} error`);
                    if (completed === total) {
                        location.reload();
                    }
                }
            });
        });
    });

})(jQuery);
