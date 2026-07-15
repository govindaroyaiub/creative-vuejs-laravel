# Reporting RPM Anomaly Detection — Design Spec

**Date**: 2026-07-15
**Status**: Approved for planning
**Scope**: `resources/js/pages/Reporting/Index.vue` (frontend only)

## 1. Purpose

Give the reporting team an always-visible signal that a day's Google Analytics
pageviews are under-reported (incomplete/late-finalized GA4 data). When pageviews
are too low, revenue-per-mille (RPM = revenue ÷ pageviews × 1000) inflates above
its normal band. A high RPM therefore means "the analytics file for this day
likely needs re-uploading."

This replaces the idea of a post-upload warning banner with a persistent column
in the per-day table, so the anomaly is visible whenever the table is viewed, not
just immediately after an upload.

## 2. Background

RPM's normal band for F1Maximaal this month is ~5.1–7.5. A real incident was
observed on 2026-07-14: partial analytics gave `views = 42,526`, producing
`RPM = 462.12 / 42,526 × 1000 = 10.87` — clearly anomalous. After re-uploading the
finalized analytics (`views = 74,700`), RPM fell to 6.19 (normal). The detection
codifies that manual check.

## 3. Requirements (confirmed with user)

- **Presentation**: an **RPM column** in the F1Maximaal per-day table, with the
  whole table **row tinted** by RPM tier. Not a popup/banner.
- **Thresholds** (two tiers, editable in Settings — see §4):
  - `RPM ≥ red`   → **red** row (default `8`, `report_settings.rpm_red`)
  - `amber ≤ RPM < red` → **amber** row (default `7.5`, `report_settings.rpm_amber`)
  - `RPM < amber` → no tint
- **Site scope**: F1Maximaal only. RPM needs pageviews, and analytics/pageviews
  exist only for `f1maximaal`. The column is not rendered for other sites.
- The two thresholds are **user-editable** in the reporting Settings modal (min =
  amber, max = red), persisted like `oguryRate`/`reminder_day`, so the band can be
  retuned without a code change.

## 4. Architecture

Coloring/computation is frontend-only. All inputs are already present in the
`store` prop rendered by `Index.vue`: each day object `d` carries `d.revenue` (all
partner keys) and, for F1Maximaal, `d.analytics.views`. No service, DB, migration,
or `TableExporter` changes.

The two thresholds are stored in the existing generic `report_settings` table
(keys `rpm_amber` / `rpm_red`) and flow through the same path as the other
reporting settings:
- `ReportingController::baseProps()` exposes `rpmAmber` (default 7.5) and `rpmRed`
  (default 8) as page props.
- `ReportingController::config()` validates them (`nullable|numeric|min:0`) and
  persists via `ReportSetting::put()`, alongside `oguryRate`/`reminder_day`.
- `Index.vue` reads them into reactive refs used by the tier logic and bound to two
  number inputs in the Settings modal; `saveSettings()` posts them to
  `/reporting/config`.

## 5. Behavior

**Constants** (top of `<script setup>`):
```
const RPM_AMBER = 7.5
const RPM_RED = 8
```

**Per-day computation** (helper, e.g. `rpmFor(d)`):
- `totalRevenue = PARTNERS.reduce((t, p) => t + (d.revenue?.[p.key] ?? 0), 0)`
  (same sum already used for the existing Total cell)
- `views = d.analytics?.views`
- if `views > 0`: `rpm = totalRevenue / views * 1000`
- else: `rpm = null`

**Row tier** (helper, e.g. `rpmTier(d)` → `'red' | 'amber' | null`):
- `views` missing/0 **and** `totalRevenue > 0` → `'red'` (missing/incomplete
  analytics is itself the "re-upload" signal; RPM is effectively infinite)
- `rpm >= RPM_RED` → `'red'`
- `rpm >= RPM_AMBER` → `'amber'`
- otherwise → `null`

**Column** — rendered only when `selectedSite === 'f1maximaal'`:
- Header `RPM`, placed immediately after the **Total** column, i.e.
  `… Total | RPM | Adhese impr. | Impr. sold | Ad requests | (delete)`.
- Cell content: `rpm.toFixed(2)`, or `—` when `rpm` is `null`.

**Row tint** — bind a class on the existing `<tr v-for="d in days">`:
- `'red'` → `bg-red-500/10`
- `'amber'` → `bg-amber-400/10`
- `null` → unchanged
- The existing per-cell amber ring on the Adhese-impressions input (missing-
  impressions signal) is independent and coexists with the row tint.

**Totals row** — show a blended RPM
(`grandTotalRevenue / Σ(d.analytics.views) × 1000`), **uncolored**, as a reference
figure. If total views is 0, show `—`.

## 6. Edge Cases

| Case | RPM cell | Row tint |
|---|---|---|
| `views > 0`, revenue in normal band | value, e.g. `6.19` | none |
| `views > 0`, `7.5 ≤ rpm < 8` | value | amber |
| `views > 0`, `rpm ≥ 8` | value | red |
| `views` 0/absent, `revenue > 0` | `—` | red |
| `views` 0/absent, `revenue = 0` (empty/future day) | `—` | none |
| Non-f1maximaal site | column not rendered | none |

## 7. Testing / Verification

The repo has no JS test harness (`package.json` has `vue-tsc`/`eslint` only, no
Vitest), so verification is by running the app:

- With current data, all July F1Maximaal rows are uncolored (RPMs ~5.1–7.5) and
  the RPM column matches the Planet Nine report's Trend-sheet RPM per day
  (e.g. 2026-07-13 → 6.38, 2026-07-14 → 6.19).
- Red path: the pre-fix 2026-07-14 case (`views = 42,526`) computes to `10.87`
  and must render red; a `views = 0` day with revenue must render `—` in red.
- `vue-tsc` and `eslint` pass on the changed file.

## 8. Out of Scope

- Applying RPM to non-F1Maximaal sites.
- Any backend/export/DB change (RPM does not need to appear in CSV/XLSX exports).
- The originally-discussed post-upload warning banner.
