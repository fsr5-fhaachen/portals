<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-6">
      <AppMessage :message="message" />
    </div>

    <div>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

const { pages } = defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
  pages: {
    type: Array as PropType<App.Models.Page[]>,
    default: () => [],
  },
});

const navigation = [
  { title: "Veranstaltungen", href: "/dashboard" },
  ...usePagesAsNavigation(pages, "/dashboard/"),
];
</script>
