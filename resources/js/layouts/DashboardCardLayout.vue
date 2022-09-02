<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" />

    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
const navigation = [
  { title: "Veranstaltungen", href: "/dashboard" },
  { title: "Test", href: "/dashboard/test" },
];
</script>
