// Background images below the fold are written as data-lazy-bg="<url>" and only
// requested when the element comes near the viewport (native loading="lazy" only
// works on <img>).
function show(element) {
    element.style.backgroundImage = `url(${JSON.stringify(element.dataset.lazyBg)})`;
    element.removeAttribute('data-lazy-bg');
}

function init() {
    const elements = document.querySelectorAll('[data-lazy-bg]');

    if (!('IntersectionObserver' in window)) {
        elements.forEach(show);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                show(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '300px 0px' });

    elements.forEach((element) => observer.observe(element));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
