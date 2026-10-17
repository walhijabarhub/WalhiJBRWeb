// The CSP build of Alpine evaluates expressions without eval()/new Function(),
// which the Content Security Policy forbids.
import Alpine from '@alpinejs/csp';
import './csp-actions';

Alpine.data('heroSlider', (total = 1) => ({
    active: 0,
    total,
    timer: null,
    touchStartX: 0,

    init() {
        this.autoplay();
    },

    autoplay() {
        this.stop();
        if (this.total > 1) {
            this.timer = setInterval(() => this.next(), 6000);
        }
    },

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    },

    next() {
        this.active = (this.active + 1) % this.total;
    },

    prev() {
        this.active = (this.active - 1 + this.total) % this.total;
    },

    nextAndRestart() {
        this.next();
        this.autoplay();
    },

    prevAndRestart() {
        this.prev();
        this.autoplay();
    },

    goToAndRestart(index) {
        this.active = index;
        this.autoplay();
    },

    bulletClass(index) {
        return this.active === index ? 'w-8 h-2.5 bg-brand-orange' : 'w-2.5 h-2.5 bg-white/50 hover:bg-white/90';
    },

    get trackStyle() {
        return `transform: translateX(-${this.active * 100}%);`;
    },

    handleTouchStart(event) {
        this.touchStartX = event.changedTouches[0].screenX;
    },

    handleTouchEnd(event) {
        const touchEndX = event.changedTouches[0].screenX;
        if (this.touchStartX - touchEndX > 40) {
            this.next();
        } else if (touchEndX - this.touchStartX > 40) {
            this.prev();
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();
