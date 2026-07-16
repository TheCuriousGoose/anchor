import { createI18n } from 'vue-i18n';
import en from '@/locales/en';
import nl from '@/locales/nl';

export type AppLocale = 'nl' | 'en';

const storageKey = 'locale';

function getStoredLocale(): AppLocale {
    if (typeof window === 'undefined') {
        return 'nl';
    }

    const stored = localStorage.getItem(storageKey);

    return stored === 'en' || stored === 'nl' ? stored : 'nl';
}

export const i18n = createI18n({
    legacy: false,
    locale: getStoredLocale(),
    fallbackLocale: 'en',
    messages: { en, nl },
});

export function setLocale(locale: AppLocale): void {
    i18n.global.locale.value = locale;

    if (typeof window !== 'undefined') {
        localStorage.setItem(storageKey, locale);
        document.documentElement.lang = locale;
    }
}
