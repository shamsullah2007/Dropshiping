/**
 * Custom Single Product Page - JavaScript
 * Handles: Gallery switching, variety selection, quantity updates, dynamic pricing
 */

document.addEventListener('DOMContentLoaded', function () {
    console.log('CSP Script Loaded');

    // ===== GALLERY SWITCHING =====
    const thumbs = document.querySelectorAll('.csp-thumb');
    const mainImage = document.getElementById('csp-main-image');
    const mainVideo = document.getElementById('csp-main-video');
    const mainDisplay = document.querySelector('.csp-main-display');
    const currentCounter = document.getElementById('csp-current');

    thumbs.forEach(thumb => {
        thumb.addEventListener('click', function () {
            const index = this.dataset.index;
            const type = this.dataset.type;
            const url = this.dataset.url;

            // Update counter
            if (currentCounter) {
                currentCounter.textContent = parseInt(index) + 1;
            }

            // Remove active class from all thumbs
            thumbs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Switch media
            if (type === 'image' && mainImage) {
                mainImage.src = url;
                mainImage.style.display = 'block';
                if (mainVideo) mainVideo.style.display = 'none';
            } else if (type === 'video' && mainVideo) {
                mainVideo.src = url;
                mainVideo.style.display = 'block';
                if (mainImage) mainImage.style.display = 'none';
                mainVideo.play().catch(e => console.log('Video play error:', e));
            }
        });
    });

    // ===== VARIETY/COLOR SWATCHES =====
    const varietySwatches = document.querySelectorAll('.csp-variety-swatch');
    let selectedVariety = null;
    const priceDisplay = document.querySelector('.csp-price');
    const selectedColorLabel = document.getElementById('csp-variety-selected');
    const basePriceValue = getBasePrice();

    varietySwatches.forEach(swatch => {
        swatch.addEventListener('click', function () {
            varietySwatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');

            selectedVariety = {
                index: this.dataset.varietyIndex,
                name: this.dataset.varietyName,
                price: parseFloat(this.dataset.varietyPrice) || 0
            };

            if (selectedColorLabel) {
                selectedColorLabel.textContent = selectedVariety.name ? `(${selectedVariety.name})` : '';
            }

            updateUnitPrice();
            updateTotalPrice();
        });
    });

    // ===== QUANTITY SELECTOR =====
    const qtyInput = document.getElementById('csp-qty-input');
    const qtyMinus = document.getElementById('csp-qty-minus');
    const qtyPlus = document.getElementById('csp-qty-plus');

    if (qtyMinus) {
        qtyMinus.addEventListener('click', function (e) {
            e.preventDefault();
            const current = parseInt(qtyInput.value) || 1;
            if (current > 1) {
                qtyInput.value = current - 1;
                updateTotalPrice();
            }
        });
    }

    if (qtyPlus) {
        qtyPlus.addEventListener('click', function (e) {
            e.preventDefault();
            const current = parseInt(qtyInput.value) || 1;
            const max = parseInt(qtyInput.max) || 999;
            if (current < max) {
                qtyInput.value = current + 1;
                updateTotalPrice();
            }
        });
    }

    if (qtyInput) {
        qtyInput.addEventListener('change', function () {
            let val = parseInt(this.value) || 1;
            const min = parseInt(this.min) || 1;
            const max = parseInt(this.max) || 999;

            if (val < min) val = min;
            if (val > max) val = max;

            this.value = val;
            updateTotalPrice();
        });

        qtyInput.addEventListener('input', function () {
            // Allow live input but validate on blur
        });
    }

    // ===== UPDATE TOTAL PRICE =====
    function updateTotalPrice() {
        const qty = parseInt(qtyInput.value) || 1;
        const varietyPrice = selectedVariety ? selectedVariety.price : 0;
        const totalPrice = (basePriceValue + varietyPrice) * qty;

        const totalDisplay = document.getElementById('csp-total-price');
        if (totalDisplay) {
            // Format price with currency
            const formatted = formatPrice(totalPrice);
            totalDisplay.textContent = formatted;
        }
    }

    // ===== GET BASE PRICE =====
    function getBasePrice() {
        const priceElement = document.querySelector('.csp-price');
        if (!priceElement) return 0;

        const text = priceElement.textContent;
        const match = text.match(/[\d.,]+/);

        if (match) {
            return parseFloat(match[0].replace(/[,]/g, ''));
        }
        return 0;
    }

    function updateUnitPrice() {
        if (!priceDisplay) return;

        const varietyPrice = selectedVariety ? selectedVariety.price : 0;
        const unitPrice = basePriceValue + varietyPrice;
        priceDisplay.textContent = formatPrice(unitPrice);
    }

    // ===== FORMAT PRICE =====
    function formatPrice(price) {
        // Get currency symbol from price display
        const priceElement = document.querySelector('.csp-price');
        let symbol = '$';

        if (priceElement) {
            const text = priceElement.textContent;
            if (text.includes('€')) symbol = '€';
            if (text.includes('£')) symbol = '£';
            if (text.includes('¥')) symbol = '¥';
            if (text.includes('₹')) symbol = '₹';
        }

        return symbol + price.toFixed(2);
    }

    // ===== ADD TO CART INTEGRATION =====
    const addToCartBtn = document.querySelector('.csp-add-to-cart-wrapper .single_add_to_cart_button');

    if (addToCartBtn) {
        // Add event listener to update quantity before submission
        const form = addToCartBtn.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                // Update the hidden quantity field
                const qtyField = form.querySelector('input[name="quantity"]');
                if (qtyField && qtyInput) {
                    qtyField.value = qtyInput.value;
                }

                // If variety is selected, you might add it to custom attributes
                if (selectedVariety) {
                    console.log('Adding to cart with variety:', selectedVariety.name);
                }
            });
        }
    }

    // ===== KEYBOARD NAVIGATION FOR QUANTITY =====
    if (qtyInput) {
        qtyInput.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                qtyPlus.click();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                qtyMinus.click();
            }
        });
    }

    // ===== TOUCH SWIPE FOR THUMBNAILS (Mobile) =====
    let touchStartX = 0;
    let touchEndX = 0;

    const thumbnailsContainer = document.querySelector('.csp-thumbnails');
    if (thumbnailsContainer) {
        thumbnailsContainer.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
        }, false);

        thumbnailsContainer.addEventListener('touchend', function (e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
    }

    function handleSwipe() {
        const activeThumb = document.querySelector('.csp-thumb.active');
        if (!activeThumb) return;

        const allThumbs = Array.from(document.querySelectorAll('.csp-thumb'));
        const currentIndex = allThumbs.indexOf(activeThumb);

        if (touchEndX < touchStartX - 50) {
            // Swiped left - next thumb
            const nextIndex = Math.min(currentIndex + 1, allThumbs.length - 1);
            allThumbs[nextIndex].click();
        } else if (touchEndX > touchStartX + 50) {
            // Swiped right - previous thumb
            const prevIndex = Math.max(currentIndex - 1, 0);
            allThumbs[prevIndex].click();
        }
    }

    // ===== KEYBOARD SHORTCUTS =====
    document.addEventListener('keydown', function (e) {
        // ArrowLeft/Right to switch gallery images
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            const activeThumb = document.querySelector('.csp-thumb.active');
            if (!activeThumb || document.activeElement === qtyInput) return;

            const allThumbs = Array.from(document.querySelectorAll('.csp-thumb'));
            const currentIndex = allThumbs.indexOf(activeThumb);

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                const prevIndex = Math.max(currentIndex - 1, 0);
                allThumbs[prevIndex].click();
            } else {
                e.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, allThumbs.length - 1);
                allThumbs[nextIndex].click();
            }
        }
    });

    // ===== INITIALIZE =====
    console.log('CSP Initialization Complete');
    updateUnitPrice();
    updateTotalPrice();

    // Log to verify elements are found
    console.log('Thumbnails found:', thumbs.length);
    console.log('Variety swatches found:', varietySwatches.length);
    console.log('Qty input found:', !!qtyInput);
});
