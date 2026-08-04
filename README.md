# 🎨 Creative Vue.js Laravel Application

A creative project management platform for agencies and creative teams — client previews, asset delivery, reporting, billing, and team collaboration in one app.

---

## 📑 Contents

- [Tech Stack](#-tech-stack)
- [Features](#-features)
- [Permission System](#-permission-system)
- [Installation](#-installation)
- [Real-Time Notifications (Reverb)](#-real-time-notifications-reverb)
- [System Monitoring (Pulse)](#-system-monitoring-pulse)
- [Production Deployment](#-production-deployment)
- [Maintenance Commands](#-maintenance-commands)
- [Adding a New Feature Page](#-adding-a-new-feature-page)
- [Security](#-security)
- [Performance](#-performance)

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3.5, TypeScript (strict), Inertia.js 2 |
| Styling | Tailwind CSS 3, shadcn-vue (Radix Vue) |
| Real-time | Laravel Reverb (WebSockets) + Laravel Echo |
| Monitoring | Laravel Pulse |
| 3D | Three.js |
| Charts | Chart.js, Recharts |
| Rich text | TipTap |
| Database | MySQL (PostgreSQL compatible) |
| Cache / Queue | Database driver by default, Redis recommended |
| PDF | barryvdh/laravel-dompdf |
| Spreadsheets | PhpSpreadsheet |
| Audit trail | spatie/laravel-activitylog |
| Error tracking | Sentry |
| Build | Vite 6 |
| Tests | Pest 3 |

---

## 📊 Features

### Client Previews — `/previews`

The core of the platform. Branded, shareable portals where clients review creative work.

- Hierarchical structure: **Preview → Category → Feedback Set → Version → Asset**
- Asset types per version: **banners, videos, GIFs, social media**
- Feedback loop with **approve / disapprove**, comments, and file attachments on responses
- Bulk edit across a preview's assets
- Per-preview activity log
- **Guest access** — clients view without an account; optionally gate behind a login (`requires_login`)
- **Live viewer tracking** — see who is looking at a preview right now
- **Tour guide** — a first-run walkthrough overlay for new client viewers
- Per-client branding (logo, colour palette)

### Tasks — `/tasks`

A Trello-style kanban workspace.

**Boards → Lists → Cards.** A board holds user-created lists; lists hold cards.

- **Multiple boards** with a switcher in the board bar; shared boards are marked
- Every new board starts with **Today · Tomorrow · This week · Later** — plain names, nothing re-buckets on its own. Rename inline, delete, reorder by dragging, or add your own with **Add list**
- The list strip **scrolls horizontally**; each list scrolls vertically on its own
- The four default lists are **protected**: renameable and reorderable around, but never deletable and never draggable themselves. Enforced by an `is_protected` column and refused server-side, so a rename can't defeat it
- **One-input card composer**, like Trello: type a title, Enter, card exists. Stays open so you can add several in a row
- **Card detail panel** for everything else — description, due date, who created it
- Cards drag by a grip handle within a list or across lists; custom lists drag to reorder. Both persist immediately
- Due-date badges, red when overdue

**Completion is an archive.** Completing a card removes it from its list rather than striking it through, which keeps long lists readable.

- One-click tick on the card (appears on hover), or a **Complete** button in the card detail panel
- Completed cards keep their list and position, so **Restore** puts them back exactly where they were
- A stale board cannot drag a completed card back — the reorder endpoint filters them out

**Bottom dock.** A floating pill, centred at the bottom of the board, holds everything that is about the board rather than a card:

- **Completed** — the current board's archive, newest first, each entry showing its list, completion date, and author, with restore and permanent delete
- **Boards** — board switcher, members, and board actions (new, rename, delete or leave). Labelled with the current board name
- The board has no top bar at all; the lists start at the top of the page for maximum vertical room

**Sharing is board-level.** Invite people to a board and they see all its lists and cards, in the same order as everyone else.

- Board members are managed by the owner only — being invited doesn't let you remove whoever invited you
- **Card members** are assignees, chosen from the board's members. Assigning notifies them
- Removing someone from a board also unassigns them from that board's cards
- The board owner deletes a board; invited members **leave** it, which notifies the owner
- A card's creator deletes it; an assignee **leaves** it, which notifies the creator
- Deleting a list deletes its cards, after a confirmation naming the count

### Reporting — `/reporting`

Partner performance reporting built on uploaded data.

- Day-level metrics with configurable report settings
- **RPM anomaly detection** — flags days that deviate from expected revenue per mille
- Charts, day cards, and a sortable data table
- File upload ingestion (spreadsheets)
- Export to spreadsheet, download as PDF

### Orbit — `/orbit`

Ad-embed inventory and delivery tracking.

- Publish a banner as an embeddable tag: `/tag/banner/{id}.js`
- Public banner serving endpoint for third-party sites and ad platforms
- Event tracking per embed
- Inventory view lists every embed regardless of activity, with per-row and total counts for the selected period — newly published embeds show as `0/0` rather than disappearing
- Activate / deactivate embeds without deleting them

### File Transfers — `/file-transfers`

Secure large-file sharing with an immersive **Three.js 3D interface**.

- Slug-based public share links — `/file-transfers-view/{slug}`
- Bulk delete
- Expiry and access control

### Bills — `/bills`

Invoicing.

- Invoices with line items and **sub-bills**
- Supporting document attachments, individually downloadable
- PDF generation via dompdf
- Amount-in-words conversion (kwn/number-to-words)

### Media Library — `/medias`

Centralised asset storage with upload, preview, and download.

### Templates — `/templates`

Reusable creative templates, uploadable and downloadable.

### Clients — `/clients`

Client records, each with their own branding and preview scope. Users can be tied to a `client_id` so client-side accounts only see their own material.

### Colour Palettes — `/color-palettes`

Named brand palettes applied to client previews.

### Creative Sizes — `/creative-sizes`

Unified management of **banner sizes** and **video sizes** from one page. The two database tables and models stay separate on purpose — only the UI is merged. Legacy direct routes remain at `/banner-sizes` and `/video-sizes`.

### Socials — `/socials`

Social media creative management and post creation.

### Support Tickets — `/support-tickets`

Internal ticketing.

- Fields: name, description, screenshot, status (pending / in progress / done), priority (low / medium / high / urgent)
- Admins update status and priority inline; regular users see read-only status
- Super-admin only deletion
- Notifies all super admins on creation, notifies the reporter on status change

### Notifications

Real-time in-app notifications over WebSockets.

- Private per-user channels (`user.{id}`)
- Unread badge, grouped by Today / Yesterday / Earlier, filterable by read state
- Typed icons per event: previews, categories, feedback (created / approved / disapproved), feedback sets, versions, assets, tasks shared, tasks left
- Deep links straight to the relevant record

### Access Manager — `/user-managements`

- **Users** — create, edit, delete; assign role, designation, and client
- **Designations** — job titles with their own permission bundles
- **Routes** — the master list of grantable URL paths that feeds the permission picker
- **Welcome flow** — new users are created with a random 40-character placeholder password and must set their own via a one-time registration link, which is revoked once used
- **Forced password change** — an admin reset grants `/change-password`, which is stripped after the user sets a new one

### Settings — `/settings`

- **Profile** — name, email, account deletion
- **Password** — self-service change
- **Appearance** — light / dark / system
- **Sidebar** — drag to reorder navigation, hide unused entries; saved per user, applies on every device
- **Timezone preferences** — per-user timestamp display

### Dashboard — `/dashboard`

Landing overview with activity and key metrics.

### Activity Logs — `/activity-logs`

Application-wide audit trail via spatie/laravel-activitylog, with configurable page size and a JSON endpoint for dashboard widgets.

### Cache Management — `/cache-management`

Storage analytics and cache-clearing tools. Always accessible regardless of permissions, so a misconfigured cache can never lock you out.

### Log Viewer — `/logs`

Read, filter, download, and clear Laravel logs from the browser.

### Pulse — `/pulse`

Performance monitoring dashboard. Requires the `pulse` permission.

### API Documentation — `/documentations`

Interactive API reference (also reachable at `/lazyDoc`).

### Tetris — `/play/tetris`

A playable Tetris with score tracking.

---

## 🔐 Permission System

Custom, and worth understanding before adding anything.

- `users.permissions` is a **JSON array of URL paths** — `['/previews', '/bills']`
- `'*'` grants everything
- `CheckUserPermission` middleware matches the request path against that array: **exact match** or **`/`-bounded prefix**. So `/previews` grants `/previews/edit/1` but *not* `/previews-delete/1` — the boundary is deliberate, since a bare prefix match over-granted
- `users.role` (`super_admin`, etc.) is checked ad-hoc for destructive actions
- The `routes` database table (`title`, `href`) is the picker list in Access Manager. **A page with no row here cannot be granted to anyone.**

---

## 🚀 Installation

### Prerequisites

- PHP 8.2+
- Node.js 18+
- Composer
- MySQL or PostgreSQL
- Redis (recommended for production)
- Nginx or Apache

### Steps

```bash
# 1. Clone
git clone https://github.com/govindaroyaiub/creative-vuejs-laravel.git
cd creative-vuejs-laravel

# 2. Dependencies
composer install
npm install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Migrate (configure .env first — see below)
php artisan migrate

# 5. Build
npm run build
```

### Database and Redis in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=creative_laravel
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Run it

```bash
# Terminal 1 — app
composer run dev

# Terminal 2 — WebSocket server
php artisan reverb:start
```

| Service | URL |
|---|---|
| Application | `http://localhost:8000` |
| Pulse dashboard | `http://localhost:8000/pulse` |
| API docs | `http://localhost:8000/lazyDoc` |

Seeded local accounts use the password `password`.

---

## 📡 Real-Time Notifications (Reverb)

### `.env`

```env
BROADCAST_CONNECTION=reverb
VITE_BROADCAST_CONNECTION=reverb

REVERB_APP_ID=creative-app
REVERB_APP_KEY=creative-key
REVERB_APP_SECRET=creative-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Start and test

```bash
php artisan reverb:start
npm run dev     # or npm run build for production
```

1. Open two browsers logged in as different users
2. Trigger a notification — create a preview, approve feedback, share a task
3. The other user gets it instantly, no refresh

### Troubleshooting

| Symptom | Cause |
|---|---|
| Connection refused | Reverb server isn't running |
| 401 Unauthorized | Not logged in, or `channels.php` authorization is wrong |
| Nothing arrives | Check the browser console for WebSocket errors |
| Port conflict | Change `REVERB_PORT` off 8080 |

---

## 🎯 System Monitoring (Pulse)

Installed, tables migrated, dashboard at `/pulse`.

### Grant access

Only users with `pulse` in their permissions array can view it.

```bash
php artisan tinker
```

```php
$user = User::find(YOUR_USER_ID);
$user->permissions = array_merge($user->permissions ?? [], ['pulse']);
$user->save();
```

### What it tracks

Slow requests · slow queries · slow jobs · exceptions · cache hit rates · queue throughput · server CPU and memory · active users · outgoing HTTP calls.

### Optional tuning

```env
PULSE_SLOW_REQUESTS_THRESHOLD=1000
PULSE_SLOW_QUERIES_THRESHOLD=1000
PULSE_SLOW_JOBS_THRESHOLD=1000
PULSE_STORAGE_KEEP="7 days"
PULSE_SLOW_REQUESTS_SAMPLE_RATE=1   # 1 = 100%, 0.5 = 50%
```

`config/pulse.php` covers ignored routes, query grouping, storage drivers, and recorders.

### Troubleshooting

| Symptom | Fix |
|---|---|
| No data | Generate traffic, wait a few seconds for aggregation, confirm recorders are enabled |
| Won't load | Grant the `pulse` permission, confirm you're logged in, `php artisan config:clear` |
| High DB load | Run `pulse:work` in the background, lower sample rates |

---

## 📦 Production Deployment

1. `APP_ENV=production`
2. Point Nginx/Apache at the `public/` directory
3. Set up queue workers
4. Enable SSL/TLS, and set `REVERB_SCHEME=https`
5. Correct permissions on `storage/` and `bootstrap/cache/`
6. Run Reverb, Pulse, and the queue under Supervisor

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=5
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log

[program:reverb]
command=php /path/to/app/artisan reverb:start
directory=/path/to/app
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/reverb.log

[program:pulse]
command=php /path/to/app/artisan pulse:work
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/pulse.log
```

---

## 🛠️ Maintenance Commands

```bash
php artisan optimize:clear            # clear all caches
php artisan db:optimization-report    # database optimisation report
php artisan cache:monitor             # cache usage
php artisan log:viewer                # system logs
php artisan pulse:work                # Pulse background worker
php artisan migrate                   # run migrations
php artisan migrate:rollback          # roll back the last batch
php artisan migrate:fresh --seed      # rebuild with seeders

npm run dev        # Vite dev server
npm run build      # production build
npm run lint       # ESLint with --fix
npm run format     # Prettier over resources/
./vendor/bin/pint  # PHP formatting
./vendor/bin/pest  # test suite
```

---

## ➕ Adding a New Feature Page

Four places, and missing any one of them breaks something quietly:

1. **Route** in `routes/web.php`, inside the `auth` + `verified` + `CheckUserPermission` group
2. **Nav entry** in `resources/js/lib/sidebar-nav.ts` (`MAIN_NAV_ITEMS` or `FOOTER_NAV_ITEMS`)
3. **Mirror the href** in `config/sidebar.php` — the backend validates saved sidebar preferences against this list, so a missing entry means users can't save a layout that includes your page
4. **Row in the `routes` table** (`title`, `href`) — otherwise admins have no way to grant the permission

The `href` is the stable key across all four.

---

## 🔐 Security

- All application routes behind authentication, email verification, and path-based permission checks
- Ownership scoping on user-private data — every read and write is filtered by the acting user, never by a request parameter
- New accounts get a 40-character random placeholder password, never a known default
- One-time registration permission, revoked once the password is set
- Login-gated previews verify the gate before exposing viewer-tracking data
- CSRF protection, validation and sanitisation on every request
- Secure file handling with type and size validation
- Environment-based configuration; no secrets in the repository

## 📈 Performance

- Composite database indexes on hot query paths
- Eager loading throughout; list endpoints hold a constant query count regardless of row count
- Batched writes instead of per-row loops on multi-record updates
- Redis-backed cache and sessions in production
- Pulse sampling to keep monitoring overhead low
- TypeScript strict mode, code splitting, and asset minification via Vite

---

## 🤝 Support

[govindaroy.ofc94@gmail.com](mailto:govindaroy.ofc94@gmail.com)

---

_Built with Laravel, Vue.js, and Inertia._
