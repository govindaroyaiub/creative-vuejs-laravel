/**
 * DEMO ONLY: state for the review-mode prototype (/previews/review/{slug}).
 *
 * Comments, replies, resolutions and per-creative approvals are kept in this
 * browser's localStorage, keyed by preview. Nothing is sent to the server.
 *
 * "Lineage" is the prototype's stand-in for the real creative-history link: a
 * creative in Round 2 is treated as the next version of the Round 1 creative
 * that sits in the same Version / Set position with the same size. In the real
 * build the uploader confirms this match on the update page.
 */
import { computed, reactive, watch } from 'vue'

export type CreativeType = 'banner' | 'gif' | 'video' | 'social'
export type Viewer = 'client' | 'team'
export type Status = 'approved' | 'changes'

export interface Author {
  name: string
  kind: Viewer
  guest?: boolean
}

export interface DemoComment {
  id: string
  assetKey: string
  lineageKey: string
  roundId: number
  roundName: string
  parentId: string | null
  author: Author
  body: string
  createdAt: number
  timecode?: number | null
  resolved?: { by: string; at: number } | null
}

export interface LineageEntry {
  roundId: number
  roundName: string
  roundIndex: number
  asset: any
  assetKey: string
  setName: string
  versionName: string
  sIdx: number
  vIdx: number
}

/** One creative in the round currently open, in page order. */
export interface RoundItem extends LineageEntry {
  lineageKey: string
}

interface Persisted {
  comments: DemoComment[]
  statuses: Record<string, Status>
  viewer: Viewer
  guestName: string
}

export const assetKeyOf = (type: string, asset: any) => `${type}:${asset?.id}`

export const sizeOf = (asset: any): { w: number; h: number } | null => {
  const w = Number(asset?.size?.width ?? asset?.width)
  const h = Number(asset?.size?.height ?? asset?.height)
  return w && h ? { w, h } : null
}

export const sizeLabel = (asset: any) => {
  const s = sizeOf(asset)
  return s ? `${s.w}×${s.h}` : 'Image'
}

const named = (v: any) => String(v ?? '').trim()

export function createReviewDemo(opts: {
  previewId: number | string
  history: Record<string, any[]>
  categories: any[]
  teamName: string
}) {
  const storageKey = `review-demo-v1:${opts.previewId}`

  // ---- Lineage ------------------------------------------------------------
  const typeOfCategory: Record<string, CreativeType> = {}
  for (const c of opts.categories || []) typeOfCategory[c.id] = c.type

  const lineageOfAsset = new Map<string, string>()
  const lineages = new Map<string, LineageEntry[]>()
  /** Every creative of every round, per project: catId -> [round entries]. */
  const roundsByCat = new Map<string, { roundId: number; entries: LineageEntry[] }[]>()
  const catOfAsset = new Map<string, string>()

  for (const [catId, rounds] of Object.entries(opts.history || {})) {
    const type = typeOfCategory[catId]
    if (!type) continue
    const catRounds: { roundId: number; entries: LineageEntry[] }[] = []
    roundsByCat.set(catId, catRounds)
    ;(rounds || []).forEach((round: any, rIdx: number) => {
      const roundEntries: LineageEntry[] = []
      catRounds.push({ roundId: round.id, entries: roundEntries })
      ;(round.sets || []).forEach((set: any, sIdx: number) => {
        ;(set.versions || []).forEach((version: any, vIdx: number) => {
          const seen: Record<string, number> = {}
          ;(version.assets || []).forEach((asset: any) => {
            const size = sizeLabel(asset)
            const n = (seen[size] = (seen[size] ?? -1) + 1)
            const lineageKey = `${catId}|${sIdx}|${vIdx}|${size}|${n}`
            const assetKey = assetKeyOf(type, asset)
            lineageOfAsset.set(assetKey, lineageKey)
            catOfAsset.set(assetKey, catId)
            const entry: LineageEntry = {
              roundId: round.id,
              roundName: named(round.name) || `Round ${rIdx + 1}`,
              roundIndex: rIdx,
              asset,
              assetKey,
              setName: named(set.name),
              versionName: named(version.name),
              sIdx,
              vIdx,
            }
            roundEntries.push(entry)
            const list = lineages.get(lineageKey) ?? []
            list.push(entry)
            lineages.set(lineageKey, list)
          })
        })
      })
    })
  }

  const lineageKeyOf = (assetKey: string) => lineageOfAsset.get(assetKey) ?? assetKey
  /**
   * The same creative in every round of its project, oldest first, one entry
   * per round. A round counts when it has the creative in the same Version /
   * Set position and size; failing that (the round was reorganised), the first
   * creative of the same size in that round. Rounds with no such size are
   * skipped. This is what review mode's history and compare use.
   */
  const lineageOf = (assetKey: string): LineageEntry[] => {
    const exact = lineages.get(lineageKeyOf(assetKey)) ?? []
    const catId = catOfAsset.get(assetKey)
    const rounds = catId ? roundsByCat.get(catId) : undefined
    if (!rounds) return exact
    const self = exact.find((e) => e.assetKey === assetKey)
    const size = self ? sizeLabel(self.asset) : null
    const out: LineageEntry[] = []
    for (const r of rounds) {
      const hit =
        exact.find((e) => e.roundId === r.roundId) ??
        (size ? r.entries.find((e) => sizeLabel(e.asset) === size) : undefined)
      if (hit) out.push(hit)
    }
    return out
  }

  // ---- Persisted state ----------------------------------------------------
  const load = (): Persisted | null => {
    try {
      const raw = localStorage.getItem(storageKey)
      return raw ? JSON.parse(raw) : null
    } catch {
      return null
    }
  }

  const state = reactive<Persisted>(
    load() ?? { comments: [], statuses: {}, viewer: 'client', guestName: 'Sarah Chen' },
  )

  watch(
    state,
    () => {
      try {
        localStorage.setItem(storageKey, JSON.stringify(state))
      } catch {
        /* private mode: demo still works for this visit */
      }
    },
    { deep: true },
  )

  const uid = () => Math.random().toString(36).slice(2, 10)
  const HOUR = 3600_000

  const clientAuthor = (): Author => ({ name: state.guestName || 'Guest', kind: 'client', guest: true })
  const teamAuthor = (): Author => ({ name: opts.teamName || 'Planet Nine', kind: 'team' })

  /** A few believable threads so the demo is not empty on first open. */
  const seed = () => {
    const now = Date.now()
    const comments: DemoComment[] = []
    const statuses: Record<string, Status> = {}
    const add = (e: LineageEntry, body: string, author: Author, ago: number, extra: Partial<DemoComment> = {}) => {
      const c: DemoComment = {
        id: uid(),
        assetKey: e.assetKey,
        lineageKey: lineageKeyOf(e.assetKey),
        roundId: e.roundId,
        roundName: e.roundName,
        parentId: null,
        author,
        body,
        createdAt: now - ago,
        ...extra,
      }
      comments.push(c)
      return c
    }

    for (const rounds of Object.values(opts.history || {})) {
      const last = rounds?.[rounds.length - 1]
      if (!last) continue
      const latest: LineageEntry[] = []
      for (const list of lineages.values()) {
        const tail = list[list.length - 1]
        if (tail && tail.roundId === last.id) latest.push(tail)
      }
      latest.sort((a, b) => a.sIdx - b.sIdx || a.vIdx - b.vIdx)

      const client = { name: 'Sarah Chen', kind: 'client' as const, guest: true }
      const team = teamAuthor()

      // 1st creative: feedback from the previous round, answered and resolved.
      const first = latest[0]
      if (first) {
        const history = lineageOf(first.assetKey)
        const prev = (history.length > 1 ? history[history.length - 2] : undefined) ?? first
        const q = add(prev, 'Can the logo be a bit bigger? It gets lost on mobile.', client, 50 * HOUR)
        add(prev, prev === first ? 'Sure, updating it now.' : `Done, logo is 20% larger in ${first.roundName}.`, team, 30 * HOUR, {
          parentId: q.id,
        })
        q.resolved = { by: team.name, at: now - 30 * HOUR }
      }
      // 2nd creative: an open request in the current round.
      const second = latest[1]
      if (second) {
        add(second, 'CTA should read "Shop now", not "Buy now".', client, 3 * HOUR)
        statuses[second.assetKey] = 'changes'
      }
      // 3rd creative: approved.
      const third = latest[2]
      if (third) statuses[third.assetKey] = 'approved'
    }

    state.comments = comments
    state.statuses = statuses
  }

  if (!load()) seed()

  const reset = () => {
    state.viewer = 'client'
    state.guestName = 'Sarah Chen'
    seed()
  }

  // ---- Queries ------------------------------------------------------------
  /** Every round of this creative's project with all its creatives, oldest first. */
  const roundsOf = (assetKey: string) => {
    const catId = catOfAsset.get(assetKey)
    const rounds = catId ? roundsByCat.get(catId) ?? [] : []
    return rounds.map((r) => ({
      roundId: r.roundId,
      roundName: r.entries[0]?.roundName ?? 'Round',
      entries: r.entries,
    }))
  }

  /** Threads on this creative in any of its rounds (same match as lineageOf). */
  const threadsFor = (assetKey: string) => {
    const keys = new Set([assetKey, ...lineageOf(assetKey).map((e) => e.assetKey)])
    const all = state.comments.filter((c) => keys.has(c.assetKey))
    return all
      .filter((c) => !c.parentId)
      .sort((a, b) => a.createdAt - b.createdAt)
      .map((root) => ({
        root,
        replies: all.filter((c) => c.parentId === root.id).sort((a, b) => a.createdAt - b.createdAt),
      }))
  }

  const countFor = (assetKey: string) => {
    const threads = threadsFor(assetKey)
    return {
      total: threads.length,
      open: threads.filter((t) => !t.root.resolved).length,
    }
  }

  const statusOf = (assetKey: string): Status | null => state.statuses[assetKey] ?? null

  // ---- Mutations ----------------------------------------------------------
  const addComment = (input: {
    entry: LineageEntry
    body: string
    parentId?: string | null
    timecode?: number | null
  }) => {
    const body = input.body.trim()
    if (!body) return
    state.comments.push({
      id: uid(),
      assetKey: input.entry.assetKey,
      lineageKey: lineageKeyOf(input.entry.assetKey),
      roundId: input.entry.roundId,
      roundName: input.entry.roundName,
      parentId: input.parentId ?? null,
      author: state.viewer === 'team' ? teamAuthor() : clientAuthor(),
      body,
      createdAt: Date.now(),
      timecode: input.timecode ?? null,
    })
  }

  const toggleResolved = (commentId: string) => {
    const c = state.comments.find((x) => x.id === commentId)
    if (!c) return
    c.resolved = c.resolved ? null : { by: state.viewer === 'team' ? teamAuthor().name : clientAuthor().name, at: Date.now() }
  }

  const setStatus = (assetKey: string, status: Status | null) => {
    if (status) state.statuses[assetKey] = status
    else delete state.statuses[assetKey]
  }

  // ---- The round on screen + review mode ----------------------------------
  const view = reactive<{
    round: RoundItem[]
    roundName: string
    type: string
    reviewing: string | null
  }>({ round: [], roundName: '', type: 'banner', reviewing: null })

  /** Called by the creative footers so the demo knows which round is showing. */
  const trackRound = (ctx: any, type: string) => {
    const items: RoundItem[] = []
    ;(ctx.feedbackSets || []).forEach((set: any, sIdx: number) => {
      ;(set.versions || []).forEach((version: any, vIdx: number) => {
        ;(version.assets || []).forEach((asset: any) => {
          const assetKey = assetKeyOf(type, asset)
          items.push({
            roundId: ctx.feedback?.id,
            roundName: named(ctx.feedback?.name) || 'This round',
            roundIndex: 0,
            asset,
            assetKey,
            setName: named(set.name),
            versionName: named(version.name),
            sIdx,
            vIdx,
            lineageKey: lineageKeyOf(assetKey),
          })
        })
      })
    })
    const sig = items.map((i) => i.assetKey).join()
    if (sig !== view.round.map((i) => i.assetKey).join()) {
      view.round = items
      view.roundName = named(ctx.feedback?.name)
      view.type = type
    }
  }

  const roundProgress = computed(() => {
    const total = view.round.length
    const approved = view.round.filter((i) => statusOf(i.assetKey) === 'approved').length
    const changes = view.round.filter((i) => statusOf(i.assetKey) === 'changes').length
    return { total, approved, changes }
  })

  const openReview = (assetKey: string) => {
    view.reviewing = assetKey
  }
  const closeReview = () => {
    view.reviewing = null
  }

  return {
    state,
    view,
    lineageOf,
    lineageKeyOf,
    roundsOf,
    threadsFor,
    countFor,
    statusOf,
    addComment,
    toggleResolved,
    setStatus,
    trackRound,
    roundProgress,
    openReview,
    closeReview,
    reset,
  }
}

export type ReviewDemo = ReturnType<typeof createReviewDemo>
