<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" :showAdminLink="user.is_admin" />

    <div class="px-4 pt-6 sm:mx-auto sm:w-full sm:max-w-lg sm:px-6 lg:px-8">
      <AppMessage :message="message" />
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-lg">
      <slot />
    </div>

    <div class="mt-6 overflow-hidden pb-12">
      <div
        class="mt-4 flex items-center justify-center px-2 text-sm text-gray-500"
      >
        <AppLink :href="packageRepositoryUrl" theme="gray">
          Powered by {{ packageName }}
        </AppLink>
      </div>
      <div class="mt-6 px-2 text-sm text-gray-500">
        <AppLink
          href="https://www.hetzner.com/"
          theme="none"
          class="flex flex-col items-center justify-center gap-2"
        >
          <span>Hosted by</span>
          <img
            class=""
            src="/images/hetzner.png"
            alt="Hetzner"
            loading="lazy"
          />
        </AppLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

const { initColorMode } = useColorMode();
initColorMode();

const packageName = __PACKAGE_NAME__;
const packageRepositoryUrl = __PACKAGE_REPOSITORY_URL__;

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
