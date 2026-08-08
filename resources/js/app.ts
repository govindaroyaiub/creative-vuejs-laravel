import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { renderMessagesHtml, summarise } from './lib/validation-errors';
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

/**
 * The component currently on screen. `router.page` is not part of the public
 * Router type, so it is tracked from the typed `navigate` event instead, seeded
 * with the initial page in `setup()` below.
 */
let currentComponent: string | undefined;
let currentPageProps: Record<string, any> | undefined;

router.on('navigate', (event) => {
    currentComponent = event.detail.page.component;
    currentPageProps = event.detail.page.props as Record<string, any>;
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        currentComponent = props.initialPage.component;
        currentPageProps = props.initialPage.props as Record<string, any>;
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

/**
 * Pages that present validation errors next to the fields themselves (via
 * InputError.vue). A toast on top of that is noise, so they opt out here rather
 * than each having to disable anything.
 *
 * Keyed on the Inertia component name, i.e. what `resolve()` receives.
 */
const PAGES_WITH_INLINE_ERRORS = [
    'auth/',
    'settings/',
    'UserManagements/Register',
    'UserManagements/ChangePassword',
    // Not inline errors, but a better handler: the preview editor resolves the
    // nested validation keys against its tree and names the offending item
    // ("Banner 3 needs a size — Banner > Round 1 > Concept A"), which the
    // generic toast cannot do. See lib/preview-save-errors.ts.
    'Previews/Update2',
];

const handlesErrorsItself = () =>
    Boolean(currentComponent) &&
    PAGES_WITH_INLINE_ERRORS.some((prefix) => currentComponent!.startsWith(prefix));

/**
 * One global handler for validation failures.
 *
 * Inertia's `error` event carries the whole bag, so every page gets readable
 * errors without its own handler. Two things worth knowing about this event:
 *
 *   - it fires *only* for validation failures (422). Server faults arrive as
 *     `exception` / `invalid`, which is why the old per-page handlers saying
 *     "Failed to create client." never appeared for a real 500 — those got no
 *     feedback at all. Both are wired up below.
 *   - its contract is `result: void`, so it cannot be cancelled. A page cannot
 *     suppress this, which is why any page keeping its own error dialog would
 *     show two. Toast placement keeps it unobtrusive next to inline errors.
 */
router.on('error', (event) => {
    if (handlesErrorsItself()) return;

    const errors = event.detail.errors ?? {};
    if (Object.keys(errors).length === 0) return;

    Swal.fire({
        icon: 'warning',
        title: summarise(errors),
        html: renderMessagesHtml(errors),
        toast: true,
        position: 'top-end',
        width: 420,
        showConfirmButton: false,
        timer: 6000,
        timerProgressBar: true,
    });
});

const serverFaultToast = (title: string) =>
    Swal.fire({
        icon: 'error',
        title,
        text: 'The server could not complete that request. Nothing was saved. Please try again.',
        toast: true,
        position: 'top-end',
        width: 420,
        showConfirmButton: false,
        timer: 6000,
        timerProgressBar: true,
    });

/**
 * A non-Inertia response — typically a 500.
 *
 * This is the gap that made a database error surface as a raw SQL stack trace:
 * `error` only fires for 422, so a 500 never reached the validation handler and
 * Inertia showed its default modal containing the framework's error page.
 *
 * Returning false suppresses that modal. It is kept when `app.debug` is on,
 * because that modal is how you actually diagnose a 500 locally; with debug off
 * the user gets a clean message instead of Laravel's error page.
 */
router.on('invalid', (event) => {
    const response = event.detail.response;
    console.error('Unexpected response', response?.status, response);

    const debug = Boolean((response?.data as any)?.props?.debug ?? currentPageProps?.debug);
    if (debug) return;

    serverFaultToast(
        response?.status === 500 ? 'Something went wrong' : `Request failed (${response?.status ?? 'network'})`,
    );

    return false;
});

// A thrown error rather than an HTTP response: network drop, JSON parse, etc.
router.on('exception', (event) => {
    console.error('Request failed', event.detail.exception);
    serverFaultToast('Something went wrong');
});

// This will set light / dark mode on page load...
initializeTheme();
