<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" />

    <slot />
  </div>
</template>

<script setup lang="ts">
const navigation = [
  { title: "Veranstaltungen", href: "/dashboard" },
  { title: "Test", href: "/dashboard/test" },
];
</script>
