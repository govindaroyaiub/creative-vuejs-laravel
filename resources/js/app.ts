import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import './echo'; // Initialize Laravel Echo for real-time WebSocket notifications
// import './timezone-init.js'; // Initialize timezone detection - DISABLED

// Ensure SweetAlert2 dialogs use the auto theme by default across the app
import Swal from 'sweetalert2';
const _swal_fire = Swal.fire.bind(Swal);
Swal.fire = (options, ...rest) => {
    // If running in SSR or without DOM, default to auto
    const hasDOM = typeof document !== 'undefined' && !!document.documentElement;

    // Determine page theme from document class when possible
    const pageTheme = hasDOM && document.documentElement.classList.contains('dark') ? 'dark' : 'light';

    if (options && typeof options === 'object') {
        // Respect explicit options.theme if provided; otherwise set based on page theme
        if (options.theme === undefined || options.theme === null || options.theme === 'auto') {
            options = Object.assign({ theme: pageTheme }, options);
        }
    }

    return _swal_fire(options, ...rest);
};

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#a0abd1', // Modern blue
        delay: 200, // Faster response
        includeCSS: true,
        showSpinner: false, // Clean look without spinner
    },
});

// This will set light / dark mode on page load...
initializeTheme();
