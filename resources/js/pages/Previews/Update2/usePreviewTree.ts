import { ref, computed, reactive } from 'vue'

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type NodeKind =
  | 'category'
  | 'feedback'
  | 'set'
  | 'version'
  | 'asset'

export type AssetType = 'banner' | 'video' | 'social' | 'gif'

export interface SelectionKey {
  kind: NodeKind
  id: number | string
  /** Asset type if kind === 'asset' (banner/video/social/gif) */
  assetType?: AssetType
}

// ---------------------------------------------------------------------------
// Tree state — wrap the Inertia preview prop so we can mutate locally and
// keep dirty/selection state alongside it.
// ---------------------------------------------------------------------------

let TMP_ID = Date.now()
const nextTmpId = () => ++TMP_ID

/**
 * IDs server-issued are normal ints. Locally-created (unsaved) records get
 * timestamps from `nextTmpId()`. Anything >= 1e12 is treated as unsaved.
 */
export const isDbId = (id: any): boolean =>
  typeof id === 'number' && id < 1_000_000_000_000

export function createPreviewTree(initialPreview: any) {
  // Deep-ish clone so we never accidentally mutate Inertia's prop
  const preview = reactive(JSON.parse(JSON.stringify(initialPreview)))

  // Ensure expected nested arrays + fields exist
  for (const c of preview.categories || []) {
    c.feedbacks ||= []
    for (const f of c.feedbacks) {
      f.feedback_sets ||= f.feedbackSets || []
      delete f.feedbackSets
      for (const s of f.feedback_sets) {
        s.versions ||= []
        for (const v of s.versions) {
          v.banners ||= []
          v.videos ||= []
          v.socials ||= []
          v.gifs ||= []
        }
      }
    }
  }

  const dirtyKeys = ref<Set<string>>(new Set())
  const selection = ref<SelectionKey | null>(null)
  const expanded = ref<Set<string>>(new Set())
  const search = ref('')
  const isSaving = ref(false)

  /** "category:5" / "feedback:12" / "asset:banner:42" */
  const keyOf = (sel: SelectionKey): string =>
    sel.kind === 'asset'
      ? `asset:${sel.assetType}:${sel.id}`
      : `${sel.kind}:${sel.id}`

  const markDirty = (sel: SelectionKey) => {
    dirtyKeys.value = new Set(dirtyKeys.value).add(keyOf(sel))
  }

  const clearDirty = () => {
    dirtyKeys.value = new Set()
  }

  const isDirty = (sel: SelectionKey) => dirtyKeys.value.has(keyOf(sel))

  const dirtyCount = computed(() => dirtyKeys.value.size)
  const hasUnsavedNew = computed(() => {
    // count records with non-DB ids
    let n = 0
    for (const c of preview.categories || []) {
      if (!isDbId(c.id)) n++
      for (const f of c.feedbacks) {
        if (!isDbId(f.id)) n++
        for (const s of f.feedback_sets) {
          if (!isDbId(s.id)) n++
          for (const v of s.versions) {
            if (!isDbId(v.id)) n++
            for (const a of [...v.banners, ...v.videos, ...v.socials, ...v.gifs]) {
              if (!isDbId(a.id)) n++
            }
          }
        }
      }
    }
    return n
  })

  const isExpanded = (key: string) => expanded.value.has(key)
  const toggleExpand = (key: string) => {
    const next = new Set(expanded.value)
    next.has(key) ? next.delete(key) : next.add(key)
    expanded.value = next
  }
  const expand = (key: string) => {
    if (!expanded.value.has(key)) {
      const next = new Set(expanded.value)
      next.add(key)
      expanded.value = next
    }
  }

  const select = (sel: SelectionKey) => {
    selection.value = sel
    // Auto-expand ancestors so the node is visible
    if (sel.kind !== 'category') {
      // we'll resolve ancestors via findPath below, but we don't need that
      // just to expand — caller can call expandPathTo(sel) explicitly.
    }
  }

  // ---------------------------------------------------------------------
  // Lookups
  // ---------------------------------------------------------------------

  const findCategory = (id: any) => preview.categories.find((c: any) => c.id === id)
  const findPath = (sel: SelectionKey): {
    category?: any
    feedback?: any
    set?: any
    version?: any
    asset?: any
  } => {
    const out: any = {}
    if (!sel) return out
    for (const c of preview.categories) {
      if (sel.kind === 'category' && c.id === sel.id) { out.category = c; return out }
      for (const f of c.feedbacks) {
        if (sel.kind === 'feedback' && f.id === sel.id) { out.category = c; out.feedback = f; return out }
        for (const s of f.feedback_sets) {
          if (sel.kind === 'set' && s.id === sel.id) { out.category = c; out.feedback = f; out.set = s; return out }
          for (const v of s.versions) {
            if (sel.kind === 'version' && v.id === sel.id) {
              out.category = c; out.feedback = f; out.set = s; out.version = v; return out
            }
            if (sel.kind === 'asset') {
              const list = v[`${sel.assetType}s` as 'banners' | 'videos' | 'socials' | 'gifs'] || []
              const a = list.find((x: any) => x.id === sel.id)
              if (a) {
                out.category = c; out.feedback = f; out.set = s; out.version = v; out.asset = a
                return out
              }
            }
          }
        }
      }
    }
    return out
  }

  const expandPathTo = (sel: SelectionKey) => {
    const p = findPath(sel)
    if (p.category) expand(`category:${p.category.id}`)
    if (p.feedback) expand(`feedback:${p.feedback.id}`)
    if (p.set) expand(`set:${p.set.id}`)
    if (p.version) expand(`version:${p.version.id}`)
  }

  // ---------------------------------------------------------------------
  // Mutators — Add
  // ---------------------------------------------------------------------

  const addCategory = (name: string, type: AssetType) => {
    const cat = {
      id: nextTmpId(),
      name,
      type,
      is_active: 0,
      feedbacks: [] as any[],
      file_transfer_slug: null,
    }
    preview.categories.push(cat)
    select({ kind: 'category', id: cat.id })
    expand(`category:${cat.id}`)
    return cat
  }

  const addFeedback = (categoryId: any, name: string, description = '') => {
    const cat = findCategory(categoryId)
    if (!cat) return
    const fb = {
      id: nextTmpId(),
      name,
      description,
      is_active: 0,
      is_approved: 0,
      category_id: categoryId,
      feedback_sets: [] as any[],
    }
    cat.feedbacks.push(fb)
    select({ kind: 'feedback', id: fb.id })
    expandPathTo({ kind: 'feedback', id: fb.id })
    return fb
  }

  const addSet = (feedbackId: any, name = '') => {
    for (const c of preview.categories) {
      for (const f of c.feedbacks) {
        if (f.id === feedbackId) {
          const s = {
            id: nextTmpId(),
            name,
            feedback_id: feedbackId,
            versions: [] as any[],
          }
          f.feedback_sets.push(s)
          select({ kind: 'set', id: s.id })
          expandPathTo({ kind: 'set', id: s.id })
          return s
        }
      }
    }
    return undefined
  }

  const addVersion = (setId: any, name = '') => {
    for (const c of preview.categories) {
      for (const f of c.feedbacks) {
        for (const s of f.feedback_sets) {
          if (s.id === setId) {
            const v = {
              id: nextTmpId(),
              name,
              feedback_set_id: setId,
              banners: [], videos: [], socials: [], gifs: [],
            }
            s.versions.push(v)
            select({ kind: 'version', id: v.id })
            expandPathTo({ kind: 'version', id: v.id })
            return v
          }
        }
      }
    }
    return undefined
  }

  const addAsset = (versionId: any, type: AssetType, partial: any = {}) => {
    for (const c of preview.categories) {
      for (const f of c.feedbacks) {
        for (const s of f.feedback_sets) {
          for (const v of s.versions) {
            if (v.id === versionId) {
              const list = v[`${type}s` as 'banners' | 'videos' | 'socials' | 'gifs']
              const asset = {
                id: nextTmpId(),
                position: list.length + 1,
                ...partial,
              }
              list.push(asset)
              select({ kind: 'asset', id: asset.id, assetType: type })
              expandPathTo({ kind: 'asset', id: asset.id, assetType: type })
              return asset
            }
          }
        }
      }
    }
  }

  // ---------------------------------------------------------------------
  // Mutators — Remove (local only; server delete is handled by the modal)
  // ---------------------------------------------------------------------

  const removeNode = (sel: SelectionKey) => {
    if (sel.kind === 'category') {
      preview.categories = preview.categories.filter((c: any) => c.id !== sel.id)
    } else if (sel.kind === 'feedback') {
      for (const c of preview.categories) {
        c.feedbacks = c.feedbacks.filter((f: any) => f.id !== sel.id)
      }
    } else if (sel.kind === 'set') {
      for (const c of preview.categories) {
        for (const f of c.feedbacks) {
          f.feedback_sets = f.feedback_sets.filter((s: any) => s.id !== sel.id)
        }
      }
    } else if (sel.kind === 'version') {
      for (const c of preview.categories) {
        for (const f of c.feedbacks) {
          for (const s of f.feedback_sets) {
            s.versions = s.versions.filter((v: any) => v.id !== sel.id)
          }
        }
      }
    } else if (sel.kind === 'asset') {
      const t = sel.assetType!
      for (const c of preview.categories) {
        for (const f of c.feedbacks) {
          for (const s of f.feedback_sets) {
            for (const v of s.versions) {
              const list = v[`${t}s` as 'banners' | 'videos' | 'socials' | 'gifs']
              const idx = list.findIndex((a: any) => a.id === sel.id)
              if (idx >= 0) list.splice(idx, 1)
            }
          }
        }
      }
    }
    if (selection.value && keyOf(selection.value) === keyOf(sel)) {
      selection.value = null
    }
  }

  return {
    // state
    preview,
    selection,
    expanded,
    search,
    isSaving,

    // dirty
    dirtyKeys,
    dirtyCount,
    hasUnsavedNew,
    markDirty,
    clearDirty,
    isDirty,

    // expansion
    isExpanded,
    toggleExpand,
    expand,
    expandPathTo,

    // selection
    select,
    keyOf,

    // lookups
    findCategory,
    findPath,

    // mutators
    addCategory,
    addFeedback,
    addSet,
    addVersion,
    addAsset,
    removeNode,
  }
}

export type PreviewTree = ReturnType<typeof createPreviewTree>
