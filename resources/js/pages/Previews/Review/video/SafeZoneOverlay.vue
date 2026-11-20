<script setup lang="ts">
/**
 * DEMO ONLY: shaded areas that platform UI covers, so clients can see whether
 * text or logos get hidden. Generic estimates, not tied to one platform.
 *  - story: top bar, action column, caption + CTA
 *  - feed: keep key content inside a 6% margin
 *  - screen: broadcast action-safe (93%) and title-safe (90%)
 *  - article: the AdChoices / close corner
 */
import type { VideoFormat } from './useVideoFormat'

defineProps<{ format: VideoFormat }>()
</script>

<template>
  <div class="absolute inset-0">
    <template v-if="format === 'story'">
      <div class="sz sz-hatch" style="left: 0; right: 0; top: 0; height: 14%"><span class="sz-label">Profile &amp; top bar</span></div>
      <div class="sz sz-hatch" style="right: 0; width: 15%; top: 38%; bottom: 22%"><span class="sz-label sz-label-v">Actions</span></div>
      <div class="sz sz-hatch" style="left: 0; right: 0; bottom: 0; height: 22%"><span class="sz-label">Caption &amp; CTA</span></div>
    </template>
    <template v-else-if="format === 'feed'">
      <div class="sz sz-line" style="inset: 6%"><span class="sz-label">Keep text inside</span></div>
    </template>
    <template v-else-if="format === 'screen'">
      <div class="sz sz-line" style="inset: 3.5%"><span class="sz-label">Action safe 93%</span></div>
      <div class="sz sz-line sz-line-strong" style="inset: 5% 5% 5% 5%"><span class="sz-label" style="top: auto; bottom: 4px">Title safe 90%</span></div>
    </template>
    <template v-else>
      <div class="sz sz-hatch" style="right: 0; top: 0; width: 16%; height: 12%"><span class="sz-label">AdChoices</span></div>
    </template>
  </div>
</template>

<style scoped>
.sz {
  position: absolute;
}
.sz-hatch {
  background: repeating-linear-gradient(135deg, rgb(239 68 68 / 0.38) 0 6px, rgb(239 68 68 / 0.18) 6px 12px);
  box-shadow: inset 0 0 0 1px rgb(239 68 68 / 0.75);
}
.sz-line {
  border: 1.5px dashed rgb(250 204 21 / 0.9);
}
.sz-line-strong {
  border-color: rgb(239 68 68 / 0.9);
}
.sz-label {
  position: absolute;
  left: 6px;
  top: 4px;
  border-radius: 9999px;
  padding: 1px 6px;
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.02em;
  white-space: nowrap;
  color: #fff;
  background: rgb(220 38 38 / 0.9);
}
.sz-label-v {
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%) rotate(-90deg);
}
</style>
