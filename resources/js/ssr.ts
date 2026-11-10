import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { createSSRApp, h, type DefineComponent } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { setRouteConfig } from '@/lib/route';

const appName = import.meta.env.VITE_APP_NAME || 'Kontur AI';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} — ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
            ),
        setup({ App, props, plugin }) {
            setRouteConfig(page.props.ziggy);

            return createSSRApp({ render: () => h(App, props) }).use(plugin);
        },
    }),
);
