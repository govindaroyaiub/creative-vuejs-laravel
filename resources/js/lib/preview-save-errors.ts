/**
 * Turns a bulk-edit validation bag into something a person can act on.
 *
 * The endpoint validates a deeply nested payload, so Laravel's keys look like
 *
 *     categories.0.feedbacks.0.feedback_sets.0.versions.0.banners.2.size_id
 *
 * and its default messages quote that path verbatim. Both preview editors used
 * to `JSON.stringify` the bag straight into a dialog, which told the user
 * nothing about which item to go and fix.
 *
 * This resolves each key against the tree that was just submitted, so the
 * message names the actual item: "Banner 3 — in Banner › Round 1 › Concept A".
 *
 * Note the editor's labels are deliberately swapped relative to the column
 * names: a `feedback_set` is shown as a "version" and a `version` as a "set".
 * See Inspector.vue and TreeNode.vue. Wording here follows the editor.
 */

export type SaveProblem = {
    /** Human path to the offending item, e.g. "Banner › Round 1 › Concept A". */
    where: string;
    /** What is wrong, phrased as a sentence fragment. */
    what: string;
};

/** Field name → how to describe it failing. */
const FIELD_PHRASES: Record<string, string> = {
    name: 'needs a name',
    description: 'needs a description',
    type: 'has an unrecognised type',
    size_id: 'needs a size',
    position: 'is missing its position',
    file: 'has a file the server rejected',
    companion_banner: 'has a backup image the server rejected',
    feedbacks: 'has no revision round',
    feedback_sets: 'has no version',
    versions: 'has no set',
    banners: 'has no banners',
};

/** Asset collection key → singular label shown to the user. */
const ASSET_LABELS: Record<string, string> = {
    banners: 'Banner',
    videos: 'Video',
    socials: 'Social',
    gifs: 'GIF',
};

const named = (node: any, fallback: string) => (node?.name || '').trim() || fallback;

/**
 * Walk the submitted tree along a validation key's indexes, collecting labels
 * for each level it actually reaches. Stops early if the tree no longer matches
 * (the user may have edited it since the request went out).
 */
function describeLocation(categories: any[], key: string): { where: string; leaf: string | null } {
    const parts = key.split('.');
    const labels: string[] = [];
    let leaf: string | null = null;

    // Indexes come from a split() so they are `string | undefined` under
    // noUncheckedIndexedAccess; treat a missing segment as "no match".
    const at = (arr: any[] | undefined, i: string | undefined) =>
        i === undefined ? undefined : (arr ?? [])[Number(i)];

    const category = at(categories, parts[1]);
    if (!category) return { where: '', leaf: null };
    labels.push(named(category, 'Untitled project'));

    const feedback = parts[2] === 'feedbacks' ? at(category.feedbacks, parts[3]) : undefined;
    if (feedback) {
        labels.push(named(feedback, 'Untitled round'));

        const set = parts[4] === 'feedback_sets' ? at(feedback.feedback_sets, parts[5]) : undefined;
        if (set) {
            labels.push(named(set, 'Untitled version'));

            const version = parts[6] === 'versions' ? at(set.versions, parts[7]) : undefined;
            if (version) {
                labels.push(named(version, 'Untitled set'));

                const assetKey = parts[8] ?? '';
                const assetIndex = parts[9];
                const assetLabel = ASSET_LABELS[assetKey];
                if (assetLabel && assetIndex !== undefined) {
                    const asset = at(version[assetKey], assetIndex);
                    leaf = named(asset, `${assetLabel} ${Number(assetIndex) + 1}`);
                }
            }
        }
    }

    return { where: labels.join(' › '), leaf };
}

/** The trailing field name of a validation key, ignoring numeric indexes. */
function fieldOf(key: string): string {
    const parts = key.split('.').filter((p) => !/^\d+$/.test(p));
    return parts[parts.length - 1] ?? key;
}

/**
 * A server message is only useful if it does not quote the raw key back. Custom
 * messages (see NewPreviewController::bulkEdit) read well and are preferred;
 * Laravel's defaults are replaced with our own phrasing.
 */
function isHumanMessage(message: string, key: string): boolean {
    return Boolean(message) && !message.includes(key) && !message.includes('categories.');
}

export function formatBulkEditErrors(
    errors: Record<string, string>,
    categories: any[],
): SaveProblem[] {
    const problems: SaveProblem[] = [];
    const seen = new Set<string>();

    Object.entries(errors ?? {}).forEach(([key, rawMessage]) => {
        const message = Array.isArray(rawMessage) ? rawMessage[0] : rawMessage;

        if (!key.startsWith('categories')) {
            // preview_id, idempotency_key and friends — nothing to locate.
            const problem = { where: '', what: message || 'is invalid' };
            const id = `${problem.where}|${problem.what}`;
            if (!seen.has(id)) {
                seen.add(id);
                problems.push(problem);
            }
            return;
        }

        const field = fieldOf(key);
        const { where, leaf } = describeLocation(categories, key);
        const trail = where ? where.split(' › ') : [];

        let problem: SaveProblem;

        if (isHumanMessage(message, key)) {
            // A custom server message already reads as a sentence but does not
            // name the item, so keep the whole trail as the location.
            problem = { where, what: message };
        } else if (leaf) {
            // An asset: name it, and let the trail locate its version.
            problem = {
                where,
                what: `${leaf} ${FIELD_PHRASES[field] ?? `has an invalid ${field.replace(/_/g, ' ')}`}.`,
            };
        } else {
            // A container: the deepest label IS the subject, so it moves out of
            // the trail and into the sentence.
            const subject = trail.pop() ?? 'This item';
            problem = {
                where: trail.join(' › '),
                what: `${subject} ${FIELD_PHRASES[field] ?? `has an invalid ${field.replace(/_/g, ' ')}`}.`,
            };
        }

        const id = `${problem.where}|${problem.what}`;
        if (!seen.has(id)) {
            seen.add(id);
            problems.push(problem);
        }
    });

    return problems;
}

const escapeHtml = (value: string) =>
    value.replace(/[&<>"']/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c] as string,
    );

/**
 * Render problems as a list for SweetAlert's `html:` option.
 *
 * Everything interpolated here is escaped. These strings contain names the user
 * typed, so passing them through raw would make a field containing `<script>`
 * into self-XSS — which is why both editors previously used `text:` and a JSON
 * dump. Escaping buys the readable list back safely.
 */
export function renderProblemsHtml(problems: SaveProblem[], limit = 12): string {
    const shown = problems.slice(0, limit);
    const hidden = problems.length - shown.length;

    const items = shown
        .map((p) => {
            const where = p.where
                ? `<div style="font-size:11px;opacity:.65;margin-top:2px">${escapeHtml(p.where)}</div>`
                : '';
            return `<li style="margin:0 0 10px;padding:0">${escapeHtml(p.what)}${where}</li>`;
        })
        .join('');

    const more = hidden > 0
        ? `<p style="font-size:12px;opacity:.7;margin:4px 0 0">…and ${hidden} more.</p>`
        : '';

    return `<ul style="text-align:left;margin:0;padding:0 0 0 18px;font-size:13px;line-height:1.45">${items}</ul>${more}`;
}
