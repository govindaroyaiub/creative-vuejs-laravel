/**
 * Shared rendering for Laravel validation bags.
 *
 * Before this, 25 of the 33 pages with an `onError` handler threw the bag away
 * and showed a fixed string —
 *
 *     onError: () => Swal.fire('Error!', 'Failed to create client.', 'error')
 *
 * so the user was told something failed but never which field or why. One page
 * dumped `JSON.stringify(errors)` instead. This module plus the global hook in
 * `app.ts` replaces all of that, so a page needs no error handling of its own.
 *
 * Field names are made readable server-side by `lang/en/validation.php`
 * ('attributes'), which covers every validation site at once. This file only
 * has to present what comes back.
 */

const escapeHtml = (value: string) =>
    value.replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] as string,
    );

/** Inertia gives `Record<string, string>`; axios shapes can give arrays. */
export type ErrorBag = Record<string, string | string[]>;

/** Flatten a bag to one message per field, in insertion order. */
export function messagesFrom(errors: ErrorBag | null | undefined): string[] {
    const seen = new Set<string>();

    return Object.values(errors ?? {})
        .map((value) => (Array.isArray(value) ? value[0] : value))
        .filter((message): message is string => Boolean(message))
        .filter((message) => {
            if (seen.has(message)) return false;
            seen.add(message);
            return true;
        });
}

/**
 * A short summary suitable for a toast title.
 */
export function summarise(errors: ErrorBag | null | undefined): string {
    const count = messagesFrom(errors).length;

    if (count === 0) return "That didn't save";
    if (count === 1) return 'One field needs attention';
    return `${count} fields need attention`;
}

/**
 * Render messages as an escaped list for SweetAlert's `html:` option.
 *
 * Escaping matters: these strings quote back values the user typed, so
 * rendering them raw would turn a field containing `<script>` into self-XSS.
 * That risk is why the old handlers used `text:` with a JSON dump; escaping
 * here buys a readable list without reintroducing it.
 */
export function renderMessagesHtml(
    errors: ErrorBag | null | undefined,
    limit = 8,
): string {
    const messages = messagesFrom(errors);

    if (messages.length === 0) {
        return '<p style="font-size:13px;margin:0">The server rejected the request. Details are in the browser console.</p>';
    }

    const shown = messages.slice(0, limit);
    const hidden = messages.length - shown.length;

    const items = shown
        .map((m) => `<li style="margin:0 0 6px;padding:0">${escapeHtml(m)}</li>`)
        .join('');

    const more =
        hidden > 0
            ? `<p style="font-size:12px;opacity:.7;margin:6px 0 0">…and ${hidden} more.</p>`
            : '';

    return `<ul style="text-align:left;margin:0;padding:0 0 0 18px;font-size:13px;line-height:1.45">${items}</ul>${more}`;
}
