<script setup lang="ts">
/**
 * DEMO ONLY: the player used inside every frame.
 * Feed-style: muted autoplay on hover, loops, click pins playback, sound
 * toggle, remaining-time badge, thin seekable progress bar. `native` shows the
 * browser controls instead (Clean view). The default slot receives the
 * playback state so frames can drive their own chrome (story progress bars).
 */
import { computed, ref } from 'vue'
import { Play, Volume2, VolumeX } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    src: string
    fit?: 'cover' | 'contain'
    native?: boolean
    /** Where the sound button and time badge sit, so they clear the frame's own UI. */
    /** 'story': just under a story header (progress bars + account row). */
    uiAt?: 'bottom' | 'top' | 'story'
  }>(),
  { fit: 'cover', native: false, uiAt: 'bottom' },
)

const el = ref<HTMLVideoElement | null>(null)
const ready = ref(false)
const playing = ref(false)
const muted = ref(true)
const pinned = ref(false)
const duration = ref(0)
const time = ref(0)

const fmt = (s: number) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`
const remaining = computed(() => fmt(Math.max(0, duration.value - time.value)))
const total = computed(() => fmt(duration.value))
const progress = computed(() => (duration.value ? (time.value / duration.value) * 100 : 0))

const play = () => el.value?.play().catch(() => {})
const pause = () => el.value?.pause()

const onEnter = () => {
  if (!props.native) play()
}
const onLeave = () => {
  if (!props.native && !pinned.value) pause()
}
const onClick = () => {
  if (props.native) return
  if (playing.value && pinned.value) {
    pinned.value = false
    pause()
  } else {
    pinned.value = true
    play()
  }
}
const toggleMute = () => {
  muted.value = !muted.value
  if (el.value) el.value.muted = muted.value
  if (!muted.value) {
    pinned.value = true
    play()
  }
}
const seek = (e: MouseEvent) => {
  const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
  if (el.value && duration.value) el.value.currentTime = ((e.clientX - r.left) / r.width) * duration.value
}
</script>

<template>
  <div class="relative h-full w-full overflow-hidden bg-black" @mouseenter="onEnter" @mouseleave="onLeave">
    <video
      ref="el"
      :src="`${src}#t=0.1`"
      :muted="muted"
      loop
      playsinline
      preload="metadata"
      :controls="native"
      controlsList="nodownload noremoteplayback"
      disablePictureInPicture
      class="absolute inset-0 h-full w-full"
      :class="[fit === 'cover' ? 'object-cover' : 'object-contain', native ? '' : 'cursor-pointer']"
      @loadedmetadata="duration = el?.duration || 0"
      @loadeddata="ready = true"
      @error="ready = true"
      @timeupdate="time = el?.currentTime || 0"
      @play="playing = true"
      @pause="playing = false"
      @click="onClick"
    />
    <div v-if="!ready" class="p2-shimmer pointer-events-none absolute inset-0" />

    <!-- Frame chrome / safe zones -->
    <div class="pointer-events-none absolute inset-0">
      <slot :progress="progress" :remaining="remaining" :total="total" :playing="playing" />
    </div>

    <template v-if="!native">
      <!-- Play hint while paused -->
      <Transition name="vp-fade">
        <div v-if="ready && !playing" class="pointer-events-none absolute inset-0 grid place-items-center">
          <span class="grid h-11 w-11 place-items-center rounded-full bg-black/45 text-white ring-1 ring-white/25 backdrop-blur-sm">
            <Play class="ml-0.5 h-5 w-5" fill="currentColor" />
          </span>
        </div>
      </Transition>

      <div
        class="absolute left-2 flex items-center gap-1.5"
        :class="uiAt === 'top' ? 'top-2' : uiAt === 'story' ? 'top-[3.25rem]' : 'bottom-3'"
      >
        <span class="rounded-full bg-black/55 px-1.5 py-0.5 text-[10px] font-medium tabular-nums text-white backdrop-blur-sm">
          {{ playing ? remaining : total }}
        </span>
        <button
          type="button"
          class="grid h-6 w-6 place-items-center rounded-full bg-black/55 text-white backdrop-blur-sm transition hover:bg-black/75"
          :aria-label="muted ? 'Unmute' : 'Mute'"
          :title="muted ? 'Sound on' : 'Sound off'"
          @click.stop="toggleMute"
        >
          <VolumeX v-if="muted" class="h-3.5 w-3.5" />
          <Volume2 v-else class="h-3.5 w-3.5" />
        </button>
      </div>

      <div class="absolute inset-x-0 bottom-0 h-1.5 cursor-pointer" title="Seek" @click.stop="seek">
        <div class="absolute inset-x-0 bottom-0 h-[3px] bg-white/20">
          <div class="h-full bg-white" :style="{ width: `${progress}%` }" />
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.vp-fade-enter-active,
.vp-fade-leave-active {
  transition: opacity 0.2s ease;
}
.vp-fade-enter-from,
.vp-fade-leave-to {
  opacity: 0;
}
</style>
