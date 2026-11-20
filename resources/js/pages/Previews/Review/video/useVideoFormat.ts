/**
 * DEMO ONLY: what kind of placement a video size is made for, and the shared
 * showcase settings (view mode, laptop / TV) remembered per viewer.
 */
import { reactive, watch } from 'vue'
import { resolvePlacement, type Placement } from '@/lib/video-placement'

export type { Placement as VideoFormat } from '@/lib/video-placement'
export type ShowcaseMode = 'clean' | 'context' | 'safe'
export type WideScreen = 'laptop' | 'tv'

export interface VideoBrand {
  name: string
  handle: string
  logo: string | null
}

export function sizeOfVideo(video: any): { w: number; h: number } {
  const w = Number(video?.size?.width) || 1920
  const h = Number(video?.size?.height) || 1080
  return { w, h }
}

/** The frame for a video: chosen in Update2, else auto from its size. */
export const formatOf = (video: any): Placement => resolvePlacement(video)

export const FORMAT_LABEL: Record<Placement, string> = {
  story: 'Story / Reel',
  feed: 'Feed post',
  screen: 'Pre-roll / CTV',
  article: 'In-page video',
  plain: 'Video',
}

/** Phone-sized cards sit side by side; plain videos follow their shape. */
export function isNarrowVideo(video: any): boolean {
  const f = formatOf(video)
  if (f === 'story' || f === 'feed') return true
  if (f !== 'plain') return false
  const { w, h } = sizeOfVideo(video)
  return w / h <= 1.05
}

export function ratioLabel(video: any): string {
  const { w, h } = sizeOfVideo(video)
  const gcd = (a: number, b: number): number => (b ? gcd(b, a % b) : a)
  const g = gcd(w, h)
  const a = w / g
  const b = h / g
  return a > 40 || b > 40 ? (w / h).toFixed(2) + ':1' : `${a}:${b}`
}

const KEY = 'video-showcase-v1'

export function createShowcaseState() {
  let saved: Partial<{ mode: ShowcaseMode; screen: WideScreen }> = {}
  try {
    saved = JSON.parse(localStorage.getItem(KEY) || '{}')
  } catch {
    /* private mode etc. */
  }
  const state = reactive({
    mode: (saved.mode ?? 'context') as ShowcaseMode,
    screen: (saved.screen ?? 'laptop') as WideScreen,
  })
  watch(state, () => {
    try {
      localStorage.setItem(KEY, JSON.stringify(state))
    } catch {
      /* ignore */
    }
  })
  return state
}

export type ShowcaseState = ReturnType<typeof createShowcaseState>
