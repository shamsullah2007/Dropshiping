/**
 * CJ Dropshipping Single Product Page - Gallery & Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    const thumbnails = document.querySelectorAll('.cj-thumb-item');
    const mainImage = document.getElementById('cj-main-image');
    const mainVideo = document.getElementById('cj-main-video');
    const imageContainer = document.querySelector('.cj-image-container');
    const imageZoom = document.getElementById('cj-image-zoom');

    if (!thumbnails.length) return;

    // Gallery thumbnail click handler
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function () {
            const type = this.dataset.type;
            const url = this.dataset.url;
            const videoId = this.dataset.id;

            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active-thumb'));
            this.classList.add('active-thumb');

            // Fade out effect
            if (mainImage) mainImage.style.opacity = '0';
            if (mainVideo) mainVideo.style.opacity = '0';

            setTimeout(() => {
                // Clear previous elements
                const existingImg = imageContainer.querySelector('img');
                const existingVideo = imageContainer.querySelector('video');

                if (type === 'image') {
                    // Hide video if visible
                    if (mainVideo) mainVideo.style.display = 'none';

                    // Show or create image
                    if (existingImg) {
                        existingImg.src = url;
                        existingImg.style.display = 'block';
                        existingImg.style.opacity = '1';
                    } else {
                        const img = document.createElement('img');
                        img.id = 'cj-main-image';
                        img.className = 'cj-main-img';
                        img.src = url;
                        img.alt = 'Product';
                        img.style.opacity = '1';
                        imageContainer.innerHTML = '';
                        imageContainer.appendChild(img);
                        imageContainer.appendChild(imageZoom);
                        addImageZoomListener(img);
                    }
                } else if (type === 'video') {
                    // Hide image if visible
                    if (mainImage) mainImage.style.display = 'none';

                    // Show or create video
                    if (existingVideo) {
                        existingVideo.style.display = 'block';
                        existingVideo.style.opacity = '1';
                    } else {
                        const video = document.createElement('video');
                        video.id = 'cj-main-video';
                        video.className = 'cj-main-video';
                        video.controls = true;
                        video.muted = true;
                        video.loop = true;
                        const source = document.createElement('source');
                        source.src = url;
                        video.appendChild(source);
                        video.style.opacity = '1';
                        imageContainer.innerHTML = '';
                        imageContainer.appendChild(video);
                    }
                }
            }, 150);
        });
    });

    // Image zoom functionality
    function addImageZoomListener(img) {
        if (!img) return;

        img.addEventListener('click', function (e) {
            if (imageZoom.classList.contains('active')) {
                closeZoom();
            } else {
                openZoom(img);
            }
        });
    }

    if (mainImage) {
        addImageZoomListener(mainImage);
    }

    function openZoom(img) {
        if (!imageZoom) return;
        const zoomImg = document.createElement('img');
        zoomImg.src = img.src;
        zoomImg.alt = img.alt;
        imageZoom.innerHTML = '';
        imageZoom.appendChild(zoomImg);
        imageZoom.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeZoom() {
        if (!imageZoom) return;
        imageZoom.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (imageZoom) {
        imageZoom.addEventListener('click', closeZoom);
    }

    // Keyboard close zoom
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && imageZoom && imageZoom.classList.contains('active')) {
            closeZoom();
        }
    });

    // Lazy load thumbnail images
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target.querySelector('img');
                    if (img && !img.src) {
                        img.src = img.dataset.src;
                        observer.unobserve(entry.target);
                    }
                }
            });
        });

        thumbnails.forEach(thumb => observer.observe(thumb));
    }

    // Smooth scroll behavior for thumbnails
    const galleryThumbnails = document.querySelector('.cj-gallery-thumbnails');
    if (galleryThumbnails) {
        let isDown = false;
        let startY;
        let scrollTop;

        galleryThumbnails.addEventListener('mousedown', (e) => {
            isDown = true;
            startY = e.pageY - galleryThumbnails.offsetTop;
            scrollTop = galleryThumbnails.scrollTop;
        });

        galleryThumbnails.addEventListener('mouseleave', () => {
            isDown = false;
        });

        galleryThumbnails.addEventListener('mouseup', () => {
            isDown = false;
        });

        galleryThumbnails.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const y = e.pageY - galleryThumbnails.offsetTop;
            const walk = (y - startY) * 1;
            galleryThumbnails.scrollTop = scrollTop - walk;
        });
    }

    // Add to cart click prevention (spam prevention)
    const addToCartBtn = document.querySelector('.single_add_to_cart_button');
    if (addToCartBtn) {
        let isClicking = false;
        addToCartBtn.addEventListener('click', function (e) {
            if (isClicking) {
                e.preventDefault();
                return;
            }
            isClicking = true;
            setTimeout(() => {
                isClicking = false;
            }, 1000);
        });
    }
});
