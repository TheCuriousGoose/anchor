import { beforeEach, describe, expect, it, vi } from 'vitest';
import { updateTheme } from '@/composables/useAppearance';

function mockMatchMedia(matches: boolean) {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches,
        media: query,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    }));
}

describe('updateTheme', () => {
    beforeEach(() => {
        document.documentElement.classList.remove('dark');
    });

    it('adds the dark class when the value is dark', () => {
        updateTheme('dark');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('removes the dark class when the value is light', () => {
        document.documentElement.classList.add('dark');

        updateTheme('light');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('follows the OS preference when the value is system', () => {
        mockMatchMedia(true);

        updateTheme('system');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});
