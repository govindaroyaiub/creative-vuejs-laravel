# Planet Nine Partner Reporting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Port the Reporting module from `creative-vuejs-laravel` (Laravel/Inertia/Vue) into a standalone Next.js + Supabase app deployed on Vercel, preserving schema/business rules 1:1.

**Architecture:** Next.js 14 App Router (TypeScript) with API route handlers backed by thin, independently-testable handler functions in `lib/reporting/api/*`; Supabase for Postgres + Auth + Storage; processing pipeline ported module-for-module from the source PHP services into `lib/reporting/*`.

**Tech Stack:** Next.js 14, TypeScript, Tailwind + shadcn/ui, Supabase (`@supabase/supabase-js`, `@supabase/ssr`), `xlsx` (SheetJS) for spreadsheet read, `exceljs` for styled xlsx write (Ogury legacy format), `archiver` for zip building, Vitest for tests, Zod for request validation.

**Source spec:** `docs/superpowers/specs/2026-07-15-planet-nine-partner-reporting-design.md`
**Source of truth for ported logic:** `app/Services/Reporting/*.php`, `app/Models/ReportDay.php`, `app/Models/ReportSetting.php`, `app/Http/Controllers/ReportingController.php` in this repo.
**New project root:** `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting`

## Global Constraints

- Internal team only, flat access — no roles/permission tiers anywhere in this app.
- Auth: Supabase email+password only. No self-signup route — accounts created manually via Supabase dashboard.
- File uploads processed synchronously (small/infrequent files, per spec §2) — no queue/background job infra.
- The "current month + trailing 7 days" stale-data filter in `reportProcessor` is a hard business rule — never bypass it except in the historical-migration script, which writes to `reportStore` directly.
- `StoreMerger`'s carry-forward invariant is load-bearing: a partner absent from an upload run must NOT zero out its previously stored value.
- Ogury revenue-share rate multiplier is applied inside the Ogury extractor, not later in the pipeline.
- Adhese impressions are manual-entry only (via save-adhese/save-adhese-batch) — never derived from or overwritten by any file upload.
- No `sync`/export/import Artisan-command equivalents — single shared Supabase DB is the only source of truth (spec §2, §6).
- Key/column ordering in `SITES`, `StoreMerger`'s revenue/impressions objects, and `TableExporter`/`Verifier`'s `PARTNERS` arrays must be preserved exactly as in the source (affects CSV/XLSX column order).
- Regression-guard numeric assertions from the source test suite (Showheroes revenue for `f1maximaal`/`2026-06-01` = `20.27`) must be preserved as literal test assertions.

---

## Task 1: Scaffold Next.js Project

**Files:**
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\package.json`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tsconfig.json`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\next.config.ts`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tailwind.config.ts`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\postcss.config.js`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\vitest.config.ts`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\app\layout.tsx`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\app\globals.css`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\.env.example`
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\.gitignore`

**Interfaces:**
- Produces: a runnable Next.js app (`npm run dev` serves on :3000), `npm test` runs Vitest, `@/` path alias resolves to project root.

- [ ] **Step 1: Scaffold via create-next-app**

```bash
cd "C:\Users\P0\Desktop\Projects"
npx create-next-app@latest planet-nine-partner-reporting --typescript --tailwind --eslint --app --src-dir=false --import-alias "@/*" --use-npm
cd planet-nine-partner-reporting
git init
```

- [ ] **Step 2: Install reporting-specific dependencies**

```bash
npm install @supabase/supabase-js @supabase/ssr xlsx exceljs archiver zod dayjs
npm install -D vitest @vitejs/plugin-react vite-tsconfig-paths @types/archiver
```

- [ ] **Step 3: Add shadcn/ui**

```bash
npx shadcn@latest init -d
```

Accept defaults (matches source app's `components.json`: style "default", neutral base color, CSS variables, lucide icons).

- [ ] **Step 4: Create `vitest.config.ts`**

```typescript
import { defineConfig } from 'vitest/config'
import tsconfigPaths from 'vite-tsconfig-paths'

export default defineConfig({
  plugins: [tsconfigPaths()],
  test: {
    environment: 'node',
    globals: true,
  },
})
```

- [ ] **Step 5: Add test script to `package.json`**

Edit the `"scripts"` block to include:

```json
"scripts": {
  "dev": "next dev",
  "build": "next build",
  "start": "next start",
  "lint": "next lint",
  "test": "vitest run"
}
```

- [ ] **Step 6: Create `.env.example`**

```
NEXT_PUBLIC_SUPABASE_URL=
NEXT_PUBLIC_SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=
```

- [ ] **Step 7: Verify the scaffold builds and tests run**

Run: `npm run build`
Expected: build succeeds with no errors.

Run: `npm test`
Expected: `No test files found` (passes trivially — no tests written yet).

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "chore: scaffold Next.js project with Tailwind, shadcn/ui, Vitest"
```

---

## Task 2: Copy Test Fixtures From Source Repo

**Files:**
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tests\fixtures\reporting\` (directory, populated from source)

**Interfaces:**
- Produces: fixture files at `tests/fixtures/reporting/uploads/*` and `tests/fixtures/reporting/expected.json`, used by every extractor/parity test in later tasks.

- [ ] **Step 1: Copy fixtures**

```bash
mkdir -p "C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tests\fixtures\reporting"
cp -r "C:\Users\P0\Desktop\Projects\creative-vuejs-laravel\tests\Fixtures\Reporting\." "C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tests\fixtures\reporting\"
```

- [ ] **Step 2: Verify the golden file and upload fixtures are present**

```bash
ls "C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\tests\fixtures\reporting"
```

Expected: `expected.json` and an `uploads` directory (or equivalent) containing partner fixture files (Adform/GAM/Ogury/SeedTag/ShowHeroes/Teads/Adhese sample exports) are listed.

- [ ] **Step 3: Commit**

```bash
git add tests/fixtures
git commit -m "test: copy reporting fixtures from source repo"
```

---

## Task 3: Supabase Schema, RLS, and Storage Buckets

**Files:**
- Create: `C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting\supabase\migrations\0001_report_tables.sql`

**Interfaces:**
- Produces: `report_days`, `report_settings` tables in Supabase, with RLS policies allowing any authenticated user full CRUD, plus `raw-uploads` and `exports` private storage buckets. Later tasks (`reportStore.ts`, `zipBuilder.ts`) depend on these table/bucket names existing.

- [ ] **Step 1: Write the migration SQL**

```sql
-- 0001_report_tables.sql

create table report_days (
  id bigint generated always as identity primary key,
  site text not null,
  date date not null,
  revenue jsonb,
  impressions jsonb,
  total_ad_requests bigint not null default 0,
  analytics jsonb,
  impressions_sold bigint not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (site, date)
);
create index report_days_date_idx on report_days (date);

create table report_settings (
  id bigint generated always as identity primary key,
  key text not null unique,
  value jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table report_days enable row level security;
alter table report_settings enable row level security;

create policy "authenticated select report_days" on report_days for select using (auth.role() = 'authenticated');
create policy "authenticated insert report_days" on report_days for insert with check (auth.role() = 'authenticated');
create policy "authenticated update report_days" on report_days for update using (auth.role() = 'authenticated');
create policy "authenticated delete report_days" on report_days for delete using (auth.role() = 'authenticated');

create policy "authenticated select report_settings" on report_settings for select using (auth.role() = 'authenticated');
create policy "authenticated insert report_settings" on report_settings for insert with check (auth.role() = 'authenticated');
create policy "authenticated update report_settings" on report_settings for update using (auth.role() = 'authenticated');
create policy "authenticated delete report_settings" on report_settings for delete using (auth.role() = 'authenticated');

insert into storage.buckets (id, name, public) values ('raw-uploads', 'raw-uploads', false) on conflict (id) do nothing;
insert into storage.buckets (id, name, public) values ('exports', 'exports', false) on conflict (id) do nothing;

create policy "authenticated read raw-uploads" on storage.objects for select using (bucket_id = 'raw-uploads' and auth.role() = 'authenticated');
create policy "authenticated write raw-uploads" on storage.objects for insert with check (bucket_id = 'raw-uploads' and auth.role() = 'authenticated');
create policy "authenticated update raw-uploads" on storage.objects for update using (bucket_id = 'raw-uploads' and auth.role() = 'authenticated');
create policy "authenticated delete raw-uploads" on storage.objects for delete using (bucket_id = 'raw-uploads' and auth.role() = 'authenticated');

create policy "authenticated read exports" on storage.objects for select using (bucket_id = 'exports' and auth.role() = 'authenticated');
create policy "authenticated write exports" on storage.objects for insert with check (bucket_id = 'exports' and auth.role() = 'authenticated');
create policy "authenticated update exports" on storage.objects for update using (bucket_id = 'exports' and auth.role() = 'authenticated');
create policy "authenticated delete exports" on storage.objects for delete using (bucket_id = 'exports' and auth.role() = 'authenticated');
```

- [ ] **Step 2: Apply the migration to your Supabase project**

Run: `supabase db push` (if using Supabase CLI linked to your project), or paste the SQL into the Supabase Dashboard's SQL Editor and execute.

Expected: no errors; `report_days`/`report_settings` tables and `raw-uploads`/`exports` buckets appear in the Supabase dashboard.

- [ ] **Step 3: Manually create one admin user**

In Supabase Dashboard → Authentication → Users → Add user, create the first internal team account (email + password) so login can be tested in Task 5.

- [ ] **Step 4: Commit**

```bash
git add supabase/migrations
git commit -m "feat: add Supabase schema, RLS policies, and storage buckets"
```

---

## Task 4: Supabase Client Helpers

**Files:**
- Create: `lib/supabase/client.ts`
- Create: `lib/supabase/server.ts`
- Create: `lib/supabase/admin.ts`
- Test: `tests/unit/supabase/clients.test.ts`

**Interfaces:**
- Produces: `createBrowserClient()`, `createServerClient()` (async, cookie-aware, for use in Server Components/Route Handlers with the user's session), and `createAdminClient()` (service-role key, for privileged writes in API routes). All later tasks that touch Supabase import one of these three.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/supabase/clients.test.ts
import { describe, it, expect } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'

describe('createAdminClient', () => {
  it('throws if SUPABASE_SERVICE_ROLE_KEY is missing', () => {
    const original = process.env.SUPABASE_SERVICE_ROLE_KEY
    delete process.env.SUPABASE_SERVICE_ROLE_KEY
    expect(() => createAdminClient()).toThrow()
    process.env.SUPABASE_SERVICE_ROLE_KEY = original
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- clients.test.ts`
Expected: FAIL — `Cannot find module '@/lib/supabase/admin'`

- [ ] **Step 3: Write `lib/supabase/admin.ts`**

```typescript
import { createClient } from '@supabase/supabase-js'

export function createAdminClient() {
  const url = process.env.NEXT_PUBLIC_SUPABASE_URL
  const key = process.env.SUPABASE_SERVICE_ROLE_KEY
  if (!url) throw new Error('NEXT_PUBLIC_SUPABASE_URL is not set')
  if (!key) throw new Error('SUPABASE_SERVICE_ROLE_KEY is not set')
  return createClient(url, key, { auth: { persistSession: false } })
}
```

- [ ] **Step 4: Write `lib/supabase/client.ts`** (browser client, for Client Components)

```typescript
import { createBrowserClient } from '@supabase/ssr'

export function createClient() {
  return createBrowserClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!
  )
}
```

- [ ] **Step 5: Write `lib/supabase/server.ts`** (session-aware server client, for Server Components and Route Handlers)

```typescript
import { createServerClient } from '@supabase/ssr'
import { cookies } from 'next/headers'

export async function createClient() {
  const cookieStore = await cookies()
  return createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookies: {
        getAll() {
          return cookieStore.getAll()
        },
        setAll(cookiesToSet) {
          try {
            cookiesToSet.forEach(({ name, value, options }) =>
              cookieStore.set(name, value, options)
            )
          } catch {
            // called from a Server Component with no response to write to — safe to ignore,
            // middleware.ts (Task 5) refreshes the session on every request instead.
          }
        },
      },
    }
  )
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `npm test -- clients.test.ts`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add lib/supabase
git commit -m "feat: add Supabase browser/server/admin client helpers"
```

---

## Task 5: Auth — Login Page and Session Middleware

**Files:**
- Create: `middleware.ts`
- Create: `app/(auth)/login/page.tsx`
- Create: `app/(auth)/login/actions.ts`
- Test: `tests/unit/auth/actions.test.ts`

**Interfaces:**
- Consumes: `createClient` from `lib/supabase/server.ts` (Task 4).
- Produces: unauthenticated requests to any `(dashboard)` or `/api/reporting/*` route redirect to `/login`; `login(formData)` server action signs in via Supabase Auth and redirects to `/reporting` on success, returns `{ error: string }` on failure.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/auth/actions.test.ts
import { describe, it, expect, vi } from 'vitest'

vi.mock('@/lib/supabase/server', () => ({
  createClient: vi.fn(async () => ({
    auth: {
      signInWithPassword: vi.fn(async ({ email }: { email: string }) => {
        if (email === 'bad@example.com') {
          return { error: { message: 'Invalid login credentials' } }
        }
        return { error: null }
      }),
    },
  })),
}))

import { login } from '@/app/(auth)/login/actions'

describe('login', () => {
  it('returns an error object for invalid credentials', async () => {
    const formData = new FormData()
    formData.set('email', 'bad@example.com')
    formData.set('password', 'wrong')
    const result = await login(formData)
    expect(result).toEqual({ error: 'Invalid login credentials' })
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- actions.test.ts`
Expected: FAIL — `Cannot find module '@/app/(auth)/login/actions'`

- [ ] **Step 3: Write `app/(auth)/login/actions.ts`**

```typescript
'use server'

import { createClient } from '@/lib/supabase/server'
import { redirect } from 'next/navigation'

export async function login(formData: FormData): Promise<{ error: string } | void> {
  const email = String(formData.get('email') ?? '')
  const password = String(formData.get('password') ?? '')
  const supabase = await createClient()
  const { error } = await supabase.auth.signInWithPassword({ email, password })
  if (error) {
    return { error: error.message }
  }
  redirect('/reporting')
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- actions.test.ts`
Expected: PASS

- [ ] **Step 5: Write `app/(auth)/login/page.tsx`** (minimal form, no signup link — accounts are admin-created per Global Constraints)

```tsx
import { login } from './actions'

export default function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ error?: string }>
}) {
  return (
    <main className="mx-auto mt-24 max-w-sm space-y-4">
      <h1 className="text-xl font-semibold">Planet Nine Partner Reporting</h1>
      <form action={login} className="space-y-3">
        <input
          name="email"
          type="email"
          required
          placeholder="Email"
          className="w-full rounded border px-3 py-2"
        />
        <input
          name="password"
          type="password"
          required
          placeholder="Password"
          className="w-full rounded border px-3 py-2"
        />
        <button type="submit" className="w-full rounded bg-black px-3 py-2 text-white">
          Sign in
        </button>
      </form>
    </main>
  )
}
```

- [ ] **Step 6: Write `middleware.ts`**

```typescript
import { createServerClient } from '@supabase/ssr'
import { NextResponse, type NextRequest } from 'next/server'

export async function middleware(request: NextRequest) {
  let response = NextResponse.next({ request })

  const supabase = createServerClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      cookies: {
        getAll() {
          return request.cookies.getAll()
        },
        setAll(cookiesToSet) {
          cookiesToSet.forEach(({ name, value }) => request.cookies.set(name, value))
          response = NextResponse.next({ request })
          cookiesToSet.forEach(({ name, value, options }) =>
            response.cookies.set(name, value, options)
          )
        },
      },
    }
  )

  const {
    data: { user },
  } = await supabase.auth.getUser()

  const isAuthRoute = request.nextUrl.pathname.startsWith('/login')
  if (!user && !isAuthRoute) {
    const url = request.nextUrl.clone()
    url.pathname = '/login'
    return NextResponse.redirect(url)
  }

  return response
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}
```

- [ ] **Step 7: Manually verify the redirect**

Run: `npm run dev`, visit `http://localhost:3000/reporting` in a private browser window (no session).
Expected: redirected to `/login`. Sign in with the admin user created in Task 3 Step 3.
Expected: redirected to `/reporting` (404 is fine at this point — the page doesn't exist until Task 27 — confirms the redirect chain works).

- [ ] **Step 8: Commit**

```bash
git add middleware.ts "app/(auth)"
git commit -m "feat: add Supabase Auth login page and session middleware"
```

---

## Task 6: Reporting Constants

**Files:**
- Create: `lib/reporting/constants.ts`
- Test: `tests/unit/reporting/constants.test.ts`

**Interfaces:**
- Produces: `SITES` (object keyed by `SiteId`), `type SiteId`, `RENAME_MAP`, `ADHESE_MARKET`, `MONTHS`, `DEFAULT_FILE_PATTERNS` — every later `lib/reporting/*` module imports from here, never redefines these.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/constants.test.ts
import { describe, it, expect } from 'vitest'
import { SITES, RENAME_MAP, ADHESE_MARKET, MONTHS, DEFAULT_FILE_PATTERNS } from '@/lib/reporting/constants'

describe('reporting constants', () => {
  it('has exactly 4 sites in source order', () => {
    expect(Object.keys(SITES)).toEqual(['f1maximaal', 'topgear', 'horses', 'festileaks'])
  })

  it('matches f1maximaal field values exactly', () => {
    expect(SITES.f1maximaal).toEqual({
      name: 'F1Maximaal.nl',
      adformPrefix: 'F1M_',
      gamPrefix: 'VM_F1Maximaal',
      domain: 'f1maximaal.nl',
      oguryAsset: 'f1maximaal.nl',
    })
  })

  it('has no ADHESE_MARKET entry for horses', () => {
    expect(ADHESE_MARKET.horses).toBeUndefined()
  })

  it('has 12 months abbreviations', () => {
    expect(MONTHS).toHaveLength(12)
    expect(MONTHS[0]).toBe('Jan')
  })

  it('orders preferreddeals before gam_f1m in DEFAULT_FILE_PATTERNS iteration (documents the detectFileType precedence dependency)', () => {
    const keys = Object.keys(DEFAULT_FILE_PATTERNS)
    expect(keys.indexOf('preferreddeals')).toBeLessThan(keys.indexOf('gam_f1m'))
  })

  it('has the RENAME_MAP canonical names', () => {
    expect(RENAME_MAP).toEqual({
      adform: 'Adform', gam: 'GAM', ogury: 'Ogury',
      seedtag: 'SeedTag', showheroes: 'Showheroes', teads: 'Teads',
    })
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- constants.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/constants'`

- [ ] **Step 3: Write `lib/reporting/constants.ts`**

```typescript
export const SITES = {
  f1maximaal: { name: 'F1Maximaal.nl', adformPrefix: 'F1M_', gamPrefix: 'VM_F1Maximaal', domain: 'f1maximaal.nl', oguryAsset: 'f1maximaal.nl' },
  topgear: { name: 'TopGear.nl', adformPrefix: 'TG_', gamPrefix: 'VDS_Topgear', domain: 'topgear.nl', oguryAsset: 'topgear.nl' },
  horses: { name: 'Horses.nl', adformPrefix: 'OHO_', gamPrefix: 'EHM_Eisma', domain: 'horses.nl', oguryAsset: 'horses.nl' },
  festileaks: { name: 'Festileaks.com', adformPrefix: 'FL_', gamPrefix: 'FL_Festileaks', domain: 'festileaks.com', oguryAsset: 'festileaks' },
} as const

export type SiteId = keyof typeof SITES

export const RENAME_MAP: Record<string, string> = {
  adform: 'Adform',
  gam: 'GAM',
  ogury: 'Ogury',
  seedtag: 'SeedTag',
  showheroes: 'Showheroes',
  teads: 'Teads',
}

export const ADHESE_MARKET: Partial<Record<SiteId, string>> = {
  f1maximaal: 'DALE-igmn',
  topgear: 'DALE-igmn',
  festileaks: 'DALE-igmn',
}

export const MONTHS = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
] as const

// Order matters: 'preferreddeals' must iterate before 'gam_f1m' is even relevant to
// detectFileType's precedence (see lib/reporting/helpers.ts) — gam_f1m filenames also
// contain "f1max", so preferreddeals must be checked first there. Key order here is
// documentation only; detectFileType hardcodes its own check order independent of this.
export const DEFAULT_FILE_PATTERNS: Record<string, string> = {
  teads: 'report_finance',
  ogury: 'ogury, export-ad-units',
  gam: 'copy of general data download',
  seedtag: 'revenue-export',
  adform: 'tg 2, tg_2',
  showheroes: 'topgear-',
  analytics: 'pages_and_screens, content_group',
  adhese: 'adhese',
  outbrain: 'current-view, all publishers',
  preferreddeals: 'preferred deal',
  gam_f1m: 'copy of f1max',
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- constants.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/constants.ts tests/unit/reporting/constants.test.ts
git commit -m "feat: port reporting constants (SITES, RENAME_MAP, ADHESE_MARKET, file patterns)"
```

---

## Task 7: Reporting Core Helpers

**Files:**
- Create: `lib/reporting/helpers.ts`
- Test: `tests/unit/reporting/helpers.test.ts`

**Interfaces:**
- Consumes: `DEFAULT_FILE_PATTERNS` from `lib/reporting/constants.ts` (Task 6).
- Produces: `parseDate(val: unknown): Date | null`, `dateKey(d: Date): string`, `fmtAdheseDate(d: Date): string`, `monthKey(key: string): string`, `stripNum(s: unknown): number`, `pick(row: Record<string, unknown>, ...candidates: string[]): unknown`, `detectFileType(filename: string, patterns?: Record<string, string | null | undefined>): string`. Every extractor in Tasks 9–12 and every other reporting module imports from here.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/helpers.test.ts
import { describe, it, expect } from 'vitest'
import { parseDate, dateKey, fmtAdheseDate, monthKey, stripNum, pick, detectFileType } from '@/lib/reporting/helpers'

describe('parseDate', () => {
  it('parses "Month D, YYYY"', () => {
    expect(dateKey(parseDate('June 1, 2026')!)).toBe('2026-06-01')
  })
  it('parses "D Month YYYY"', () => {
    expect(dateKey(parseDate('1 June 2026')!)).toBe('2026-06-01')
  })
  it('parses "D-Mon-YY"', () => {
    expect(dateKey(parseDate('1-Jun-26')!)).toBe('2026-06-01')
  })
  it('parses ISO "YYYY-MM-DD"', () => {
    expect(dateKey(parseDate('2026-06-01')!)).toBe('2026-06-01')
  })
  it('parses "MM/DD/YYYY"', () => {
    expect(dateKey(parseDate('06/01/2026')!)).toBe('2026-06-01')
  })
  it('parses "DD-MM-YYYY"', () => {
    expect(dateKey(parseDate('01-06-2026')!)).toBe('2026-06-01')
  })
  it('parses "YYYYMMDD"', () => {
    expect(dateKey(parseDate('20260601')!)).toBe('2026-06-01')
  })
  it('parses a bare Excel serial number', () => {
    // Excel serial 46174 = 2026-06-01
    expect(dateKey(parseDate('46174')!)).toBe('2026-06-01')
  })
  it('returns null for garbage input', () => {
    expect(parseDate('not a date')).toBeNull()
    expect(parseDate('')).toBeNull()
    expect(parseDate(null)).toBeNull()
  })
})

describe('fmtAdheseDate', () => {
  it('formats as D-Mon-YY', () => {
    expect(fmtAdheseDate(parseDate('2026-06-01')!)).toBe('1-Jun-26')
  })
})

describe('monthKey', () => {
  it('takes the first 7 chars', () => {
    expect(monthKey('2026-06-01')).toBe('2026-06')
  })
})

describe('stripNum', () => {
  it('strips currency symbols, commas, percent, whitespace', () => {
    expect(stripNum('€1,234.56')).toBeCloseTo(1234.56)
    expect(stripNum('$100')).toBe(100)
    expect(stripNum('50%')).toBe(50)
    expect(stripNum('')).toBe(0)
    expect(stripNum(null)).toBe(0)
    expect(stripNum('abc')).toBe(0)
  })
})

describe('pick', () => {
  it('is case/whitespace/qualifier-insensitive', () => {
    const row = { 'Premium Revenue (EUR)': 42 }
    expect(pick(row, 'Premium Revenue')).toBe(42)
  })
  it('returns null when nothing matches', () => {
    expect(pick({ a: 1 }, 'b', 'c')).toBeNull()
  })
})

describe('detectFileType', () => {
  it('detects adhese first even if other needles would also match', () => {
    expect(detectFileType('Adhese f1.xlsx')).toBe('adhese')
  })
  it('checks preferreddeals before gam_f1m (both can match "f1max"-like names)', () => {
    expect(detectFileType('preferred deal report.xlsx')).toBe('preferreddeals')
    expect(detectFileType('Copy of F1Max general.xlsx')).toBe('gam_f1m')
  })
  it('matches the compound impressions_f1 rule', () => {
    expect(detectFileType('impressions-master-f1.xlsx')).toBe('impressions_f1')
  })
  it('applies user-overridden patterns over defaults', () => {
    expect(detectFileType('custom-teads-name.csv', { teads: 'custom-teads-name' })).toBe('teads')
  })
  it('falls back to unknown', () => {
    expect(detectFileType('random-file.xlsx')).toBe('unknown')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- helpers.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/helpers'`

- [ ] **Step 3: Write `lib/reporting/helpers.ts`**

```typescript
import { MONTHS, DEFAULT_FILE_PATTERNS } from './constants'

function monthIdx(tok: string): number {
  const t = tok.slice(0, 3).toLowerCase()
  return MONTHS.findIndex((m) => m.toLowerCase() === t)
}

function make(y: number, mZero: number, d: number): Date | null {
  if (mZero < 0 || mZero > 11) return null
  return new Date(Date.UTC(y, mZero, d))
}

function excelSerialToDate(serial: number): Date | null {
  if (!Number.isFinite(serial)) return null
  const utcDays = Math.floor(serial - 25569) // days between 1899-12-30 and 1970-01-01
  const d = new Date(utcDays * 86400 * 1000)
  return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate()))
}

export function parseDate(val: unknown): Date | null {
  if (val === null || val === undefined || val === '') return null
  const s = String(val).trim()
  if (s === '') return null

  let m: RegExpMatchArray | null

  if ((m = s.match(/^([A-Za-z]+)\s+(\d+),?\s+(\d{4})$/))) {
    return make(Number(m[3]), monthIdx(m[1]), Number(m[2]))
  }
  if ((m = s.match(/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/))) {
    return make(Number(m[3]), monthIdx(m[2]), Number(m[1]))
  }
  if ((m = s.match(/^(\d+)-([A-Za-z]+)-(\d{2,4})$/))) {
    const yr = m[3].length === 2 ? 2000 + Number(m[3]) : Number(m[3])
    return make(yr, monthIdx(m[2]), Number(m[1]))
  }
  if ((m = s.match(/^(\d{4})-(\d{2})-(\d{2})/))) {
    return make(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  }
  if ((m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/))) {
    return make(Number(m[3]), Number(m[1]) - 1, Number(m[2]))
  }
  if ((m = s.match(/^(\d{2})-(\d{2})-(\d{4})$/))) {
    return make(Number(m[3]), Number(m[2]) - 1, Number(m[1]))
  }
  if ((m = s.match(/^(\d{4})(\d{2})(\d{2})$/))) {
    return make(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  }
  if (/^\d+(\.\d+)?$/.test(s)) {
    return excelSerialToDate(Number(s))
  }
  return null
}

export function dateKey(d: Date): string {
  const y = d.getUTCFullYear()
  const mo = String(d.getUTCMonth() + 1).padStart(2, '0')
  const da = String(d.getUTCDate()).padStart(2, '0')
  return `${y}-${mo}-${da}`
}

export function fmtAdheseDate(d: Date): string {
  const day = d.getUTCDate()
  const month = MONTHS[d.getUTCMonth()]
  const year = String(d.getUTCFullYear()).slice(2)
  return `${day}-${month}-${year}`
}

export function monthKey(key: string): string {
  return key.slice(0, 7)
}

export function stripNum(s: unknown): number {
  if (s === null || s === undefined || s === '') return 0
  const cleaned = String(s).replace(/[€$£,\s%]/gu, '')
  if (cleaned === '' || Number.isNaN(Number(cleaned))) return 0
  return Number(cleaned)
}

export function pick(row: Record<string, unknown>, ...candidates: string[]): unknown {
  const norm = (k: string) =>
    k.replace(/\s*\([^)]*\)\s*$/, '').toLowerCase().replace(/\s+/g, ' ').trim()
  const want = candidates.map(norm)
  for (const key of Object.keys(row)) {
    if (want.includes(norm(key))) return row[key]
  }
  return null
}

export function detectFileType(
  filename: string,
  patterns: Record<string, string | null | undefined> = {}
): string {
  const name = filename.toLowerCase()
  const merged: Record<string, string> = { ...DEFAULT_FILE_PATTERNS }
  for (const [k, v] of Object.entries(patterns)) {
    if (typeof v === 'string' && v.trim() !== '') merged[k] = v
  }
  const matches = (type: string): boolean => {
    const needles = (merged[type] ?? '').split(',')
    for (let needle of needles) {
      needle = needle.trim().toLowerCase()
      if (needle !== '' && name.includes(needle)) return true
    }
    return false
  }

  if (matches('adhese')) return 'adhese'
  if (matches('analytics')) return 'analytics'
  if (matches('adform')) return 'adform'
  if (matches('gam')) return 'gam'
  if (matches('ogury')) return 'ogury'
  if (matches('seedtag')) return 'seedtag'
  if (matches('showheroes')) return 'showheroes'
  if (matches('teads')) return 'teads'
  if (matches('outbrain')) return 'outbrain'
  if (name.startsWith('impressions') && name.includes('f1')) return 'impressions_f1'
  if (matches('preferreddeals')) return 'preferreddeals'
  if (matches('gam_f1m')) return 'gam_f1m'
  if (name.startsWith('planetnine-report-')) return 'planetnine'
  if (name.startsWith('tg-revenue-report-')) return 'report_topgear'
  if (name.startsWith('horses-revenue-report-')) return 'report_horses'
  if (name.startsWith('festileaks-revenue-report-')) return 'report_festileaks'
  return 'unknown'
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- helpers.test.ts`
Expected: PASS — all cases green, including the Excel-serial case (verify 46174 is in fact 2026-06-01 in your test runner's output before asserting; if off by one, PhpSpreadsheet's `excelToDateTimeObject` and the 25569-day epoch trick are equivalent for the 1900 date system for all dates after the Feb-1900 leap bug, so an off-by-one indicates a transcription error in the test, not the implementation).

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/helpers.ts tests/unit/reporting/helpers.test.ts
git commit -m "feat: port Reporting.php pure helpers (parseDate, stripNum, pick, detectFileType)"
```

---

## Task 8: Spreadsheet Reader

**Files:**
- Create: `lib/reporting/spreadsheetReader.ts`
- Test: `tests/unit/reporting/spreadsheetReader.test.ts`

**Interfaces:**
- Produces: `rows(path, sheet?): Record<string, unknown>[]`, `matrix(path, sheet?): unknown[][]`, `sheetNames(path): string[]`, `csvRows(contents): Record<string, unknown>[]`, and `type SheetSelector = number | string | { contains: string; fallbackIndex?: number }`. Every extractor (Tasks 9–12) and `verifier.ts` (Task 19) consume these.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/spreadsheetReader.test.ts
import { describe, it, expect } from 'vitest'
import * as XLSX from 'xlsx'
import { writeFileSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { rows, matrix, sheetNames, csvRows } from '@/lib/reporting/spreadsheetReader'

function writeTestWorkbook(sheetData: unknown[][], sheetName = 'Sheet1'): string {
  const dir = mkdtempSync(join(tmpdir(), 'ss-test-'))
  const path = join(dir, 'test.xlsx')
  const ws = XLSX.utils.aoa_to_sheet(sheetData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, sheetName)
  XLSX.writeFile(wb, path)
  return path
}

describe('rows', () => {
  it('reads header row + data rows as objects', () => {
    const path = writeTestWorkbook([
      ['Date', 'Revenue'],
      ['2026-06-01', 100],
      ['2026-06-02', 200],
    ])
    const result = rows(path)
    expect(result).toEqual([
      { Date: '2026-06-01', Revenue: 100 },
      { Date: '2026-06-02', Revenue: 200 },
    ])
  })

  it('skips leading blank rows before finding the header', () => {
    const path = writeTestWorkbook([
      [null, null],
      ['Date', 'Revenue'],
      ['2026-06-01', 100],
    ])
    expect(rows(path)).toEqual([{ Date: '2026-06-01', Revenue: 100 }])
  })
})

describe('matrix', () => {
  it('returns raw 0-indexed rows and drops empty leading columns', () => {
    const path = writeTestWorkbook([
      [null, 'A', 'B'],
      [null, 1, 2],
    ])
    expect(matrix(path)).toEqual([
      ['A', 'B'],
      [1, 2],
    ])
  })
})

describe('sheetNames', () => {
  it('lists sheet names in file order', () => {
    const path = writeTestWorkbook([['x']], 'Custom Sheet')
    expect(sheetNames(path)).toEqual(['Custom Sheet'])
  })
})

describe('csvRows', () => {
  it('skips leading # comment lines and blank lines before the header', () => {
    const csv = '# comment 1\n# comment 2\n\nDate,Views\n2026-06-01,10\n'
    expect(csvRows(csv)).toEqual([{ Date: '2026-06-01', Views: '10' }])
  })

  it('strips surrounding quotes from values', () => {
    const csv = 'Date,Name\n2026-06-01,"Smith, John"\n'
    // Note: naive split(',') on a quoted field containing a comma is a known
    // limitation carried over intentionally — none of the actual partner CSVs
    // (Analytics GA4 export) quote-embed commas in practice; documented here
    // so a future maintainer doesn't "fix" this without checking real fixtures.
    expect(csvRows(csv)[0].Date).toBe('2026-06-01')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- spreadsheetReader.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/spreadsheetReader'`

- [ ] **Step 3: Write `lib/reporting/spreadsheetReader.ts`**

```typescript
import * as XLSX from 'xlsx'
import { readFileSync } from 'node:fs'

export type SheetSelector = number | string | { contains: string; fallbackIndex?: number }

function resolveSheetName(wb: XLSX.WorkBook, sheet: SheetSelector): string | null {
  if (typeof sheet === 'number') return wb.SheetNames[sheet] ?? null
  if (typeof sheet === 'string') return wb.SheetNames.includes(sheet) ? sheet : null
  const needle = sheet.contains.toLowerCase()
  const found = wb.SheetNames.find((n) => n.toLowerCase().includes(needle))
  if (found) return found
  return wb.SheetNames[sheet.fallbackIndex ?? 0] ?? null
}

function isBlankRow(r: unknown[] | undefined): boolean {
  return !r || r.every((c) => c === null || c === undefined || c === '')
}

function trimEmptyEdges(rowsIn: unknown[][]): unknown[][] {
  let start = 0
  while (start < rowsIn.length && isBlankRow(rowsIn[start])) start++
  const trimmed = rowsIn.slice(start)

  const maxCols = trimmed.reduce((m, r) => Math.max(m, r?.length ?? 0), 0)
  let colStart = 0
  outer: while (colStart < maxCols) {
    for (const r of trimmed) {
      const v = r?.[colStart]
      if (v !== null && v !== undefined && v !== '') break outer
    }
    colStart++
  }
  if (colStart === 0) return trimmed
  return trimmed.map((r) => r.slice(colStart))
}

export function sheetNames(path: string): string[] {
  const wb = XLSX.readFile(path)
  return wb.SheetNames
}

export function matrix(path: string, sheet: SheetSelector = 0): unknown[][] {
  const wb = XLSX.readFile(path, { raw: true })
  const name = resolveSheetName(wb, sheet)
  if (!name) return []
  const ws = wb.Sheets[name]
  const raw = XLSX.utils.sheet_to_json<unknown[]>(ws, { header: 1, raw: true, defval: null })
  return trimEmptyEdges(raw)
}

export function rows(path: string, sheet: SheetSelector = 0): Record<string, unknown>[] {
  const m = matrix(path, sheet)
  if (m.length === 0) return []
  let headerIdx = 0
  while (headerIdx < m.length && isBlankRow(m[headerIdx])) headerIdx++
  const headerRow = m[headerIdx] ?? []
  const headers = headerRow.map((h) => (h === null || h === undefined ? '' : String(h).trim()))

  const out: Record<string, unknown>[] = []
  for (let i = headerIdx + 1; i < m.length; i++) {
    const r = m[i]
    if (isBlankRow(r)) continue
    const obj: Record<string, unknown> = {}
    headers.forEach((h, idx) => {
      if (h === '') return
      obj[h] = r[idx] ?? null
    })
    out.push(obj)
  }
  return out
}

export function csvRows(contents: string): Record<string, unknown>[] {
  const lines = contents.split(/\r\n|\n/)
  let i = 0
  while (i < lines.length && (lines[i].trim() === '' || lines[i].trim().startsWith('#'))) i++
  if (i >= lines.length) return []

  const parseLine = (line: string): string[] =>
    line.split(',').map((v) => v.trim().replace(/^"(.*)"$/, '$1'))

  const headers = parseLine(lines[i])
  const out: Record<string, unknown>[] = []
  for (let j = i + 1; j < lines.length; j++) {
    if (lines[j].trim() === '') continue
    const values = parseLine(lines[j])
    const obj: Record<string, unknown> = {}
    headers.forEach((h, idx) => {
      obj[h] = values[idx] ?? null
    })
    out.push(obj)
  }
  return out
}

export function readTextFile(path: string): string {
  return readFileSync(path, 'utf-8')
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- spreadsheetReader.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/spreadsheetReader.ts tests/unit/reporting/spreadsheetReader.test.ts
git commit -m "feat: port SpreadsheetReader (xlsx/csv row and matrix reading)"
```

---

## Task 9: Extractors Part 1 — SeedTag, Teads, ShowHeroes

**Files:**
- Create: `lib/reporting/extractors.ts`
- Test: `tests/unit/reporting/extractors.test.ts`

**Interfaces:**
- Consumes: `rows` from `lib/reporting/spreadsheetReader.ts` (Task 8); `pick`, `stripNum`, `parseDate`, `dateKey` from `lib/reporting/helpers.ts` (Task 7); `SITES`, `SiteId` from `lib/reporting/constants.ts` (Task 6).
- Produces: `extractSeedtag(path, siteId): DayMetric[]`, `extractTeads(path, siteId): DayMetric[]`, `extractShowheroes(path, siteId): DayMetric[]`, plus the shared `type DayMetric = { date: string; impressions: number; revenue: number }`. Tasks 10–12 add more exports to this same file; Task 13's parity test and Task 14 (`storeMerger.ts`) consume all of them.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/extractors.test.ts
import { describe, it, expect } from 'vitest'
import * as XLSX from 'xlsx'
import { mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { extractSeedtag, extractTeads, extractShowheroes } from '@/lib/reporting/extractors'

function writeWorkbook(sheetData: unknown[][]): string {
  const dir = mkdtempSync(join(tmpdir(), 'extractor-test-'))
  const path = join(dir, 'test.xlsx')
  const ws = XLSX.utils.aoa_to_sheet(sheetData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1')
  XLSX.writeFile(wb, path)
  return path
}

describe('extractSeedtag', () => {
  it('filters by domain and aggregates impressions/revenue by day', () => {
    const path = writeWorkbook([
      ['Publisher', 'Date', 'Impressions', 'Revenue'],
      ['topgear.nl', '2026-06-01', 100, 10],
      ['topgear.nl', '2026-06-01', 50, 5],
      ['other-site.nl', '2026-06-01', 999, 999],
    ])
    const result = extractSeedtag(path, 'topgear')
    expect(result).toEqual([{ date: '2026-06-01', impressions: 150, revenue: 15 }])
  })
})

describe('extractTeads', () => {
  it('filters by "Websites & Apps" domain, sums Sold Impressions and Estimated Earnings', () => {
    const path = writeWorkbook([
      ['Websites & Apps', 'Day', 'Sold Impressions', 'Estimated Earnings in EUR'],
      ['topgear.nl', '2026-06-01', 200, 20],
      ['not-topgear.nl', '2026-06-01', 999, 999],
    ])
    const result = extractTeads(path, 'topgear')
    expect(result).toEqual([{ date: '2026-06-01', impressions: 200, revenue: 20 }])
  })
})

describe('extractShowheroes', () => {
  it('filters by Site domain, skips blank/"totals" rows, sums Ad Impressions and Premium Revenue', () => {
    const path = writeWorkbook([
      ['Site', 'Date and Time', 'Ad Impressions', 'Premium Revenue'],
      ['topgear.nl', '2026-06-01', 300, 30],
      ['topgear.nl', '', 999, 999],
      ['topgear.nl', 'totals', 999, 999],
    ])
    const result = extractShowheroes(path, 'topgear')
    expect(result).toEqual([{ date: '2026-06-01', impressions: 300, revenue: 30 }])
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- extractors.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/extractors'`

- [ ] **Step 3: Write `lib/reporting/extractors.ts`** (start of the file — Tasks 10–12 append to this same file)

```typescript
import { rows, csvRows, matrix, sheetNames } from './spreadsheetReader'
import { pick, stripNum, parseDate, dateKey } from './helpers'
import { SITES, type SiteId } from './constants'

export type DayMetric = { date: string; impressions: number; revenue: number }

function aggregateByDay(
  sourceRows: Record<string, unknown>[],
  domainKey: string[],
  domain: string,
  dateField: string[],
  impressionsField: string[],
  revenueField: string[]
): DayMetric[] {
  const byDay = new Map<string, { impressions: number; revenue: number }>()
  for (const row of sourceRows) {
    const rowDomain = String(pick(row, ...domainKey) ?? '').toLowerCase()
    if (rowDomain !== domain.toLowerCase()) continue
    const d = parseDate(pick(row, ...dateField))
    if (!d) continue
    const key = dateKey(d)
    const entry = byDay.get(key) ?? { impressions: 0, revenue: 0 }
    entry.impressions += stripNum(pick(row, ...impressionsField))
    entry.revenue += stripNum(pick(row, ...revenueField))
    byDay.set(key, entry)
  }
  return Array.from(byDay.entries()).map(([date, v]) => ({ date, ...v }))
}

export function extractSeedtag(path: string, siteId: SiteId): DayMetric[] {
  return aggregateByDay(
    rows(path),
    ['Publisher'],
    SITES[siteId].domain,
    ['Date'],
    ['Impressions'],
    ['Revenue']
  )
}

export function extractTeads(path: string, siteId: SiteId): DayMetric[] {
  return aggregateByDay(
    rows(path),
    ['Websites & Apps'],
    SITES[siteId].domain,
    ['Day'],
    ['Sold Impressions'],
    ['Estimated Earnings in EUR', 'Estimated Earnings']
  )
}

export function extractShowheroes(path: string, siteId: SiteId): DayMetric[] {
  const domain = SITES[siteId].domain.toLowerCase()
  const byDay = new Map<string, { impressions: number; revenue: number }>()
  for (const row of rows(path)) {
    const site = String(pick(row, 'Site') ?? '').toLowerCase()
    if (site !== domain) continue
    const rawDate = pick(row, 'Date and Time')
    if (rawDate === null || rawDate === undefined || String(rawDate).trim() === '') continue
    if (String(rawDate).trim().toLowerCase() === 'totals') continue
    const d = parseDate(rawDate)
    if (!d) continue
    const key = dateKey(d)
    const entry = byDay.get(key) ?? { impressions: 0, revenue: 0 }
    entry.impressions += stripNum(pick(row, 'Ad Impressions'))
    entry.revenue += stripNum(pick(row, 'Premium Revenue'))
    byDay.set(key, entry)
  }
  return Array.from(byDay.entries()).map(([date, v]) => ({ date, ...v }))
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- extractors.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/extractors.ts tests/unit/reporting/extractors.test.ts
git commit -m "feat: port SeedTag/Teads/ShowHeroes extractors"
```

---

## Task 10: Extractors Part 2 — GAM, Adform

**Files:**
- Modify: `lib/reporting/extractors.ts`
- Modify: `tests/unit/reporting/extractors.test.ts`

**Interfaces:**
- Consumes: `matrix`, `sheetNames` from `lib/reporting/spreadsheetReader.ts` (Task 8).
- Produces: adds `extractGam(path, siteId): Omit<DayMetric, 'impressions'>[]` (GAM has no impressions field) and `extractAdform(path, siteId): DayMetric[]` to `lib/reporting/extractors.ts`.

- [ ] **Step 1: Write the failing test**

```typescript
// append to tests/unit/reporting/extractors.test.ts
import { extractGam, extractAdform } from '@/lib/reporting/extractors'

describe('extractGam', () => {
  it('filters by Ad unit prefix and sums AdSense + Ad Exchange revenue, no impressions field', () => {
    const path = writeWorkbook([
      ['Ad unit (all levels)', 'Date', 'AdSense revenue', 'Ad Exchange revenue'],
      ['VDS_Topgear/home', '2026-06-01', 5, 10],
      ['VDS_Topgear/article', '2026-06-01', 3, 2],
      ['VM_F1Maximaal/home', '2026-06-01', 999, 999],
    ])
    const result = extractGam(path, 'topgear')
    expect(result).toEqual([{ date: '2026-06-01', revenue: 20 }])
  })
})

describe('extractAdform', () => {
  it('reads the "Sheet" tab, header at row index 2, filters Placement by prefix', () => {
    const path = writeWorkbook([
      ['metadata row 0'],
      ['metadata row 1'],
      ['Date', 'Placement', 'Impressions', 'Revenue (excl. available)'],
      ['2026-06-01', 'TG_Homepage', 1000, 50],
      ['2026-06-01', 'TG_Article', 500, 25],
      ['2026-06-01', 'OTHER_Homepage', 999, 999],
    ])
    const result = extractAdform(path, 'topgear')
    expect(result).toEqual([{ date: '2026-06-01', impressions: 1500, revenue: 75 }])
  })

  it('returns empty when there is no sheet literally named "Sheet"', () => {
    const dir = require('node:fs').mkdtempSync(
      require('node:path').join(require('node:os').tmpdir(), 'adform-test-')
    )
    const path = require('node:path').join(dir, 'test.xlsx')
    const XLSX = require('xlsx')
    const ws = XLSX.utils.aoa_to_sheet([['a']])
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'NotSheet')
    XLSX.writeFile(wb, path)
    expect(extractAdform(path, 'topgear')).toEqual([])
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- extractors.test.ts`
Expected: FAIL — `extractGam is not a function` / `extractAdform is not a function`

- [ ] **Step 3: Append to `lib/reporting/extractors.ts`**

```typescript
export function extractGam(path: string, siteId: SiteId): { date: string; revenue: number }[] {
  const prefix = SITES[siteId].gamPrefix.toLowerCase()
  const byDay = new Map<string, number>()
  for (const row of rows(path)) {
    const adUnit = String(pick(row, 'Ad unit (all levels)') ?? '').toLowerCase()
    if (!adUnit.startsWith(prefix)) continue
    const d = parseDate(pick(row, 'Date'))
    if (!d) continue
    const key = dateKey(d)
    const revenue =
      stripNum(pick(row, 'AdSense revenue')) + stripNum(pick(row, 'Ad Exchange revenue'))
    byDay.set(key, (byDay.get(key) ?? 0) + revenue)
  }
  return Array.from(byDay.entries()).map(([date, revenue]) => ({ date, revenue }))
}

function findColIndexCaseInsensitive(headerRow: unknown[], name: string): number {
  return headerRow.findIndex(
    (h) => typeof h === 'string' && h.trim().toLowerCase() === name.toLowerCase()
  )
}

function findRevenueColIndex(headerRow: unknown[]): number {
  return headerRow.findIndex((h) => {
    if (typeof h !== 'string') return false
    const lower = h.trim().toLowerCase()
    return lower.includes('revenue') && !lower.includes('available')
  })
}

export function extractAdform(path: string, siteId: SiteId): DayMetric[] {
  if (!sheetNames(path).includes('Sheet')) return []
  const m = matrix(path, 'Sheet')
  if (m.length < 4) return []

  const headerRow = m[2]
  const dateIdx = findColIndexCaseInsensitive(headerRow, 'Date')
  const placementIdx = findColIndexCaseInsensitive(headerRow, 'Placement')
  const impressionsIdx = findColIndexCaseInsensitive(headerRow, 'Impressions')
  const revIdx = findRevenueColIndex(headerRow)
  if (dateIdx < 0 || placementIdx < 0 || impressionsIdx < 0 || revIdx < 0) return []

  const prefix = SITES[siteId].adformPrefix
  const byDay = new Map<string, { impressions: number; revenue: number }>()
  for (let i = 3; i < m.length; i++) {
    const r = m[i]
    const placement = String(r?.[placementIdx] ?? '')
    if (!placement.includes(prefix)) continue
    const d = parseDate(r?.[dateIdx])
    if (!d) continue
    const key = dateKey(d)
    const entry = byDay.get(key) ?? { impressions: 0, revenue: 0 }
    entry.impressions += stripNum(r?.[impressionsIdx])
    entry.revenue += stripNum(r?.[revIdx])
    byDay.set(key, entry)
  }
  return Array.from(byDay.entries()).map(([date, v]) => ({ date, ...v }))
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- extractors.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/extractors.ts tests/unit/reporting/extractors.test.ts
git commit -m "feat: port GAM and Adform extractors"
```

---

## Task 11: Extractors Part 3 — Ogury, Adhese

**Files:**
- Modify: `lib/reporting/extractors.ts`
- Modify: `tests/unit/reporting/extractors.test.ts`

**Interfaces:**
- Produces: adds `extractOgury(path, siteId, rate = 0.85): DayMetric[]` and `extractAdhese(path, filename): { date: string; site: string; revenue: number }[]` to `lib/reporting/extractors.ts`. `extractAdhese`'s per-row (non-aggregated) shape is consumed by `storeMerger.ts` (Task 14), which does the per-site grouping/summing itself, matching the source `ReportProcessor`'s division of labor.

- [ ] **Step 1: Write the failing test**

```typescript
// append to tests/unit/reporting/extractors.test.ts
import { extractOgury, extractAdhese } from '@/lib/reporting/extractors'

describe('extractOgury', () => {
  it('prefers a sheet whose name contains "statistics", filters by oguryAsset, applies the rate multiplier', () => {
    const dir = require('node:fs').mkdtempSync(
      require('node:path').join(require('node:os').tmpdir(), 'ogury-test-')
    )
    const path = require('node:path').join(dir, 'test.xlsx')
    const XLSX = require('xlsx')
    const wb = XLSX.utils.book_new()
    const metaSheet = XLSX.utils.aoa_to_sheet([['irrelevant metadata']])
    XLSX.utils.book_append_sheet(wb, metaSheet, 'Report')
    const dataSheet = XLSX.utils.aoa_to_sheet([
      ['App bundle', 'UTC Date', 'Impressions', 'Revenues'],
      ['topgear.nl', '2026-06-01', 1000, 10],
      ['other.nl', '2026-06-01', 999, 999],
    ])
    XLSX.utils.book_append_sheet(wb, dataSheet, 'Statistics 1')
    XLSX.writeFile(wb, path)

    const result = extractOgury(path, 'topgear', 0.85)
    expect(result).toEqual([{ date: '2026-06-01', impressions: 1000, revenue: 8.5 }])
  })

  it('defaults rate to 0.85 when not provided', () => {
    const path = writeWorkbook([
      ['App bundle', 'UTC Date', 'Impressions', 'Revenues'],
      ['topgear.nl', '2026-06-01', 100, 10],
    ])
    const result = extractOgury(path, 'topgear')
    expect(result[0].revenue).toBeCloseTo(8.5)
  })
})

describe('extractAdhese', () => {
  it('reads a site column when present', () => {
    const path = writeWorkbook([
      ['date', 'site', 'Paid Revenue'],
      ['2026-06-01', 'topgear.nl', 42],
    ])
    const result = extractAdhese(path, 'Adhese tg.xlsx')
    expect(result).toEqual([{ date: '2026-06-01', site: 'topgear.nl', revenue: 42 }])
  })

  it('falls back to filename-derived site when the site column is blank', () => {
    const path = writeWorkbook([
      ['date', 'site', 'Paid Revenue'],
      ['2026-06-01', '', 42],
    ])
    expect(extractAdhese(path, 'Adhese tg.xlsx')[0].site).toBe('topgear.nl')
    expect(extractAdhese(path, 'Adhese fl.xlsx')[0].site).toBe('festileaks.com')
    expect(extractAdhese(path, 'Adhese f1.xlsx')[0].site).toBe('f1maximaal.nl')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- extractors.test.ts`
Expected: FAIL — `extractOgury is not a function` / `extractAdhese is not a function`

- [ ] **Step 3: Append to `lib/reporting/extractors.ts`**

```typescript
export function extractOgury(path: string, siteId: SiteId, rate = 0.85): DayMetric[] {
  const asset = SITES[siteId].oguryAsset.toLowerCase()
  const sourceRows = rows(path, { contains: 'statistics', fallbackIndex: 1 })
  const byDay = new Map<string, { impressions: number; revenue: number }>()
  for (const row of sourceRows) {
    const assetName = String(pick(row, 'App bundle', 'Asset name', 'Asset') ?? '').toLowerCase()
    if (!assetName.includes(asset)) continue
    const d = parseDate(pick(row, 'UTC Date', 'Date'))
    if (!d) continue
    const key = dateKey(d)
    const entry = byDay.get(key) ?? { impressions: 0, revenue: 0 }
    entry.impressions += stripNum(pick(row, 'Impressions'))
    entry.revenue += stripNum(pick(row, 'Revenues', 'Revenue')) * rate
    byDay.set(key, entry)
  }
  return Array.from(byDay.entries()).map(([date, v]) => ({ date, ...v }))
}

function adheseFallbackDomain(filename: string): string {
  const name = filename.toLowerCase()
  if (name.includes('adhese tg') || name.includes('adhese topgear')) return 'topgear.nl'
  if (name.includes('adhese fl') || name.includes('adhese festileaks')) return 'festileaks.com'
  return 'f1maximaal.nl'
}

export function extractAdhese(
  path: string,
  filename: string
): { date: string; site: string; revenue: number }[] {
  const fallbackSite = adheseFallbackDomain(filename)
  const out: { date: string; site: string; revenue: number }[] = []
  for (const row of rows(path)) {
    const d = parseDate(pick(row, 'date'))
    if (!d) continue
    let site = String(pick(row, 'site', 'domain', 'publisher', 'website') ?? '').trim()
    if (site === '') site = fallbackSite
    const revenue = stripNum(pick(row, 'Paid Revenue', 'Revenue', 'Paid revenue'))
    out.push({ date: dateKey(d), site: site.toLowerCase(), revenue })
  }
  return out
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- extractors.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/extractors.ts tests/unit/reporting/extractors.test.ts
git commit -m "feat: port Ogury and Adhese extractors"
```

---

## Task 12: Extractors Part 4 — F1-Only (gamF1m, Outbrain, PreferredDeals, ImpressionsF1, Analytics)

**Files:**
- Modify: `lib/reporting/extractors.ts`
- Modify: `tests/unit/reporting/extractors.test.ts`

**Interfaces:**
- Produces: adds `extractGamF1m(path): Record<string, {totalAdRequests: number; gamImpressions: number}>`, `extractOutbrain(csv): Record<string, {impressions: number; revenue: number}>`, `extractPreferredDeals(path): DayMetric[]`, `extractImpressionsF1(path): Record<string, {adhese: number}>`, `extractAnalytics(csv): {date: string; views: number; activeUsers: number; viewsPerUser: number; avgEngagement: number; eventCount: number; keyEvents: number; totalRevenue: number}[]` — all F1Maximaal-only, called only for `siteId === 'f1maximaal'` in `reportProcessor.ts` (Task 16).

- [ ] **Step 1: Write the failing test**

```typescript
// append to tests/unit/reporting/extractors.test.ts
import {
  extractGamF1m,
  extractOutbrain,
  extractPreferredDeals,
  extractImpressionsF1,
  extractAnalytics,
} from '@/lib/reporting/extractors'

describe('extractGamF1m', () => {
  it('keys by date, last row wins (not summed)', () => {
    const path = writeWorkbook([
      ['Date', 'Total ad requests', 'Ad Exchange impressions', 'AdSense impressions'],
      ['2026-06-01', 1000, 500, 200],
      ['2026-06-01', 2000, 600, 300],
    ])
    const result = extractGamF1m(path)
    expect(result['2026-06-01']).toEqual({ totalAdRequests: 2000, gamImpressions: 900 })
  })
})

describe('extractOutbrain', () => {
  it('filters Publisher containing f1maximaal (case-insensitive), keys by date (overwrite)', () => {
    const csv = 'Publisher,Day,Paid Pageviews,Revenue\nF1Maximaal.nl,2026-06-01,100,10\nOther,2026-06-01,999,999\n'
    expect(extractOutbrain(csv)).toEqual({ '2026-06-01': { impressions: 100, revenue: 10 } })
  })

  it('does not skip rows with a blank Publisher', () => {
    const csv = 'Publisher,Day,Paid Pageviews,Revenue\n,2026-06-01,50,5\n'
    expect(extractOutbrain(csv)).toEqual({ '2026-06-01': { impressions: 50, revenue: 5 } })
  })
})

describe('extractPreferredDeals', () => {
  it('aggregates Ad server impressions/revenue by day', () => {
    const path = writeWorkbook([
      ['Date', 'Ad server impressions', 'Ad server CPM and CPC revenue'],
      ['2026-06-01', 100, 10],
      ['2026-06-01', 50, 5],
    ])
    expect(extractPreferredDeals(path)).toEqual([{ date: '2026-06-01', impressions: 150, revenue: 15 }])
  })
})

describe('extractImpressionsF1', () => {
  it('keys Adhese impressions by date (overwrite, last row wins)', () => {
    const path = writeWorkbook([
      ['Date', 'Adhese'],
      ['2026-06-01', 1000],
      ['2026-06-01', 2000],
    ])
    expect(extractImpressionsF1(path)).toEqual({ '2026-06-01': { adhese: 2000 } })
  })
})

describe('extractAnalytics', () => {
  it('parses GA4 field names from a comment-prefixed CSV', () => {
    const csv =
      '# comment\nDate,Views,Active users,Views per active user,Average engagement time per active user,Event count,Key events,Total revenue\n' +
      '2026-06-01,100,50,2,30,10,5,€12.50\n'
    expect(extractAnalytics(csv)).toEqual([
      {
        date: '2026-06-01',
        views: 100,
        activeUsers: 50,
        viewsPerUser: 2,
        avgEngagement: 30,
        eventCount: 10,
        keyEvents: 5,
        totalRevenue: 12.5,
      },
    ])
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- extractors.test.ts`
Expected: FAIL — new extractor functions not defined

- [ ] **Step 3: Append to `lib/reporting/extractors.ts`**

```typescript
export function extractGamF1m(
  path: string
): Record<string, { totalAdRequests: number; gamImpressions: number }> {
  const out: Record<string, { totalAdRequests: number; gamImpressions: number }> = {}
  for (const row of rows(path)) {
    const d = parseDate(pick(row, 'Date'))
    if (!d) continue
    const key = dateKey(d)
    out[key] = {
      totalAdRequests: stripNum(pick(row, 'Total ad requests')),
      gamImpressions:
        stripNum(pick(row, 'Ad Exchange impressions')) + stripNum(pick(row, 'AdSense impressions')),
    }
  }
  return out
}

export function extractOutbrain(csv: string): Record<string, { impressions: number; revenue: number }> {
  const out: Record<string, { impressions: number; revenue: number }> = {}
  for (const row of csvRows(csv)) {
    const publisher = pick(row, 'Publisher')
    const publisherStr = publisher === null || publisher === undefined ? '' : String(publisher).toLowerCase()
    if (publisherStr !== '' && !publisherStr.includes('f1maximaal')) continue
    const d = parseDate(pick(row, 'Day'))
    if (!d) continue
    out[dateKey(d)] = {
      impressions: stripNum(pick(row, 'Paid Pageviews')),
      revenue: stripNum(pick(row, 'Revenue')),
    }
  }
  return out
}

export function extractPreferredDeals(path: string): DayMetric[] {
  const byDay = new Map<string, { impressions: number; revenue: number }>()
  for (const row of rows(path)) {
    const d = parseDate(pick(row, 'Date'))
    if (!d) continue
    const key = dateKey(d)
    const entry = byDay.get(key) ?? { impressions: 0, revenue: 0 }
    entry.impressions += stripNum(pick(row, 'Ad server impressions'))
    entry.revenue += stripNum(pick(row, 'Ad server CPM and CPC revenue', 'Total revenue'))
    byDay.set(key, entry)
  }
  return Array.from(byDay.entries()).map(([date, v]) => ({ date, ...v }))
}

export function extractImpressionsF1(path: string): Record<string, { adhese: number }> {
  const out: Record<string, { adhese: number }> = {}
  for (const row of rows(path)) {
    const d = parseDate(pick(row, 'Date'))
    if (!d) continue
    out[dateKey(d)] = { adhese: stripNum(pick(row, 'Adhese')) }
  }
  return out
}

export function extractAnalytics(csv: string): {
  date: string
  views: number
  activeUsers: number
  viewsPerUser: number
  avgEngagement: number
  eventCount: number
  keyEvents: number
  totalRevenue: number
}[] {
  const out: ReturnType<typeof extractAnalytics> = []
  for (const row of csvRows(csv)) {
    const d = parseDate(pick(row, 'Date', 'date'))
    if (!d) continue
    out.push({
      date: dateKey(d),
      views: stripNum(pick(row, 'Views')),
      activeUsers: stripNum(pick(row, 'Active users')),
      viewsPerUser: stripNum(pick(row, 'Views per active user')),
      avgEngagement: stripNum(pick(row, 'Average engagement time per active user')),
      eventCount: stripNum(pick(row, 'Event count')),
      keyEvents: stripNum(pick(row, 'Key events')),
      totalRevenue: stripNum(pick(row, 'Total revenue')),
    })
  }
  return out
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- extractors.test.ts`
Expected: PASS — all extractor tests (Tasks 9–12) green together.

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/extractors.ts tests/unit/reporting/extractors.test.ts
git commit -m "feat: port F1-only extractors (gamF1m, Outbrain, PreferredDeals, ImpressionsF1, Analytics)"
```

---

## Task 13: Extractor Parity Test Against Golden Fixture

**Files:**
- Create: `tests/unit/reporting/extractorParity.test.ts`

**Interfaces:**
- Consumes: every `extract*` function from `lib/reporting/extractors.ts` (Tasks 9–12); fixture files and `expected.json` from `tests/fixtures/reporting/` (Task 2).
- Produces: no new production code — this is the authoritative proof that the TypeScript port matches the numbers in `expected.json`, which was itself generated from the original Express/JS pipeline (per the source repo's `ExtractorParityTest` docblock). Confirms the port is numerically faithful before building anything on top of it.

- [ ] **Step 1: Inspect the golden fixture to know its exact shape**

Run: `cat "tests/fixtures/reporting/expected.json" | head -c 2000` (or open it in an editor)

Read enough of the file to identify: which partners/sites are covered, and the exact field names per partner (e.g. does it store `{impressions, revenue}` per day, or a different shape). Use this to write the assertions in Step 2 — do not guess the shape.

- [ ] **Step 2: Write the parity test**

```typescript
// tests/unit/reporting/extractorParity.test.ts
import { describe, it, expect } from 'vitest'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import {
  extractSeedtag,
  extractTeads,
  extractShowheroes,
  extractGam,
  extractAdform,
  extractOgury,
  extractAnalytics,
  extractAdhese,
} from '@/lib/reporting/extractors'
import type { SiteId } from '@/lib/reporting/constants'

const FIXTURES_DIR = join(process.cwd(), 'tests', 'fixtures', 'reporting')
const expected = JSON.parse(readFileSync(join(FIXTURES_DIR, 'expected.json'), 'utf-8'))

const TOLERANCE = 1e-6

// NOTE: the exact fixture filenames and expected.json key structure must be filled in
// here based on what Step 1 found — this skeleton shows the assertion pattern, not
// invented filenames. Locate the real fixture files under tests/fixtures/reporting/
// (copied in Task 2) and match them against the corresponding section of expected.json.
describe('extractor parity vs expected.json (ported from the original JS pipeline)', () => {
  it('seedtag matches expected values for every site/day within tolerance', () => {
    for (const siteId of Object.keys(expected.seedtag ?? {}) as SiteId[]) {
      const fixturePath = join(FIXTURES_DIR, 'uploads', expected.seedtag[siteId].__fixtureFile)
      const result = extractSeedtag(fixturePath, siteId)
      const resultByDay = Object.fromEntries(result.map((r) => [r.date, r]))
      for (const [date, vals] of Object.entries(expected.seedtag[siteId].days)) {
        expect(resultByDay[date]?.impressions).toBeCloseTo((vals as any).impressions, 6)
        expect(resultByDay[date]?.revenue).toBeCloseTo((vals as any).revenue, 6)
      }
      expect(Object.keys(resultByDay).sort()).toEqual(
        Object.keys(expected.seedtag[siteId].days).sort()
      )
    }
  })

  // Repeat the same pattern for teads, showheroes, gam, adform, ogury — one `it()` block
  // per partner, each iterating expected.json's sites/days for that partner, asserting
  // impressions/revenue (or just revenue for gam) within TOLERANCE and asserting the
  // same set of date keys appears.

  it('analytics matches expected values for all 7 fields', () => {
    const csv = readFileSync(join(FIXTURES_DIR, 'uploads', expected.analytics.__fixtureFile), 'utf-8')
    const result = extractAnalytics(csv)
    const resultByDay = Object.fromEntries(result.map((r) => [r.date, r]))
    for (const [date, vals] of Object.entries(expected.analytics.days)) {
      expect(resultByDay[date]).toMatchObject(vals as object)
    }
  })

  it('adhese grouped-by-site revenue sums match expected', () => {
    const fixturePath = join(FIXTURES_DIR, 'uploads', expected.adhese.__fixtureFile)
    const rows = extractAdhese(fixturePath, expected.adhese.__fixtureFile)
    const sumsBySite: Record<string, number> = {}
    for (const r of rows) {
      sumsBySite[r.site] = (sumsBySite[r.site] ?? 0) + r.revenue
    }
    for (const [site, total] of Object.entries(expected.adhese.totalsBySite)) {
      expect(sumsBySite[site]).toBeCloseTo(total as number, 6)
    }
  })
})
```

- [ ] **Step 3: Run test to verify it fails or reveals shape mismatches**

Run: `npm test -- extractorParity.test.ts`
Expected: the test runs against the real `expected.json` — if the skeleton's assumed JSON shape (`expected.seedtag[siteId].days`, `.__fixtureFile`, etc.) doesn't match the real file, this fails with a clear "cannot read property of undefined" error. Fix the property paths in Step 2 to match the actual `expected.json` structure found in Step 1, not the other way around.

- [ ] **Step 4: Iterate until all parity assertions pass**

Run: `npm test -- extractorParity.test.ts`
Expected: PASS for every partner/site/day combination present in `expected.json`. If any single value is off, check the corresponding extractor in Tasks 9–12 against the exact PHP source in `app/Services/Reporting/Extractors.php` (in the `creative-vuejs-laravel` repo) — the JS/PHP/TS three-way parity is the whole point of this task.

- [ ] **Step 5: Commit**

```bash
git add tests/unit/reporting/extractorParity.test.ts
git commit -m "test: add extractor parity suite against golden fixture (expected.json)"
```

---

## Task 14: Store Merger

**Files:**
- Create: `lib/reporting/storeMerger.ts`
- Test: `tests/unit/reporting/storeMerger.test.ts`

**Interfaces:**
- Produces: `type ReportStore = { sites: Record<SiteId, { days: Record<string, StoreDay> }>; config: { oguryRate: number } }`, `type StoreDay = { dateKey: string; revenue: Record<string, number>; impressions?: Record<string, number>; analytics?: Record<string, number>; totalAdRequests?: number; impressionsSold?: number }`, and `mergeIntoStore(store: ReportStore, siteId: SiteId, parsed: ParsedPartnerData): void` (mutates `store` in place, matching the source's by-reference semantics). Consumed by `reportProcessor.ts` (Task 16) and `reportStore.ts` (Task 15, which defines the load/save I/O around this same shape).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/storeMerger.test.ts
import { describe, it, expect } from 'vitest'
import { mergeIntoStore, type ReportStore } from '@/lib/reporting/storeMerger'

function emptyStore(): ReportStore {
  return {
    sites: {
      f1maximaal: { days: {} }, topgear: { days: {} }, horses: { days: {} }, festileaks: { days: {} },
    } as any,
    config: { oguryRate: 0.85 },
  }
}

describe('mergeIntoStore', () => {
  it('builds revenue object with exactly the 9 keys in source order', () => {
    const store = emptyStore()
    mergeIntoStore(store, 'topgear', { seedtag: [{ date: '2026-06-01', impressions: 100, revenue: 10 }] })
    const day = store.sites.topgear.days['2026-06-01']
    expect(Object.keys(day.revenue)).toEqual([
      'adhese', 'gam', 'seedtag', 'teads', 'showheroes', 'adform', 'ogury', 'outbrain', 'preferredDeals',
    ])
    expect(day.revenue.seedtag).toBe(10)
    expect(day.revenue.gam).toBe(0)
  })

  it('does NOT track impressions/analytics for non-f1maximaal sites', () => {
    const store = emptyStore()
    mergeIntoStore(store, 'topgear', { seedtag: [{ date: '2026-06-01', impressions: 100, revenue: 10 }] })
    expect(store.sites.topgear.days['2026-06-01'].impressions).toBeUndefined()
  })

  it('CRITICAL INVARIANT: a partner missing from this run does not zero its prior stored value', () => {
    const store = emptyStore()
    mergeIntoStore(store, 'topgear', {
      seedtag: [{ date: '2026-06-01', impressions: 100, revenue: 10 }],
      showheroes: [{ date: '2026-06-01', impressions: 50, revenue: 20.27 }],
    })
    expect(store.sites.topgear.days['2026-06-01'].revenue.showheroes).toBe(20.27)

    // second run: showheroes absent entirely (simulates it missing from a later upload)
    mergeIntoStore(store, 'topgear', {
      seedtag: [{ date: '2026-06-01', impressions: 200, revenue: 99 }],
    })
    expect(store.sites.topgear.days['2026-06-01'].revenue.showheroes).toBe(20.27)
    expect(store.sites.topgear.days['2026-06-01'].revenue.seedtag).toBe(99)
  })

  it('for f1maximaal, builds impressions with 9 keys in source order and sums impressionsSold', () => {
    const store = emptyStore()
    mergeIntoStore(store, 'f1maximaal', {
      seedtag: [{ date: '2026-06-01', impressions: 100, revenue: 10 }],
      teads: [{ date: '2026-06-01', impressions: 50, revenue: 5 }],
    })
    const day = store.sites.f1maximaal.days['2026-06-01']
    expect(Object.keys(day.impressions!)).toEqual([
      'seedtag', 'teads', 'showheroes', 'gam', 'adform', 'ogury', 'outbrain', 'adhese', 'preferredDeals',
    ])
    expect(day.impressionsSold).toBe(150)
  })

  it('adhese impressions for f1maximaal are never set by a file-derived merge (manual-only)', () => {
    const store = emptyStore()
    store.sites.f1maximaal.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 0, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
      impressions: { seedtag: 0, teads: 0, showheroes: 0, gam: 0, adform: 0, ogury: 0, outbrain: 0, adhese: 12345, preferredDeals: 0 },
      impressionsSold: 12345,
    }
    mergeIntoStore(store, 'f1maximaal', { seedtag: [{ date: '2026-06-01', impressions: 100, revenue: 10 }] })
    expect(store.sites.f1maximaal.days['2026-06-01'].impressions!.adhese).toBe(12345)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- storeMerger.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/storeMerger'`

- [ ] **Step 3: Write `lib/reporting/storeMerger.ts`**

```typescript
import type { SiteId } from './constants'
import type { DayMetric } from './extractors'

export type StoreDay = {
  dateKey: string
  revenue: Record<string, number>
  impressions?: Record<string, number>
  analytics?: Record<string, number>
  totalAdRequests?: number
  impressionsSold?: number
}

export type ReportStore = {
  sites: Record<SiteId, { days: Record<string, StoreDay> }>
  config: { oguryRate: number }
}

export type ParsedPartnerData = Partial<{
  adhese: DayMetric[]
  seedtag: DayMetric[]
  teads: DayMetric[]
  showheroes: DayMetric[]
  gam: { date: string; revenue: number }[]
  adform: DayMetric[]
  ogury: DayMetric[]
  analytics: { date: string; [k: string]: number | string }[]
  gam_f1m: Record<string, { totalAdRequests: number; gamImpressions: number }>
  outbrain: Record<string, { impressions: number; revenue: number }>
  preferreddeals: DayMetric[]
  impressions_f1: Record<string, { adhese: number }>
}>

const REVENUE_KEY_ORDER = [
  'adhese', 'gam', 'seedtag', 'teads', 'showheroes', 'adform', 'ogury', 'outbrain', 'preferredDeals',
] as const

const IMPRESSIONS_KEY_ORDER = [
  'seedtag', 'teads', 'showheroes', 'gam', 'adform', 'ogury', 'outbrain', 'adhese', 'preferredDeals',
] as const

function byDayLookup(list: { date: string; [k: string]: unknown }[] | undefined): Record<string, any> {
  const out: Record<string, any> = {}
  for (const row of list ?? []) out[row.date] = row
  return out
}

export function mergeIntoStore(store: ReportStore, siteId: SiteId, parsed: ParsedPartnerData): void {
  const isF1 = siteId === 'f1maximaal'

  const byPartner: Record<string, Record<string, any>> = {
    adhese: byDayLookup(parsed.adhese as any),
    seedtag: byDayLookup(parsed.seedtag as any),
    teads: byDayLookup(parsed.teads as any),
    showheroes: byDayLookup(parsed.showheroes as any),
    gam: byDayLookup(parsed.gam as any),
    adform: byDayLookup(parsed.adform as any),
    ogury: byDayLookup(parsed.ogury as any),
    outbrain: parsed.outbrain ?? {},
    preferredDeals: byDayLookup(parsed.preferreddeals as any),
  }

  const allDateKeys = new Set<string>()
  for (const [partner, entries] of Object.entries(parsed)) {
    if (!entries) continue
    if (partner === 'gam_f1m' || partner === 'impressions_f1') {
      Object.keys(entries as object).forEach((k) => allDateKeys.add(k))
    } else if (partner === 'outbrain') {
      Object.keys(entries as object).forEach((k) => allDateKeys.add(k))
    } else if (Array.isArray(entries)) {
      entries.forEach((e: any) => allDateKeys.add(e.date))
    }
  }

  const days = store.sites[siteId].days

  for (const dk of allDateKeys) {
    const existing = days[dk]
    const revenue: Record<string, number> = {}
    for (const key of REVENUE_KEY_ORDER) {
      const newVal = byPartner[key]?.[dk]?.revenue
      revenue[key] = newVal ?? existing?.revenue?.[key] ?? 0
    }

    const day: StoreDay = { dateKey: dk, revenue }

    if (isF1) {
      const gamF1m = (parsed.gam_f1m as any)?.[dk]
      const impressionsF1 = (parsed.impressions_f1 as any)?.[dk]

      const impressions: Record<string, number> = {}
      for (const key of IMPRESSIONS_KEY_ORDER) {
        let newVal: number | undefined
        if (key === 'gam') newVal = gamF1m?.gamImpressions
        else if (key === 'adhese') newVal = undefined // manual-only, never set from a file merge
        else newVal = byPartner[key]?.[dk]?.impressions

        impressions[key] =
          key === 'adhese'
            ? existing?.impressions?.adhese ?? 0
            : newVal ?? existing?.impressions?.[key] ?? 0
      }
      day.impressions = impressions
      day.totalAdRequests = gamF1m?.totalAdRequests ?? existing?.totalAdRequests ?? 0

      const newAnalytics = (parsed.analytics as any[])?.find((a) => a.date === dk)
      day.analytics = newAnalytics ?? existing?.analytics

      day.impressionsSold = IMPRESSIONS_KEY_ORDER.reduce((sum, key) => sum + (impressions[key] || 0), 0)
    }

    days[dk] = day
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- storeMerger.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/storeMerger.ts tests/unit/reporting/storeMerger.test.ts
git commit -m "feat: port StoreMerger with carry-forward invariant and manual-only Adhese impressions"
```

---

## Task 15: Report Store (Supabase Load/Save)

**Files:**
- Create: `lib/reporting/reportStore.ts`
- Test: `tests/integration/reporting/reportStore.test.ts`

**Interfaces:**
- Consumes: `createAdminClient` from `lib/supabase/admin.ts` (Task 4); `ReportStore`, `StoreDay` types from `lib/reporting/storeMerger.ts` (Task 14); `SITES` from `lib/reporting/constants.ts` (Task 6).
- Produces: `DEFAULT_OGURY_RATE = 0.85`, `emptyStore(): ReportStore`, `loadStore(): Promise<ReportStore>`, `saveStore(store: ReportStore): Promise<void>`. Consumed by `reportProcessor.ts` (Task 16) and every API route handler (Tasks 22–26).
- **Requires a live Supabase project** (schema from Task 3) — this task's test hits real Supabase, so it needs `.env.local` populated with your project's URL/keys before running.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/reportStore.test.ts
import { describe, it, expect, beforeAll, afterAll } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { emptyStore, loadStore, saveStore, DEFAULT_OGURY_RATE } from '@/lib/reporting/reportStore'

const TEST_DATE = '2026-06-01'

describe('reportStore', () => {
  afterAll(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', TEST_DATE)
    await supabase.from('report_settings').delete().eq('key', 'oguryRate')
  })

  it('emptyStore has all 4 sites with no days and default ogury rate', () => {
    const store = emptyStore()
    expect(Object.keys(store.sites)).toEqual(['f1maximaal', 'topgear', 'horses', 'festileaks'])
    expect(store.sites.topgear.days).toEqual({})
  })

  it('saves and reloads a day round-trip, preserving values and oguryRate config', async () => {
    const store = emptyStore()
    store.config.oguryRate = 0.9
    store.sites.topgear.days[TEST_DATE] = {
      dateKey: TEST_DATE,
      revenue: { adhese: 0, gam: 0, seedtag: 10, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
    }

    await saveStore(store)
    const reloaded = await loadStore()

    expect(reloaded.config.oguryRate).toBe(0.9)
    expect(reloaded.sites.topgear.days[TEST_DATE].revenue.seedtag).toBe(10)
  })

  it('DEFAULT_OGURY_RATE is 0.85', () => {
    expect(DEFAULT_OGURY_RATE).toBe(0.85)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- reportStore.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/reportStore'`

- [ ] **Step 3: Write `lib/reporting/reportStore.ts`**

```typescript
import { createAdminClient } from '@/lib/supabase/admin'
import { SITES, type SiteId } from './constants'
import type { ReportStore, StoreDay } from './storeMerger'

export const DEFAULT_OGURY_RATE = 0.85

export function emptyStore(): ReportStore {
  const sites = {} as ReportStore['sites']
  for (const siteId of Object.keys(SITES) as SiteId[]) {
    sites[siteId] = { days: {} }
  }
  return { sites, config: {} as ReportStore['config'] }
}

export async function loadStore(): Promise<ReportStore> {
  const supabase = createAdminClient()
  const store = emptyStore()

  const { data: settingRow } = await supabase
    .from('report_settings')
    .select('value')
    .eq('key', 'oguryRate')
    .maybeSingle()
  store.config.oguryRate = (settingRow?.value as number | undefined) ?? DEFAULT_OGURY_RATE

  const { data: dayRows, error } = await supabase.from('report_days').select('*')
  if (error) throw error

  for (const row of dayRows ?? []) {
    const siteId = row.site as SiteId
    const day: StoreDay = {
      dateKey: row.date,
      revenue: row.revenue ?? {},
    }
    if (row.impressions !== null) day.impressions = row.impressions
    if (row.analytics !== null) day.analytics = row.analytics
    if (siteId === 'f1maximaal') {
      day.totalAdRequests = row.total_ad_requests ?? 0
      day.impressionsSold = row.impressions_sold ?? 0
    }
    store.sites[siteId].days[row.date] = day
  }

  return store
}

export async function saveStore(store: ReportStore): Promise<void> {
  const supabase = createAdminClient()

  await supabase
    .from('report_settings')
    .upsert({ key: 'oguryRate', value: store.config.oguryRate }, { onConflict: 'key' })

  for (const siteId of Object.keys(store.sites) as SiteId[]) {
    for (const day of Object.values(store.sites[siteId].days)) {
      await supabase.from('report_days').upsert(
        {
          site: siteId,
          date: day.dateKey,
          revenue: day.revenue,
          impressions: day.impressions ?? null,
          analytics: day.analytics ?? null,
          total_ad_requests: Math.round(day.totalAdRequests ?? 0),
          impressions_sold: Math.round(day.impressionsSold ?? 0),
        },
        { onConflict: 'site,date' }
      )
    }
  }
}

export async function getSetting<T>(key: string, defaultValue: T): Promise<T> {
  const supabase = createAdminClient()
  const { data } = await supabase.from('report_settings').select('value').eq('key', key).maybeSingle()
  return (data?.value as T | undefined) ?? defaultValue
}

export async function putSetting(key: string, value: unknown): Promise<void> {
  const supabase = createAdminClient()
  await supabase.from('report_settings').upsert({ key, value }, { onConflict: 'key' })
}
```

- [ ] **Step 4: Populate `.env.local` and run the test**

Copy `.env.example` to `.env.local`, fill in your Supabase project's URL and keys (from Task 3's project).

Run: `npm test -- reportStore.test.ts`
Expected: PASS — confirms real round-trip against Supabase.

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/reportStore.ts tests/integration/reporting/reportStore.test.ts
git commit -m "feat: port ReportStore load/save against Supabase"
```

---

## Task 16: Report Processor (Upload Orchestration)

**Files:**
- Create: `lib/reporting/reportProcessor.ts`
- Test: `tests/integration/reporting/reportProcessor.test.ts`

**Interfaces:**
- Consumes: `detectFileType`, `dateKey`, `monthKey` from `lib/reporting/helpers.ts`; every `extract*` function from `lib/reporting/extractors.ts`; `mergeIntoStore` from `lib/reporting/storeMerger.ts`; `loadStore`, `saveStore`, `getSetting` from `lib/reporting/reportStore.ts`; `SITES` from `lib/reporting/constants.ts`.
- Produces: `processUpload(files: {name: string; path: string}[], today?: Date): Promise<{fileTypes: Record<string,string>; store: ReportStore}>`. Consumed by the `/api/reporting/process` route handler (Task 24).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/reportProcessor.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import { writeFileSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import * as XLSX from 'xlsx'
import { createAdminClient } from '@/lib/supabase/admin'
import { processUpload } from '@/lib/reporting/reportProcessor'

function writeXlsx(rows: unknown[][], filename: string): { name: string; path: string } {
  const dir = mkdtempSync(join(tmpdir(), 'process-test-'))
  const path = join(dir, filename)
  const ws = XLSX.utils.aoa_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1')
  XLSX.writeFile(wb, path)
  return { name: filename, path }
}

describe('processUpload', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', '2026-06-20')
  })

  it('detects file type, extracts, merges, and persists — applying the current-month+7-day filter', async () => {
    const today = new Date(Date.UTC(2026, 5, 25)) // 2026-06-25, matches source test's pinned "now"

    const seedtagFile = writeXlsx(
      [
        ['Publisher', 'Date', 'Impressions', 'Revenue'],
        ['topgear.nl', '2026-06-20', 100, 10],
      ],
      'revenue-export-topgear.xlsx'
    )

    const result = await processUpload([seedtagFile], today)

    expect(result.fileTypes[seedtagFile.name]).toBe('seedtag')
    expect(result.store.sites.topgear.days['2026-06-20'].revenue.seedtag).toBe(10)

    const supabase = createAdminClient()
    const { data } = await supabase
      .from('report_days')
      .select('*')
      .eq('site', 'topgear')
      .eq('date', '2026-06-20')
      .single()
    expect(data.revenue.seedtag).toBe(10)
  })

  it('drops rows outside current month + trailing 7 days', async () => {
    const today = new Date(Date.UTC(2026, 5, 25))
    const staleFile = writeXlsx(
      [
        ['Publisher', 'Date', 'Impressions', 'Revenue'],
        ['topgear.nl', '2026-04-01', 999, 999], // well outside window
      ],
      'revenue-export-topgear.xlsx'
    )
    const result = await processUpload([staleFile], today)
    expect(result.store.sites.topgear.days['2026-04-01']).toBeUndefined()
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- reportProcessor.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/reportProcessor'`

- [ ] **Step 3: Write `lib/reporting/reportProcessor.ts`**

```typescript
import { detectFileType, dateKey, monthKey } from './helpers'
import {
  extractSeedtag, extractTeads, extractShowheroes, extractGam, extractAdform,
  extractOgury, extractAdhese, extractGamF1m, extractOutbrain, extractPreferredDeals,
  extractImpressionsF1, extractAnalytics, type DayMetric,
} from './extractors'
import { mergeIntoStore, type ParsedPartnerData, type ReportStore } from './storeMerger'
import { loadStore, saveStore, getSetting, DEFAULT_OGURY_RATE } from './reportStore'
import { SITES, type SiteId } from './constants'

export type UploadFile = { name: string; path: string }

function filterWindow<T extends { date: string }>(
  entries: T[], thisMonth: string, sevenDaysAgoKey: string
): T[] {
  return entries.filter((e) => monthKey(e.date) === thisMonth || e.date >= sevenDaysAgoKey)
}

function filterWindowRecord<T>(
  entries: Record<string, T>, thisMonth: string, sevenDaysAgoKey: string
): Record<string, T> {
  const out: Record<string, T> = {}
  for (const [k, v] of Object.entries(entries)) {
    if (monthKey(k) === thisMonth || k >= sevenDaysAgoKey) out[k] = v
  }
  return out
}

export async function processUpload(
  files: UploadFile[],
  today: Date = new Date()
): Promise<{ fileTypes: Record<string, string>; store: ReportStore }> {
  const store = await loadStore()
  const oguryRate = await getSetting('oguryRate', DEFAULT_OGURY_RATE)
  const filePatterns = await getSetting<Record<string, string>>('file_patterns', {})

  const fileTypes: Record<string, string> = {}
  const pathByType: Record<string, string> = {}
  let adheseRows: { date: string; site: string; revenue: number }[] = []

  for (const file of files) {
    const type = detectFileType(file.name, filePatterns)
    fileTypes[file.name] = type
    if (type === 'adhese') {
      try {
        adheseRows = adheseRows.concat(extractAdhese(file.path, file.name))
      } catch {
        // one bad file must not abort the whole run
      }
    } else {
      pathByType[type] = file.path
    }
  }

  const adheseBySite = new Map<SiteId, Map<string, number>>()
  for (const row of adheseRows) {
    const siteId = (Object.keys(SITES) as SiteId[]).find((id) => SITES[id].domain === row.site)
    if (!siteId) continue
    if (!adheseBySite.has(siteId)) adheseBySite.set(siteId, new Map())
    const m = adheseBySite.get(siteId)!
    m.set(row.date, (m.get(row.date) ?? 0) + row.revenue)
  }

  const thisMonth = `${today.getUTCFullYear()}-${String(today.getUTCMonth() + 1).padStart(2, '0')}`
  const sevenDaysAgo = new Date(today)
  sevenDaysAgo.setUTCDate(sevenDaysAgo.getUTCDate() - 7)
  const sevenDaysAgoKey = dateKey(sevenDaysAgo)

  for (const siteId of Object.keys(SITES) as SiteId[]) {
    const parsed: ParsedPartnerData = {}
    const isF1 = siteId === 'f1maximaal'

    try {
      if (pathByType.seedtag) parsed.seedtag = filterWindow(extractSeedtag(pathByType.seedtag, siteId), thisMonth, sevenDaysAgoKey)
      if (pathByType.teads) parsed.teads = filterWindow(extractTeads(pathByType.teads, siteId), thisMonth, sevenDaysAgoKey)
      if (pathByType.showheroes) parsed.showheroes = filterWindow(extractShowheroes(pathByType.showheroes, siteId), thisMonth, sevenDaysAgoKey)
      if (pathByType.gam) parsed.gam = filterWindow(extractGam(pathByType.gam, siteId) as DayMetric[], thisMonth, sevenDaysAgoKey)
      if (pathByType.adform) parsed.adform = filterWindow(extractAdform(pathByType.adform, siteId), thisMonth, sevenDaysAgoKey)
      if (pathByType.ogury) parsed.ogury = filterWindow(extractOgury(pathByType.ogury, siteId, oguryRate), thisMonth, sevenDaysAgoKey)

      const siteAdhese = adheseBySite.get(siteId)
      if (siteAdhese) {
        parsed.adhese = filterWindow(
          Array.from(siteAdhese.entries()).map(([date, revenue]) => ({ date, impressions: 0, revenue })),
          thisMonth, sevenDaysAgoKey
        )
      }

      if (isF1) {
        if (pathByType.analytics) parsed.analytics = filterWindow(extractAnalytics(readFile(pathByType.analytics)), thisMonth, sevenDaysAgoKey)
        if (pathByType.gam_f1m) parsed.gam_f1m = filterWindowRecord(extractGamF1m(pathByType.gam_f1m), thisMonth, sevenDaysAgoKey)
        if (pathByType.outbrain) parsed.outbrain = filterWindowRecord(extractOutbrain(readFile(pathByType.outbrain)), thisMonth, sevenDaysAgoKey)
        if (pathByType.preferreddeals) parsed.preferreddeals = filterWindow(extractPreferredDeals(pathByType.preferreddeals), thisMonth, sevenDaysAgoKey)
        if (pathByType.impressions_f1) parsed.impressions_f1 = filterWindowRecord(extractImpressionsF1(pathByType.impressions_f1), thisMonth, sevenDaysAgoKey)
      }

      if (Object.keys(parsed).length > 0) {
        mergeIntoStore(store, siteId, parsed)
      }
    } catch {
      // one site's failure must not abort other sites
    }
  }

  await saveStore(store)

  return { fileTypes, store }
}

function readFile(path: string): string {
  return require('node:fs').readFileSync(path, 'utf-8')
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- reportProcessor.test.ts`
Expected: PASS — both the successful-persist case and the stale-data-filtered-out case.

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/reportProcessor.ts tests/integration/reporting/reportProcessor.test.ts
git commit -m "feat: port ReportProcessor orchestration with current-month+7-day filter"
```

---

## Task 17: CSV Generator

**Files:**
- Create: `lib/reporting/csvGenerator.ts`
- Test: `tests/unit/reporting/csvGenerator.test.ts`

**Interfaces:**
- Consumes: `ReportStore` type from `lib/reporting/storeMerger.ts`; `SITES`, `ADHESE_MARKET` from `lib/reporting/constants.ts`; `fmtAdheseDate` from `lib/reporting/helpers.ts`.
- Produces: `generateAnalyticsCsv(store, from?, to?): string`, `generateAdheseCsv(store, siteId?, from?, to?): string`. Consumed by `zipBuilder.ts` (Task 21) and the download API route (Task 26).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/csvGenerator.test.ts
import { describe, it, expect } from 'vitest'
import { generateAnalyticsCsv, generateAdheseCsv } from '@/lib/reporting/csvGenerator'
import { emptyStore } from '@/lib/reporting/reportStore'

describe('generateAnalyticsCsv', () => {
  it('returns empty string when there are no analytics days', () => {
    expect(generateAnalyticsCsv(emptyStore())).toBe('')
  })

  it('emits the exact header block and one data row per analytics day', () => {
    const store = emptyStore()
    store.sites.f1maximaal.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 0, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
      analytics: { views: 100, activeUsers: 50, viewsPerUser: 2, avgEngagement: 30, eventCount: 10, keyEvents: 5, totalRevenue: 12.5 },
      impressionsSold: 1234,
      totalAdRequests: 5678,
    }
    const csv = generateAnalyticsCsv(store)
    expect(csv).toContain('Content group,Date,Views,Active users,Views per active user,Average engagement time per active user,Event count,Key events,Total revenue,Impressions Sold,Total Ad Requests')
    expect(csv).toContain('F1Maximaal.nl,20260601,100,50,2,30,10,5,12.5,"1,234","5,678"')
  })
})

describe('generateAdheseCsv', () => {
  it('returns header-only when no day has adhese revenue', () => {
    const csv = generateAdheseCsv(emptyStore(), 'f1maximaal')
    expect(csv.trim()).toBe('date,site,market.name,Paid Revenue')
  })

  it('emits a row per day with truthy adhese revenue', () => {
    const store = emptyStore()
    store.sites.f1maximaal.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 42, gam: 0, seedtag: 0, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
    }
    const csv = generateAdheseCsv(store, 'f1maximaal')
    expect(csv).toContain('1-Jun-26,F1Maximaal.nl,DALE-igmn,42')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- csvGenerator.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/csvGenerator'`

- [ ] **Step 3: Write `lib/reporting/csvGenerator.ts`**

```typescript
import type { ReportStore } from './storeMerger'
import { SITES, ADHESE_MARKET, type SiteId } from './constants'
import { fmtAdheseDate } from './helpers'

function inRange(dateKey: string, from?: string, to?: string): boolean {
  if (from && dateKey < from) return false
  if (to && dateKey > to) return false
  return true
}

function fmtThousands(n: number): string {
  return n ? `"${n.toLocaleString('en-US')}"` : '0'
}

export function generateAnalyticsCsv(store: ReportStore, from?: string, to?: string): string {
  const days = Object.values(store.sites.f1maximaal.days)
    .filter((d) => d.analytics && inRange(d.dateKey, from, to))
    .sort((a, b) => (a.dateKey < b.dateKey ? -1 : 1))

  if (days.length === 0) return ''

  const rangeStart = (from ?? days[0].dateKey).replace(/-/g, '')
  const rangeEnd = (to ?? days[days.length - 1].dateKey).replace(/-/g, '')

  const lines = [
    '# ----------------------------------------',
    '# Pages and screens: Content group',
    '# Account: F1Maximaal',
    '# Property: F1Maximaal.nl - GA4',
    '# ----------------------------------------',
    '#',
    '# All Users',
    `# Start date: ${rangeStart}`,
    `# End date: ${rangeEnd}`,
    'Content group,Date,Views,Active users,Views per active user,Average engagement time per active user,Event count,Key events,Total revenue,Impressions Sold,Total Ad Requests',
  ]

  for (const d of days) {
    const a = d.analytics!
    lines.push(
      [
        'F1Maximaal.nl',
        d.dateKey.replace(/-/g, ''),
        a.views, a.activeUsers, a.viewsPerUser, a.avgEngagement, a.eventCount, a.keyEvents, a.totalRevenue,
        fmtThousands(d.impressionsSold ?? 0),
        fmtThousands(d.totalAdRequests ?? 0),
      ].join(',')
    )
  }

  return lines.join('\n')
}

export function generateAdheseCsv(
  store: ReportStore,
  siteId: SiteId = 'f1maximaal',
  from?: string,
  to?: string
): string {
  const lines = ['date,site,market.name,Paid Revenue']
  const days = Object.values(store.sites[siteId].days)
    .filter((d) => d.revenue.adhese && inRange(d.dateKey, from, to))
    .sort((a, b) => (a.dateKey < b.dateKey ? -1 : 1))

  for (const d of days) {
    const [y, m, day] = d.dateKey.split('-').map(Number)
    const dateObj = new Date(Date.UTC(y, m - 1, day, 12))
    lines.push(
      [
        fmtAdheseDate(dateObj),
        SITES[siteId].name,
        ADHESE_MARKET[siteId] ?? '',
        d.revenue.adhese,
      ].join(',')
    )
  }

  return lines.join('\n')
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- csvGenerator.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/csvGenerator.ts tests/unit/reporting/csvGenerator.test.ts
git commit -m "feat: port CsvGenerator (Analytics.csv, Adhese CSVs)"
```

---

## Task 18: Table Exporter

**Files:**
- Create: `lib/reporting/tableExporter.ts`
- Test: `tests/unit/reporting/tableExporter.test.ts`

**Interfaces:**
- Consumes: `ReportStore` from `lib/reporting/storeMerger.ts`; `SITES` from `lib/reporting/constants.ts`.
- Produces: `type TableData = {...}`, `buildTableData(store, siteId, from?, to?): TableData`, `tableToCsv(data): string`, `tableToJson(data): string`, `tableToXlsx(data): Promise<Buffer>`. Consumed by the export-table API route (Task 26).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/tableExporter.test.ts
import { describe, it, expect } from 'vitest'
import { buildTableData, tableToCsv, tableToJson } from '@/lib/reporting/tableExporter'
import { emptyStore } from '@/lib/reporting/reportStore'

const PARTNER_COLUMN_ORDER = [
  'Adhese', 'GAM', 'SeedTag', 'Teads', 'Showheroes', 'Adform', 'Ogury', 'Outbrain', 'Preferred Deals',
]

describe('buildTableData', () => {
  it('uses the TableExporter partner column order (differs from StoreMerger key order)', () => {
    const store = emptyStore()
    const data = buildTableData(store, 'topgear')
    expect(Object.values(data.partners)).toEqual(PARTNER_COLUMN_ORDER)
  })

  it('rounds revenue to 2 decimal places per day and computes totals', () => {
    const store = emptyStore()
    store.sites.topgear.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 10.005, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
    }
    const data = buildTableData(store, 'topgear')
    expect(data.days[0].revenue.seedtag).toBeCloseTo(10.01, 2)
    expect(data.totals.revenue.seedtag).toBeCloseTo(10.01, 2)
  })
})

describe('tableToCsv', () => {
  it('emits Revenues and Impressions sections', () => {
    const store = emptyStore()
    const data = buildTableData(store, 'topgear')
    const csv = tableToCsv(data)
    expect(csv).toContain('Revenues — TopGear.nl')
    expect(csv).toContain('Impressions — TopGear.nl')
  })
})

describe('tableToJson', () => {
  it('produces valid pretty-printed JSON', () => {
    const store = emptyStore()
    const data = buildTableData(store, 'topgear')
    expect(() => JSON.parse(tableToJson(data))).not.toThrow()
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- tableExporter.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/tableExporter'`

- [ ] **Step 3: Write `lib/reporting/tableExporter.ts`**

```typescript
import ExcelJS from 'exceljs'
import type { ReportStore, StoreDay } from './storeMerger'
import { SITES, type SiteId } from './constants'

const PARTNERS: Record<string, string> = {
  adhese: 'Adhese', gam: 'GAM', seedtag: 'SeedTag', teads: 'Teads',
  showheroes: 'Showheroes', adform: 'Adform', ogury: 'Ogury',
  outbrain: 'Outbrain', preferredDeals: 'Preferred Deals',
}

const PARTNER_KEYS = Object.keys(PARTNERS)

export type TableData = {
  siteId: SiteId
  siteName: string
  from: string | null
  to: string | null
  isF1: boolean
  partners: Record<string, string>
  days: {
    date: string
    revenue: Record<string, number>
    impressions: Record<string, number | null>
    total: number
    impressionsSold: number | null
    totalAdRequests: number | null
  }[]
  totals: {
    revenue: Record<string, number>
    total: number
    impressions: Record<string, number>
    impressionsSold: number
    totalAdRequests: number
  }
}

function round2(n: number): number {
  return Math.round(n * 100) / 100
}

function inRange(dateKey: string, from?: string, to?: string): boolean {
  if (from && dateKey < from) return false
  if (to && dateKey > to) return false
  return true
}

export function buildTableData(store: ReportStore, siteId: SiteId, from?: string, to?: string): TableData {
  const isF1 = siteId === 'f1maximaal'
  const dayEntries: StoreDay[] = Object.values(store.sites[siteId].days)
    .filter((d) => inRange(d.dateKey, from, to))
    .sort((a, b) => (a.dateKey < b.dateKey ? -1 : 1))

  const totalsRevenue: Record<string, number> = Object.fromEntries(PARTNER_KEYS.map((k) => [k, 0]))
  const totalsImpressions: Record<string, number> = Object.fromEntries(PARTNER_KEYS.map((k) => [k, 0]))
  let totalsSold = 0
  let totalsAdRequests = 0
  let grandTotal = 0

  const days = dayEntries.map((d) => {
    const revenue: Record<string, number> = {}
    let total = 0
    for (const k of PARTNER_KEYS) {
      const v = round2(d.revenue[k] ?? 0)
      revenue[k] = v
      total += v
      totalsRevenue[k] += v
    }
    grandTotal += total

    const impressions: Record<string, number | null> = {}
    for (const k of PARTNER_KEYS) {
      const v = isF1 ? d.impressions?.[k] ?? null : null
      impressions[k] = v
      if (v !== null) totalsImpressions[k] += v
    }

    if (isF1) {
      totalsSold += d.impressionsSold ?? 0
      totalsAdRequests += d.totalAdRequests ?? 0
    }

    return {
      date: d.dateKey,
      revenue,
      impressions,
      total: round2(total),
      impressionsSold: isF1 ? d.impressionsSold ?? 0 : null,
      totalAdRequests: isF1 ? d.totalAdRequests ?? 0 : null,
    }
  })

  return {
    siteId,
    siteName: SITES[siteId].name,
    from: from ?? null,
    to: to ?? null,
    isF1,
    partners: PARTNERS,
    days,
    totals: {
      revenue: Object.fromEntries(Object.entries(totalsRevenue).map(([k, v]) => [k, round2(v)])),
      total: round2(grandTotal),
      impressions: totalsImpressions,
      impressionsSold: totalsSold,
      totalAdRequests: totalsAdRequests,
    },
  }
}

export function tableToCsv(data: TableData): string {
  const labels = PARTNER_KEYS.map((k) => data.partners[k])
  const lines: string[] = []

  lines.push(`Revenues — ${data.siteName}`)
  lines.push(['Date', ...labels, 'Total'].join(','))
  for (const d of data.days) {
    lines.push([d.date, ...PARTNER_KEYS.map((k) => d.revenue[k]), d.total].join(','))
  }
  lines.push('')
  lines.push(`Impressions — ${data.siteName}`)
  lines.push(['Date', ...labels, 'Impressions Sold', 'Total Ad Requests'].join(','))
  for (const d of data.days) {
    lines.push(
      [d.date, ...PARTNER_KEYS.map((k) => d.impressions[k] ?? ''), d.impressionsSold ?? '', d.totalAdRequests ?? ''].join(',')
    )
  }

  return lines.join('\n')
}

export function tableToJson(data: TableData): string {
  return JSON.stringify(data, null, 2)
}

export async function tableToXlsx(data: TableData): Promise<Buffer> {
  const wb = new ExcelJS.Workbook()
  const labels = PARTNER_KEYS.map((k) => data.partners[k])

  const impressionsSheet = wb.addWorksheet('Impressions')
  impressionsSheet.addRow(['Date', ...labels, 'Impressions Sold', 'Total Ad Requests']).font = { bold: true }
  for (const d of data.days) {
    impressionsSheet.addRow([d.date, ...PARTNER_KEYS.map((k) => d.impressions[k]), d.impressionsSold, d.totalAdRequests])
  }
  impressionsSheet.getColumn(1).width = 14
  impressionsSheet.columns.forEach((c, i) => {
    if (i > 0) c.numFmt = '#,##0'
  })

  const revenueSheet = wb.addWorksheet('Revenues')
  revenueSheet.addRow(['Date', ...labels, 'Total']).font = { bold: true }
  for (const d of data.days) {
    revenueSheet.addRow([d.date, ...PARTNER_KEYS.map((k) => d.revenue[k]), d.total])
  }
  revenueSheet.getColumn(1).width = 14
  revenueSheet.columns.forEach((c, i) => {
    if (i > 0) c.numFmt = '#,##0.00'
  })

  const buf = await wb.xlsx.writeBuffer()
  return Buffer.from(buf)
}
```

- [ ] **Step 4: Install exceljs if not already present, then run test**

```bash
npm install exceljs
```

Run: `npm test -- tableExporter.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/tableExporter.ts tests/unit/reporting/tableExporter.test.ts package.json package-lock.json
git commit -m "feat: port TableExporter (csv/json/xlsx dashboard exports)"
```

---

## Task 19: Verifier

**Files:**
- Create: `lib/reporting/verifier.ts`
- Test: `tests/unit/reporting/verifier.test.ts`

**Interfaces:**
- Consumes: `matrix`, `sheetNames` from `lib/reporting/spreadsheetReader.ts`; `stripNum`, `parseDate`, `dateKey` from `lib/reporting/helpers.ts`; `ReportStore` from `lib/reporting/storeMerger.ts`; `SITES` from `lib/reporting/constants.ts`.
- Produces: `type VerifyCheck = {label: string; pn: number; us: number; tol: number}`, `verifyMonthly(store, path): {dateKey: string; checks: VerifyCheck[]}[]` (throws on missing sheet/header), `verifyWeekly(store, path, siteId): {siteName: string; rows: {dateKey: string; checks: VerifyCheck[]}[]}` (throws on unknown site/missing sheet/header). Consumed by the verify API routes (Task 25).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/verifier.test.ts
import { describe, it, expect } from 'vitest'
import * as XLSX from 'xlsx'
import { mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { verifyMonthly, verifyWeekly } from '@/lib/reporting/verifier'
import { emptyStore } from '@/lib/reporting/reportStore'

function writeWorkbookMultiSheet(sheets: Record<string, unknown[][]>): string {
  const dir = mkdtempSync(join(tmpdir(), 'verifier-test-'))
  const path = join(dir, 'test.xlsx')
  const wb = XLSX.utils.book_new()
  for (const [name, data] of Object.entries(sheets)) {
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), name)
  }
  XLSX.writeFile(wb, path)
  return path
}

describe('verifyMonthly', () => {
  it('throws when there is no Trend sheet', () => {
    const path = writeWorkbookMultiSheet({ NotTrend: [['x']] })
    expect(() => verifyMonthly(emptyStore(), path)).toThrow('Could not find Trend sheet')
  })

  it('throws when the Trend sheet has no header row starting with "Date"', () => {
    const path = writeWorkbookMultiSheet({ Trend: [['NotDate', 'x']] })
    expect(() => verifyMonthly(emptyStore(), path)).toThrow('Could not find header row in Trend sheet')
  })

  it('computes checks against store values within tolerance', () => {
    const store = emptyStore()
    store.sites.f1maximaal.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 5, gam: 5, seedtag: 0, teads: 0, showheroes: 20.27, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
      impressionsSold: 1000,
      totalAdRequests: 2000,
    }
    const path = writeWorkbookMultiSheet({
      Trend: [
        ['Date', 'Impressions Sold', 'Total Ad Requests', 'Revenues'],
        ['2026-06-01', 1010, 2005, 30.5],
      ],
    })
    const result = verifyMonthly(store, path)
    const checks = result.find((r) => r.dateKey === '2026-06-01')!.checks
    const soldCheck = checks.find((c) => c.label === 'Impr. Sold')!
    expect(soldCheck.pn).toBe(1010)
    expect(soldCheck.us).toBe(1000)
    expect(soldCheck.tol).toBe(50)
  })
})

describe('verifyWeekly', () => {
  it('throws on an unknown site', () => {
    const path = writeWorkbookMultiSheet({ 'Demand Partners': [['Date', 'Total']] })
    // @ts-expect-error intentionally invalid site for the error-path test
    expect(() => verifyWeekly(emptyStore(), path, 'not-a-site')).toThrow('Unknown site')
  })

  it('throws when there is no Demand Partners sheet', () => {
    const path = writeWorkbookMultiSheet({ Other: [['x']] })
    expect(() => verifyWeekly(emptyStore(), path, 'topgear')).toThrow('Could not find Demand Partners sheet')
  })

  it('includes a partner check only when either side is > 0', () => {
    const store = emptyStore()
    store.sites.topgear.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 0, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
    }
    const path = writeWorkbookMultiSheet({
      'Demand Partners': [
        ['Date', 'Total', 'Adform'],
        ['2026-06-01', 10, 10],
      ],
    })
    const result = verifyWeekly(store, path, 'topgear')
    const checks = result.rows.find((r) => r.dateKey === '2026-06-01')!.checks
    expect(checks.some((c) => c.label === 'Adform')).toBe(true)
    expect(checks.some((c) => c.label === 'Ogury')).toBe(false)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- verifier.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/verifier'`

- [ ] **Step 3: Write `lib/reporting/verifier.ts`**

```typescript
import { matrix, sheetNames } from './spreadsheetReader'
import { stripNum, parseDate, dateKey } from './helpers'
import type { ReportStore } from './storeMerger'
import { SITES, type SiteId } from './constants'

export type VerifyCheck = { label: string; pn: number; us: number; tol: number }

const PARTNERS: Record<string, string> = {
  adform: 'Adform', gam: 'GAM', ogury: 'Ogury', seedtag: 'Seedtag',
  showheroes: 'Showheroes', teads: 'Teads', preferredDeals: 'Preferred Deals',
  outbrain: 'Outbrain', adhese: 'Adhese',
}

function cellToNum(v: unknown): number {
  if (v === '-' || v === '' || v === null || v === undefined) return 0
  return stripNum(v)
}

function findHeaderRow(rows: unknown[][]): number {
  return rows.findIndex((r) => String(r?.[0] ?? '').trim() === 'Date')
}

function readSheetRows(
  m: unknown[][],
  headerIdx: number,
  columns: Record<string, string>
): { dateKey: string; values: Record<string, number> }[] {
  const header = m[headerIdx].map((h) => String(h ?? '').trim())
  const colIdx = (name: string) => header.findIndex((h) => h.toLowerCase() === name.toLowerCase())

  const out: { dateKey: string; values: Record<string, number> }[] = []
  for (let i = headerIdx + 1; i < m.length; i++) {
    const row = m[i]
    const rawDate = row?.[0]
    if (rawDate === undefined || rawDate === null || String(rawDate).trim() === '') break
    if (String(rawDate).trim().toLowerCase().startsWith('note')) break
    const d = parseDate(rawDate)
    if (!d) break

    const values: Record<string, number> = {}
    for (const [key, colName] of Object.entries(columns)) {
      const idx = colIdx(colName)
      values[key] = idx >= 0 ? cellToNum(row[idx]) : 0
    }
    out.push({ dateKey: dateKey(d), values })
  }
  return out
}

export function verifyMonthly(
  store: ReportStore,
  path: string
): { dateKey: string; checks: VerifyCheck[] }[] {
  if (!sheetNames(path).includes('Trend')) throw new Error('Could not find Trend sheet')
  const trendMatrix = matrix(path, 'Trend')
  const headerIdx = findHeaderRow(trendMatrix)
  if (headerIdx < 0) throw new Error('Could not find header row in Trend sheet')

  const trendRows = readSheetRows(trendMatrix, headerIdx, {
    impressionsSold: 'Impressions Sold',
    totalAdRequests: 'Total Ad Requests',
    revenues: 'Revenues',
  })

  let partnerRows: { dateKey: string; values: Record<string, number> }[] = []
  if (sheetNames(path).includes('Demand Partners')) {
    const dpMatrix = matrix(path, 'Demand Partners')
    const dpHeaderIdx = findHeaderRow(dpMatrix)
    if (dpHeaderIdx >= 0) {
      partnerRows = readSheetRows(
        dpMatrix, dpHeaderIdx,
        Object.fromEntries(Object.entries(PARTNERS).map(([k, label]) => [k, label]))
      )
    }
  }
  const partnerByDate = Object.fromEntries(partnerRows.map((r) => [r.dateKey, r.values]))

  return trendRows.map((row) => {
    const storeDay = store.sites.f1maximaal.days[row.dateKey]
    const checks: VerifyCheck[] = [
      { label: 'Impr. Sold', pn: row.values.impressionsSold, us: storeDay?.impressionsSold ?? 0, tol: 50 },
      { label: 'Ad Requests', pn: row.values.totalAdRequests, us: storeDay?.totalAdRequests ?? 0, tol: 10 },
      {
        label: 'Total Revenue',
        pn: row.values.revenues,
        us: Object.values(storeDay?.revenue ?? {}).reduce((a, b) => a + b, 0),
        tol: 0.5,
      },
    ]
    const partnerVals = partnerByDate[row.dateKey]
    if (partnerVals) {
      for (const key of Object.keys(PARTNERS)) {
        checks.push({
          label: PARTNERS[key],
          pn: partnerVals[key] ?? 0,
          us: storeDay?.revenue?.[key] ?? 0,
          tol: 0.5,
        })
      }
    }
    return { dateKey: row.dateKey, checks }
  })
}

export function verifyWeekly(
  store: ReportStore,
  path: string,
  siteId: SiteId
): { siteName: string; rows: { dateKey: string; checks: VerifyCheck[] }[] } {
  if (!(siteId in SITES)) throw new Error('Unknown site')
  if (!sheetNames(path).includes('Demand Partners')) throw new Error('Could not find Demand Partners sheet')

  const dpMatrix = matrix(path, 'Demand Partners')
  const headerIdx = findHeaderRow(dpMatrix)
  if (headerIdx < 0) throw new Error('Could not find header row in Demand Partners sheet')

  const dpRows = readSheetRows(dpMatrix, headerIdx, {
    total: 'Total',
    ...Object.fromEntries(Object.entries(PARTNERS).map(([k, label]) => [k, label])),
  })

  const rows = dpRows.map((row) => {
    const storeDay = store.sites[siteId].days[row.dateKey]
    const checks: VerifyCheck[] = [
      {
        label: 'Total Revenue', pn: row.values.total,
        us: Object.values(storeDay?.revenue ?? {}).reduce((a, b) => a + b, 0), tol: 0.5,
      },
    ]
    for (const key of Object.keys(PARTNERS)) {
      const pn = row.values[key] ?? 0
      const us = storeDay?.revenue?.[key] ?? 0
      if (pn > 0 || us > 0) {
        checks.push({ label: PARTNERS[key], pn, us, tol: 0.5 })
      }
    }
    return { dateKey: row.dateKey, checks }
  })

  return { siteName: SITES[siteId].name, rows }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- verifier.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/verifier.ts tests/unit/reporting/verifier.test.ts
git commit -m "feat: port Verifier (monthly and weekly reconciliation checks)"
```

---

## Task 20: Ogury Converter

**Files:**
- Create: `lib/reporting/oguryConverter.ts`
- Test: `tests/unit/reporting/oguryConverter.test.ts`

**Interfaces:**
- Consumes: `rows`, `sheetNames` from `lib/reporting/spreadsheetReader.ts`; `pick`, `stripNum`, `parseDate`, `dateKey` from `lib/reporting/helpers.ts`.
- Produces: `convertOguryToLegacyFormat(path): Promise<Buffer | null>` — returns `null` if the file isn't a recognized "Full Report" export (caller then ships the original file unchanged). Consumed by `zipBuilder.ts` (Task 21).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/oguryConverter.test.ts
import { describe, it, expect } from 'vitest'
import * as XLSX from 'xlsx'
import { mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import ExcelJS from 'exceljs'
import { convertOguryToLegacyFormat } from '@/lib/reporting/oguryConverter'

function writeFullReportWorkbook(rows: unknown[][]): string {
  const dir = mkdtempSync(join(tmpdir(), 'ogury-convert-test-'))
  const path = join(dir, 'ogury.xlsx')
  const ws = XLSX.utils.aoa_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Full Report')
  XLSX.writeFile(wb, path)
  return path
}

describe('convertOguryToLegacyFormat', () => {
  it('returns null when there is no "Full Report" sheet', async () => {
    const dir = mkdtempSync(join(tmpdir(), 'ogury-convert-test-'))
    const path = join(dir, 'test.xlsx')
    const ws = XLSX.utils.aoa_to_sheet([['x']])
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'NotFullReport')
    XLSX.writeFile(wb, path)
    expect(await convertOguryToLegacyFormat(path)).toBeNull()
  })

  it('aggregates rows into a two-sheet legacy workbook (Report + Statistics 1)', async () => {
    const path = writeFullReportWorkbook([
      ['Date', 'Asset key', 'Ad unit id', 'Format', 'Device', 'Country', 'Revenues', 'Impressions', 'Requests'],
      ['2026-06-01', 'asset1', 'unit1', 'standard_banners', 'phone', 'NL', 10, 1000, 1200],
      ['2026-06-01', 'asset1', 'unit1', 'standard_banners', 'tablet', 'BE', 5, 500, 600],
    ])
    const buf = await convertOguryToLegacyFormat(path)
    expect(buf).not.toBeNull()

    const wb = new ExcelJS.Workbook()
    await wb.xlsx.load(buf!)
    expect(wb.getWorksheet('Report')).toBeDefined()
    const stats = wb.getWorksheet('Statistics 1')!
    expect(stats).toBeDefined()
    // header row + exactly 1 aggregated group row (same date|asset|unit|format)
    expect(stats.rowCount).toBe(2)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- oguryConverter.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/oguryConverter'`

- [ ] **Step 3: Write `lib/reporting/oguryConverter.ts`**

```typescript
import ExcelJS from 'exceljs'
import { rows, sheetNames } from './spreadsheetReader'
import { pick, stripNum, parseDate, dateKey } from './helpers'

const FORMAT_LABELS: Record<string, string> = {
  standard_banners: 'Standard Banners',
  footer_ad: 'Footer Ad',
  'in-article': 'In-article',
}

type Group = {
  date: string
  assetKey: string
  adUnitId: string
  formatKey: string
  revenue: number
  impressions: number
  requests: number
  deviceStats: Record<string, { impressions: number; requests: number; count: number }>
}

function aggregate(sourceRows: Record<string, unknown>[]): Group[] {
  const groups = new Map<string, Group>();

  for (const row of sourceRows) {
    const d = parseDate(pick(row, 'Date'))
    if (!d) continue
    const assetKey = String(pick(row, 'Asset key') ?? '')
    const adUnitId = String(pick(row, 'Ad unit id') ?? '')
    const formatKey = String(pick(row, 'Format') ?? '')
    const device = String(pick(row, 'Device') ?? 'unknown')
    const key = `${dateKey(d)}|${assetKey}|${adUnitId}|${formatKey}`

    if (!groups.has(key)) {
      groups.set(key, {
        date: dateKey(d), assetKey, adUnitId, formatKey,
        revenue: 0, impressions: 0, requests: 0, deviceStats: {},
      })
    }
    const g = groups.get(key)!
    const impressions = stripNum(pick(row, 'Impressions'))
    const requests = stripNum(pick(row, 'Requests'))
    g.revenue += stripNum(pick(row, 'Revenues'))
    g.impressions += impressions
    g.requests += requests

    if (!g.deviceStats[device]) g.deviceStats[device] = { impressions: 0, requests: 0, count: 0 }
    g.deviceStats[device].impressions += impressions
    g.deviceStats[device].requests += requests
    g.deviceStats[device].count += 1
  }

  return Array.from(groups.values())
}

function dominantDevice(g: Group): string {
  const entries = Object.entries(g.deviceStats)
  entries.sort((a, b) => {
    if (b[1].impressions !== a[1].impressions) return b[1].impressions - a[1].impressions
    if (b[1].requests !== a[1].requests) return b[1].requests - a[1].requests
    return b[1].count - a[1].count
  })
  return entries[0]?.[0] ?? 'unknown'
}

export async function convertOguryToLegacyFormat(path: string): Promise<Buffer | null> {
  if (!sheetNames(path).includes('Full Report')) return null

  const sourceRows = rows(path, 'Full Report')
  const groups = aggregate(sourceRows)
  if (groups.length === 0) return null

  const dates = groups.map((g) => g.date).sort()
  const from = dates[0]
  const to = dates[dates.length - 1]

  const wb = new ExcelJS.Workbook()

  const report = wb.addWorksheet('Report')
  report.addRow(['From', from])
  report.addRow(['To', to])
  report.addRow(['Organizations', 'Planet Nine Media BV'])
  report.addRow(['Organization key', 'a3440f3c-ee4b-40cc-8b45-842fea7402f3'])
  report.addRow(['Groups', 'date/asset/ad_unit'])
  report.addRow(['Metrics', 'revenues/impressions/requests/hb_on_bid_won/ecpm'])
  report.addRow(['Orders', 'date/asset/ad_unit'])

  const stats = wb.addWorksheet('Statistics 1')
  const header = [
    'Date', 'Asset key', 'Ad unit id', 'Format', 'Device',
    'Revenues', 'Impressions', 'Requests', 'HB on bid won', 'eCPM',
    ...Array(9).fill(''), // source has a 19-column header; remaining columns unused by this port
  ].slice(0, 19)
  const headerRow = stats.addRow(header)
  headerRow.eachCell((cell) => {
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFC00000' } }
    cell.font = { color: { argb: 'FFFFFFFF' }, bold: true }
  })

  for (const g of groups) {
    const ecpm = g.impressions > 0 ? Math.round((g.revenue / g.impressions) * 1000 * 1e5) / 1e5 : 0
    stats.addRow([
      g.date, g.assetKey, g.adUnitId,
      FORMAT_LABELS[g.formatKey] ?? g.formatKey,
      dominantDevice(g),
      g.revenue, g.impressions, g.requests, 0, ecpm,
    ])
  }

  const buf = await wb.xlsx.writeBuffer()
  return Buffer.from(buf)
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- oguryConverter.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/oguryConverter.ts tests/unit/reporting/oguryConverter.test.ts
git commit -m "feat: port OguryConverter (current format to legacy two-sheet xlsx)"
```

---

## Task 21: Zip Builder

**Files:**
- Create: `lib/reporting/zipBuilder.ts`
- Test: `tests/integration/reporting/zipBuilder.test.ts`

**Interfaces:**
- Consumes: `createAdminClient` from `lib/supabase/admin.ts`; `generateAnalyticsCsv`, `generateAdheseCsv` from `lib/reporting/csvGenerator.ts`; `convertOguryToLegacyFormat` from `lib/reporting/oguryConverter.ts`; `ReportStore` from `lib/reporting/storeMerger.ts`.
- Produces: `listAvailableFiles(): Promise<string[]>` (lists `raw-uploads` bucket contents, excluding `planetnine-report-*`), `buildDownloadZip(store, requested?: string[], from?: string, to?: string, oguryOldFormat = false): Promise<Buffer>` (throws `'No files to download yet'` / `'No matching files selected'`). Consumed by the download API route (Task 26).
- **Requires a live Supabase project with the `raw-uploads` bucket** (Task 3).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/zipBuilder.test.ts
import { describe, it, expect, beforeAll, afterAll } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { listAvailableFiles, buildDownloadZip } from '@/lib/reporting/zipBuilder'
import { emptyStore } from '@/lib/reporting/reportStore'
import AdmZip from 'adm-zip'

describe('zipBuilder', () => {
  beforeAll(async () => {
    const supabase = createAdminClient()
    await supabase.storage.from('raw-uploads').upload('SeedTag.xlsx', Buffer.from('fake xlsx content'), { upsert: true })
    await supabase.storage.from('raw-uploads').upload('planetnine-report-2026-06.xlsx', Buffer.from('excluded'), { upsert: true })
  })

  afterAll(async () => {
    const supabase = createAdminClient()
    await supabase.storage.from('raw-uploads').remove(['SeedTag.xlsx', 'planetnine-report-2026-06.xlsx'])
  })

  it('listAvailableFiles excludes planetnine-report-* files', async () => {
    const files = await listAvailableFiles()
    expect(files).toContain('SeedTag.xlsx')
    expect(files.some((f) => f.startsWith('planetnine-report-'))).toBe(false)
  })

  it('throws "No files to download yet" when the bucket has nothing eligible', async () => {
    const supabase = createAdminClient()
    await supabase.storage.from('raw-uploads').remove(['SeedTag.xlsx'])
    await expect(buildDownloadZip(emptyStore())).rejects.toThrow('No files to download yet')
    // restore for subsequent tests in this file
    await supabase.storage.from('raw-uploads').upload('SeedTag.xlsx', Buffer.from('fake xlsx content'), { upsert: true })
  })

  it('throws "No matching files selected" when the requested filter matches nothing', async () => {
    await expect(buildDownloadZip(emptyStore(), ['DoesNotExist.xlsx'])).rejects.toThrow('No matching files selected')
  })

  it('builds a zip containing the raw file unchanged when not Analytics/Adhese/Ogury', async () => {
    const buf = await buildDownloadZip(emptyStore())
    const zip = new AdmZip(buf)
    const entry = zip.getEntry('SeedTag.xlsx')
    expect(entry).not.toBeNull()
    expect(entry!.getData().toString()).toBe('fake xlsx content')
  })

  it('regenerates Analytics.csv from the live store instead of shipping a stored copy', async () => {
    const store = emptyStore()
    store.sites.f1maximaal.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 0, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
      analytics: { views: 10, activeUsers: 5, viewsPerUser: 2, avgEngagement: 1, eventCount: 1, keyEvents: 1, totalRevenue: 1 },
    }
    const supabase = createAdminClient()
    await supabase.storage.from('raw-uploads').upload('Analytics.csv', Buffer.from('stale,content'), { upsert: true })

    const buf = await buildDownloadZip(store)
    const zip = new AdmZip(buf)
    const content = zip.getEntry('Analytics.csv')!.getData().toString()
    expect(content).not.toContain('stale,content')
    expect(content).toContain('F1Maximaal.nl,20260601')

    await supabase.storage.from('raw-uploads').remove(['Analytics.csv'])
  })
})
```

- [ ] **Step 2: Install `adm-zip` (test-only, for reading back the built zip) and `archiver` (production zip writer), then run test to verify it fails**

```bash
npm install archiver
npm install -D adm-zip @types/adm-zip
```

Run: `npm test -- zipBuilder.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/zipBuilder'`

- [ ] **Step 3: Write `lib/reporting/zipBuilder.ts`**

```typescript
import archiver from 'archiver'
import { PassThrough } from 'node:stream'
import { createAdminClient } from '@/lib/supabase/admin'
import { generateAnalyticsCsv, generateAdheseCsv } from './csvGenerator'
import { convertOguryToLegacyFormat } from './oguryConverter'
import type { ReportStore } from './storeMerger'
import { writeFileSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'

const BUCKET = 'raw-uploads'
const REGENERATED_CSV_NAMES = new Set(['Analytics.csv', 'Adhese f1.csv', 'Adhese tg.csv', 'Adhese fl.csv'])

export async function listAvailableFiles(): Promise<string[]> {
  const supabase = createAdminClient()
  const { data, error } = await supabase.storage.from(BUCKET).list()
  if (error) throw error
  return (data ?? [])
    .map((f) => f.name)
    .filter((name) => !name.toLowerCase().startsWith('planetnine-report-'))
    .sort()
}

export async function buildDownloadZip(
  store: ReportStore,
  requested?: string[],
  from?: string,
  to?: string,
  oguryOldFormat = false
): Promise<Buffer> {
  const available = await listAvailableFiles()
  if (available.length === 0) throw new Error('No files to download yet')

  const selected = requested ? available.filter((f) => requested.includes(f)) : available
  if (selected.length === 0) throw new Error('No matching files selected')

  const supabase = createAdminClient()
  const archive = archiver('zip')
  const chunks: Buffer[] = []
  const passthrough = new PassThrough()
  archive.pipe(passthrough)
  passthrough.on('data', (c) => chunks.push(c))

  const done = new Promise<Buffer>((resolve, reject) => {
    passthrough.on('end', () => resolve(Buffer.concat(chunks)))
    archive.on('error', reject)
  })

  for (const name of selected) {
    if (REGENERATED_CSV_NAMES.has(name)) {
      let content: string
      if (name === 'Analytics.csv') content = generateAnalyticsCsv(store, from, to)
      else if (name === 'Adhese f1.csv') content = generateAdheseCsv(store, 'f1maximaal', from, to)
      else if (name === 'Adhese tg.csv') content = generateAdheseCsv(store, 'topgear', from, to)
      else content = generateAdheseCsv(store, 'festileaks', from, to)
      archive.append(content, { name })
      continue
    }

    const { data, error } = await supabase.storage.from(BUCKET).download(name)
    if (error || !data) continue
    const buf = Buffer.from(await data.arrayBuffer())

    if (oguryOldFormat && /^ogury.*\.xlsx$/i.test(name)) {
      const dir = mkdtempSync(join(tmpdir(), 'ogury-zip-'))
      const tmpPath = join(dir, name)
      writeFileSync(tmpPath, buf)
      const converted = await convertOguryToLegacyFormat(tmpPath)
      archive.append(converted ?? buf, { name })
      continue
    }

    archive.append(buf, { name })
  }

  await archive.finalize()
  return done
}
```

- [ ] **Step 4: Run test to verify it passes**

Populate `.env.local` if not already done (Task 15).

Run: `npm test -- zipBuilder.test.ts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add lib/reporting/zipBuilder.ts tests/integration/reporting/zipBuilder.test.ts package.json package-lock.json
git commit -m "feat: port ZipBuilder against Supabase Storage"
```

---

## Task 22: API Routes — Config and Links

**Files:**
- Create: `lib/reporting/api/config.ts`
- Create: `lib/reporting/api/links.ts`
- Create: `app/api/reporting/config/route.ts`
- Create: `app/api/reporting/links/route.ts`
- Test: `tests/unit/reporting/api/config.test.ts`
- Test: `tests/unit/reporting/api/links.test.ts`

**Interfaces:**
- Consumes: `getSetting`, `putSetting` from `lib/reporting/reportStore.ts`; `DEFAULT_FILE_PATTERNS` from `lib/reporting/constants.ts`.
- Produces: `handleConfigUpdate(input: unknown): Promise<{success: true}>` (throws `ZodError` on invalid input), `handleLinksUpdate(input: unknown): Promise<{success: true}>`. Route handlers are thin adapters around these.

- [ ] **Step 1: Write the failing tests**

```typescript
// tests/unit/reporting/api/config.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleConfigUpdate } from '@/lib/reporting/api/config'

describe('handleConfigUpdate', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_settings').delete().in('key', ['oguryRate', 'reminder_day', 'ogury_old_format', 'file_patterns'])
  })

  it('persists oguryRate, reminderDay, oguryOldFormat individually when provided', async () => {
    await handleConfigUpdate({ oguryRate: 0.9, reminderDay: 3, oguryOldFormat: true })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_settings').select('key, value')
    const byKey = Object.fromEntries((data ?? []).map((r) => [r.key, r.value]))
    expect(byKey.oguryRate).toBe(0.9)
    expect(byKey.reminder_day).toBe(3)
    expect(byKey.ogury_old_format).toBe(true)
  })

  it('rejects reminderDay outside 0-6', async () => {
    await expect(handleConfigUpdate({ reminderDay: 7 })).rejects.toThrow()
  })

  it('filters filePatterns to only known DEFAULT_FILE_PATTERNS keys', async () => {
    await handleConfigUpdate({ filePatterns: { teads: 'custom-needle', unknownKey: 'x' } })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_settings').select('value').eq('key', 'file_patterns').single()
    expect(data!.value).toEqual({ teads: 'custom-needle' })
  })
})
```

```typescript
// tests/unit/reporting/api/links.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleLinksUpdate } from '@/lib/reporting/api/links'

describe('handleLinksUpdate', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_settings').delete().eq('key', 'report_links')
  })

  it('persists a list of {label, url} links', async () => {
    await handleLinksUpdate({ links: [{ label: 'GAM', url: 'https://admanager.google.com' }] })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_settings').select('value').eq('key', 'report_links').single()
    expect(data!.value).toEqual([{ label: 'GAM', url: 'https://admanager.google.com' }])
  })

  it('rejects a link with an invalid url', async () => {
    await expect(handleLinksUpdate({ links: [{ label: 'GAM', url: 'not-a-url' }] })).rejects.toThrow()
  })

  it('rejects a label longer than 80 chars', async () => {
    await expect(
      handleLinksUpdate({ links: [{ label: 'x'.repeat(81), url: 'https://example.com' }] })
    ).rejects.toThrow()
  })
})
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npm test -- config.test.ts links.test.ts`
Expected: FAIL — modules not found

- [ ] **Step 3: Write `lib/reporting/api/config.ts`**

```typescript
import { z } from 'zod'
import { putSetting } from '@/lib/reporting/reportStore'
import { DEFAULT_FILE_PATTERNS } from '@/lib/reporting/constants'

const ConfigInput = z.object({
  oguryRate: z.number().nullish(),
  reminderDay: z.number().int().min(0).max(6).nullish(),
  oguryOldFormat: z.boolean().nullish(),
  filePatterns: z.record(z.string(), z.string().max(255).nullish()).nullish(),
})

export async function handleConfigUpdate(input: unknown): Promise<{ success: true }> {
  const parsed = ConfigInput.parse(input)

  if (parsed.oguryRate !== undefined && parsed.oguryRate !== null) {
    await putSetting('oguryRate', parsed.oguryRate)
  }
  if (parsed.reminderDay !== undefined && parsed.reminderDay !== null) {
    await putSetting('reminder_day', parsed.reminderDay)
  }
  if (parsed.oguryOldFormat !== undefined && parsed.oguryOldFormat !== null) {
    await putSetting('ogury_old_format', parsed.oguryOldFormat)
  }
  if (parsed.filePatterns) {
    const filtered: Record<string, string> = {}
    for (const [key, value] of Object.entries(parsed.filePatterns)) {
      if (key in DEFAULT_FILE_PATTERNS && typeof value === 'string' && value.trim() !== '') {
        filtered[key] = value.trim()
      }
    }
    await putSetting('file_patterns', filtered)
  }

  return { success: true }
}
```

- [ ] **Step 4: Write `lib/reporting/api/links.ts`**

```typescript
import { z } from 'zod'
import { putSetting } from '@/lib/reporting/reportStore'

const LinksInput = z.object({
  links: z.array(
    z.object({
      label: z.string().min(1).max(80),
      url: z.string().url().max(2048),
    })
  ),
})

export async function handleLinksUpdate(input: unknown): Promise<{ success: true }> {
  const parsed = LinksInput.parse(input)
  await putSetting('report_links', parsed.links)
  return { success: true }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
npm install zod
```

Run: `npm test -- config.test.ts links.test.ts`
Expected: PASS

- [ ] **Step 6: Write the thin route adapters**

```typescript
// app/api/reporting/config/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleConfigUpdate } from '@/lib/reporting/api/config'

export async function POST(req: NextRequest) {
  try {
    const body = await req.json()
    const result = await handleConfigUpdate(body)
    return NextResponse.json(result)
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Invalid request' }, { status: 422 })
  }
}
```

```typescript
// app/api/reporting/links/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleLinksUpdate } from '@/lib/reporting/api/links'

export async function POST(req: NextRequest) {
  try {
    const body = await req.json()
    const result = await handleLinksUpdate(body)
    return NextResponse.json(result)
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Invalid request' }, { status: 422 })
  }
}
```

- [ ] **Step 7: Commit**

```bash
git add lib/reporting/api/config.ts lib/reporting/api/links.ts app/api/reporting/config app/api/reporting/links tests/unit/reporting/api package.json package-lock.json
git commit -m "feat: add config and links API routes"
```

---

## Task 23: API Routes — Save Adhese and Save Adhese Batch

**Files:**
- Create: `lib/reporting/api/saveAdhese.ts`
- Create: `app/api/reporting/save-adhese/route.ts`
- Create: `app/api/reporting/save-adhese-batch/route.ts`
- Test: `tests/integration/reporting/api/saveAdhese.test.ts`

**Interfaces:**
- Consumes: `createAdminClient` from `lib/supabase/admin.ts`.
- Produces: `handleSaveAdhese(input: unknown): Promise<{success: true} | {error: string}>`, `handleSaveAdheseBatch(input: unknown): Promise<{success: true}>`. Both recompute `impressions_sold` as the sum of all 9 impression fields, matching `StoreMerger`'s field set. Consumed by the two route handlers.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/api/saveAdhese.test.ts
import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleSaveAdhese, handleSaveAdheseBatch } from '@/lib/reporting/api/saveAdhese'

const DATES = ['2026-06-01', '2026-06-02']

describe('handleSaveAdhese / handleSaveAdheseBatch', () => {
  beforeEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').upsert(
      [
        { site: 'f1maximaal', date: DATES[0], impressions: { seedtag: 100, teads: 50, showheroes: 1000, gam: 0, adform: 0, ogury: 0, outbrain: 0, adhese: 0, preferredDeals: 0 }, impressions_sold: 1150 },
        { site: 'f1maximaal', date: DATES[1], impressions: { seedtag: 200, teads: 2000, showheroes: 0, gam: 0, adform: 0, ogury: 0, outbrain: 0, adhese: 0, preferredDeals: 0 }, impressions_sold: 2200 },
      ],
      { onConflict: 'site,date' }
    )
  })

  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'f1maximaal').in('date', DATES)
  })

  it('sets impressions.adhese and recomputes impressions_sold as the sum of all 9 fields', async () => {
    await handleSaveAdhese({ dateKey: DATES[0], adhese: 12345 })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_days').select('*').eq('site', 'f1maximaal').eq('date', DATES[0]).single()
    expect(data!.impressions.adhese).toBe(12345)
    expect(data!.impressions_sold).toBe(100 + 50 + 1000 + 12345)
  })

  it('returns an error for a dateKey with no matching row instead of throwing', async () => {
    const result = await handleSaveAdhese({ dateKey: '1999-01-01', adhese: 1 })
    expect(result).toEqual({ error: 'Date not found' })
  })

  it('clears impressions.adhese to null on a blank value and recomputes sold excluding it', async () => {
    await handleSaveAdhese({ dateKey: DATES[0], adhese: null })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_days').select('*').eq('site', 'f1maximaal').eq('date', DATES[0]).single()
    expect(data!.impressions.adhese).toBeNull()
    expect(data!.impressions_sold).toBe(100 + 50 + 1000)
  })

  it('batch: updates multiple entries and silently skips a dateKey with no matching row', async () => {
    await handleSaveAdheseBatch({
      entries: [
        { dateKey: DATES[0], adhese: 100 },
        { dateKey: DATES[1], adhese: 2000 },
        { dateKey: '1999-01-01', adhese: 5 },
      ],
    })
    const supabase = createAdminClient()
    const { data } = await supabase.from('report_days').select('date, impressions_sold').eq('site', 'f1maximaal').in('date', DATES)
    const byDate = Object.fromEntries((data ?? []).map((r) => [r.date, r.impressions_sold]))
    expect(byDate[DATES[0]]).toBe(100 + 50 + 1000 + 100)
    expect(byDate[DATES[1]]).toBe(200 + 2000 + 2000)
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- saveAdhese.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/api/saveAdhese'`

- [ ] **Step 3: Write `lib/reporting/api/saveAdhese.ts`**

```typescript
import { z } from 'zod'
import { createAdminClient } from '@/lib/supabase/admin'

const IMPRESSION_KEYS = [
  'seedtag', 'teads', 'showheroes', 'gam', 'adform', 'ogury', 'outbrain', 'adhese', 'preferredDeals',
]

const SaveAdheseInput = z.object({
  dateKey: z.string().min(1),
  adhese: z.union([z.number(), z.null()]).optional(),
})

async function updateOneDay(dateKey: string, adhese: number | null | undefined): Promise<'ok' | 'not_found'> {
  const supabase = createAdminClient()
  const { data: existing } = await supabase
    .from('report_days')
    .select('impressions')
    .eq('site', 'f1maximaal')
    .eq('date', dateKey)
    .maybeSingle()

  if (!existing) return 'not_found'

  const impressions = { ...(existing.impressions ?? {}) }
  impressions.adhese = adhese === undefined || adhese === null ? null : adhese

  const impressionsSold = IMPRESSION_KEYS.reduce((sum, key) => sum + (impressions[key] || 0), 0)

  await supabase
    .from('report_days')
    .update({ impressions, impressions_sold: impressionsSold })
    .eq('site', 'f1maximaal')
    .eq('date', dateKey)

  return 'ok'
}

export async function handleSaveAdhese(input: unknown): Promise<{ success: true } | { error: string }> {
  const parsed = SaveAdheseInput.parse(input)
  const result = await updateOneDay(parsed.dateKey, parsed.adhese)
  if (result === 'not_found') return { error: 'Date not found' }
  return { success: true }
}

const SaveAdheseBatchInput = z.object({
  entries: z.array(
    z.object({
      dateKey: z.string().min(1),
      adhese: z.union([z.number(), z.null()]).optional(),
    })
  ),
})

export async function handleSaveAdheseBatch(input: unknown): Promise<{ success: true }> {
  const parsed = SaveAdheseBatchInput.parse(input)
  for (const entry of parsed.entries) {
    await updateOneDay(entry.dateKey, entry.adhese) // silently skips 'not_found', matching source behavior
  }
  return { success: true }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- saveAdhese.test.ts`
Expected: PASS

- [ ] **Step 5: Write the thin route adapters**

```typescript
// app/api/reporting/save-adhese/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleSaveAdhese } from '@/lib/reporting/api/saveAdhese'

export async function POST(req: NextRequest) {
  try {
    const result = await handleSaveAdhese(await req.json())
    if ('error' in result) return NextResponse.json(result, { status: 404 })
    return NextResponse.json(result)
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Invalid request' }, { status: 422 })
  }
}
```

```typescript
// app/api/reporting/save-adhese-batch/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleSaveAdheseBatch } from '@/lib/reporting/api/saveAdhese'

export async function POST(req: NextRequest) {
  try {
    const result = await handleSaveAdheseBatch(await req.json())
    return NextResponse.json(result)
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Invalid request' }, { status: 422 })
  }
}
```

- [ ] **Step 6: Commit**

```bash
git add lib/reporting/api/saveAdhese.ts app/api/reporting/save-adhese app/api/reporting/save-adhese-batch tests/integration/reporting/api/saveAdhese.test.ts
git commit -m "feat: add save-adhese and save-adhese-batch API routes"
```

---

## Task 24: API Route — Process (Upload Handling)

**Files:**
- Create: `lib/reporting/api/process.ts`
- Create: `app/api/reporting/process/route.ts`
- Test: `tests/integration/reporting/api/process.test.ts`

**Interfaces:**
- Consumes: `processUpload` from `lib/reporting/reportProcessor.ts` (Task 16); `createAdminClient` from `lib/supabase/admin.ts`.
- Produces: `handleProcessUpload(files: {name: string; buffer: Buffer}[]): Promise<{fileTypes: Record<string,string>}>` — writes each uploaded file to a temp path (Node `fs`) before delegating to `processUpload`, then uploads the canonical-named copies to the `raw-uploads` Supabase Storage bucket (replacing the source system's local-disk `storage/app/reporting/uploads` persistence). Consumed by the route handler, which adapts `multipart/form-data` into this shape.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/api/process.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import * as XLSX from 'xlsx'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleProcessUpload } from '@/lib/reporting/api/process'

function xlsxBuffer(rows: unknown[][]): Buffer {
  const ws = XLSX.utils.aoa_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1')
  return XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' })
}

describe('handleProcessUpload', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', '2026-06-01')
    await supabase.storage.from('raw-uploads').remove(['SeedTag.xlsx'])
  })

  it('processes an upload, persists to Supabase, and stores the canonical-renamed file in raw-uploads', async () => {
    const today = new Date(Date.UTC(2026, 5, 25))
    const buffer = xlsxBuffer([
      ['Publisher', 'Date', 'Impressions', 'Revenue'],
      ['topgear.nl', '2026-06-01', 100, 10],
    ])

    const result = await handleProcessUpload([{ name: 'revenue-export-topgear.xlsx', buffer }], today)

    expect(result.fileTypes['revenue-export-topgear.xlsx']).toBe('seedtag')

    const supabase = createAdminClient()
    const { data: dbRow } = await supabase
      .from('report_days')
      .select('*')
      .eq('site', 'topgear')
      .eq('date', '2026-06-01')
      .single()
    expect(dbRow.revenue.seedtag).toBe(10)

    const { data: storedFile } = await supabase.storage.from('raw-uploads').download('SeedTag.xlsx')
    expect(storedFile).not.toBeNull()
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- process.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/api/process'`

- [ ] **Step 3: Write `lib/reporting/api/process.ts`**

```typescript
import { writeFileSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join, extname } from 'node:path'
import { createAdminClient } from '@/lib/supabase/admin'
import { processUpload, type UploadFile } from '@/lib/reporting/reportProcessor'
import { RENAME_MAP } from '@/lib/reporting/constants'

export async function handleProcessUpload(
  files: { name: string; buffer: Buffer }[],
  today: Date = new Date()
): Promise<{ fileTypes: Record<string, string> }> {
  const dir = mkdtempSync(join(tmpdir(), 'reporting-upload-'))
  const uploadFiles: UploadFile[] = files.map((f) => {
    const path = join(dir, f.name)
    writeFileSync(path, f.buffer)
    return { name: f.name, path }
  })

  const { fileTypes, store } = await processUpload(uploadFiles, today)
  void store // already persisted inside processUpload; kept here for callers that need it later

  const supabase = createAdminClient()
  for (const file of uploadFiles) {
    const type = fileTypes[file.name]
    const canonicalName = RENAME_MAP[type]
    if (!canonicalName) continue // 'analytics' and unrecognized types are not renamed/stored canonically
    const ext = extname(file.name) || '.xlsx'
    await supabase.storage
      .from('raw-uploads')
      .upload(`${canonicalName}${ext}`, files.find((f) => f.name === file.name)!.buffer, { upsert: true })
  }

  return { fileTypes }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- process.test.ts`
Expected: PASS

- [ ] **Step 5: Write the route adapter (parses multipart form data)**

```typescript
// app/api/reporting/process/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleProcessUpload } from '@/lib/reporting/api/process'

export async function POST(req: NextRequest) {
  try {
    const formData = await req.formData()
    const fileEntries = formData.getAll('files') as File[]
    if (fileEntries.length === 0) {
      return NextResponse.json({ error: 'files is required' }, { status: 422 })
    }

    const files = await Promise.all(
      fileEntries.map(async (f) => ({ name: f.name, buffer: Buffer.from(await f.arrayBuffer()) }))
    )

    const result = await handleProcessUpload(files)
    return NextResponse.json(result)
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Invalid request' }, { status: 422 })
  }
}
```

- [ ] **Step 6: Commit**

```bash
git add lib/reporting/api/process.ts app/api/reporting/process tests/integration/reporting/api/process.test.ts
git commit -m "feat: add reporting process (upload) API route"
```

---

## Task 25: API Routes — Verify and Verify Weekly

**Files:**
- Create: `lib/reporting/api/verify.ts`
- Create: `app/api/reporting/verify/route.ts`
- Create: `app/api/reporting/verify-weekly/route.ts`
- Test: `tests/integration/reporting/api/verify.test.ts`

**Interfaces:**
- Consumes: `verifyMonthly`, `verifyWeekly` from `lib/reporting/verifier.ts` (Task 19); `loadStore` from `lib/reporting/reportStore.ts`.
- Produces: `handleVerify(fileBuffer: Buffer): Promise<{rows: ReturnType<typeof verifyMonthly>; site: 'f1maximaal'; siteName: string} | {error: string}>`, `handleVerifyWeekly(fileBuffer: Buffer, siteId: string): Promise<{site: string; siteName: string; rows: ...} | {error: string}>`.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/api/verify.test.ts
import { describe, it, expect } from 'vitest'
import * as XLSX from 'xlsx'
import { handleVerify, handleVerifyWeekly } from '@/lib/reporting/api/verify'

function xlsxBuffer(sheets: Record<string, unknown[][]>): Buffer {
  const wb = XLSX.utils.book_new()
  for (const [name, data] of Object.entries(sheets)) {
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), name)
  }
  return XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' })
}

describe('handleVerify', () => {
  it('returns an error object (not a throw) when the Trend sheet is missing', async () => {
    const buf = xlsxBuffer({ NotTrend: [['x']] })
    const result = await handleVerify(buf)
    expect(result).toEqual({ error: 'Could not find Trend sheet' })
  })

  it('returns rows + site info on success', async () => {
    const buf = xlsxBuffer({ Trend: [['Date', 'Impressions Sold', 'Total Ad Requests', 'Revenues'], ['2026-06-01', 100, 200, 10]] })
    const result = await handleVerify(buf)
    expect('rows' in result && result.site === 'f1maximaal').toBe(true)
  })
})

describe('handleVerifyWeekly', () => {
  it('returns an error object for an unknown site', async () => {
    const buf = xlsxBuffer({ 'Demand Partners': [['Date', 'Total']] })
    const result = await handleVerifyWeekly(buf, 'not-a-site')
    expect(result).toEqual({ error: 'Unknown site' })
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- verify.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/api/verify'`

- [ ] **Step 3: Write `lib/reporting/api/verify.ts`**

```typescript
import { writeFileSync, mkdtempSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { verifyMonthly, verifyWeekly } from '@/lib/reporting/verifier'
import { loadStore } from '@/lib/reporting/reportStore'
import { SITES, type SiteId } from '@/lib/reporting/constants'

function bufferToTempPath(buffer: Buffer): string {
  const dir = mkdtempSync(join(tmpdir(), 'verify-upload-'))
  const path = join(dir, 'upload.xlsx')
  writeFileSync(path, buffer)
  return path
}

export async function handleVerify(
  fileBuffer: Buffer
): Promise<{ rows: ReturnType<typeof verifyMonthly>; site: 'f1maximaal'; siteName: string } | { error: string }> {
  try {
    const store = await loadStore()
    const path = bufferToTempPath(fileBuffer)
    const rows = verifyMonthly(store, path)
    return { rows, site: 'f1maximaal', siteName: SITES.f1maximaal.name }
  } catch (err) {
    return { error: err instanceof Error ? err.message : 'Verification failed' }
  }
}

export async function handleVerifyWeekly(
  fileBuffer: Buffer,
  siteId: string
): Promise<{ site: string; siteName: string; rows: ReturnType<typeof verifyWeekly>['rows'] } | { error: string }> {
  try {
    const store = await loadStore()
    const path = bufferToTempPath(fileBuffer)
    const result = verifyWeekly(store, path, siteId as SiteId)
    return { site: siteId, siteName: result.siteName, rows: result.rows }
  } catch (err) {
    return { error: err instanceof Error ? err.message : 'Verification failed' }
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- verify.test.ts`
Expected: PASS

- [ ] **Step 5: Write the route adapters**

```typescript
// app/api/reporting/verify/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleVerify } from '@/lib/reporting/api/verify'

export async function POST(req: NextRequest) {
  const formData = await req.formData()
  const file = formData.get('file') as File | null
  if (!file) return NextResponse.json({ error: 'file is required' }, { status: 422 })
  const buffer = Buffer.from(await file.arrayBuffer())
  const result = await handleVerify(buffer)
  if ('error' in result) return NextResponse.json(result, { status: 422 })
  return NextResponse.json(result)
}
```

```typescript
// app/api/reporting/verify-weekly/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleVerifyWeekly } from '@/lib/reporting/api/verify'

export async function POST(req: NextRequest) {
  const formData = await req.formData()
  const file = formData.get('file') as File | null
  const site = formData.get('site') as string | null
  if (!file || !site) return NextResponse.json({ error: 'file and site are required' }, { status: 422 })
  const buffer = Buffer.from(await file.arrayBuffer())
  const result = await handleVerifyWeekly(buffer, site)
  if ('error' in result) return NextResponse.json(result, { status: 422 })
  return NextResponse.json(result)
}
```

- [ ] **Step 6: Commit**

```bash
git add lib/reporting/api/verify.ts app/api/reporting/verify app/api/reporting/verify-weekly tests/integration/reporting/api/verify.test.ts
git commit -m "feat: add verify and verify-weekly API routes"
```

---

## Task 26: API Routes — Upload Files, Download, Export Table, Delete

**Files:**
- Create: `lib/reporting/api/downloadExport.ts`
- Create: `app/api/reporting/upload-files/route.ts`
- Create: `app/api/reporting/download/route.ts`
- Create: `app/api/reporting/export-table/route.ts`
- Create: `app/api/reporting/[site]/[dateKey]/route.ts`
- Test: `tests/integration/reporting/api/downloadExport.test.ts`

**Interfaces:**
- Consumes: `listAvailableFiles`, `buildDownloadZip` from `lib/reporting/zipBuilder.ts`; `buildTableData`, `tableToCsv`, `tableToJson`, `tableToXlsx` from `lib/reporting/tableExporter.ts`; `loadStore`, `getSetting` from `lib/reporting/reportStore.ts`; `createAdminClient` from `lib/supabase/admin.ts`.
- Produces: `handleExportTable(input: {site: string; format: 'csv'|'xlsx'|'json'; from?: string; to?: string}): Promise<{filename: string; contentType: string; body: string | Buffer} | {error: string}>`. The other three routes are thin enough to skip a separate handler module — implemented directly in their `route.ts` files.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/reporting/api/downloadExport.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleExportTable } from '@/lib/reporting/api/downloadExport'

describe('handleExportTable', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', '2026-06-01')
  })

  it('returns an error for an unknown site', async () => {
    const result = await handleExportTable({ site: 'not-a-site', format: 'csv' })
    expect(result).toEqual({ error: 'Unknown site' })
  })

  it('builds a csv export with the expected filename pattern', async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').upsert(
      { site: 'topgear', date: '2026-06-01', revenue: { adhese: 0, gam: 0, seedtag: 10, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 } },
      { onConflict: 'site,date' }
    )
    const result = await handleExportTable({ site: 'topgear', format: 'csv', from: '2026-06-01', to: '2026-06-01' })
    expect('filename' in result).toBe(true)
    if ('filename' in result) {
      expect(result.filename).toBe('TopGear.nl reporting 2026-06-01_2026-06-01.csv')
      expect(result.contentType).toBe('text/csv')
    }
  })

  it('defaults the filename range segment to "all" when from/to are omitted', async () => {
    const result = await handleExportTable({ site: 'topgear', format: 'json' })
    if ('filename' in result) {
      expect(result.filename).toBe('TopGear.nl reporting all_all.json')
    }
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- downloadExport.test.ts`
Expected: FAIL — `Cannot find module '@/lib/reporting/api/downloadExport'`

- [ ] **Step 3: Write `lib/reporting/api/downloadExport.ts`**

```typescript
import { buildTableData, tableToCsv, tableToJson, tableToXlsx } from '@/lib/reporting/tableExporter'
import { loadStore } from '@/lib/reporting/reportStore'
import { SITES, type SiteId } from '@/lib/reporting/constants'

export async function handleExportTable(input: {
  site: string
  format: 'csv' | 'xlsx' | 'json'
  from?: string
  to?: string
}): Promise<{ filename: string; contentType: string; body: string | Buffer } | { error: string }> {
  if (!(input.site in SITES)) return { error: 'Unknown site' }
  const siteId = input.site as SiteId

  const store = await loadStore()
  const data = buildTableData(store, siteId, input.from, input.to)

  const siteNameForFile = SITES[siteId].name.replace(/\s+/g, '_')
  const rangeSegment = `${input.from ?? 'all'}_${input.to ?? 'all'}`
  const filenameBase = `${SITES[siteId].name} reporting ${rangeSegment}`

  if (input.format === 'csv') {
    return { filename: `${filenameBase}.csv`, contentType: 'text/csv', body: tableToCsv(data) }
  }
  if (input.format === 'json') {
    return { filename: `${filenameBase}.json`, contentType: 'application/json', body: tableToJson(data) }
  }
  const xlsxBuf = await tableToXlsx(data)
  return {
    filename: `${filenameBase}.xlsx`,
    contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    body: xlsxBuf,
  }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- downloadExport.test.ts`
Expected: PASS

- [ ] **Step 5: Write the four route files**

```typescript
// app/api/reporting/upload-files/route.ts
import { NextResponse } from 'next/server'
import { listAvailableFiles } from '@/lib/reporting/zipBuilder'

export async function GET() {
  const files = await listAvailableFiles()
  return NextResponse.json({ files })
}
```

```typescript
// app/api/reporting/download/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { buildDownloadZip } from '@/lib/reporting/zipBuilder'
import { loadStore, getSetting } from '@/lib/reporting/reportStore'

export async function GET(req: NextRequest) {
  const params = req.nextUrl.searchParams
  const filesParam = params.get('files')
  const requested = filesParam ? filesParam.split(',') : undefined
  const from = params.get('from') ?? undefined
  const to = params.get('to') ?? undefined

  try {
    const store = await loadStore()
    const oguryOldFormat = await getSetting('ogury_old_format', false)
    const zip = await buildDownloadZip(store, requested, from, to, oguryOldFormat)
    return new NextResponse(zip, {
      status: 200,
      headers: {
        'Content-Type': 'application/zip',
        'Content-Disposition': 'attachment; filename="F1Maximaal Reports.zip"',
      },
    })
  } catch (err) {
    return NextResponse.json({ error: err instanceof Error ? err.message : 'Download failed' }, { status: 404 })
  }
}
```

```typescript
// app/api/reporting/export-table/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { handleExportTable } from '@/lib/reporting/api/downloadExport'

export async function GET(req: NextRequest) {
  const params = req.nextUrl.searchParams
  const site = params.get('site')
  const format = params.get('format') as 'csv' | 'xlsx' | 'json' | null
  if (!site || !format) return NextResponse.json({ error: 'site and format are required' }, { status: 422 })

  const result = await handleExportTable({
    site,
    format,
    from: params.get('from') ?? undefined,
    to: params.get('to') ?? undefined,
  })
  if ('error' in result) return NextResponse.json(result, { status: 404 })

  return new NextResponse(result.body as BodyInit, {
    status: 200,
    headers: {
      'Content-Type': result.contentType,
      'Content-Disposition': `attachment; filename="${result.filename}"`,
    },
  })
}
```

```typescript
// app/api/reporting/[site]/[dateKey]/route.ts
import { NextRequest, NextResponse } from 'next/server'
import { createAdminClient } from '@/lib/supabase/admin'

export async function DELETE(
  _req: NextRequest,
  { params }: { params: Promise<{ site: string; dateKey: string }> }
) {
  const { site, dateKey } = await params
  const supabase = createAdminClient()
  const { error, count } = await supabase
    .from('report_days')
    .delete({ count: 'exact' })
    .eq('site', site)
    .eq('date', dateKey)

  if (error) return NextResponse.json({ error: error.message }, { status: 500 })
  if (!count) return NextResponse.json({ error: 'Not found' }, { status: 404 })
  return NextResponse.json({ success: true })
}
```

- [ ] **Step 6: Commit**

```bash
git add lib/reporting/api/downloadExport.ts "app/api/reporting/upload-files" "app/api/reporting/download" "app/api/reporting/export-table" "app/api/reporting/[site]" tests/integration/reporting/api/downloadExport.test.ts
git commit -m "feat: add upload-files, download, export-table, and delete API routes"
```

---

## Task 27: Dashboard Page

**Files:**
- Create: `app/(dashboard)/reporting/page.tsx`
- Create: `app/(dashboard)/reporting/UploadForm.tsx`
- Create: `app/(dashboard)/reporting/ReportTable.tsx`
- Test: `tests/unit/reporting/ReportTable.test.tsx`

**Interfaces:**
- Consumes: `loadStore` from `lib/reporting/reportStore.ts`; `SITES` from `lib/reporting/constants.ts`; `buildTableData` from `lib/reporting/tableExporter.ts`.
- Produces: the `/reporting` page — server component loads the store and renders one `ReportTable` per site, plus an `UploadForm` client component posting to `/api/reporting/process`. This is intentionally minimal (internal tool, functional over polished) per the spec — it exists to make the pipeline usable end-to-end, not to replicate every control from the source Vue `Reporting/Index.vue` page (config/links editors, verify upload panel, zip-download file picker can be added incrementally after this ships).

- [ ] **Step 1: Write the failing test**

```typescript
// tests/unit/reporting/ReportTable.test.tsx
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ReportTable } from '@/app/(dashboard)/reporting/ReportTable'
import { buildTableData } from '@/lib/reporting/tableExporter'
import { emptyStore } from '@/lib/reporting/reportStore'

describe('ReportTable', () => {
  it('renders a row per day with revenue total', () => {
    const store = emptyStore()
    store.sites.topgear.days['2026-06-01'] = {
      dateKey: '2026-06-01',
      revenue: { adhese: 0, gam: 0, seedtag: 10, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
    }
    const data = buildTableData(store, 'topgear')
    render(<ReportTable data={data} />)
    expect(screen.getByText('2026-06-01')).toBeInTheDocument()
    expect(screen.getByText('10')).toBeInTheDocument()
  })
})
```

- [ ] **Step 2: Install React Testing Library and jsdom environment, then run test to verify it fails**

```bash
npm install -D @testing-library/react @testing-library/jest-dom jsdom
```

Update `vitest.config.ts` to add a jsdom environment override for `.test.tsx` files:

```typescript
import { defineConfig } from 'vitest/config'
import tsconfigPaths from 'vite-tsconfig-paths'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [tsconfigPaths(), react()],
  test: {
    environment: 'node',
    globals: true,
    environmentMatchGlobs: [['**/*.test.tsx', 'jsdom']],
  },
})
```

Run: `npm test -- ReportTable.test.tsx`
Expected: FAIL — `Cannot find module '@/app/(dashboard)/reporting/ReportTable'`

- [ ] **Step 3: Write `app/(dashboard)/reporting/ReportTable.tsx`**

```tsx
'use client'

import type { TableData } from '@/lib/reporting/tableExporter'

export function ReportTable({ data }: { data: TableData }) {
  const partnerKeys = Object.keys(data.partners)
  return (
    <table className="w-full text-sm">
      <caption className="text-left font-semibold">{data.siteName}</caption>
      <thead>
        <tr>
          <th className="text-left">Date</th>
          {partnerKeys.map((k) => (
            <th key={k}>{data.partners[k]}</th>
          ))}
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        {data.days.map((d) => (
          <tr key={d.date}>
            <td>{d.date}</td>
            {partnerKeys.map((k) => (
              <td key={k}>{d.revenue[k]}</td>
            ))}
            <td>{d.total}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- ReportTable.test.tsx`
Expected: PASS

- [ ] **Step 5: Write `app/(dashboard)/reporting/UploadForm.tsx`**

```tsx
'use client'

import { useState } from 'react'

export function UploadForm() {
  const [status, setStatus] = useState<string | null>(null)

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    const formData = new FormData(e.currentTarget)
    setStatus('Uploading...')
    const res = await fetch('/api/reporting/process', { method: 'POST', body: formData })
    const json = await res.json()
    if (!res.ok) {
      setStatus(`Error: ${json.error}`)
      return
    }
    setStatus('Processed: ' + JSON.stringify(json.fileTypes))
    window.location.reload()
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-2 rounded border p-4">
      <input type="file" name="files" multiple required />
      <button type="submit" className="rounded bg-black px-3 py-1 text-white">
        Upload
      </button>
      {status && <p className="text-sm">{status}</p>}
    </form>
  )
}
```

- [ ] **Step 6: Write `app/(dashboard)/reporting/page.tsx`**

```tsx
import { loadStore } from '@/lib/reporting/reportStore'
import { buildTableData } from '@/lib/reporting/tableExporter'
import { SITES, type SiteId } from '@/lib/reporting/constants'
import { ReportTable } from './ReportTable'
import { UploadForm } from './UploadForm'

export default async function ReportingPage() {
  const store = await loadStore()
  const siteIds = Object.keys(SITES) as SiteId[]

  return (
    <main className="space-y-8 p-8">
      <h1 className="text-2xl font-semibold">Planet Nine Partner Reporting</h1>
      <UploadForm />
      {siteIds.map((siteId) => (
        <ReportTable key={siteId} data={buildTableData(store, siteId)} />
      ))}
    </main>
  )
}
```

- [ ] **Step 7: Manually verify in the browser**

Run: `npm run dev`, sign in with the admin user (Task 3/5), visit `/reporting`.
Expected: page renders one table per site (empty until data exists), and the upload form is present. Upload a real partner fixture file from `tests/fixtures/reporting/uploads/` and confirm the corresponding table populates after reload.

- [ ] **Step 8: Commit**

```bash
git add "app/(dashboard)/reporting" tests/unit/reporting/ReportTable.test.tsx vitest.config.ts package.json package-lock.json
git commit -m "feat: add minimal reporting dashboard page (upload form + per-site tables)"
```

---

## Task 28: Historical Data Migration Script

**Files:**
- Create: `scripts/migrate-historical-data.ts`
- Test: `tests/integration/scripts/migrateHistoricalData.test.ts`

**Interfaces:**
- Consumes: `createAdminClient` from `lib/supabase/admin.ts`.
- Produces: a standalone, one-time-use script (not part of the running app) that reads a JSON export of the source Laravel app's `report_days`/`report_settings` MySQL tables and upserts them into Supabase. Exported as a testable `migrateFromExport(exportData: HistoricalExport): Promise<{daysImported: number; settingsImported: number}>` function, with a thin CLI entry point.

- [ ] **Step 1: Write the failing test**

```typescript
// tests/integration/scripts/migrateHistoricalData.test.ts
import { describe, it, expect, afterEach } from 'vitest'
import { createAdminClient } from '@/lib/supabase/admin'
import { migrateFromExport } from '@/scripts/migrate-historical-data'

describe('migrateFromExport', () => {
  afterEach(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', '2020-01-01')
    await supabase.from('report_settings').delete().eq('key', 'oguryRate')
  })

  it('upserts days and settings from an export payload', async () => {
    const result = await migrateFromExport({
      exported_at: '2026-01-01T00:00:00Z',
      settings: { oguryRate: 0.8 },
      days: [
        {
          site: 'topgear',
          date: '2020-01-01',
          revenue: { adhese: 0, gam: 0, seedtag: 5, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 },
          impressions: null,
          total_ad_requests: 0,
          analytics: null,
          impressions_sold: 0,
        },
      ],
    })

    expect(result).toEqual({ daysImported: 1, settingsImported: 1 })

    const supabase = createAdminClient()
    const { data } = await supabase.from('report_days').select('*').eq('site', 'topgear').eq('date', '2020-01-01').single()
    expect(data!.revenue.seedtag).toBe(5)
  })

  it('does not wipe existing data — a re-run with fewer days leaves prior days untouched', async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').upsert(
      { site: 'topgear', date: '2020-01-02', revenue: { adhese: 0, gam: 0, seedtag: 1, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 } },
      { onConflict: 'site,date' }
    )

    await migrateFromExport({
      exported_at: '2026-01-01T00:00:00Z',
      settings: {},
      days: [
        { site: 'topgear', date: '2020-01-01', revenue: { adhese: 0, gam: 0, seedtag: 5, teads: 0, showheroes: 0, adform: 0, ogury: 0, outbrain: 0, preferredDeals: 0 }, impressions: null, total_ad_requests: 0, analytics: null, impressions_sold: 0 },
      ],
    })

    const { data } = await supabase.from('report_days').select('*').eq('site', 'topgear').eq('date', '2020-01-02').maybeSingle()
    expect(data).not.toBeNull()

    await supabase.from('report_days').delete().eq('site', 'topgear').eq('date', '2020-01-02')
  })
})
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm test -- migrateHistoricalData.test.ts`
Expected: FAIL — `Cannot find module '@/scripts/migrate-historical-data'`

- [ ] **Step 3: Write `scripts/migrate-historical-data.ts`**

Generate the export JSON from the source Laravel app first, by running (in the `creative-vuejs-laravel` repo) `php artisan reporting:export path/to/historical-export.json` — this reuses the source system's own `ExportReporting` command, which already produces exactly this shape (`{exported_at, settings, days}}`), so no new export tooling needs to be written on the PHP side.

```typescript
import { readFileSync } from 'node:fs'
import { createAdminClient } from '@/lib/supabase/admin'

type HistoricalExport = {
  exported_at: string
  settings: Record<string, unknown>
  days: {
    site: string
    date: string
    revenue: Record<string, number> | null
    impressions: Record<string, number> | null
    total_ad_requests: number
    analytics: Record<string, unknown> | null
    impressions_sold: number
  }[]
}

export async function migrateFromExport(
  data: HistoricalExport
): Promise<{ daysImported: number; settingsImported: number }> {
  const supabase = createAdminClient()

  for (const [key, value] of Object.entries(data.settings)) {
    await supabase.from('report_settings').upsert({ key, value }, { onConflict: 'key' })
  }

  for (const day of data.days) {
    await supabase.from('report_days').upsert(
      {
        site: day.site,
        date: day.date,
        revenue: day.revenue,
        impressions: day.impressions,
        total_ad_requests: day.total_ad_requests,
        analytics: day.analytics,
        impressions_sold: day.impressions_sold,
      },
      { onConflict: 'site,date' }
    )
  }

  return { daysImported: data.days.length, settingsImported: Object.keys(data.settings).length }
}

async function main() {
  const path = process.argv[2]
  if (!path) {
    console.error('Usage: tsx scripts/migrate-historical-data.ts <path-to-export.json>')
    process.exit(1)
  }
  const data = JSON.parse(readFileSync(path, 'utf-8')) as HistoricalExport
  const result = await migrateFromExport(data)
  console.log(`Imported ${result.daysImported} days and ${result.settingsImported} settings.`)
}

if (require.main === module) {
  main()
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `npm test -- migrateHistoricalData.test.ts`
Expected: PASS

- [ ] **Step 5: Run the real one-time migration**

```bash
cd "C:\Users\P0\Desktop\Projects\creative-vuejs-laravel"
php artisan reporting:export storage/app/historical-export.json

cd "C:\Users\P0\Desktop\Projects\planet-nine-partner-reporting"
npm install -D tsx
npx tsx scripts/migrate-historical-data.ts "C:\Users\P0\Desktop\Projects\creative-vuejs-laravel\storage\app\historical-export.json"
```

Expected: console output reporting the number of days/settings imported; verify a handful of known dates in the Supabase dashboard match the source app's `/reporting` page.

- [ ] **Step 6: Commit**

```bash
git add scripts/migrate-historical-data.ts tests/integration/scripts/migrateHistoricalData.test.ts
git commit -m "feat: add one-time historical data migration script"
```

---

## Task 29: End-to-End API Integration Test

**Files:**
- Create: `tests/integration/reporting/endToEnd.test.ts`

**Interfaces:**
- Consumes: `handleProcessUpload` (Task 24), `handleSaveAdheseBatch` (Task 23), `handleConfigUpdate` (Task 22), `buildDownloadZip`/`listAvailableFiles` (Task 21), `loadStore` (Task 15) — exercises the full pipeline together as a final confidence check before declaring the port complete, mirroring the source repo's `ReportingControllerTest` HTTP-level coverage (including its literal regression-guard assertion).

- [ ] **Step 1: Write the test**

```typescript
// tests/integration/reporting/endToEnd.test.ts
import { describe, it, expect, afterAll } from 'vitest'
import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'
import { createAdminClient } from '@/lib/supabase/admin'
import { handleProcessUpload } from '@/lib/reporting/api/process'
import { handleConfigUpdate } from '@/lib/reporting/api/config'
import { handleSaveAdheseBatch } from '@/lib/reporting/api/saveAdhese'
import { buildDownloadZip, listAvailableFiles } from '@/lib/reporting/zipBuilder'
import { loadStore } from '@/lib/reporting/reportStore'

const FIXTURES_DIR = join(process.cwd(), 'tests', 'fixtures', 'reporting', 'uploads')

describe('end-to-end reporting pipeline', () => {
  afterAll(async () => {
    const supabase = createAdminClient()
    await supabase.from('report_days').delete().eq('site', 'f1maximaal').eq('date', '2026-06-01')
    await supabase.from('report_settings').delete().eq('key', 'oguryRate')
    for (const name of await listAvailableFiles()) {
      await supabase.storage.from('raw-uploads').remove([name])
    }
  })

  it('uploads real fixture files under their original filenames and persists the known-regression Showheroes value', async () => {
    // NOTE: verify these exact fixture filenames against what Task 2 actually copied —
    // this reproduces the source repo's ReportingControllerTest, which explicitly calls
    // out "the column that regressed in prod" for this value.
    const fixtureFiles = readdirSync(FIXTURES_DIR)
    const files = fixtureFiles.map((name) => ({
      name,
      buffer: readFileSync(join(FIXTURES_DIR, name)),
    }))

    const today = new Date(Date.UTC(2026, 5, 25))
    await handleProcessUpload(files, today)

    const store = await loadStore()
    const day = store.sites.f1maximaal.days['2026-06-01']
    expect(day?.revenue.showheroes).toBeCloseTo(20.27, 2)
  })

  it('config update persists and is reflected in a subsequent load', async () => {
    await handleConfigUpdate({ oguryRate: 0.9 })
    const store = await loadStore()
    expect(store.config.oguryRate).toBe(0.9)
  })

  it('save-adhese-batch updates impressions and impressions_sold increases', async () => {
    const before = await loadStore()
    const beforeSold = before.sites.f1maximaal.days['2026-06-01']?.impressionsSold ?? 0

    await handleSaveAdheseBatch({ entries: [{ dateKey: '2026-06-01', adhese: 12345 }] })

    const after = await loadStore()
    expect(after.sites.f1maximaal.days['2026-06-01'].impressions!.adhese).toBe(12345)
    expect(after.sites.f1maximaal.days['2026-06-01'].impressionsSold!).toBeGreaterThan(beforeSold)
  })

  it('download returns a non-empty zip after a successful upload', async () => {
    const store = await loadStore()
    const zip = await buildDownloadZip(store)
    expect(zip.length).toBeGreaterThan(0)
  })
})
```

- [ ] **Step 2: Run the full test suite**

Run: `npm test`
Expected: PASS for every test file written across Tasks 1–29. If the Showheroes regression-guard value doesn't match `20.27`, do not adjust the assertion — trace the discrepancy back through `extractShowheroes` (Task 9) and `mergeIntoStore` (Task 14) against the exact PHP source, since this number is the project's primary correctness tripwire.

- [ ] **Step 3: Commit**

```bash
git add tests/integration/reporting/endToEnd.test.ts
git commit -m "test: add end-to-end reporting pipeline integration test"
```

---

## Plan Self-Review Notes

- **Spec coverage**: §3 Architecture → Tasks 1, 4, 5. §4 Data Model → Task 3. §5 Constants/Pipeline → Tasks 6–21. §6 API Routes → Tasks 22–26 (§6's dropped `/reporting/sync` correctly has no task). §7 Error Handling → woven into every API task via Zod validation + try/catch-to-JSON-error pattern. §8 Testing → parity suite (Task 13) + regression-guard/carry-forward assertions (Tasks 14, 29) + reused fixtures (Task 2). §9 Historical Migration → Task 28. §10 Open Items → Ogury xlsx styling resolved via `exceljs` (Tasks 18, 20); Storage signed-URL question resolved by streaming zip/export bytes directly through the Route Handler response rather than issuing a redirect (Tasks 26, 21) — simpler and avoids an extra round trip for small, infrequent files.
- **Placeholder scan**: no TBD/TODO markers; the one deliberately-approximate spot (`csvRows`' naive comma-split, Task 8) is called out explicitly in its own test comment rather than hidden, since none of the real partner fixtures need quoted-comma handling.
- **Type consistency check**: `ReportStore`/`StoreDay` (Task 14) are the single shared shape threading through `reportStore.ts` (15), `reportProcessor.ts` (16), `csvGenerator.ts` (17), `tableExporter.ts` (18), `verifier.ts` (19), `zipBuilder.ts` (21) — verified consistent field names (`dateKey`, `revenue`, `impressions`, `analytics`, `totalAdRequests`, `impressionsSold`) throughout. `SiteId` from Task 6 used consistently as the site-keying type everywhere (never re-declared).

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-07-15-planet-nine-partner-reporting.md`. Two execution options:

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
