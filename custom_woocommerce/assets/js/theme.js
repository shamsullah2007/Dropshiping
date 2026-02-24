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

    // Edit Product functionality
    const editProductBtns = document.querySelectorAll('.edit-product-btn');
    editProductBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            loadProductForEdit(productId);
        });
    });

    // Delete Product functionality
    const deleteProductBtns = document.querySelectorAll('.delete-product-btn');
    deleteProductBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const productId = this.getAttribute('data-product-id');
            if (confirm('Are you sure you want to delete this product?')) {
                deleteProduct(productId);
            }
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
                        location.reload();
                    }
                });
            });
        });
    }

    // Make uploadedImages and currentSelectedIndex accessible globally for showBulkForm
    window.uploadedImages = uploadedImages;
    window.currentSelectedIndex = currentSelectedIndex;
});

// Global categories cache
let productCategories = [];

// Load categories on page load
function loadProductCategories() {
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'cw_get_categories'
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                productCategories = data.data;
            }
        });
}

// Call on page load
if (typeof ajaxurl !== 'undefined') {
    loadProductCategories();
}

function generateCategoryOptions(selectedCategory = '') {
    let options = '<option value="">Select a category</option>';
    productCategories.forEach(cat => {
        const selected = cat.name === selectedCategory ? ' selected' : '';
        options += `<option value="${cat.name}"${selected}>${cat.name}</option>`;
    });
    return options;
}

function showBulkForm(index) {
    const bulkFormsContainer = document.getElementById('bulkFormsContainer');
    const uploadedImages = window.uploadedImages || [];
    const imageData = uploadedImages.find(img => img.index === index);

    if (!imageData) {
        console.log('No image data found for index:', index);
        return;
    }

    // Update active state on preview
    document.querySelectorAll('.bulk-image-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.bulk-image-item[data-index="${index}"]`)?.classList.add('active');

    // Clear previous form
    bulkFormsContainer.innerHTML = '';
    window.currentSelectedIndex = index;

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
            <select name="cw_product_category" id="cw_product_category_${index}">
                ${generateCategoryOptions()}
            </select>
        </div>
        <div class="form-group">
            <label for="cw_product_description_${index}">Description</label>
            <textarea name="cw_product_description" id="cw_product_description_${index}"></textarea>
        </div>
    `;

    bulkFormsContainer.appendChild(formDiv);
}

function loadProductForEdit(productId) {
    const editTab = document.getElementById('edit-product-tab');
    const editContainer = document.getElementById('editProductContainer');

    editTab.style.display = 'block';
    editTab.click();

    editContainer.innerHTML = '<p>Loading product...</p>';

    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'cw_get_product_for_edit',
            product_id: productId,
            nonce: document.querySelector('[name="cw_add_bulk_nonce"]')?.value
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const product = data.data;
                const varieties = product.varieties || [];
                const galleryImages = product.gallery_images || [];
                const videos = product.videos || [];

                let varietiesHtml = '';
                varieties.forEach((variety, index) => {
                    varietiesHtml += `
                        <div class="cw-variety-row" data-index="${index}" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px;">
                            <div style="display: grid; grid-template-columns: 60px 1fr 150px auto; gap: 15px; align-items: start;">
                                <div>
                                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Image</label>
                                    <div class="variety-image-preview" style="width: 40px; height: 40px; border: 2px dashed #ddd; border-radius: 6px; background: #fafafa; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; position: relative;">
                                        <img src="${variety.image_url}" alt="Variety Image" style="display: ${variety.image_url ? 'block' : 'none'}; width: 100%; height: 100%; object-fit: cover;">
                                        <span style="text-align: center; font-size: 9px; color: #999;" data-placeholder="true" style="display: ${variety.image_url ? 'none' : 'block'};">Click</span>
                                    </div>
                                    <input type="hidden" class="variety-image-id" name="cw_variety_image_id_${index}" value="${variety.image_id || ''}">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Name/Color <span style="color: red;">*</span></label>
                                    <input type="text" name="cw_variety_color_${index}" class="variety-color-name" placeholder="e.g., Black, Large, Red M" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" value="${variety.color_name || ''}" required>
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Price</label>
                                    <input type="number" name="cw_variety_price_${index}" class="variety-price" step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" value="${variety.price || '0'}">
                                </div>
                                <div style="padding-top: 25px;">
                                    <button type="button" class="button button-small delete-variety-btn" data-index="${index}" style="background: #dc3545; color: white; border-color: #dc3545; cursor: pointer;">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                let galleryHtml = '';
                galleryImages.forEach((img, index) => {
                    galleryHtml += `
                        <div class="gallery-item" data-image-id="${img.id}" style="position: relative; display: inline-block; margin: 5px; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;">
                            <img src="${img.thumb}" alt="Gallery" style="width: 80px; height: 80px; object-fit: cover;">
                            <button type="button" class="delete-gallery-btn" data-image-id="${img.id}" style="position: absolute; top: 0; right: 0; background: #dc3545; color: white; border: none; padding: 2px 6px; cursor: pointer; font-size: 12px;">×</button>
                            <input type="hidden" name="cw_product_gallery_existing[]" value="${img.id}">
                        </div>
                    `;
                });

                let videosHtml = '';
                videos.forEach((video, index) => {
                    videosHtml += `
                        <div class="video-item" data-video-id="${video.id}" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; margin: 8px 0; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="font-weight: 600; color: #333;">${video.title || 'Video'}</span>
                                <br>
                                <small style="color: #666;">${video.url}</small>
                            </div>
                            <button type="button" class="button button-small delete-video-btn" data-video-id="${video.id}" style="background: #dc3545; color: white; border-color: #dc3545; cursor: pointer;">Delete</button>
                            <input type="hidden" name="cw_product_videos_existing[]" value="${video.id}">
                        </div>
                    `;
                });

                editContainer.innerHTML = `
                <form class="cw-add-product-form" id="edit-product-form">
                    <input type="hidden" name="product_id" value="${product.id}">
                    
                    <!-- Main Image Section -->
                    <div class="cw-image-preview" style="background-image: url('${product.image}');" class="${product.image ? 'has-image' : ''}"></div>
                    <label for="edit-product-image" class="cw-image-label">Product Image</label>
                    <input type="file" id="edit-product-image" name="product_image" accept="image/*">
                    
                    <!-- Basic Fields -->
                    <label for="edit-product-title">Title *</label>
                    <input type="text" id="edit-product-title" name="product_title" value="${product.title}" required>
                    
                    <label for="edit-product-price">Price *</label>
                    <input type="number" step="0.01" id="edit-product-price" name="product_price" value="${product.price}" required>
                    
                    <label for="edit-product-sku">SKU</label>
                    <input type="text" id="edit-product-sku" name="product_sku" value="${product.sku}">
                    
                    <label for="edit-product-category">Category</label>
                    <select id="edit-product-category" name="product_category">
                        ${generateCategoryOptions(product.category)}
                    </select>
                    
                    <label for="edit-product-description">Description</label>
                    <textarea id="edit-product-description" name="product_description" rows="6">${product.description}</textarea>

                    <!-- Gallery Images Section -->
                    <div style="margin-top: 30px; border-top: 2px solid #e0e0e0; padding-top: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #333;">Product Gallery (Optional)</h3>
                        <label for="edit-product-gallery" class="cw-image-label">Gallery Images</label>
                        <input type="file" id="edit-product-gallery" name="cw_product_gallery[]" accept="image/*" multiple>
                        <p style="color: #666; font-size: 0.9rem; margin: 8px 0 15px;">You can select multiple images for the product gallery</p>
                        <div id="edit-gallery-preview" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;">
                            ${galleryHtml}
                        </div>
                    </div>

                    <!-- Videos Section -->
                    <div style="margin-top: 30px; border-top: 2px solid #e0e0e0; padding-top: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #333;">Product Videos (Optional)</h3>
                        <label for="edit-product-videos" class="cw-image-label">Upload Videos</label>
                        <input type="file" id="edit-product-videos" name="cw_product_videos[]" accept="video/*" multiple>
                        <p style="color: #666; font-size: 0.9rem; margin: 8px 0 15px;">Supported formats: MP4, WebM, Ogg</p>
                        <div id="edit-videos-preview" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                            ${videosHtml}
                        </div>
                    </div>

                    <!-- Varieties Section -->
                    <div class="cw-varieties-section" style="margin-top: 30px; border-top: 2px solid #e0e0e0; padding-top: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #333;">Product Varieties (Optional)</h3>
                        <p style="color: #666; margin: 0 0 20px 0; font-size: 0.9rem;">Add different options for your product (colors, sizes, etc.)</p>
                        
                        <div id="cw-varieties-container" style="display: flex; flex-direction: column; gap: 15px;">
                            ${varietiesHtml}
                        </div>
                        
                        <button type="button" id="cw-add-variety-btn" class="button button-secondary" style="margin-top: 15px;">+ Add Variety</button>
                        <input type="hidden" id="cw-variety-count" name="cw_variety_count" value="${varieties.length}">
                    </div>
                    
                    <button type="submit" class="button button-accent">Update Product</button>
                    <button type="button" class="button button-outline" id="cancel-edit-btn">Cancel</button>
                </form>
            `;

                // Handle form submission
                document.getElementById('edit-product-form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    updateProduct(this);
                });

                // Handle cancel button
                document.getElementById('cancel-edit-btn').addEventListener('click', function () {
                    editTab.style.display = 'none';
                    document.querySelector('.pm-tab-btn[data-tab="all-products"]').click();
                });

                // Handle image preview
                const imageInput = document.getElementById('edit-product-image');
                const imagePreview = editContainer.querySelector('.cw-image-preview');
                imageInput.addEventListener('change', function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            imagePreview.style.backgroundImage = `url('${e.target.result}')`;
                            imagePreview.classList.add('has-image');
                        };
                        reader.readAsDataURL(file);
                    }
                });

                // Handle gallery preview
                const galleryInput = document.getElementById('edit-product-gallery');
                const galleryPreview = document.getElementById('edit-gallery-preview');
                galleryInput.addEventListener('change', function () {
                    Array.from(this.files).forEach(file => {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const galleryItem = document.createElement('div');
                            galleryItem.className = 'gallery-item';
                            galleryItem.style.cssText = 'position: relative; display: inline-block; margin: 5px; border: 1px solid #ddd; border-radius: 6px; overflow: hidden;';
                            galleryItem.innerHTML = `
                                <img src="${e.target.result}" alt="Gallery" style="width: 80px; height: 80px; object-fit: cover;">
                                <button type="button" class="delete-temp-gallery-btn" style="position: absolute; top: 0; right: 0; background: #dc3545; color: white; border: none; padding: 2px 6px; cursor: pointer; font-size: 12px;">×</button>
                            `;
                            const deleteBtn = galleryItem.querySelector('.delete-temp-gallery-btn');
                            deleteBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                galleryItem.remove();
                            });
                            galleryPreview.appendChild(galleryItem);
                        };
                        reader.readAsDataURL(file);
                    });
                });

                // Handle videos preview
                const videosInput = document.getElementById('edit-product-videos');
                const videosPreview = document.getElementById('edit-videos-preview');
                videosInput.addEventListener('change', function () {
                    Array.from(this.files).forEach(file => {
                        const videoItem = document.createElement('div');
                        videoItem.className = 'video-item';
                        videoItem.style.cssText = 'background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px; margin: 8px 0; display: flex; align-items: center; justify-content: space-between;';
                        videoItem.innerHTML = `
                            <div>
                                <span style="font-weight: 600; color: #333;">${file.name}</span>
                                <br>
                                <small style="color: #666;">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                            </div>
                            <button type="button" class="button button-small delete-temp-video-btn" style="background: #dc3545; color: white; border-color: #dc3545; cursor: pointer;">Delete</button>
                        `;
                        const deleteBtn = videoItem.querySelector('.delete-temp-video-btn');
                        deleteBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            videoItem.remove();
                        });
                        videosPreview.appendChild(videoItem);
                    });
                });

                // Delete existing gallery images
                document.querySelectorAll('.delete-gallery-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        btn.closest('.gallery-item').remove();
                    });
                });

                // Delete existing videos
                document.querySelectorAll('.delete-video-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        btn.closest('.video-item').remove();
                    });
                });

                // Initialize variety form handlers
                initializeEditVarietyHandlers(editContainer);
            } else {
                editContainer.innerHTML = `<p style="color: red;">${data.data.message || 'Failed to load product'}</p>`;
            }
        });
}

// Initialize variety form handlers for edit form
function initializeEditVarietyHandlers(container) {
    let varietyCount = parseInt(container.querySelector('#cw-variety-count')?.value || 0);

    // Add variety button
    const addVarietyBtn = container.querySelector('#cw-add-variety-btn');
    if (addVarietyBtn) {
        addVarietyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            addEditVarietyRow(container);
        });
    }

    // Delete variety buttons
    container.querySelectorAll('.delete-variety-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            this.closest('.cw-variety-row').remove();
            updateEditVarietyCount(container);
        });
    });

    // Attach image upload handlers to existing varieties
    container.querySelectorAll('.cw-variety-row').forEach(row => {
        attachEditImageUploadHandler(row, container);
    });
}

// Add new variety row for edit form
function addEditVarietyRow(container) {
    const varietyCount = parseInt(container.querySelector('#cw-variety-count')?.value || 0);
    const index = varietyCount;
    const varietiesContainer = container.querySelector('#cw-varieties-container');

    const html = `
        <div class="cw-variety-row" data-index="${index}" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px;">
            <div style="display: grid; grid-template-columns: 100px 1fr 150px auto; gap: 15px; align-items: start;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Image</label>
                    <div class="variety-image-preview" style="width: 100px; height: 100px; border: 2px dashed #ddd; border-radius: 6px; background: #fafafa; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; position: relative;">
                        <img src="" alt="Variety Image" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <span style="text-align: center; font-size: 11px; color: #999;" data-placeholder="true">Click to upload</span>
                    </div>
                    <input type="hidden" class="variety-image-id" name="cw_variety_image_id_${index}">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Name/Color <span style="color: red;">*</span></label>
                    <input type="text" name="cw_variety_color_${index}" class="variety-color-name" placeholder="e.g., Black, Large, Red M" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 8px;">Price</label>
                    <input type="number" name="cw_variety_price_${index}" class="variety-price" step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                </div>
                <div style="padding-top: 25px;">
                    <button type="button" class="button button-small delete-variety-btn" data-index="${index}" style="background: #dc3545; color: white; border-color: #dc3545; cursor: pointer;">Delete</button>
                </div>
            </div>
        </div>
    `;

    varietiesContainer.insertAdjacentHTML('beforeend', html);
    const newRow = varietiesContainer.querySelector(`[data-index="${index}"]`);
    attachEditImageUploadHandler(newRow, container);

    // Attach delete handler
    newRow.querySelector('.delete-variety-btn').addEventListener('click', function (e) {
        e.preventDefault();
        this.closest('.cw-variety-row').remove();
        updateEditVarietyCount(container);
    });

    updateEditVarietyCount(container);
}

// Image upload handler for edit form
function attachEditImageUploadHandler(row, container) {
    const preview = row.querySelector('.variety-image-preview');

    preview.addEventListener('click', function () {
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
            row.querySelector('.variety-image-id').value = attachment.id;
            const img = row.querySelector('.variety-image-preview img');
            img.src = attachment.url;
            img.style.display = 'block';
            row.querySelector('[data-placeholder]').style.display = 'none';
        });

        frame.open();
    });
}

// Update variety count for edit form
function updateEditVarietyCount(container) {
    container.querySelector('#cw-variety-count').value = container.querySelectorAll('.cw-variety-row').length;
}

function updateProduct(form) {
    const formData = new FormData(form);
    formData.append('action', 'cw_update_product');
    formData.append('nonce', document.querySelector('[name="cw_add_bulk_nonce"]')?.value);

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';

    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Product';

            if (data.success) {
                alert('Product updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.data.message || 'Failed to update product'));
            }
        });
}

function deleteProduct(productId) {
    fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'cw_delete_product',
            product_id: productId,
            nonce: document.querySelector('[name="cw_add_bulk_nonce"]')?.value
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Product deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.data.message || 'Failed to delete product'));
            }
        });
}
