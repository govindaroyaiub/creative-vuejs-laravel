/**
 * Where each banner size naturally runs on a page, so the whole Set can be
 * shown at once as a campaign takeover. Unknown sizes go to `other`.
 */
import { reactive, watch } from 'vue'

export type BannerView = 'clean' | 'desktop' | 'mobile'

export const bw = (b: any) => Number(b?.size?.width) || 300
export const bh = (b: any) => Number(b?.size?.height) || 250
export const sizeLabel = (b: any) => `${bw(b)}×${bh(b)}`
export const bannerSrc = (b: any) => `/${String(b?.path || '').replace(/\/+$/, '')}/index.html`

const isWide = (b: any) => bw(b) >= 728 && bh(b) <= 300 // billboard, leaderboard
const isSky = (b: any) => bw(b) <= 160 && bh(b) >= 400 // 120x600, 160x600
const isTall = (b: any) => bw(b) <= 300 && bh(b) >= 500 // 300x600, 300x1050
const isStrip = (b: any) => bw(b) <= 320 && bh(b) <= 100 // 320x50, 320x100, 300x50
const isInterstitial = (b: any) => (bw(b) === 320 && bh(b) === 480) || (bw(b) === 480 && bh(b) === 320)

/** Page widths: wide enough that a 728 leaderboard fits the article column. */
export const PAGE_W = 1300
export const PAGE_W_WITH_GUTTER = 1400
const ARTICLE_MAX = 760

export interface DesktopSlots {
  top: any | null
  footer: any | null
  /** Left gutter beside the content: the first skyscraper. */
  gutter: any | null
  rail: any[]
  article: any[]
  /** Strips and interstitials: they run on phones (Mobile view). */
  mobileOnly: any[]
  other: any[]
}

/**
 * Desktop news page. Largest wide = billboard on top, next = footer, more
 * wides go in the article; first skyscraper in the left gutter; tall units
 * and the first <=300 box in the right rail; the rest in the article.
 */
export function desktopSlots(banners: any[]): DesktopSlots {
  const slots: DesktopSlots = { top: null, footer: null, gutter: null, rail: [], article: [], mobileOnly: [], other: [] }
  const taken = new Set<any>()
  const put = (list: any[], b: any) => {
    list.push(b)
    taken.add(b)
  }

  const wides = banners.filter(isWide).sort((a, b) => bw(b) * bh(b) - bw(a) * bh(a))
  if (wides[0]) {
    slots.top = wides[0]
    taken.add(wides[0])
  }
  if (wides[1]) {
    slots.footer = wides[1]
    taken.add(wides[1])
  }
  wides.slice(2).forEach((b) => put(bw(b) <= ARTICLE_MAX ? slots.article : slots.other, b))

  const skies = banners.filter(isSky)
  if (skies[0]) {
    slots.gutter = skies[0]
    taken.add(skies[0])
  }
  skies.slice(1).forEach((b) => put(slots.rail, b))

  banners.filter((b) => !taken.has(b) && isTall(b)).forEach((b) => put(slots.rail, b))
  banners.filter((b) => !taken.has(b) && (isStrip(b) || isInterstitial(b))).forEach((b) => put(slots.mobileOnly, b))

  let railBox = false
  for (const b of banners.filter((x) => !taken.has(x))) {
    if (!railBox && bw(b) <= 300 && bh(b) < 500) {
      railBox = true
      put(slots.rail, b)
    } else if (bw(b) <= ARTICLE_MAX) {
      put(slots.article, b)
    } else {
      put(slots.other, b)
    }
  }
  return slots
}

export interface MobileSlots {
  sticky: any | null
  inline: any[]
  desktopOnly: any[]
}

/**
 * Mobile web: a strip sticks to the bottom, anything up to 360 wide runs in
 * the article. Skyscrapers are desktop formats, so they are listed, not shown.
 */
export function mobileSlots(banners: any[]): MobileSlots {
  const strips = banners.filter(isStrip)
  const sticky = strips[0] ?? null
  const desktop = (b: any) => bw(b) > 360 || isSky(b)
  const inline = banners.filter((b) => b !== sticky && !desktop(b))
  const desktopOnly = banners.filter((b) => b !== sticky && desktop(b))
  return { sticky, inline, desktopOnly }
}

/** Does the desktop page / the phone have at least one banner in a real slot? */
export function viewsFor(banners: any[]): { desktop: boolean; mobile: boolean } {
  const d = desktopSlots(banners)
  const m = mobileSlots(banners)
  return {
    desktop: !!(d.top || d.footer || d.gutter || d.rail.length || d.article.length),
    mobile: !!(m.sticky || m.inline.length),
  }
}

const KEY = 'banner-showcase-v1'

export function createBannerShowcaseState() {
  let saved: Partial<{ view: BannerView }> = {}
  try {
    saved = JSON.parse(localStorage.getItem(KEY) || '{}')
  } catch {
    /* private mode etc. */
  }
  const state = reactive({
    view: (['clean', 'desktop', 'mobile'].includes(saved.view as string) ? saved.view : 'desktop') as BannerView,
    /** Bump to restart every banner at once. */
    replay: 0,
    /** Banner id hovered in the size strip: its slot gets highlighted. */
    hover: null as number | null,
  })
  watch(
    () => state.view,
    () => {
      try {
        localStorage.setItem(KEY, JSON.stringify({ view: state.view }))
      } catch {
        /* ignore */
      }
    },
  )
  return state
}

export type BannerShowcaseState = ReturnType<typeof createBannerShowcaseState>
