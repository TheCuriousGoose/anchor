import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AdminPagination from '@/components/AdminPagination.vue';
import type { Paginated } from '@/types/admin';

const { visit } = vi.hoisted(() => ({ visit: vi.fn() }));

vi.mock('@inertiajs/vue3', () => ({
    router: { visit },
}));

function paginated(overrides: Partial<Paginated<unknown>> = {}): Paginated<unknown> {
    return {
        data: [],
        current_page: 2,
        last_page: 3,
        per_page: 10,
        total: 25,
        from: 11,
        to: 20,
        links: [
            { url: '/users?page=1', label: '1', active: false },
            { url: '/users?page=2', label: '2', active: true },
            { url: '/users?page=3', label: '3', active: false },
        ],
        ...overrides,
    };
}

describe('AdminPagination', () => {
    beforeEach(() => {
        visit.mockClear();
    });

    it('does not render when there is only one page', () => {
        const wrapper = mount(AdminPagination, {
            props: { paginated: paginated({ last_page: 1 }) },
        });

        expect(wrapper.find('div').exists()).toBe(false);
    });

    it('renders the current range and page counters', () => {
        const wrapper = mount(AdminPagination, {
            props: { paginated: paginated() },
        });

        expect(wrapper.text()).toContain('Showing 11–20 of 25');
        expect(wrapper.text()).toContain('Page 2 of 3');
    });

    it('disables previous/next at the bounds and navigates via the Inertia router otherwise', async () => {
        const wrapper = mount(AdminPagination, {
            props: { paginated: paginated() },
        });

        const [previous, next] = wrapper.findAll('button');

        expect(previous.attributes('disabled')).toBeUndefined();
        expect(next.attributes('disabled')).toBeUndefined();

        await next.trigger('click');

        expect(visit).toHaveBeenCalledWith('/users?page=3', {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    });

    it('disables next on the last page', () => {
        const wrapper = mount(AdminPagination, {
            props: { paginated: paginated({ current_page: 3 }) },
        });

        const [, next] = wrapper.findAll('button');

        expect(next.attributes('disabled')).toBeDefined();
    });
});
