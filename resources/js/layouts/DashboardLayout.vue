<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" />

    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
      <AppMessage :message="message" />
    </div>

    <div>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";

const { pages, user } = defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
  pages: {
    type: Array as PropType<App.Models.Page[]>,
    default: () => [],
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const isTutorPage = ref(window.location.pathname.includes("/dashboard/tutor"));
const navigation = [
  {
    title: "Veranstaltungen",
    href: "/dashboard" + (isTutorPage ? "/tutor" : ""),
  },
  ...usePagesAsNavigation(pages, "/dashboard/"),
];
</script>
