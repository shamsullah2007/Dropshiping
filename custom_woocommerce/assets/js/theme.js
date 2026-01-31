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

        updateCarousel();
    });
});
