import { config } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import en from '@/locales/en';
import nl from '@/locales/nl';

// jsdom doesn't implement these, but reka-ui / tiptap / vue-sonner rely on them...
class ResizeObserverStub {
    observe() {}
    unobserve() {}
    disconnect() {}
}

class IntersectionObserverStub {
    observe() {}
    unobserve() {}
    disconnect() {}
    takeRecords() {
        return [];
    }
}

globalThis.ResizeObserver ??= ResizeObserverStub as unknown as typeof ResizeObserver;
globalThis.IntersectionObserver ??= IntersectionObserverStub as unknown as typeof IntersectionObserver;

if (!window.matchMedia) {
    window.matchMedia = (query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: () => {},
        removeListener: () => {},
        addEventListener: () => {},
        removeEventListener: () => {},
        dispatchEvent: () => false,
    });
}

if (!Element.prototype.scrollIntoView) {
    Element.prototype.scrollIntoView = () => {};
}

if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = () => false;
}

if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = () => {};
}

// Component tests can render real translated strings without wiring i18n manually per test...
export const testI18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en, nl },
});

config.global.plugins.push(testI18n);
