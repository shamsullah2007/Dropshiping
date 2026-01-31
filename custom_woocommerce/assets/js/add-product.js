document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('#cw-product-image');
    const preview = document.querySelector('.cw-image-preview');

    if (!fileInput || !preview) {
        return;
    }

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
});
