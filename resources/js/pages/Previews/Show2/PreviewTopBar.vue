<script setup lang="ts">
import { computed, inject, type Ref } from 'vue'
import { Menu, Palette, LogOut, Calendar, Users, Sun, Moon } from 'lucide-vue-next'

const props = defineProps<{
  preview: any
  client: any
  headerLogo: any
  viewers: string[]
  isAuthenticated: boolean
  authUserClientName: string
}>()

defineEmits<{
  (e: 'open-sidebar'): void
  (e: 'open-palette'): void
  (e: 'logout', evt: Event): void
}>()

const theme = inject<{ isDark: Ref<boolean>; toggleDark: () => void }>('show2Theme')!
const isDark = computed(() => theme.isDark.value)
const toggleDark = () => theme.toggleDark()

// When the user scrolls past the topbar, the logo fades out here and
// fades into the top of the sticky sidebar (handled in ProjectSidebar).
// The container keeps its lg:w-72 width so the layout doesn't shift.
const isScrolled = inject<Ref<boolean>>('show2Scrolled')

const formatDate = (s: string) => {
  if (!s) return ''
  const d = new Date(s)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const initials = (name: string) =>
  name?.trim()?.charAt(0).toUpperCase() || '?'

const showPlanetNineLogo = computed(() => props.preview?.show_planetnine_logo === 1 && props.headerLogo?.logo)
const showSidebarLogo = computed(() => props.preview?.show_sidebar_logo === 1 && props.client?.logo)
const isPlanetNineStaff = computed(() => props.authUserClientName === 'Planet Nine')
</script>

<template>
  <header
    class="sticky top-0 z-30 border-b backdrop-blur-xl"
    :style="{ borderColor: 'var(--p2-hairline)', background: 'color-mix(in srgb, var(--p2-bg) 75%, transparent)' }"
  >
    <!-- Accent hairline — 1px gradient stroke marking the boundary
         between the chrome and the canvas. Subtle in light mode,
         glowing in dark. -->
    <div
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 h-px opacity-70"
      :style="{
        background: 'linear-gradient(90deg, transparent 0%, var(--p2-accent) 35%, var(--p2-accent-2) 65%, transparent 100%)',
      }"
    />
    <div class="mx-auto flex w-full max-w-[2000px] items-center gap-4 px-4 py-3 lg:gap-6 lg:px-8">
      <!-- Mobile menu trigger -->
      <button
        type="button"
        class="grid h-9 w-9 place-items-center rounded-full border bg-[var(--p2-surface-muted)] text-[var(--p2-text-muted)] backdrop-blur-md transition-colors duration-200 ease-p2-expo hover:text-[var(--p2-text)] lg:hidden"
        :style="{ borderColor: 'var(--p2-border)' }"
        aria-label="Open projects"
        @click="$emit('open-sidebar')"
      >
        <Menu class="h-4 w-4" />
      </button>

      <!-- Logo column — on desktop sits over the sidebar (matches its
           w-72), with the logo centered inside it. Fades + lifts away
           when scrolled, so the logo can re-appear inside the sticky
           sidebar; the container keeps its width either way to avoid
           layout shift. -->
      <div class="flex shrink-0 items-center justify-center lg:w-72">
        <div
          class="transition-all duration-500 ease-p2-cinema"
          :class="isScrolled
            ? 'opacity-0 -translate-y-1.5 pointer-events-none'
            : 'opacity-100 translate-y-0'"
        >
          <img
            v-if="showPlanetNineLogo"
            :src="`/logos/${headerLogo.logo}`"
            alt="logo"
            class="h-11 w-auto rounded"
          />
          <img
            v-else-if="showSidebarLogo"
            :src="`/logos/${client.logo}`"
            alt="logo"
            class="h-9 w-auto rounded"
          />
        </div>
      </div>

      <!-- Preview info — sits in the same column as the main content. -->
      <div class="min-w-0 flex-1">
        <p class="p2-label">Preview</p>
        <h1 class="mt-1 truncate text-sm font-semibold tracking-tight text-[var(--p2-text)] sm:text-base">
          {{ preview.name }}
        </h1>
        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-[var(--p2-text-muted)]">
          <span v-if="client?.name" class="truncate">{{ client.name }}</span>
          <span
            v-if="client?.name"
            class="hidden h-1 w-1 rounded-full sm:inline-block"
            :style="{ background: 'var(--p2-border-strong)' }"
          />
          <span class="p2-mono hidden items-center gap-1.5 text-[11px] tracking-wide sm:inline-flex">
            <Calendar class="h-3 w-3" />
            {{ formatDate(preview.created_at) }}
          </span>
        </div>
      </div>

      <div class="ml-auto flex items-center gap-2">
        <!-- Viewer badges (Planet Nine staff only) -->
        <div
          v-if="isPlanetNineStaff && viewers.length"
          class="hidden items-center gap-2 rounded-full border bg-[var(--p2-surface-muted)] px-2.5 py-1 backdrop-blur-md sm:flex"
          :style="{ borderColor: 'var(--p2-border)' }"
        >
          <Users class="h-3.5 w-3.5 text-[var(--p2-text-subtle)]" />
          <div class="flex -space-x-1.5">
            <span
              v-for="(v, i) in viewers.slice(0, 4)"
              :key="i"
              class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-semibold text-white"
              :style="{ background: 'var(--p2-accent)', boxShadow: '0 0 0 2px var(--p2-bg)' }"
              :title="v"
            >
              {{ initials(v) }}
            </span>
            <span
              v-if="viewers.length > 4"
              class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-semibold text-[var(--p2-text-muted)]"
              :style="{ background: 'var(--p2-surface)', boxShadow: '0 0 0 2px var(--p2-bg)' }"
            >
              +{{ viewers.length - 4 }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2" data-tour="theme">
          <button
            type="button"
            class="grid h-9 w-9 place-items-center rounded-full border bg-[var(--p2-surface-muted)] text-[var(--p2-text-muted)] backdrop-blur-md transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
            :style="{ borderColor: 'var(--p2-border)' }"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <Sun v-if="isDark" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="inline-flex h-9 items-center gap-2 rounded-full border bg-[var(--p2-surface-muted)] px-4 text-sm font-medium text-[var(--p2-text-muted)] backdrop-blur-md transition-colors duration-300 ease-p2-expo hover:text-[var(--p2-accent)]"
            :style="{ borderColor: 'var(--p2-border)' }"
            aria-label="Theme"
            @click="$emit('open-palette')"
          >
            <Palette class="h-4 w-4" />
            <span class="hidden sm:inline">Theme</span>
          </button>
        </div>

        <form
          v-if="preview?.requires_login"
          method="POST"
          action="/preview/logout"
          @submit="(e) => $emit('logout', e)"
        >
          <input type="hidden" name="preview_id" :value="preview.id" />
          <button
            type="submit"
            class="inline-flex h-9 items-center gap-2 rounded-full border border-red-500/30 bg-red-500/10 px-4 text-sm font-medium text-red-500 backdrop-blur-md transition-colors duration-300 ease-p2-expo hover:border-red-500/50 hover:bg-red-500/15"
          >
            <LogOut class="h-4 w-4" />
            <span class="hidden sm:inline">Logout</span>
          </button>
        </form>
      </div>
    </div>
  </header>
</template>
