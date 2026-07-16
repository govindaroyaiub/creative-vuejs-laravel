# Reporting: "Days" cards tab (F1Maximaal)

**Date:** 2026-07-16
**Status:** Approved

## Problem

The Table tab is a wall of data. The daily operational workflow on F1Maximaal —
checking Adhese impressions, Impressions sold and Ad requests per day, filling
Adhese gaps, deleting bad days — only needs a handful of fields. A simplified
day-card view serves that workflow without touching the Table.

## Scope

- **F1Maximaal only.** TopGear/Horses/Festileaks days don't carry Adhese
  impressions / Impressions sold / Ad requests, so the tab is hidden for them.
- Table tab stays exactly as-is.

## Design

### Tab

- New tab `days`, labeled **Days**, first in the tab row for F1Maximaal:
  Days | Summary | Table | Verify | Email.
- `tabs` becomes a computed that prepends `days` when `selectedSite === 'f1maximaal'`.
- Default tab on page load is `days` (F1Maximaal is the default site).
- Switching to F1Maximaal always lands on Days; switching away while on Days
  falls back to Table (other sites can't render the cards — their days carry no
  impressions object, which is also why the cards section uses `v-if`, not `v-show`).

### Cards

- Responsive grid (2–4 per row), **newest day first** (Table already covers
  chronological order).
- Each card:
  - Header: date, RPM badge (same amber/red banding as table rows), delete button
    (reuses `deleteDay`).
  - Rows: Adhese impressions (editable input, same `saveAdhese` save-on-change as
    the table), Impressions sold, Ad requests.
  - Card background/border tinted amber/red via the same `rpmTier` logic so
    anomaly days stand out in the grid.
- No revenue / partner figures on cards — that stays the Table's job.

### Toolbar (above the grid)

- Day count + active range (same as Table header line).
- Export dropdown (xlsx/csv/json) — same `exportTable` actions as the Table tab.
- Missing-Adhese "Fill now" banner reused.

## Out of scope

- Any change to the Table tab.
- Cards for non-F1Maximaal sites.
