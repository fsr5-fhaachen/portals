<template>
  <div>
    <slot />
  </div>
</template>

<script setup lang="ts">
const { initColorMode } = useColorMode();
initColorMode();
</script>
