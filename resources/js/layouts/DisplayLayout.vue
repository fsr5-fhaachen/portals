<template>
  <div
    :class="[
      'flex h-screen flex-col overflow-hidden bg-cover',
      backgroundClass,
    ]"
  >
    <div class="flex-grow">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
const useKeyableBackground =
  new URLSearchParams(window.location.search).get("backgroundKeyable") ===
  "true";
const backgroundClass = useKeyableBackground
  ? "bg-green-500"
  : 'bg-slate-950 bg-[url("/images/layout/display/background.jpg")]';
</script>
