document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    const animatedItems = document.querySelectorAll('[data-animate]');
    const avatarInput = document.querySelector('#cw-profile-avatar');

    const updateHeader = () => {
        if (!header) {
            return;
        }
        if (window.scrollY > 10) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    if ('IntersectionObserver' in window && animatedItems.length) {
        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.2 }
        );

        animatedItems.forEach((item) => observer.observe(item));
    } else {
        animatedItems.forEach((item) => item.classList.add('is-visible'));
    }

    if (avatarInput) {
        avatarInput.addEventListener('change', () => {
            const form = avatarInput.closest('form');
            if (form) {
                form.submit();
            }
        });
    }

    const accountPanel = document.querySelector('.cw-account-panel');
    const accountContent = document.querySelector('.woocommerce-MyAccount-content');
    if (accountPanel && accountContent) {
        accountPanel.appendChild(accountContent);
        accountContent.classList.add('cw-account-content');
    }

    // Carousel functionality
    const carousels = document.querySelectorAll('[data-carousel-id]');

    carousels.forEach(carousel => {
        const carouselId = carousel.getAttribute('data-carousel-id');
        const track = carousel.querySelector('.carousel-track');
        const prevBtn = document.querySelector(`.carousel-prev[data-carousel="${carouselId}"]`);
        const nextBtn = document.querySelector(`.carousel-next[data-carousel="${carouselId}"]`);
        const items = track.querySelectorAll('.carousel-item');

        if (!track || items.length === 0) return;

        let currentIndex = 0;
        const itemsPerView = getItemsPerView();
        const maxIndex = Math.max(0, items.length - itemsPerView);

        function getItemsPerView() {
            if (window.innerWidth <= 600) return 1;
            if (window.innerWidth <= 900) return 2;
            return 3;
        }

        function updateCarousel() {
            const itemWidth = items[0].offsetWidth;
            const gap = 20;
            const offset = -(currentIndex * (itemWidth + gap));
            track.style.transform = `translateX(${offset}px)`;

            if (prevBtn) prevBtn.disabled = currentIndex === 0;
            if (nextBtn) nextBtn.disabled = currentIndex >= maxIndex;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateCarousel();
                }
            });
        }

        window.addEventListener('resize', () => {
            const newItemsPerView = getItemsPerView();
            const newMaxIndex = Math.max(0, items.length - newItemsPerView);
            if (currentIndex > newMaxIndex) {
                currentIndex = newMaxIndex;
            }
            updateCarousel();
        });

        // Auto-advance carousel every second
        let autoplayInterval = setInterval(() => {
            if (currentIndex < maxIndex) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateCarousel();
        }, 1000);

        // Pause autoplay on hover
        carousel.addEventListener('mouseenter', () => {
            clearInterval(autoplayInterval);
        });

        carousel.addEventListener('mouseleave', () => {
            autoplayInterval = setInterval(() => {
                if (currentIndex < maxIndex) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                updateCarousel();
            }, 1000);
        });

        updateCarousel();
    });

    // Product Manager Tabs
    const tabBtns = document.querySelectorAll('.pm-tab-btn');
    const tabContents = document.querySelectorAll('.pm-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Bulk Image Upload
    const bulkImagesInput = document.getElementById('bulk-images');
    const bulkImagesPreview = document.getElementById('bulkImagesPreview');
    const bulkFormsContainer = document.getElementById('bulkFormsContainer');
    const bulkAddAllBtn = document.getElementById('bulkAddAllBtn');
    let uploadedImages = [];
    let currentSelectedIndex = null;

    if (bulkImagesInput) {
        bulkImagesInput.addEventListener('change', (e) => {
            uploadedImages = [];
            bulkImagesPreview.innerHTML = '';
            bulkFormsContainer.innerHTML = '';
            currentSelectedIndex = null;

            Array.from(e.target.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    const imageData = {
                        file: file,
                        preview: event.target.result,
                        index: index,
                    };
                    uploadedImages.push(imageData);

                    // Add preview
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'bulk-image-item';
                    previewDiv.setAttribute('data-index', index);
                    previewDiv.innerHTML = `
                        <img src="${event.target.result}" alt="Preview ${index}">
                        <button type="button" class="remove-image" data-index="${index}">×</button>
                    `;

                    // Click on image to show form
                    previewDiv.addEventListener('click', (e) => {
                        if (!e.target.classList.contains('remove-image')) {
                            showBulkForm(index);
                        }
                    });

                    bulkImagesPreview.appendChild(previewDiv);

                    // Show/hide add all button
                    if (uploadedImages.length > 0) {
                        bulkAddAllBtn.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            });
        });

        // Remove image
        bulkImagesPreview.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-image')) {
                e.stopPropagation();
                const index = parseInt(e.target.getAttribute('data-index'));
                uploadedImages = uploadedImages.filter(img => img.index !== index);
                e.target.closest('.bulk-image-item').remove();
                document.getElementById(`bulk-form-${index}`)?.remove();

                if (currentSelectedIndex === index) {
                    currentSelectedIndex = null;
                    bulkFormsContainer.innerHTML = '';
                }

                if (uploadedImages.length === 0) {
                    bulkAddAllBtn.style.display = 'none';
                }
            }
        });

        // Add all products
        bulkAddAllBtn.addEventListener('click', () => {
            const forms = document.querySelectorAll('.bulk-product-form');
            let allValid = true;

            forms.forEach(form => {
                const title = form.querySelector('input[name="cw_product_title"]')?.value;
                const price = form.querySelector('input[name="cw_product_price"]')?.value;

                if (!title || !price) {
                    allValid = false;
                    form.style.borderColor = '#ef4444';
                }
            });

            if (!allValid) {
                alert('Please fill in Title and Price for all products');
                return;
            }

            bulkAddAllBtn.disabled = true;
            bulkAddAllBtn.textContent = 'Adding Products...';

            // Submit each form
            let submitted = 0;
            forms.forEach((form, idx) => {
                const formData = new FormData(form);
                formData.append('action', 'cw_add_bulk_product');
                formData.append('nonce', document.querySelector('[name="cw_add_bulk_nonce"]')?.value);

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData,
                }).then(res => res.json()).then(data => {
                    submitted++;
                    if (data.success) {
                        form.style.borderColor = '#10b981';
                    } else {
                        form.style.borderColor = '#ef4444';
                    }

                    if (submitted === forms.length) {
                        bulkAddAllBtn.disabled = false;
                        bulkAddAllBtn.textContent = 'Add All Products';
                        alert('Products added successfully!');
                        bulkImagesInput.value = '';
                        bulkImagesPreview.innerHTML = '';
                        bulkFormsContainer.innerHTML = '';
                    }
                });
            });
        });
    }
});

function showBulkForm(index) {
    const bulkFormsContainer = document.getElementById('bulkFormsContainer');
    const imageData = uploadedImages.find(img => img.index === index);

    if (!imageData) return;

    // Update active state on preview
    document.querySelectorAll('.bulk-image-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.bulk-image-item[data-index="${index}"]`)?.classList.add('active');

    // Clear previous form
    bulkFormsContainer.innerHTML = '';
    currentSelectedIndex = index;

    // Create and show form for this image
    const formDiv = document.createElement('div');
    formDiv.id = `bulk-form-${index}`;
    formDiv.className = 'bulk-product-form';

    formDiv.innerHTML = `
        <input type="hidden" name="cw_bulk_image_index" value="${index}">
        <input type="hidden" name="cw_bulk_image_data" value="${imageData.preview}">
        <div class="form-group">
            <label for="cw_product_title_${index}">Title *</label>
            <input type="text" name="cw_product_title" id="cw_product_title_${index}" required>
        </div>
        <div class="form-group">
            <label for="cw_product_price_${index}">Price *</label>
            <input type="number" step="0.01" name="cw_product_price" id="cw_product_price_${index}" required>
        </div>
        <div class="form-group">
            <label for="cw_product_sku_${index}">SKU</label>
            <input type="text" name="cw_product_sku" id="cw_product_sku_${index}">
        </div>
        <div class="form-group">
            <label for="cw_product_category_${index}">Category</label>
            <input type="text" name="cw_product_category" id="cw_product_category_${index}">
        </div>
        <div class="form-group">
            <label for="cw_product_description_${index}">Description</label>
            <textarea name="cw_product_description" id="cw_product_description_${index}"></textarea>
        </div>
    `;

    bulkFormsContainer.appendChild(formDiv);
}
