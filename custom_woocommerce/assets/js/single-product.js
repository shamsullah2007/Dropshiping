// Image Zoom Functionality
(function () {
    'use strict';

    function initImageZoom() {
        // Check if we're on a single product page
        if (!document.body.classList.contains('single-product')) {
            return;
        }

        // Create zoom modal
        const zoomModal = document.createElement('div');
        zoomModal.className = 'image-zoom-modal';
        zoomModal.innerHTML = `
            <button class="image-zoom-close" aria-label="Close zoom">&times;</button>
            <img src="" alt="Product zoom" />
        `;
        document.body.appendChild(zoomModal);

        // Get product gallery images
        const galleryImages = document.querySelectorAll('.woocommerce-product-gallery__image img');

        // Add click handlers to images
        galleryImages.forEach(img => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', function (e) {
                e.preventDefault();
                const zoomImg = zoomModal.querySelector('img');
                zoomImg.src = this.src;
                zoomModal.classList.add('active');
            });
        });

        // Close button
        zoomModal.querySelector('.image-zoom-close').addEventListener('click', function () {
            zoomModal.classList.remove('active');
        });

        // Close on background click
        zoomModal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });

        // Close on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                zoomModal.classList.remove('active');
            }
        });

        // Add zoom info text
        galleryImages.forEach(img => {
            if (!img.nextElementSibling || !img.nextElementSibling.classList.contains('zoom-hint')) {
                const hint = document.createElement('small');
                hint.className = 'zoom-hint';
                hint.style.display = 'block';
                hint.style.textAlign = 'center';
                hint.style.marginTop = '8px';
                hint.style.color = 'var(--cw-muted)';
                hint.style.fontSize = '0.85rem';
                hint.textContent = 'Click to zoom';
                img.parentElement.appendChild(hint);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImageZoom);
    } else {
        initImageZoom();
    }

    // Reinitialize on AJAX (for WooCommerce product updates)
    document.addEventListener('wc_single_product_refreshed', initImageZoom);

})();

// CJ-style variant swatches and size grid
(function () {
    'use strict';

    function isColorAttribute(name, label) {
        return /color/i.test(name + ' ' + label);
    }

    function isSizeAttribute(name, label) {
        return /size|option/i.test(name + ' ' + label);
    }

    function attributeHasImages(variations, attrKey) {
        for (let i = 0; i < variations.length; i++) {
            const attrs = variations[i].attributes || {};
            if (attrs[attrKey] && variations[i].cj_variant_image) {
                return true;
            }
        }
        return false;
    }

    function getFirstMatchingImage(variations, attrKey, value) {
        for (let i = 0; i < variations.length; i++) {
            const attrs = variations[i].attributes || {};
            if (attrs[attrKey] === value && variations[i].cj_variant_image) {
                return variations[i].cj_variant_image;
            }
        }
        return '';
    }

    function preloadImages(variations) {
        variations.forEach(variation => {
            if (variation.cj_variant_image) {
                const img = new Image();
                img.src = variation.cj_variant_image;
            }
        });
    }

    function updateMainImage(url) {
        if (!url) {
            return;
        }
        const mainImage = document.getElementById('cj-main-image');
        if (mainImage) {
            mainImage.src = url;
        }
    }

    function updatePriceSkuStock($form, variation) {
        if (!variation) {
            return;
        }

        const $summaryPrice = $form.closest('.summary').find('.price');
        if ($summaryPrice.length && variation.price_html) {
            $summaryPrice.html(variation.price_html);
        }

        const skuValue = variation.sku || variation.cj_variant_sku || '';
        if (skuValue) {
            const $sku = $form.closest('.summary').find('.sku');
            if ($sku.length) {
                $sku.text(skuValue);
            }
        }

        const $availability = $form.find('.woocommerce-variation-availability');
        if ($availability.length && variation.availability_html) {
            $availability.html(variation.availability_html);
        }
    }

    function syncSelectedButtons($form) {
        $form.find('.cj-variation-group').each(function () {
            const $group = window.jQuery(this);
            const attrKey = $group.data('attribute');
            const $select = $form.find('select[name="' + attrKey + '"]');
            if (!$select.length) {
                return;
            }
            const selected = $select.val();
            $group.find('[data-value]').removeClass('cj-swatch--selected');
            if (selected) {
                $group.find('[data-value="' + selected.replace(/"/g, '\\"') + '"]').addClass('cj-swatch--selected');
            }

        });
    }

    function buildSwatchUI($form, variations) {
        const $ = window.jQuery;

        $form.find('select[name^="attribute_"]').each(function () {
            const $select = $(this);
            const attrKey = $select.attr('name');
            const labelText = $select.closest('tr').find('label').text().trim();

            if (!attrKey) {
                return;
            }

            const isColor = isColorAttribute(attrKey, labelText);
            const isSize = isSizeAttribute(attrKey, labelText);
            const hasImages = attributeHasImages(variations, attrKey);

            // Show swatches for Color or images, but NEVER for Size (Size is always text buttons)
            const useSwatch = !isSize && (isColor || hasImages);

            if (!useSwatch && !isSize) {
                return;
            }

            if ($select.next('.cj-variation-group').length) {
                return;
            }

            $select.addClass('cj-variation-select-hidden');

            const $group = $('<div class="cj-variation-group"></div>');
            $group.attr('data-attribute', attrKey);
            const labelBase = labelText || (useSwatch ? 'Color' : 'Size');
            $group.append('<div class="cj-variation-label">' + labelBase + '</div>');

            const $list = $('<div class="cj-variation-options"></div>');
            if (useSwatch) {
                $list.addClass('cj-swatch-list');
            } else {
                $list.addClass('cj-size-list');
            }

            let optionCount = 0;
            $select.find('option').each(function () {
                const value = $(this).attr('value');
                const text = $(this).text().trim();
                if (!value) {
                    return;
                }

                optionCount++;

                if (useSwatch) {
                    const imgUrl = getFirstMatchingImage(variations, attrKey, value);
                    const $item = $('<div class="cj-swatch-item"></div>');
                    const titleText = isColor ? ('Color ' + text) : text;
                    $item.append('<div class="cj-swatch-title">' + titleText + '</div>');

                    const $btn = $('<button type="button" class="cj-swatch" data-value="' + value + '"></button>');
                    if (imgUrl) {
                        $btn.append('<img src="' + imgUrl + '" alt="' + text + '" loading="lazy">');
                    } else {
                        $btn.append('<span class="cj-swatch-text">' + text + '</span>');
                    }
                    $item.append($btn);
                    $list.append($item);
                } else {
                    const $btn = $('<button type="button" class="cj-size-btn" data-value="' + value + '">' + text + '</button>');
                    $list.append($btn);
                }
            });

            $group.append($list);
            $select.after($group);
        });

        $form.on('click', '.cj-swatch, .cj-size-btn', function () {
            const $btn = $(this);
            const value = $btn.data('value');
            const $group = $btn.closest('.cj-variation-group');
            const attrKey = $group.data('attribute');
            const $select = $form.find('select[name="' + attrKey + '"]');

            if (!$select.length) {
                return;
            }

            $select.val(value).trigger('change');
            syncSelectedButtons($form);
        });

        $form.on('change', 'select[name^="attribute_"]', function () {
            syncSelectedButtons($form);
        });

        $form.on('found_variation', function (event, variation) {
            updateMainImage(variation.cj_variant_image || (variation.image && variation.image.src));
            updatePriceSkuStock($form, variation);
            syncSelectedButtons($form);
        });

        $form.on('reset_data', function () {
            $form.find('.cj-swatch, .cj-size-btn').removeClass('cj-swatch--selected');
        });
    }

    function autoSelectFirstVariation($form, variations) {
        if (!variations.length) {
            return;
        }

        let chosen = variations.find(v => v.is_in_stock);
        if (!chosen) {
            chosen = variations[0];
        }

        const attrs = chosen.attributes || {};
        Object.keys(attrs).forEach(attrKey => {
            const value = attrs[attrKey];
            const $select = $form.find('select[name="' + attrKey + '"]');
            if ($select.length && value) {
                $select.val(value);
            }
        });

        $form.trigger('check_variations');
        $form.trigger('change');
    }

    function initVariantUI() {
        if (!document.body.classList.contains('single-product')) {
            return;
        }

        const $ = window.jQuery;
        if (!$) {
            return;
        }

        const $form = $('.variations_form');
        if (!$form.length) {
            return;
        }

        const variations = $form.data('product_variations') || [];
        if (!variations.length) {
            return;
        }

        preloadImages(variations);
        buildSwatchUI($form, variations);
        autoSelectFirstVariation($form, variations);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVariantUI);
    } else {
        initVariantUI();
    }
})();

// Review Section Enhancements
(function () {
    'use strict';

    function enhanceReviews() {
        // Add smooth scroll to review form
        const reviewLink = document.querySelector('a[href="#reviews"]');
        if (reviewLink) {
            reviewLink.addEventListener('click', function (e) {
                e.preventDefault();
                const reviewSection = document.getElementById('reviews');
                if (reviewSection) {
                    reviewSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        // Enhance review stars visibility
        const starRatings = document.querySelectorAll('.woocommerce #reviews .star-rating');
        starRatings.forEach(star => {
            star.style.display = 'inline-block';
            star.style.fontSize = '1.1rem';
        });

        // Add verified badge styling if available
        const comments = document.querySelectorAll('.woocommerce #reviews ol.commentlist li.comment');
        comments.forEach(comment => {
            const meta = comment.querySelector('.comment-text');
            if (meta) {
                meta.style.marginTop = '12px';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhanceReviews);
    } else {
        enhanceReviews();
    }

})();
