import { describe, expect, it } from 'vitest';
import { getInitials } from '@/composables/useInitials';

describe('getInitials', () => {
    it('returns an empty string when no name is given', () => {
        expect(getInitials(undefined)).toBe('');
        expect(getInitials('')).toBe('');
        expect(getInitials('   ')).toBe('');
    });

    it('uses the single initial for a one-word name', () => {
        expect(getInitials('Madonna')).toBe('M');
    });

    it('combines first and last initials for multi-word names', () => {
        expect(getInitials('Ada Lovelace')).toBe('AL');
        expect(getInitials('  Grace   Brewster Hopper  ')).toBe('GH');
    });

    it('uppercases initials regardless of input casing', () => {
        expect(getInitials('john doe')).toBe('JD');
    });
});
