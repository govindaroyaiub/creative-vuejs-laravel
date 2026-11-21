<script setup lang="ts">
/**
 * Renders its content at a design width (real pixels) and scales it down to
 * the available width, keeping the outer height in step. Never upscales.
 * `fluid`: when there is more room than `width`, the content simply gets that
 * wider width (scale 1) instead of sitting centred with empty sides.
 */
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{ width: number; fluid?: boolean }>()
const outer = ref<HTMLElement | null>(null)
const inner = ref<HTMLElement | null>(null)
const scale = ref(1)
const innerH = ref(0)
const layoutW = ref(props.width)
let ro: ResizeObserver | null = null

const measure = () => {
  if (!outer.value || !inner.value) return
  // 2px less than the box, so the frame's 1px edges are never clipped.
  const cw = outer.value.clientWidth - 2
  layoutW.value = props.fluid ? Math.max(props.width, cw) : props.width
  scale.value = Math.min(1, cw / layoutW.value)
  innerH.value = inner.value.offsetHeight
}
onMounted(() => {
  ro = new ResizeObserver(measure)
  if (outer.value) ro.observe(outer.value)
  if (inner.value) ro.observe(inner.value)
  measure()
})
watch(() => [props.width, props.fluid], () => nextTick(measure))
onBeforeUnmount(() => ro?.disconnect())
</script>

<template>
  <div ref="outer" class="w-full overflow-hidden" :style="{ height: `${innerH * scale}px` }">
    <div
      ref="inner"
      class="mx-auto origin-top-left"
      :style="{ width: `${layoutW}px`, transform: `scale(${scale})`, marginLeft: scale < 1 ? 0 : 'auto' }"
    >
      <slot :scale="scale" />
    </div>
  </div>
</template>
