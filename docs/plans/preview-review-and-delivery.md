# Preview review & delivery: vision and plan

Status: **planning.** Built so far: Show2 improvements (committed) and a UI demo
(`/previews/review/{slug}`, not committed).
Last updated: 2026-11-20

---

## 1. The vision in one paragraph

A preview becomes a **review workspace around each creative**, not a list of
uploads per round. Every creative (each size of a banner, GIF, video or social
image) has **one identity across rounds**, its own **conversation**, its own
**approval**, and a **history** the client can compare round against round.
When the work is approved, the **delivery is built from the files already
uploaded**: banner zips plus backup images, in a clean folder structure, with
clean filenames. Nobody uploads anything twice, and the client never sees an
internal name.

```
              TEAM (Update2)                         CLIENT (Show2)
   upload round ─► match to previous round   review creative ─► comment / approve
   answer last round's comments              compare with earlier rounds
   approve round ─► delivery built           download clean delivery
                         │                              ▲
                         └──────── one creative ────────┘
                         (identity · files · comments · approvals · history)
```

---

## 2. Today (facts that shape the plan)

**Pages**

| Route | Page | Status |
|---|---|---|
| `/previews/show/{slug}` | `Show.vue` | Live client viewer. All links and emails point here. |
| `/previews/show2/{slug}` | `Show2.vue` | Redesigned viewer. Not linked yet. |
| `/previews/update/{id}`, `/update2/{id}` | `Update2.vue` | Live team editor (both routes). |
| `/previews/review/{slug}` | `Review.vue` | Demo for managers. Staff only. Writes nothing. |
| — | `Update.vue` | Dead code. No route renders it. |

**Data tree.** The UI labels are swapped relative to the table names.
```
new_previews
 └ new_categories          "Project"   type: banner | gif | video | social
    └ new_feedback         "Round"     description, is_active, is_approved
       └ new_feedback_sets "Version"
          └ new_versions   "Set"
             └ new_banners | new_gifs | new_videos | new_socials
```

**What makes the work hard today**
1. **Four creative tables with almost the same columns.** Everything is written four times: `bulkEdit` has about 350 lines of near-duplicate asset handling, plus 4 models, 4 controllers, 4 render endpoints and 4 delete routes. The copies already drift (the GIF branch never updates `name`).
2. **No link between rounds.** Round 2's 300×250 is a new row that knows nothing about Round 1's.
3. **The original banner zip is deleted** after extraction, so it can't be delivered.
4. **Approval makes the team re-upload the final files.** The transfer stores the paths as comma-separated text, and the files are named `name_{timestamp}_{uniqid}`. **Clients see those names.**
5. **Approval is one boolean on the round.** There's no per-creative status, and no record of who approved or when.
6. **One 600-line `bulkEdit`** saves the whole tree, with no ownership checks on nested ids.
7. **Public viewers write shared state** (`is_active`, theme) for everyone.
8. **Hard deletes and cascades everywhere.** Deleting a *size* deletes every creative of that size.

---

## 3. The ideas (features)

### 3.1 Comments on each creative
- Each creative has a thread with replies, resolve, and video timestamps.
- The round description stays only as an optional, short **Round summary**.
- Threads follow the creative across rounds: the client's "logo bigger" (Round 1) shows next to the team's "Done" (Round 2).

### 3.2 Answering feedback on upload
When the team uploads a new round in Update2, they see **the previous round's commented creatives** and reply there ("Done" / "Not changing, because…"). The reply lands in the client's thread.

### 3.3 Approval per creative
- The client marks each creative **Approved** or **Changes requested**, and the round shows progress ("4/6 approved").
- The team's final **Approve round** step stays, and is what triggers delivery.

### 3.4 Review mode
Click a creative to open it full screen:
- real size, ◀ ▶ through the round
- decision buttons, the thread, and round history

### 3.5 Compare rounds
- The client picks **a round, then any creative in it**; the matching creative is suggested.
- Banners/GIFs: replay both. Video: synced play. Images: overlay slider.
- Only across rounds, never within one round.

### 3.6 Delivery built from uploads
- Approving builds the file transfer from the uploaded creatives.
- Banners deliver **zip + backup image** per size. Videos, GIFs and images deliver the file.
- Folders follow the round's Version → Set structure, and every name is clean:
  ```
  Spring Campaign/
    Version A/Set 1/SpringCampaign_300x250.zip
    Version A/Set 1/SpringCampaign_300x250_backup.jpg
  ```
- "Download all" gives one zip with this structure. Extra files (fonts, PSDs) can still be added by hand.

### 3.7 Already done in Show2
- One payload for the whole round.
- Exact-size loading placeholders.
- Collapsible Sets with remembered state, and scroll-to-open.
- No invented names for unnamed items.
- Social image sizes are stored.
- Sidebar footer.

---

## 4. Target data model

New tables and changes. The existing preview → category → round → version → set tables stay.

```
creatives                          the identity of one creative across rounds
  id, category_id, label, type, timestamps

creative_files                     replaces new_banners / new_gifs / new_videos / new_socials
  id, version_id ("Set"), creative_id → creatives, type,
  size_id (nullable), width, height, position,
  original_name, file_path, zip_path, backup_path, file_size,
  meta JSON (video: codec, fps, aspect, companion_path),
  timestamps, deleted_at

creative_comments
  id, creative_file_id, parent_id, author_user_id | guest_name + guest_email,
  body, timecode, resolved_at, resolved_by, file_path_at_time, timestamps

creative_reviews                   client decision per creative per round
  id, creative_file_id, status (approved | changes),
  author_user_id | guest_name, timestamps

round_approvals                    the team's final sign-off (replaces is_approved)
  id, feedback_id, approved_by, approved_at, file_transfer_id

file_transfer_items                replaces the comma-separated file_transfers.file_path
  id, file_transfer_id, creative_file_id (nullable), kind (main | backup | extra),
  path, delivery_name
```

**File storage:** Laravel `Storage` disk instead of raw `public_path()`, so it's ready for S3 later.
```
previews/{preview_id}/{creative_file_id}/original.zip   kept (no more deleting)
previews/{preview_id}/{creative_file_id}/extracted/      used by the preview iframe
previews/{preview_id}/{creative_file_id}/backup.jpg
```
- No `uniqid`: the id already makes the path unique.
- Delivery names are generated from the project name + size and served with a `Content-Disposition` filename.

---

## 5. Backend changes (what and why)

| # | Change | Why |
|---|---|---|
| B1 | **One `creative_files` table.** Migrate the 4 tables, keeping ids. Old `render*` endpoints return the same JSON from it. | Write everything once. Comments, reviews and transfers become simple foreign keys. Live Show.vue keeps working. |
| B2 | **`creatives` identity + matching on upload.** Auto-match by Version/Set position + size, falling back to size. The uploader confirms. Backfill existing previews. | History, compare, and comments that follow the creative. |
| B3 | **Keep the original zip + `Storage` disk + clean delivery names.** | Delivery without re-upload. Clients never see internal names. |
| B4 | **`file_transfer_items`, built on approve.** | No second upload, and a proper structure instead of CSV text. |
| B5 | **`creative_comments`, `creative_reviews`, `round_approvals`.** | Features 3.1–3.3, with a record of who did what and when. |
| B6 | **Split `bulkEdit` into a `PreviewTreeSaver` service**, one handler per level, with ownership checks on every nested id. | Readable, testable, and closes the cross-preview write gap. |
| B7 | **Soft deletes on the preview tree.** | Comments, history and transfers never point at nothing; deletes can be undone. |
| B8 | **`size_id` foreign key: restrict instead of cascade.** | Deleting a size can no longer wipe creatives. |
| B9 | **Notifications after commit, through the queue** (`afterCommit`). | No notifications for failed saves; saves are faster. |
| B10 | **Public viewers stop writing `is_active` / theme.** Selection lives in the URL or the browser; `is_active` is the team's default. | One client's click no longer changes what everyone sees. |
| B11 | **`description` nullable.** | Rounds can be saved without notes. |
| B12 | **Return Inertia responses from delete/approve endpoints, and fix the missing `Log`/`Redirect` imports.** | Update2 updates without a reload; error paths stop crashing. |

**Deliberately skipped**
- **Renaming the swapped tables** (`new_feedback_sets` = "Version", `new_versions` = "Set"): high risk, no user benefit. New code uses clear model names (`Round`, `RoundVersion`, `RoundSet`).
- **A full remodel to Creative → Versions** with rounds as views: B1 + B2 give about 90% of it without rewriting the round structure.
- **Rewriting Show.vue:** it keeps working through the compatible endpoints until clients are moved to Show2.

---

## 6. Open decisions

| # | Question | Proposal |
|---|---|---|
| D1 | Who can comment: guests and/or logged-in users? | Follow the preview's `requires_login`. Guests give a name (+ optional email) and get a "Guest" badge. *For managers.* |
| D2 | On a new round upload, must every previous comment be answered? | Required, with a "not changing, because…" option. |
| D3 | A creative is deleted: what happens to its comments? | Keep them as history (soft delete). |
| D4 | Approval level: round, Set or creative? Today **Approve** is on the round in Update2 (`FeedbackEditor.vue`). | Client decisions per creative; the team's final approval per round. |
| D5 | Editing an approved round, when the delivery references live files. | Lock approved rounds; to change anything, disapprove first. |
| D6 | Folder name when a Version/Set has no name. | "Version A" / "Set 1" in delivery folders only. |
| D7 | Is a backup image required before a banner round can be approved? | Yes, warn and block approve. |
| D8 | Fix existing transfers' download names? | Yes, serve them through the clean-name download endpoint. |
| D9 | Email: per comment or digest? SMTP is not set up yet. | Digest; set up SMTP. |

---

## 7. Roadmap

| Phase | Contents | Risk |
|---|---|---|
| 0 | **Safety fixes:** B7, B8, B9, B10, B11, B12, plus the ownership checks from B6. | Low. Protects current data. |
| 1 | **Foundation:** B1 (one creative table, compatible endpoints) + B2 (identity, matching step in Update2, backfill). | Medium. The big migration. Do it on a copy of production data first. |
| 2 | **Storage + delivery:** B3 + B4. Keep zips, backup images in Update2, transfer built on approve, clean names. | Medium |
| 3 | **Review features:** B5. Comments, per-creative review, answering feedback on upload, round approvals; replace the demo's browser storage. | Medium |
| 4 | **Compare + review mode** on real data. | Low |
| 5 | **Move clients from Show to Show2**: update links + notification URLs; retire Show.vue and Update.vue. | Low |
| — | Split `bulkEdit` (B6), done gradually inside phases 1–3. | — |

---

## 8. Existing bugs (found while mapping, not fixed)

- **Update2 doesn't refresh after delete/approve/disapprove.** Those endpoints return `response('', 200)` instead of an Inertia response, so the action succeeds on the server but the editor stays stale. *(Inferred from code, not reproduced.)* → B12
- **Approve/disapprove error paths crash** on the missing `Log`/`Redirect` imports. → B12
- **No ownership check in `bulkEdit`:** nested ids aren't checked against the preview being saved. → B6
- **Notifications go out even if the save rolls back.** → B9
- **A new video without a file fails the whole save** (`path` is NOT NULL).
- **Social `.svg`** is accepted in the browser but rejected by the server.
- **A creative added without a file** is silently dropped on save.
- **Deleting a size deletes its creatives.** → B8
- **Viewer clicks change `is_active` / theme for everyone.** → B10
- **Unused `/previews/{social|gif}/edit/{id}` routes** delete and recreate rows (new ids). Remove them.
- **Sidebar "Updated" date** reads `new_categories.updated_at`, which changes on viewer clicks. Should use the newest creative upload.
- **Clients see `name_{timestamp}_{uniqid}`** in downloaded transfer files. → B3 / D8

---

## 9. Demo reference

`/previews/review/{slug}`: Show2 plus a comment/status row under each creative, review mode (stage, history, compare by round + creative, approve, comments) and a demo bar (client/team view, progress, reset). Its data stays in the browser.

To remove it, delete:
- `Previews/Review.vue` and `Previews/Review/`
- `PreviewReviewDemoController` and its route
- its two tests
