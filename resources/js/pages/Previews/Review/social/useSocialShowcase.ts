/**
 * Social image showcase: frame per image (chosen in Update2, else auto from
 * size) and the shared view settings, remembered per viewer.
 */
import { reactive, watch } from 'vue'
import { resolveSocialPlacement, type SocialPlacement } from '@/lib/social-placement'

export type SocialMode = 'clean' | 'context' | 'safe'

export const socialFormatOf = (social: any): SocialPlacement => resolveSocialPlacement(social)

export const SOCIAL_LABEL: Record<SocialPlacement, string> = {
  feed: 'Feed post',
  story: 'Story',
  link: 'Link ad',
  pin: 'Pin',
  storyboard: 'Storyboard',
  plain: 'Image',
}

export function sizeOfSocial(social: any): { w: number; h: number } {
  return { w: Number(social?.width) || 1080, h: Number(social?.height) || 1080 }
}

export function ratioOf(social: any): string {
  const { w, h } = sizeOfSocial(social)
  const gcd = (a: number, b: number): number => (b ? gcd(b, a % b) : a)
  const g = gcd(w, h)
  return w / g > 40 || h / g > 40 ? `${(w / h).toFixed(2)}:1` : `${w / g}:${h / g}`
}

const KEY = 'social-showcase-v1'

export function createSocialShowcaseState() {
  let saved: Partial<{ mode: SocialMode; carousel: boolean }> = {}
  try {
    saved = JSON.parse(localStorage.getItem(KEY) || '{}')
  } catch {
    /* private mode etc. */
  }
  const state = reactive({
    // A saved mode that no longer exists (the old 'grid') falls back to In context.
    mode: (['clean', 'context', 'safe'].includes(saved.mode as string) ? saved.mode : 'context') as SocialMode,
    /** Several feed images in one Set are shown as one swipeable post. */
    carousel: saved.carousel ?? true,
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

export type SocialShowcaseState = ReturnType<typeof createSocialShowcaseState>
