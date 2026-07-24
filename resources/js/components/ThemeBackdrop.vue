<script setup lang="ts">
// Decorative ambient accent wash + dark-mode starfield. Purely cosmetic —
// mirrors the Reporting/Update2 chrome. Sits behind page content (z-0);
// wrap your content in a z-[1] container. Only meaningful inside `.p9-theme`.
</script>

<template>
    <div aria-hidden="true" class="p9-ambient" />
    <div aria-hidden="true" class="p9-stars" />
</template>
