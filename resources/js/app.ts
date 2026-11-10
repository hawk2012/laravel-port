import '../css/app.css';
import './bootstrap';

import { createSSRApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { setRouteConfig } from '@/lib/route';

const appName = import.meta.env.VITE_APP_NAME || 'Kontur AI';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        setRouteConfig(props.initialPage.props.ziggy);

        createSSRApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: {
        color: '#6366f1',
    },
});
