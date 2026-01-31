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
