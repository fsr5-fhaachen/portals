<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" :showAdminLink="user.is_admin" />

    <div class="px-4 pt-6 sm:mx-auto sm:w-full sm:max-w-lg sm:px-6 lg:px-8">
      <AppMessage :message="message" />
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

const { initColorMode } = useColorMode();
initColorMode();

const { pages } = defineProps({
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

const navigation = [
  { title: "Veranstaltungen", href: "/dashboard" },
  ...usePagesAsNavigation(pages, "/dashboard/"),
];
</script>
