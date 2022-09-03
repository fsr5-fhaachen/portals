<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" />

    <div class="sm:mx-auto sm:w-full sm:max-w-lg px-4 sm:px-6 lg:px-8 pt-6">
      <AppMessage :message="message" />
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
});

const navigation = [
  { title: "Veranstaltungen", href: "/dashboard" },
  { title: "Test", href: "/dashboard/test" },
];
</script>
