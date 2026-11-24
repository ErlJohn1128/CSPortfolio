import './image-crop';

window.changeSlide = function (button, direction) {
    const carousel = button.closest('[data-carousel]');
    const slides = carousel.querySelectorAll('.carousel-slide');
    const currentIndex = Array.from(slides).findIndex(
        (slide) => !slide.classList.contains('hidden')
    );

    slides[currentIndex].classList.add('hidden');

    const nextIndex = (currentIndex + direction + slides.length) % slides.length;
    slides[nextIndex].classList.remove('hidden');
};

const modeToggle = document.getElementById('mode-toggle');
const htmlElement = document.documentElement;

if (modeToggle) {
    modeToggle.checked = htmlElement.classList.contains('dark');

    modeToggle.addEventListener('change', () => {
        const isDark = modeToggle.checked;
        const toggleBounds = modeToggle.getBoundingClientRect();

        htmlElement.style.setProperty('--theme-toggle-x', `${toggleBounds.left + toggleBounds.width / 2}px`);
        htmlElement.style.setProperty('--theme-toggle-y', `${toggleBounds.top + toggleBounds.height / 2}px`);

        const applyTheme = () => {
            htmlElement.classList.toggle('dark', isDark);
            localStorage.setItem('flux.appearance', isDark ? 'dark' : 'light');
        };

        if (document.startViewTransition) {
            document.startViewTransition(applyTheme);
        } else {
            applyTheme();
        }
    });
}