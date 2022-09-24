<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" :showAdminLink="user.is_admin" />

    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
      <AppMessage :message="message" />
    </div>

    <div>
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, PropType } from "vue";

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

const isTutorPage = computed(() => {
  return window.location.pathname.includes("/dashboard/tutor");
});
const navigation = [
  {
    title: "Veranstaltungen",
    href: "/dashboard" + (isTutorPage.value ? "/tutor" : ""),
  },
  ...usePagesAsNavigation(pages, "/dashboard/"),
];
</script>
