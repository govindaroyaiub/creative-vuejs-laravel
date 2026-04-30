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
  <header class="sticky top-0 z-30 border-b border-zinc-200/70 bg-white/85 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/85">
    <!-- Accent gradient stripe -->
    <div
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 h-[2px]"
      :style="{
        background: 'linear-gradient(90deg, var(--p2-accent) 0%, var(--p2-accent-2) 60%, var(--p2-accent) 100%)',
      }"
    />
    <div class="mx-auto flex w-full max-w-[1800px] items-center gap-4 px-4 py-3 lg:px-8">
      <!-- Mobile menu trigger -->
      <button
        type="button"
        class="rounded-lg border border-zinc-200 bg-white p-2 text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-zinc-100 lg:hidden"
        aria-label="Open projects"
        @click="$emit('open-sidebar')"
      >
        <Menu class="h-5 w-5" />
      </button>

      <!-- Brand block -->
      <div class="flex min-w-0 items-center gap-3">
        <img
          v-if="showPlanetNineLogo"
          :src="`/logos/${headerLogo.logo}`"
          alt="logo"
          class="h-12 w-auto shrink-0 rounded"
        />
        <img
          v-else-if="showSidebarLogo"
          :src="`/logos/${client.logo}`"
          alt="logo"
          class="h-9 w-auto shrink-0 rounded"
        />
        <div class="hidden h-8 w-px bg-zinc-200 dark:bg-zinc-800 sm:block" />
        <div class="min-w-0">
          <h1 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100 sm:text-base">
            {{ preview.name }}
          </h1>
          <div class="mt-0.5 flex items-center gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap">
            <span v-if="client?.name" class="truncate">{{ client.name }}</span>
            <span v-if="client?.name" class="hidden h-1 w-1 rounded-full bg-zinc-300 dark:bg-zinc-700 sm:inline-block" />
            <span class="hidden items-center gap-1 sm:inline-flex">
              <Calendar class="h-3 w-3" />
              {{ formatDate(preview.created_at) }}
            </span>
          </div>
        </div>
      </div>

      <div class="ml-auto flex items-center gap-2">
        <!-- Viewer badges (Planet Nine staff only) -->
        <div
          v-if="isPlanetNineStaff && viewers.length"
          class="hidden items-center gap-1 rounded-full border border-zinc-200 bg-white px-2 py-1 dark:border-zinc-800 dark:bg-zinc-900 sm:flex"
        >
          <Users class="h-3.5 w-3.5 text-zinc-400 dark:text-zinc-500" />
          <div class="flex -space-x-1.5">
            <span
              v-for="(v, i) in viewers.slice(0, 4)"
              :key="i"
              class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white text-[10px] font-semibold text-white dark:border-zinc-900"
              :style="{ background: `var(--p2-accent)` }"
              :title="v"
            >
              {{ initials(v) }}
            </span>
            <span
              v-if="viewers.length > 4"
              class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-zinc-200 text-[10px] font-semibold text-zinc-600 dark:border-zinc-900 dark:bg-zinc-800 dark:text-zinc-300"
            >
              +{{ viewers.length - 4 }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2" data-tour="theme">
          <button
            type="button"
            class="grid h-9 w-9 place-items-center rounded-lg border border-zinc-200 bg-white text-zinc-600 transition hover:border-[var(--p2-accent)] hover:text-[var(--p2-accent)] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <Sun v-if="isDark" class="h-4 w-4" />
            <Moon v-else class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-[var(--p2-accent)] hover:text-[var(--p2-accent)] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
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
            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-zinc-800"
          >
            <LogOut class="h-4 w-4" />
            <span class="hidden sm:inline">Logout</span>
          </button>
        </form>
      </div>
    </div>
  </header>
</template>
