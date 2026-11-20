/**
 * Where a video is shown in the client viewer (the "frame"). Chosen per video
 * in Update2; null / '' means auto, picked from the video's size.
 * Stored in new_videos.placement. Keep in sync with
 * newVideo::PLACEMENTS on the server.
 */
export type Placement = 'story' | 'feed' | 'screen' | 'article' | 'plain'

export const PLACEMENTS: { value: Placement; label: string; hint: string }[] = [
  { value: 'story', label: 'Story / Reel', hint: 'Full-screen phone, vertical' },
  { value: 'feed', label: 'Feed post', hint: 'Phone feed, square or 4:5' },
  { value: 'screen', label: 'Laptop / TV', hint: 'Pre-roll on a video site or a TV ad break' },
  { value: 'article', label: 'In-page article', hint: 'Video ad inside a web article' },
  { value: 'plain', label: 'Plain player', hint: 'No frame, just the video' },
]

export const placementLabel = (p: Placement) => PLACEMENTS.find((o) => o.value === p)?.label ?? p

/** What "Auto" picks for a size: small sizes are in-page, otherwise the shape decides. */
export function autoPlacement(width?: number | null, height?: number | null): Placement {
  const w = Number(width) || 0
  const h = Number(height) || 0
  if (!w || !h) return 'screen'
  if (w <= 500 && h <= 600) return 'article'
  const r = w / h
  if (r < 0.7) return 'story'
  if (r <= 1.05) return 'feed'
  return 'screen'
}

export function isPlacement(v: unknown): v is Placement {
  return PLACEMENTS.some((o) => o.value === v)
}

/** The video's chosen frame, or the auto one for its size. */
export function resolvePlacement(video: any): Placement {
  return isPlacement(video?.placement) ? video.placement : autoPlacement(video?.size?.width, video?.size?.height)
}
