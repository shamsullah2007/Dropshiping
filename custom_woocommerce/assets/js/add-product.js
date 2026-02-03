document.addEventListener('DOMContentLoaded', () => {
    // Main product image preview
    const fileInput = document.querySelector('#cw-product-image');
    const preview = document.querySelector('#cw-main-image-preview');

    if (fileInput && preview) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (!file) {
                preview.style.backgroundImage = '';
                preview.classList.remove('has-image');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                preview.style.backgroundImage = `url(${event.target.result})`;
                preview.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        });
    }

    // Gallery images preview
    const galleryInput = document.querySelector('#cw-product-gallery');
    const galleryPreview = document.querySelector('#cw-gallery-preview');

    if (galleryInput && galleryPreview) {
        galleryInput.addEventListener('change', () => {
            // Clear previous previews
            galleryPreview.innerHTML = '';

            const files = galleryInput.files;
            if (!files || files.length === 0) {
                return;
            }

            // Display each selected image
            Array.from(files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (event) => {
                    const imgWrapper = document.createElement('div');
                    imgWrapper.className = 'cw-gallery-item';
                    imgWrapper.style.cssText = `
                        position: relative;
                        display: inline-block;
                        width: 100px;
                        height: 100px;
                        margin: 8px;
                        border: 2px solid #e5e5e5;
                        border-radius: 4px;
                        overflow: hidden;
                        background-size: cover;
                        background-position: center;
                    `;
                    imgWrapper.style.backgroundImage = `url(${event.target.result})`;

                    // Add image number badge
                    const badge = document.createElement('span');
                    badge.textContent = index + 1;
                    badge.style.cssText = `
                        position: absolute;
                        top: 4px;
                        left: 4px;
                        background: rgba(0, 0, 0, 0.7);
                        color: #fff;
                        padding: 2px 6px;
                        border-radius: 3px;
                        font-size: 0.75rem;
                        font-weight: 600;
                    `;
                    imgWrapper.appendChild(badge);

                    galleryPreview.appendChild(imgWrapper);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Multiple videos preview
    const videoInput = document.querySelector('#cw-product-video');
    const videoPreview = document.querySelector('#cw-video-preview');

    if (videoInput && videoPreview) {
        videoInput.addEventListener('change', () => {
            // Clear previous preview
            videoPreview.innerHTML = '';

            const files = videoInput.files;
            if (!files || files.length === 0) {
                return;
            }

            // Display each selected video
            Array.from(files).forEach((file, index) => {
                // Check if it's a video file
                if (!file.type.startsWith('video/')) {
                    alert('Please select only valid video files');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    const videoWrapper = document.createElement('div');
                    videoWrapper.className = 'cw-video-item';
                    videoWrapper.style.cssText = `
                        display: inline-block;
                        width: 180px;
                        margin: 8px;
                        border: 2px solid #e5e5e5;
                        border-radius: 4px;
                        overflow: hidden;
                        background: #000;
                    `;

                    const video = document.createElement('video');
                    video.controls = true;
                    video.muted = true;
                    video.style.cssText = `
                        width: 100%;
                        height: auto;
                        display: block;
                    `;
                    video.src = event.target.result;

                    // Add video number badge
                    const badge = document.createElement('span');
                    badge.textContent = `Video ${index + 1}`;
                    badge.style.cssText = `
                        position: absolute;
                        top: 4px;
                        left: 4px;
                        background: rgba(255, 77, 79, 0.9);
                        color: #fff;
                        padding: 4px 8px;
                        border-radius: 3px;
                        font-size: 0.75rem;
                        font-weight: 600;
                        z-index: 10;
                    `;

                    videoWrapper.style.position = 'relative';
                    videoWrapper.appendChild(badge);
                    videoWrapper.appendChild(video);
                    videoPreview.appendChild(videoWrapper);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});
