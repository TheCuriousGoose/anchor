import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { createSSRApp, h, type Component, type DefineComponent } from 'vue';
import { renderToString } from 'vue/server-renderer';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { i18n } from '@/lib/i18n';

const appName = 'AnchorNotes';
type PageComponent = DefineComponent & {
    layout?: Component | Component[];
};

const pages = import.meta.glob<{ default: PageComponent }>('./pages/**/*.vue');

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: async (name) => {
            const pageComponent = pages[`./pages/${name}.vue`];

            if (!pageComponent) {
                throw new Error(`Page component not found: ${name}`);
            }

            const component = (await pageComponent()).default;

            switch (true) {
                case name === 'Welcome' ||
                    name === 'Workspace' ||
                    name === 'About' ||
                    name === 'Privacy':
                    return component;
                case name.startsWith('auth/'):
                    component.default.layout = AuthLayout;
                    break;
                case name.startsWith('settings/'):
                    component.default.layout = [AppLayout, SettingsLayout];
                    break;
                default:
                    component.default.layout = AppLayout;
            }

            return component;
        },
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(i18n);
        },
    }),
);
