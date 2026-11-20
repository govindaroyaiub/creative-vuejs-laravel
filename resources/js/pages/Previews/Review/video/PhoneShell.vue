<script setup lang="ts">
/** DEMO ONLY: a generic phone body. The screen content goes in the slot. */
import { Signal, Wifi, BatteryFull } from 'lucide-vue-next'

withDefaults(defineProps<{ width?: number; screenAspect?: string; statusDark?: boolean }>(), {
  width: 272,
  screenAspect: '9 / 19.5',
  statusDark: false,
})
</script>

<template>
  <div class="phone" :style="{ width: `${width}px` }">
    <div class="phone-screen" :style="{ aspectRatio: screenAspect }">
      <slot />
      <!-- Status bar -->
      <div
        class="pointer-events-none absolute inset-x-0 top-0 z-20 flex h-7 items-center justify-between px-5 text-[10px] font-semibold"
        :class="statusDark ? 'text-zinc-900' : 'text-white'"
      >
        <span>9:41</span>
        <span class="phone-island" />
        <span class="flex items-center gap-1">
          <Signal class="h-3 w-3" />
          <Wifi class="h-3 w-3" />
          <BatteryFull class="h-3.5 w-3.5" />
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.phone {
  flex-shrink: 0;
  max-width: 100%;
  /* outer radius = screen radius (36px) + bezel, so the curves run parallel */
  padding: 6px;
  border-radius: 42px;
  background: linear-gradient(145deg, #2a2a30, #0d0d10);
  box-shadow:
    0 0 0 1px rgb(255 255 255 / 0.06) inset,
    0 30px 60px -20px rgb(0 0 0 / 0.45),
    0 12px 24px -12px rgb(0 0 0 / 0.35);
}
.phone-screen {
  position: relative;
  overflow: hidden;
  border-radius: 36px;
  background: #000;
}
.phone-island {
  position: absolute;
  left: 50%;
  top: 7px;
  height: 17px;
  width: 74px;
  transform: translateX(-50%);
  border-radius: 9999px;
  background: #000;
}
</style>
