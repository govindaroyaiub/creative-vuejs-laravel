# Reporting: Update2-style visual refresh

**Date:** 2026-07-23
**Status:** Approved

## Problem

The user likes the visual language of the Previews "Update2" editor
(`resources/js/pages/Previews/Update2.vue` + its `Update2/` component tree):
JetBrains Mono, glass-surface cards, hairline borders, uppercase tracked
micro-labels, tabular numerals, an accent-colored ambient glow, and a
starfield/aurora backdrop in dark mode. They want that same chrome applied to
`resources/js/pages/Reporting/Index.vue`, which currently uses a plain
Tailwind `font-mono` stack and stock shadcn `Card` styling.

## Scope

- **Style only.** No script/logic changes, no new Vue components, no change
  to any tab's functional behavior (Days/Summary/Table/Verify/Email, and the
  Settings/Adhese/Download/Links/Email modals).
- Applies to the **whole page** — header, tab bar, all five tab bodies, and
  all modals.
- Single file touched: `resources/js/pages/Reporting/Index.vue` (root wrapper
  class + one new `<style>` block + targeted class additions on existing
  elements).

## Design

### Tokens (fixed — no per-item palette, unlike Update2)

- `--rpt-accent: #e2483d` — existing Reporting brand red, unchanged from
  current buttons/badges.
- `--rpt-accent-2: #f59e0b` — amber, already used on this page for the
  RPM amber/red anomaly tint. Reusing it as the second ambient hue ties the
  new decorative glow to a color the page already gives meaning to, instead
  of introducing an arbitrary new one.
- Derived soft/muted/glow alpha variants for each, computed the same way
  Update2 does (`${accent}1a`, `${accent}38`, `${accent}66`), but as static
  CSS custom properties (no `:style` binding needed since both accents are
  fixed, not per-page-instance data).
- Surface/text/border tokens (`--rpt-bg`, `--rpt-surface`, `--rpt-text`,
  `--rpt-border`, `--rpt-hairline`, etc.) mirror Update2's light/dark values
  exactly.

### Root wrapper

- The page's outer `<div class="min-h-screen bg-background font-mono ...">`
  gains a `rpt-root` class. No `:style` binding needed (tokens are static
  CSS, not computed from props).

### Ambient + starfield

- Two `aria-hidden="true"` decorative `<div>`s (`.rpt-ambient`,
  `.rpt-stars`) inserted immediately inside `.rpt-root`, before the header —
  same technique as Update2's `.update2-ambient` / `.update2-stars`.
- Light mode: soft dual radial-gradient wash (red + amber), low opacity.
- Dark mode: full aurora bloom (three radial gradients) + twinkling star
  dots, animated via the same `@keyframes` approach as Update2, gated by
  `prefers-reduced-motion: reduce` (animation disabled, opacity fixed).
- All real content wrapped so it sits above these layers (`z-index: 1`),
  same `.update2-root > :not(...)` sibling-selector trick.

### Font

- `@import` JetBrains Mono (400/500/600) in the new `<style>` block.
- `.rpt-root { font-family: 'JetBrains Mono', ui-monospace, ...; }` replaces
  the Tailwind `font-mono` utility class already on the root div.
- `font-feature-settings: 'cv11', 'ss01', 'tnum'` on the root — tabular
  numerals benefit every €/RPM/impression figure already on this page.

### Glass cards

- New `.rpt-glass` utility class: translucent surface background
  (`--rpt-surface-muted`), `backdrop-filter: blur(12px)`, hairline border.
- Added (as an extra class, not a replacement) to every `<Card>` currently
  on the page: Upload card, Days grid cards, Summary KPI/chart cards, Table
  wrapper card, Verify cards, Email cards, and the Settings / Adhese-batch /
  Download / Report-links dialog surfaces.
- The existing `rpmCardClass` amber/red tinting on Days cards is unaffected
  — it layers its own background/border color on top of the glass base.

### Labels

- New `.rpt-label` utility (11px, uppercase, `letter-spacing: 0.14em`,
  muted color) swapped onto existing small caption/field-label spans that
  are acting as field labels — e.g. the "Adhese impr." / "Impr. sold" / "Ad
  requests" captions in the Days cards, Summary KPI captions, Table column
  header cells. Purely a class swap on existing `<span>`/`<th>` elements
  already carrying `text-muted-foreground text-xs`-style classes.

### Focus rings

- `.rpt-root :focus-visible { outline: none; box-shadow: 0 0 0 2px
  var(--rpt-bg), 0 0 0 4px var(--rpt-accent); border-radius: inherit; }` —
  same treatment Update2 applies globally within its root.

## Out of scope

- Any change to Update2 itself (no shared/extracted component — this spec
  duplicates the relevant CSS pattern into Reporting's own `<style>` block
  rather than factoring out a shared chrome component, to avoid touching the
  Previews editor).
- Any change to tab logic, data fetching, modal behavior, or the Days-tab
  "last 8 / show all" toggle added earlier.
- Any new Vue components or file splits.
