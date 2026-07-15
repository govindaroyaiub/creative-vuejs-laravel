# Planet Nine Partner Reporting — Design Spec

**Date**: 2026-07-15
**Status**: Approved for planning
**Source system**: `app/Services/Reporting/*`, `app/Models/ReportDay.php`, `app/Models/ReportSetting.php`, `app/Http/Controllers/ReportingController.php` in this repo (`creative-vuejs-laravel`)
**Target project location**: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting` (new, separate folder — not a subfolder of this repo)

## 1. Purpose & Scope

Extract the **Reporting module only** from the existing Laravel + Vue/Inertia monolith into a standalone application:

- **Frontend/backend**: Next.js (App Router, TypeScript) on Vercel
- **Database**: Supabase (Postgres) + Supabase Auth + Supabase Storage
- Explicitly **out of scope**: previews/feedback/versions, bills/clients, file transfers, media library, Orbit (banner ad-serving — a separate subsystem, not part of the partner-revenue Reporting pipeline despite superficial "ads" overlap), support tickets, templates, user management, activity logs, Pulse/cache/log-viewer admin tooling.

This is a **port, not a redesign** — same schema shape, same business rules, same constants, same numeric outputs. The existing PHP pipeline was itself ported 1:1 from an original standalone Express/Node app and has a golden-file parity test (`ExtractorParityTest`) proving numeric fidelity; the same fixtures/expected values carry over to prove the TypeScript port is faithful too.

## 2. Requirements (confirmed with user)

- Reporting module only, standalone app — confirmed.
- **Users**: internal Planet Nine team only, no client-facing access.
- **Access control**: flat — any authenticated user sees/edits everything, no roles/tiers.
- **Data ingestion**: raw ad-network files (Adform/GAM/Ogury/SeedTag/ShowHeroes/Teads/Adhese/Outbrain exports) are uploaded via the app UI, processed synchronously, written to Supabase. Replaces the old `php artisan reporting:import`/`ImportLegacyReporting` console-command flow entirely — no console commands in the new app.
- **File volume**: small, infrequent uploads — synchronous processing in a single Vercel serverless function call is sufficient; no background job queue needed.
- **Auth**: Supabase Auth, email + password. No self-signup page — accounts are created manually by an admin via the Supabase dashboard.
- **Historical data**: existing `report_days`/`report_settings` data from the Laravel/MySQL app must be migrated into Supabase (one-time migration, not an ongoing sync).
- **Cross-environment sync**: not needed. The existing `POST /reporting/sync`, `reporting:export`/`reporting:import` Artisan commands, and the git-committed `database/reporting-export.json` snapshot existed to reconcile data across multiple separate Laravel installs (each with its own local DB). The new app has one shared Supabase DB as single source of truth, so this entire mechanism is dropped, not ported.

## 3. Architecture

**Stack**: Next.js 14+ (App Router, TypeScript), Tailwind + shadcn/ui (visually consistent with the existing app), deployed on Vercel. Supabase for Postgres DB, Auth, and Storage (raw uploaded files + generated exports/zips).

**Layout**:
```
app/
  (auth)/login/            — email+password login (Supabase Auth), no signup route
  (dashboard)/reporting/   — protected pages (session-gated via middleware)
  api/reporting/...        — route handlers, use Supabase service-role key for writes
lib/reporting/             — ported processing modules (see §5)
middleware.ts              — checks Supabase session via @supabase/ssr, redirects to /login if absent
```

**Auth flow**: Supabase session via cookies (`@supabase/ssr`). `middleware.ts` gates all `(dashboard)` and `api/reporting/*` routes. No public/unauthenticated reporting pages (unlike the old app's public preview viewer — not applicable here since Reporting has no client-facing surface).

**Data flow**: Upload file (client) → API route parses via `lib/reporting/*` → writes normalized rows to Supabase Postgres → dashboard pages query Supabase directly (server components) for display/exports.

## 4. Data Model (Supabase / Postgres)

Same two-table generic-JSON-store design as the source system — intentionally not normalized into per-partner columns, matching the "port, not redesign" directive.

```sql
create table report_days (
  id bigint generated always as identity primary key,
  site text not null,                    -- f1maximaal | topgear | horses | festileaks
  date date not null,
  revenue jsonb,                          -- {adhese,gam,seedtag,teads,showheroes,adform,ogury,outbrain,preferredDeals}
  impressions jsonb,                      -- same 9 keys; f1maximaal only carries the full set
  total_ad_requests bigint not null default 0,
  analytics jsonb,                        -- GA4 metrics, f1maximaal only
  impressions_sold bigint not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (site, date)
);
create index report_days_date_idx on report_days (date);

create table report_settings (
  id bigint generated always as identity primary key,
  key text not null unique,               -- oguryRate, reminder_day, ogury_old_format, file_patterns, report_links
  value jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
```

**RLS policy**: any authenticated user may select/insert/update/delete on both tables. No per-row ownership.

**Storage buckets**: `raw-uploads` (canonical partner files, replaces `storage/app/reporting/uploads` on local disk — Vercel's filesystem is ephemeral) and `exports` (generated zips), both private, accessed via signed URLs.

**Note on `report_settings`**: `last_run` and `synced_at` keys from the source system are tied to the dropped sync mechanism (§2) and are not needed. `oguryRate`, `reminder_day`, `ogury_old_format`, `file_patterns`, `report_links` all carry over unchanged.

## 5. Constants & Processing Pipeline (`lib/reporting/`)

Ported verbatim into `lib/reporting/constants.ts`, preserving exact key order (both PHP arrays and JS objects preserve insertion order, so column/field ordering in CSV/XLSX exports ports cleanly):

- `SITES` — 4 sites (f1maximaal, topgear, horses, festileaks), each with `name`, `adformPrefix`, `gamPrefix`, `domain`, `oguryAsset`
- `RENAME_MAP`, `ADHESE_MARKET`, `MONTHS`, `DEFAULT_FILE_PATTERNS`

One TS module per PHP service, same responsibilities:

| Module | Responsibility | Key porting notes |
|---|---|---|
| `spreadsheetReader.ts` | Read xlsx/csv (rows/matrix/sheetNames/csvRows) | Replaces PhpSpreadsheet with `xlsx` (SheetJS) npm package |
| `extractors.ts` | One function per partner (adhese, seedtag, teads, showheroes, gam, adform, ogury, gamF1m, outbrain, impressionsF1, preferredDeals, analytics) | Ogury revenue-share rate multiplier stays applied **inside** the extractor, not later in the pipeline |
| `storeMerger.ts` | Merge parsed partner data into in-memory store | **Critical invariant, must be preserved**: a partner missing from a run is NOT zeroed — falls back `newValue ?? existing ?? 0`. Adhese impressions are never derived from a file — manual-entry only, never overwritten by merge |
| `reportProcessor.ts` | Orchestrate an upload run: detect type → parse → group adhese by site → filter → merge → persist → regenerate files | **Hard business rule preserved**: strips rows outside "current month + trailing 7 days" — any historical backfill must write to `reportStore.ts` directly, bypassing this filter (same as source system's test fixtures do) |
| `reportStore.ts` | Load/save against Supabase (`report_days`/`report_settings`) | Same in-memory shape as source; `oguryRate` defaults to `0.85` |
| `csvGenerator.ts` | Regenerate Analytics.csv / Adhese CSVs | Exact header text and number formatting preserved (downstream tooling re-ingests these) |
| `tableExporter.ts` | Dashboard table export (csv/xlsx/json) | Same `PARTNERS` column order (differs from `storeMerger`'s key order — both preserved exactly as in source) |
| `verifier.ts` | Reconcile stored data against Planetnine reports (monthly/weekly) | Same tolerances (Impr. Sold ±50, Ad Requests ±10, Revenue ±0.5) |
| `oguryConverter.ts` | Convert current Ogury export format to legacy two-sheet format | SheetJS write instead of PhpSpreadsheet write; same sheet structure/styling |
| `zipBuilder.ts` | Build downloadable zip of canonical files | Uses `archiver` or `jszip` instead of PHP `ZipArchive`; reads from Supabase Storage bucket instead of local disk; same `planetnine-report-*` exclusion rule |

## 6. API Routes

| Source (Laravel) | Target (Next.js) | Notes |
|---|---|---|
| `GET /reporting` | `app/(dashboard)/reporting/page.tsx` | Server component, queries Supabase directly |
| `POST /reporting/process` | `POST /api/reporting/process` | Same current-month+7-day filter |
| `POST /reporting/save-adhese` | `POST /api/reporting/save-adhese` | Same impressions-sold recompute |
| `POST /reporting/save-adhese-batch` | `POST /api/reporting/save-adhese-batch` | Same silent-skip-missing-row behavior |
| `POST /reporting/verify` | `POST /api/reporting/verify` | Monthly check |
| `POST /reporting/verify-weekly` | `POST /api/reporting/verify-weekly` | Weekly check |
| `POST /reporting/config` | `POST /api/reporting/config` | oguryRate/reminderDay/oguryOldFormat/filePatterns |
| `POST /reporting/links` | `POST /api/reporting/links` | report_links |
| `GET /reporting/upload-files` | `GET /api/reporting/upload-files` | List files from Supabase Storage bucket |
| `GET /reporting/download` | `GET /api/reporting/download` | Zip build, same exclusion/regeneration rules |
| `GET /reporting/export-table` | `GET /api/reporting/export-table` | csv/xlsx/json |
| `DELETE /reporting/{siteId}/{dateKey}` | `DELETE /api/reporting/[site]/[dateKey]` | Delete a `report_days` row |

**Dropped, not ported**: `POST /reporting/sync` and the export/import Artisan-command mechanism (§2).

## 7. Error Handling

- Thrown errors in `verifier.ts`/`zipBuilder.ts` (missing sheet, missing header row, unknown site, no files to download) → caught in the API route, returned as `{error: message}` JSON with an appropriate status code (422/404), same semantics as the source system's flash-based errors.
- `reportProcessor.ts` preserves the source's defensive per-file/per-site try/catch — one bad file or one failing site does not abort the whole upload run.
- Request validation via Zod schemas at the top of each API route (file required, `dateKey` required, `reminderDay` 0–6, `oguryRate` numeric, etc.) — same field rules as the source system's Laravel validation.
- Client-side error display via toast (e.g. `sonner`, compatible with shadcn/ui) instead of Inertia flash messages.

## 8. Testing

Port the existing Pest test suite structure to Vitest, reusing the same fixture files from the source repo (`tests/Fixtures/Reporting/*`, `expected.json`):

- **`extractorParity.test.ts`** — reuse the golden-file `expected.json` (originally generated from the standalone Express app), assert TS extractors match within `1e-6` tolerance for every partner/site.
- **`storePipeline.test.ts`** — round-trip test (build store → save/load via Supabase → assert). Preserves the known regression-guard assertion (Showheroes revenue for `f1maximaal`/`2026-06-01` = `20.27`) and the carry-forward invariant test (re-merging with a partner absent from the run leaves its prior stored value unchanged).
- **`saveAdheseBatch.test.ts`** — batch update/recompute-impressions-sold/skip-missing-row assertions.
- **`reportingApi.test.ts`** — HTTP-level equivalent of `ReportingControllerTest`: upload→process persists correct values, download returns a zip, verify returns expected checks, config persists, unauthenticated requests are rejected.

## 9. Migration of Historical Data

One-time script (not part of the ongoing app) to export all rows from the source Laravel app's `report_days`/`report_settings` MySQL tables and insert them into the new Supabase Postgres tables, preserving exact `site`/`date`/JSON-blob values. Run once before cutover; not a recurring sync (see §2).

## 10. Open Items / Risks

- Exact column list for `report_days`/`report_settings` was verified directly against the source migration file (`database/migrations/2026_06_25_000000_create_report_tables.php`) — confirmed accurate.
- `OguryConverter`'s legacy-format xlsx output includes cell styling (dark-red header fill, specific column formats) — SheetJS's base package doesn't do cell styling; may need `exceljs` instead of `xlsx` specifically for this one module's write path. To be resolved during implementation planning.
- Supabase Storage signed-URL flow for zip downloads needs a concrete implementation approach (stream vs redirect) — to be resolved during implementation planning.
