/**
 * Canonical sidebar nav definitions.
 *
 * Single source of truth for both AppSidebar.vue (which renders the
 * actual navigation) and the settings/Sidebar.vue page (which lets
 * the user reorder + hide entries). The `href` is the stable key:
 * - Permissions are checked against it (`CheckUserPermission`).
 * - User preferences in `users.nav_preferences` reference it.
 * - The seeded `routes` table uses the same hrefs.
 *
 * If you add a new entry, mirror the href in `config/sidebar.php`
 * so the backend validates it as a known sidebar item; otherwise
 * users won't be able to save preferences that include the new
 * entry.
 */
import {
    Activity,
    BarChart3,
    ChartNoAxesCombined,
    FileCode,
    Handshake,
    HardDriveIcon,
    ImagePlay,
    LayoutTemplate,
    Link2,
    Megaphone,
    Orbit,
    Paintbrush,
    ReceiptText,
    Ruler,
    Type,
    Users,
} from 'lucide-vue-next';
import type { NavItem } from '@/types';

export type SidebarSection = 'main' | 'footer';

export const MAIN_NAV_ITEMS: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: ChartNoAxesCombined },
    { title: 'Reporting', href: '/reporting', icon: BarChart3 },
    { title: 'Previews', href: '/previews', icon: Megaphone },
    { title: 'Color Palettes', href: '/color-palettes', icon: Paintbrush },
    { title: 'Clients', href: '/clients', icon: Handshake },
    { title: 'Creative Sizes', href: '/creative-sizes', icon: Ruler },
    { title: 'Bills', href: '/bills', icon: ReceiptText },
    { title: 'File Transfers', href: '/file-transfers', icon: Link2 },
    { title: 'Media Library', href: '/medias', icon: ImagePlay },
    { title: 'Tetris', href: '/play/tetris', icon: Type },
    { title: 'Templates', href: '/templates', icon: LayoutTemplate },
    { title: 'Orbit', href: '/orbit', icon: Orbit },
];

export const FOOTER_NAV_ITEMS: NavItem[] = [
    { title: 'Access Manager', href: '/user-managements/designations', icon: Users },
    { title: 'Cache Management', href: '/cache-management', icon: HardDriveIcon },
    { title: 'Documentations', href: '/documentations', icon: FileCode },
    { title: 'Pulse', href: '/pulse', icon: Activity, external: true },
];

/**
 * Per-section preference entry stored in `users.nav_preferences`.
 *
 *   { href: '/previews', visible: true }
 *
 * Order in the array IS the user-chosen order.
 */
export interface NavPreferenceEntry {
    href: string;
    visible: boolean;
}

export interface NavPreferences {
    main?: NavPreferenceEntry[];
    footer?: NavPreferenceEntry[];
}

/**
 * Apply a user's saved preferences to the canonical list.
 *
 * Rules:
 *   1. Items the user has explicitly positioned keep that order.
 *   2. Items in the canonical list that the user hasn't seen yet
 *      (added in a later release) are appended to the end as
 *      visible — so a fresh feature isn't silently hidden.
 *   3. Items in the user's saved list that no longer exist in the
 *      canonical list (renamed or removed) are dropped.
 *   4. Hidden items are filtered out before render.
 */
export function applyNavPreferences(
    canonical: NavItem[],
    saved: NavPreferenceEntry[] | undefined | null,
): NavItem[] {
    if (!saved || saved.length === 0) {
        return canonical;
    }

    const byHref = new Map(canonical.map((it) => [it.href, it]));
    const orderedHrefs = saved.map((p) => p.href).filter((h) => byHref.has(h));
    const hiddenHrefs = new Set(saved.filter((p) => !p.visible).map((p) => p.href));

    // Section A: items the user has positioned, in their chosen order.
    const ordered: NavItem[] = orderedHrefs
        .filter((h) => !hiddenHrefs.has(h))
        .map((h) => byHref.get(h)!)
        .filter(Boolean);

    // Section B: any canonical items the user hasn't seen yet — append
    // to the end as visible so new entries aren't accidentally hidden.
    const seen = new Set(saved.map((p) => p.href));
    const newcomers = canonical.filter((it) => !seen.has(it.href));

    return [...ordered, ...newcomers];
}

/**
 * Build the editor-shape (one row per canonical item, in the user's
 * order, with each row's visibility flag) for the settings page.
 * Always returns every canonical item — even hidden ones — because
 * the settings page needs to render them with toggle switches.
 */
export function preferencesForEditor(
    canonical: NavItem[],
    saved: NavPreferenceEntry[] | undefined | null,
): Array<NavItem & { visible: boolean }> {
    const byHref = new Map(canonical.map((it) => [it.href, it]));
    const seenInSaved = new Set<string>();
    const rows: Array<NavItem & { visible: boolean }> = [];

    for (const p of saved ?? []) {
        const item = byHref.get(p.href);
        if (!item) continue;
        rows.push({ ...item, visible: p.visible });
        seenInSaved.add(p.href);
    }

    for (const item of canonical) {
        if (!seenInSaved.has(item.href)) {
            rows.push({ ...item, visible: true });
        }
    }

    return rows;
}

// ─── Cross-section support ─────────────────────────────────────────────────────
// Items may be moved between Workspace (main) and Footer tools, so rendering and
// the editor resolve hrefs against the union of all items, and place each item in
// whichever section the user's saved prefs put it (falling back to its default).

export const ALL_NAV_ITEMS: NavItem[] = [...MAIN_NAV_ITEMS, ...FOOTER_NAV_ITEMS];

const DEFAULT_SECTION: Record<string, SidebarSection> = (() => {
    const m: Record<string, SidebarSection> = {};
    for (const it of MAIN_NAV_ITEMS) m[it.href] = 'main';
    for (const it of FOOTER_NAV_ITEMS) m[it.href] = 'footer';
    return m;
})();

/** Visible items to RENDER in a section (honours cross-section moves + hidden). */
export function navItemsForSection(section: SidebarSection, prefs: NavPreferences | null | undefined): NavItem[] {
    const byHref = new Map(ALL_NAV_ITEMS.map((it) => [it.href, it]));
    const saved = prefs?.[section] ?? [];
    const seen = new Set<string>([...(prefs?.main ?? []), ...(prefs?.footer ?? [])].map((p) => p.href));
    const ordered = saved
        .filter((p) => p.visible && byHref.has(p.href))
        .map((p) => byHref.get(p.href)!);
    const newcomers = ALL_NAV_ITEMS.filter((it) => !seen.has(it.href) && DEFAULT_SECTION[it.href] === section);
    return [...ordered, ...newcomers];
}

/** Editor rows for a section (includes hidden), honouring cross-section moves. */
export function editorRowsForSection(
    section: SidebarSection,
    prefs: NavPreferences | null | undefined,
): Array<NavItem & { visible: boolean }> {
    const byHref = new Map(ALL_NAV_ITEMS.map((it) => [it.href, it]));
    const saved = prefs?.[section] ?? [];
    const seen = new Set<string>([...(prefs?.main ?? []), ...(prefs?.footer ?? [])].map((p) => p.href));
    const rows: Array<NavItem & { visible: boolean }> = [];
    for (const p of saved) {
        const it = byHref.get(p.href);
        if (it) rows.push({ ...it, visible: p.visible });
    }
    for (const it of ALL_NAV_ITEMS) {
        if (!seen.has(it.href) && DEFAULT_SECTION[it.href] === section) rows.push({ ...it, visible: true });
    }
    return rows;
}
