(function ($) {
    'use strict';

    let varietyCount = 0;

    // Initialize when document is ready
    $(document).ready(function () {
        // Add variety button
        $(document).on('click', '#cw-add-variety-btn', function (e) {
            e.preventDefault();
            addVarietyRow();
        });

        // Delete variety row
        $(document).on('click', '.delete-variety-btn', function (e) {
            e.preventDefault();
            $(this).closest('.cw-variety-row').remove();
            updateVarietyCount();
        });

        // Initialize any existing variety rows
        $('.cw-variety-row').each(function () {
            varietyCount++;
            attachImageUploadHandler($(this));
        });
    });

    // Add new variety row
    function addVarietyRow() {
        const index = varietyCount++;
        const html = `
            <div class="cw-variety-row" data-index="${index}" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px;">
                <div style="display: grid; grid-template-columns: 100px 1fr 150px auto; gap: 15px; align-items: start;">
                    <!-- Image Upload -->
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">
                            Image
                        </label>
                        <div class="variety-image-preview" style="width: 100px; height: 100px; border: 2px dashed #ddd; border-radius: 6px; background: #fafafa; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; position: relative;">
                            <img src="" alt="Variety Image" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                            <span style="text-align: center; font-size: 11px; color: #999;" data-placeholder="true">
                                Click to upload
                            </span>
                        </div>
                        <input type="hidden" class="variety-image-id" name="cw_variety_image_id_${index}">
                    </div>
                    
                    <!-- Name/Color -->
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">
                            Name/Color <span style="color: red;">*</span>
                        </label>
                        <input type="text" name="cw_variety_color_${index}" class="variety-color-name" placeholder="e.g., Black, Large, Red M" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
                    </div>
                    
                    <!-- Price -->
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">
                            Price
                        </label>
                        <input type="number" name="cw_variety_price_${index}" class="variety-price" step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <!-- Delete Button -->
                    <div style="padding-top: 25px;">
                        <button type="button" class="button button-small delete-variety-btn" data-index="${index}" style="background: #dc3545; color: white; border-color: #dc3545; cursor: pointer;">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#cw-varieties-container').append(html);
        updateVarietyCount();

        // Attach image upload handler to new row
        attachImageUploadHandler($(`[data-index="${index}"]`));
    }

    // Image upload handler
    function attachImageUploadHandler($row) {
        const $preview = $row.find('.variety-image-preview');

        $preview.on('click', function () {
            // Check if wp.media is available
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('Media library not available. Please refresh the page.');
                return;
            }

            const frame = wp.media({
                title: 'Select Variety Image',
                button: { text: 'Use Image' },
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                $row.find('.variety-image-id').val(attachment.id);
                $preview.find('img').attr('src', attachment.url).show();
                $preview.find('[data-placeholder]').hide();
            });

            frame.open();
        });
    }

    // Update variety count
    function updateVarietyCount() {
        $('#cw-variety-count').val($('.cw-variety-row').length);
    }

})(jQuery);
