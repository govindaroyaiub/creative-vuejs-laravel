/**
 * Switching a showcase view (Clean / In context / On a site / Mobile / Safe
 * zones) focuses the Set: the Set (`#set-{id}`, header included) is brought
 * to the top of the screen, like opening a Set does in AssetCanvas (smooth,
 * instant with reduced motion).
 *
 * To make that reliable on every switch:
 *  - the showcase keeps at least a screen of height, so even a short view at
 *    the end of the page leaves room to scroll the Set to the top (otherwise
 *    the browser clamps the scroll and the Set lands lower each time);
 *  - the page is first held still where the toolbar was (instant), so Sets
 *    above changing height cannot make it drift;
 *  - a Set already at the top is not scrolled again, so repeated switches
 *    stay put once focused.
 */
import { nextTick, type Ref } from 'vue'

export function useViewFocus(root: Ref<HTMLElement | null>) {
  return async function switchView(apply: () => void) {
    const box = root.value
    const toolbar = box?.firstElementChild as HTMLElement | null
    if (!box || !toolbar) {
      apply()
      return
    }

    const before = toolbar.getBoundingClientRect().top
    box.style.minHeight = `${window.innerHeight - 32}px`

    apply()
    await nextTick()
    requestAnimationFrame(() =>
      requestAnimationFrame(() => {
        // Hold the toolbar where it was, then focus the Set.
        const shift = toolbar.getBoundingClientRect().top - before
        if (Math.abs(shift) > 1) window.scrollBy({ top: shift, behavior: 'instant' as ScrollBehavior })

        const set = box.closest<HTMLElement>('[id^="set-"]') ?? box
        const top = set.getBoundingClientRect().top
        if (top >= 0 && top <= 40) return
        const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
        set.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' })
      }),
    )
  }
}
