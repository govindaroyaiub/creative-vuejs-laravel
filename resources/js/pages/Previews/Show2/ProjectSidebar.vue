<script setup lang="ts">
import { computed, inject, nextTick, onBeforeUnmount, onMounted, watch, type Ref } from 'vue'
import { ImagePlay, Video, Image, GalleryThumbnails, X, Folder, PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next'

const props = defineProps<{
  categories: any[]
  activeCategory: any
  isLoading: boolean
  isMobileOpen: boolean
  /** Short viewport with a long list: use the drawer instead of the column. */
  forceDrawerNav?: boolean
  /** Hidden on purpose by the viewer, to give the creatives the full width. */
  collapsed?: boolean
  preview?: any
  client?: any
  headerLogo?: any
}>()

defineEmits<{
  (e: 'select', id: number): void
  (e: 'close'): void
  (e: 'collapse'): void
  (e: 'expand'): void
}>()

/**
 * Keep the selected project visible in the list.
 *
 * With enough categories the list scrolls, so the highlighted one can sit below
 * the fold — you open the preview and cannot tell which project you are looking
 * at without scrolling the sidebar. Nudge each list just enough to bring it in.
 *
 * `scrollTop` is set directly rather than calling `scrollIntoView()`, which also
 * scrolls ancestor scrollers — here that would drag the whole preview page.
 *
 * Both lists are handled: the desktop card and the mobile drawer render their
 * own copy, and only one is on screen at a time.
 */
const revealActiveCategory = (attempt = 0) => {
  let handled = false

  document.querySelectorAll<HTMLElement>('[data-category-list]').forEach((list) => {
    if (list.scrollHeight <= list.clientHeight) return

    const active = list.querySelector<HTMLElement>('[data-active="true"]')
    if (!active) return

    const item = active.getBoundingClientRect()
    const view = list.getBoundingClientRect()
    const margin = 12

    if (item.top < view.top + margin) {
      list.scrollTop -= view.top + margin - item.top
    } else if (item.bottom > view.bottom - margin) {
      list.scrollTop += item.bottom - (view.bottom - margin)
    }

    handled = true
  })

  // Show2 fetches its categories *after* mounting, and shows skeletons while it
  // does — so on load and on reload there is no `[data-active]` row to scroll to
  // yet, and firing once simply missed. Retry for a few frames until the real
  // rows exist. Measured: without this, scrollTop stayed 0 on load while the
  // click path scrolled correctly.
  if (!handled && attempt < 40) {
    requestAnimationFrame(() => revealActiveCategory(attempt + 1))
  }
}

/**
 * The list's height changes without the selection changing: the logo panel
 * above it slides open once the page is scrolled (max-h-24), and the window
 * itself can be resized. Both shrink the list and can push the active row back
 * out of view — measured going from 55px of overflow to 127px after a click.
 * A ResizeObserver catches every such case; scrolling does not resize, so this
 * cannot feed back on itself.
 */
let listObserver: ResizeObserver | null = null

onMounted(() =>
  nextTick(() => {
    revealActiveCategory()

    if (typeof ResizeObserver === 'undefined') return

    listObserver = new ResizeObserver(() => revealActiveCategory())
    document
      .querySelectorAll<HTMLElement>('[data-category-list]')
      .forEach((list) => listObserver!.observe(list))
  }),
)

onBeforeUnmount(() => {
  listObserver?.disconnect()
  listObserver = null
})

// The active category changes without this component remounting, and the mobile
// drawer only gains height once it opens — so both need to re-run it.
watch(
  () => [
    props.activeCategory?.id,
    props.categories.map((c: any) => c.is_active).join(),
    props.isMobileOpen,
    props.isLoading,
  ],
  () => nextTick(() => revealActiveCategory()),
)

// Same visibility logic as the topbar — keeps the two locations in
// sync so whichever logo is "currently active" is the one that
// transfers between zones on scroll.
const showPlanetNineLogo = computed(() => props.preview?.show_planetnine_logo === 1 && props.headerLogo?.logo)
const showSidebarLogo = computed(() => props.preview?.show_sidebar_logo === 1 && props.client?.logo)
const hasAnyLogo = computed(() => showPlanetNineLogo.value || showSidebarLogo.value)

// Scroll state (provided by Show2.vue). When true, the topbar logo is
// faded out and we slide our copy into the top of the sidebar card.
const isScrolled = inject<Ref<boolean>>('show2Scrolled')

const formatDate = (s: string) => {
  if (!s) return ''
  const d = new Date(s)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const typeIcon = (type: string) => {
  switch (type) {
    case 'banner':
      return GalleryThumbnails
    case 'video':
      return Video
    case 'social':
      return Image
    case 'gif':
      return ImagePlay
    default:
      return Folder
  }
}

const typeLabel = (type: string) => {
  switch (type) {
    case 'banner':
      return 'Banners'
    case 'video':
      return 'Videos'
    case 'social':
      return 'Social'
    case 'gif':
      return 'GIFs'
    default:
      return 'Project'
  }
}

/**
 * Newest project first.
 *
 * `created_at` alone is not enough: categories added in one go share a
 * timestamp to the second, and Array.sort is stable, so equal timestamps kept
 * their insertion order (id ascending) — leaving the list part descending, part
 * ascending. Falling back to id, which is monotonic, makes "the one I just
 * added is at the top" hold in every case.
 */
const sorted = computed(() =>
  [...props.categories].sort((a, b) => {
    const byDate = new Date(b.created_at).getTime() - new Date(a.created_at).getTime()

    return byDate !== 0 ? byDate : Number(b.id) - Number(a.id)
  })
)
</script>

<template>
  <!-- Desktop -->
  <Transition name="p2-col">
    <aside
      v-if="!forceDrawerNav && !collapsed"
      class="sticky top-[80px] hidden w-72 shrink-0 self-start lg:block"
      data-tour="projects"
    >
      <!-- `self-start` is what keeps this sticky. The parent is a flex row, so
           without it this column stretches to the full row height — and a sticky
           element that already fills its containing block has no room left to
           shift within it, so it stops following the scroll entirely. Sizing to
           its own content is what gives it that room back.

           The card is sized by its rows, not by the viewport, so a preview with
           three creatives does not leave a tall empty panel down the page.

           Two bounds keep that honest:
             min-h — a floor, matching the column width, so one or two rows still
                     read as a panel instead of a stray chip;
             max-h — a ceiling at the viewport, which is what hands scrolling back
                     to the list inside (it is `min-h-0 flex-1 overflow-y-auto`,
                     so it only starts scrolling once this ceiling is hit). -->
      <div
        class="p2-glass flex w-72 max-h-[calc(100vh-100px)] min-h-[18rem] flex-col overflow-hidden rounded-3xl"
      >
        <!-- Header row: the count on the left, the logo on the right.
             The logo appears once the user has scrolled past the topbar (whose
             own logo fades out at the same moment), so branding is never off
             screen entirely.

             It shares this row rather than owning a panel above it on purpose.
             The row is already taller than the logo, so the logo costs no height:
             nothing shifts when it fades in, there is no band of empty space
             while it is hidden, and there is no `max-h` clamp to cut it off.
             `shrink-0` keeps the row itself from being squeezed flat when the
             column runs short of height. -->
        <div
          class="flex shrink-0 items-center justify-between gap-3 border-b px-5 py-4"
          :style="{ borderColor: 'var(--p2-hairline)' }"
        >
          <div class="min-w-0">
            <p class="p2-label">Creatives</p>
            <div class="mt-1 text-xs text-[var(--p2-text-muted)]">
              <span class="p2-mono">{{ categories.length }}</span>
              {{ categories.length === 1 ? 'creative' : 'creatives' }}
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <Transition
              enter-active-class="transition-opacity duration-300 ease-p2-cinema"
              leave-active-class="transition-opacity duration-200 ease-p2-cinema"
              enter-from-class="opacity-0"
              leave-to-class="opacity-0"
            >
              <!-- The client's own logo belongs here; the header logo has the top
                   bar. Falls back to the header logo when the client has none, so
                   nothing is shown while `hasAnyLogo` says otherwise.

                   `max-w-[7rem]` is what stops a wide logo being clipped by the
                   288px column: `object-contain` then trades height for width, so
                   a very wide mark renders shorter but whole. -->
              <img
                v-if="hasAnyLogo && isScrolled"
                :src="showSidebarLogo ? `/logos/${client.logo}` : `/logos/${headerLogo.logo}`"
                alt="logo"
                class="h-8 w-auto max-w-[6rem] shrink-0 rounded object-contain"
              />
            </Transition>

            <!-- Collapsing hands the width to the creatives. Re-opening is the
                 same button the drawer uses in the top bar, which appears there
                 as soon as this column is gone. -->
            <button
              type="button"
              class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-200 ease-p2-expo hover:text-[var(--p2-text)]"
              :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
              title="Hide the creatives list"
              aria-label="Hide the creatives list"
              @click="$emit('collapse')"
            >
              <PanelLeftClose class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-3 py-3" data-category-list>
          <template v-if="isLoading">
            <div class="space-y-2 px-2">
              <div
                v-for="n in 5"
                :key="n"
                class="h-14 animate-pulse rounded-xl"
                :style="{ background: 'var(--p2-hairline)' }"
              />
            </div>
          </template>

          <template v-else-if="!categories.length">
            <div class="px-4 py-8 text-center text-sm text-[var(--p2-text-muted)]">
              No projects yet.
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col gap-1">
              <button
                v-for="cat in sorted"
                :key="cat.id"
                type="button"
                :data-active="cat.is_active === 1"
                :class="[
                  'group relative w-full rounded-xl px-3 py-2.5 text-left transition-all duration-300 ease-p2-expo',
                  cat.is_active === 1
                    ? 'text-white shadow-sm'
                    : 'text-[var(--p2-text)] hover:bg-[var(--p2-accent-soft)]',
                ]"
                :style="
                  cat.is_active === 1
                    ? { background: 'linear-gradient(135deg, var(--p2-accent) 0%, var(--p2-accent-2) 100%)' }
                    : undefined
                "
                @click="$emit('select', cat.id)"
              >
                <div class="flex items-center gap-2.5">
                  <span
                    :class="[
                      'grid h-7 w-7 shrink-0 place-items-center rounded-lg',
                      cat.is_active === 1 ? 'bg-white/20 text-white' : '',
                    ]"
                    :style="cat.is_active !== 1
                      ? { color: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }
                      : undefined"
                    :title="typeLabel(cat.type)"
                  >
                    <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                    <div
                      class="p2-mono mt-0.5 text-[11px] tracking-wide"
                      :class="cat.is_active === 1 ? 'text-white/80' : 'text-[var(--p2-text-subtle)]'"
                    >
                      {{ formatDate(cat.created_at) }}
                    </div>
                  </div>
                </div>
              </button>
            </div>
          </template>
        </div>
      </div>
    </aside>
  </Transition>

  <!-- Collapsed rail.
       Collapsing must not cost you navigation: the top bar's restore button is
       at the top of the page, so switching creatives from halfway down meant
       scrolling up first. This is `fixed`, so it stays with you wherever you
       are, and switching from it never expands the column.

       Restore sits at the top, above the hairline: it is the one control whose
       position should not move as creatives are added, so it stays where the
       eye lands first rather than sliding down the pill.

       Numbered rather than type-icon'd on purpose. The icons say banner /
       video / social, and a preview is usually several banners — four identical
       squares identify nothing. The number matches the row's position in the
       list, and the name is on hover and on the accessible label.

       The wrapper stays mounted so the Transition inside has something to
       animate against; it is `pointer-events-none` so an invisible fixed box
       never eats clicks meant for the creatives. -->
  <div class="pointer-events-none fixed left-4 top-1/2 z-30 hidden -translate-y-1/2 lg:block">
    <Transition name="p2-rail">
      <nav
        v-if="collapsed && !forceDrawerNav"
        class="p2-glass pointer-events-auto flex max-h-[70vh] flex-col items-center gap-1 overflow-y-auto rounded-full p-2 shadow-lg"
        aria-label="Creatives"
        data-rail
      >
        <button
          type="button"
          class="p2-rail-item grid h-8 w-8 shrink-0 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-200 ease-p2-expo"
          title="Show the creatives list"
          aria-label="Show the creatives list"
          @click="$emit('expand')"
        >
          <PanelLeftOpen class="h-4 w-4" />
        </button>

        <span
          v-if="sorted.length"
          aria-hidden="true"
          class="my-0.5 h-px w-4 shrink-0"
          :style="{ background: 'var(--p2-hairline)' }"
        />

        <button
          v-for="(cat, i) in sorted"
          :key="cat.id"
          type="button"
          class="p2-rail-item grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold transition-colors duration-200 ease-p2-expo"
          :style="
            activeCategory?.id === cat.id
              ? { background: 'var(--p2-accent-soft)', color: 'var(--p2-accent)' }
              : { color: 'var(--p2-text-muted)' }
          "
          :title="cat.name"
          :aria-label="cat.name"
          :aria-current="activeCategory?.id === cat.id ? 'true' : undefined"
          :data-active="activeCategory?.id === cat.id"
          @click="$emit('select', cat.id)"
        >
          {{ i + 1 }}
        </button>
      </nav>
    </Transition>
  </div>

  <!-- Mobile drawer -->
  <Transition name="fade">
    <div
      v-if="isMobileOpen"
      :class="['fixed inset-0 z-40 backdrop-blur-md', forceDrawerNav ? '' : 'lg:hidden']"
      style="background: rgba(11, 11, 16, 0.55);"
      @click="$emit('close')"
    />
  </Transition>
  <Transition name="slide-left">
    <aside
      v-if="isMobileOpen"
      :class="['fixed inset-y-0 left-0 z-50 w-[85%] max-w-sm shadow-2xl', forceDrawerNav ? '' : 'lg:hidden']"
      :style="{ background: 'var(--p2-bg)' }"
      @click.stop
    >
      <div
        class="flex items-center justify-between border-b px-5 py-4"
        :style="{ borderColor: 'var(--p2-hairline)' }"
      >
        <div>
          <p class="p2-label" :style="{ color: 'var(--p2-accent)' }">Projects</p>
          <div class="mt-1 text-xs text-[var(--p2-text-muted)]">
            <span class="p2-mono">{{ categories.length }}</span>
            {{ categories.length === 1 ? 'project' : 'projects' }}
          </div>
        </div>
        <button
          type="button"
          class="grid h-8 w-8 place-items-center rounded-full text-[var(--p2-text-muted)] transition-colors duration-200 ease-p2-expo hover:text-[var(--p2-text)]"
          :style="{ background: 'var(--p2-surface-muted)', borderColor: 'var(--p2-border)' }"
          aria-label="Close"
          @click="$emit('close')"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
      <div class="h-[calc(100%-72px)] overflow-y-auto px-3 py-3" data-category-list>
        <div class="flex flex-col gap-1">
          <button
            v-for="cat in sorted"
            :key="cat.id"
            type="button"
            :data-active="cat.is_active === 1"
            :class="[
              'group relative w-full rounded-xl px-3 py-2.5 text-left transition-all duration-300 ease-p2-expo',
              cat.is_active === 1
                ? 'bg-[var(--p2-accent-soft)] text-[var(--p2-text)]'
                : 'text-[var(--p2-text)] hover:bg-[var(--p2-accent-soft)]',
            ]"
            @click="$emit('select', cat.id)"
          >
            <span
              v-if="cat.is_active === 1"
              class="absolute inset-y-2 left-0 w-0.5 rounded-r-full"
              :style="{ background: 'var(--p2-accent)' }"
            />
            <div class="flex items-center gap-2.5">
              <span
                class="grid h-7 w-7 shrink-0 place-items-center rounded-lg"
                :style="{ color: 'var(--p2-accent)', background: 'var(--p2-accent-soft)' }"
                :title="typeLabel(cat.type)"
              >
                <component :is="typeIcon(cat.type)" class="h-3.5 w-3.5" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium">{{ cat.name }}</div>
                <div class="p2-mono mt-0.5 text-[11px] tracking-wide text-[var(--p2-text-subtle)]">
                  {{ formatDate(cat.created_at) }}
                </div>
              </div>
            </div>
          </button>
        </div>
      </div>
    </aside>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(-100%);
}

/* Collapsing / restoring the desktop column.
 *
 * The width is what carries the movement: the parent is a flex row, so as this
 * narrows the creatives widen into the space in the same frame — one motion
 * rather than the column vanishing and the content jumping after it.
 *
 * `overflow: hidden` only while animating. The card inside is a fixed `w-72`,
 * so it gets clipped as the column narrows instead of its rows reflowing into
 * an ever-thinner strip on the way out.
 *
 * `margin-right` cancels the row's `gap-6`. Without it the 24px gutter would
 * still be there at width 0 and disappear on unmount — a small jump right at
 * the end of an otherwise smooth move.
 *
 * The `!important` is deliberate and narrow: it exists to beat the `w-72`
 * utility on the element, which is the steady-state width the transition
 * animates from and back to. */
.p2-col-enter-active,
.p2-col-leave-active {
  overflow: hidden;
  transition:
    width 340ms var(--p2-ease-cinema),
    margin-right 340ms var(--p2-ease-cinema),
    transform 340ms var(--p2-ease-cinema),
    opacity 200ms ease;
}
.p2-col-enter-from,
.p2-col-leave-to {
  width: 0 !important;
  margin-right: -1.5rem;
  transform: translateX(-1.25rem);
  opacity: 0;
}

/* The rail slides in from the edge it belongs to, a beat after the column has
   finished leaving so the two do not cross over each other. */
.p2-rail-enter-active {
  transition:
    opacity 220ms ease 120ms,
    transform 320ms var(--p2-ease-cinema) 120ms;
}
.p2-rail-leave-active {
  transition:
    opacity 160ms ease,
    transform 220ms var(--p2-ease-cinema);
}
.p2-rail-enter-from,
.p2-rail-leave-to {
  opacity: 0;
  transform: translateX(-1.5rem);
}

/* Hover only on the inactive items — the active one already carries the accent
   fill and should not flicker to a different colour under the cursor. */
.p2-rail-item:not([data-active='true']):hover {
  background: var(--p2-surface-muted);
  color: var(--p2-text);
}

/* The rail scrolls when a preview has more creatives than fit; the scrollbar
   itself would break the pill's outline, so it is hidden. */
[data-rail] {
  scrollbar-width: none;
}
[data-rail]::-webkit-scrollbar {
  display: none;
}

/* Someone who has asked the OS for less motion gets the state change without
   the slide — the rest of this page honours that too. */
@media (prefers-reduced-motion: reduce) {
  .p2-col-enter-active,
  .p2-col-leave-active {
    transition: opacity 120ms ease;
  }
  .p2-col-enter-from,
  .p2-col-leave-to {
    width: 18rem !important;
    margin-right: 0;
    transform: none;
  }
  .p2-rail-enter-active,
  .p2-rail-leave-active {
    transition: opacity 120ms ease;
  }
  .p2-rail-enter-from,
  .p2-rail-leave-to {
    transform: none;
  }
}
</style>
