<script setup lang="ts">
// Decorative ambient accent wash + dark-mode starfield. Purely cosmetic —
// mirrors the Reporting/Update2 chrome. Only meaningful inside `.p9-theme`.
//
// Both layers are `absolute; z-index: 0`, so any later sibling paints on top
// without needing a z-index of its own. Do NOT give the content wrapper one:
// a positioned element with a z-index opens a stacking context, which traps
// every `fixed` modal inside the page below the sidebar. Order in the DOM is
// what keeps this backdrop behind the content.
</script>

<template>
    <div aria-hidden="true" class="p9-ambient" />
    <div aria-hidden="true" class="p9-stars" />
</template>
