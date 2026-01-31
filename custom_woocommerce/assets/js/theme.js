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
                editContainer.innerHTML = `
                <form class="cw-add-product-form" id="edit-product-form">
                    <input type="hidden" name="product_id" value="${product.id}">
                    <div class="cw-image-preview" style="background-image: url('${product.image}');" class="${product.image ? 'has-image' : ''}"></div>
                    <label for="edit-product-image" class="cw-image-label">Product Image</label>
                    <input type="file" id="edit-product-image" name="product_image" accept="image/*">
                    
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
            } else {
                editContainer.innerHTML = `<p style="color: red;">${data.data.message || 'Failed to load product'}</p>`;
            }
        });
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
