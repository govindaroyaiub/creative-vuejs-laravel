/**
 * Where a social image is shown in the client viewer (the "frame"). Chosen per
 * image in Update2; null / '' means auto, picked from width / height.
 * Stored in new_socials.placement. Keep in sync with newSocial::PLACEMENTS.
 */
export type SocialPlacement = 'feed' | 'story' | 'link' | 'pin' | 'storyboard' | 'plain'

export const SOCIAL_PLACEMENTS: { value: SocialPlacement; label: string; hint: string }[] = [
  { value: 'feed', label: 'Feed post', hint: 'Phone feed, square or 4:5 (carousels too)' },
  { value: 'story', label: 'Story', hint: 'Full-screen phone, vertical' },
  { value: 'link', label: 'Link ad', hint: 'Wide image with a link card, desktop feed' },
  { value: 'pin', label: 'Pin', hint: 'Tall image in a two-column board' },
  { value: 'storyboard', label: 'Storyboard', hint: "Panels in order with a play-through; groups the Set's storyboard images" },
  { value: 'plain', label: 'Plain image', hint: 'No frame, just the image' },
]

export const socialPlacementLabel = (p: SocialPlacement) => SOCIAL_PLACEMENTS.find((o) => o.value === p)?.label ?? p

/** What "Auto" picks for an image size. Storyboard is never auto: it has to be chosen. */
export function autoSocialPlacement(width?: number | null, height?: number | null): SocialPlacement {
  const w = Number(width) || 0
  const h = Number(height) || 0
  if (!w || !h) return 'feed'
  const r = w / h
  if (r < 0.62) return 'story' // 9:16
  if (r < 0.78) return 'pin' // 2:3
  if (r <= 1.3) return 'feed' // 4:5 … 1:1 … slightly wide
  return 'link' // 1.91:1
}

export function resolveSocialPlacement(social: any): SocialPlacement {
  return SOCIAL_PLACEMENTS.some((o) => o.value === social?.placement)
    ? social.placement
    : autoSocialPlacement(social?.width, social?.height)
}
