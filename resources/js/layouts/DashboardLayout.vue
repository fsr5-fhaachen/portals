<template>
  <div class="min-h-full">
    <AppNavbar :navigation="navigation" :user="user" :modules="modules" />

    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
      <AppMessage :message="message" />
    </div>

    <div>
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
          href="https://www.hetzner.com/de?mtm_campaign=fh_aachen24_sponsoring&mtm_med/"
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
import { computed, PropType } from "vue";

const { initColorMode } = useColorMode();
initColorMode();

const packageName = __PACKAGE_NAME__;
const packageRepositoryUrl = __PACKAGE_REPOSITORY_URL__;

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
    type: Object as PropType<Models.User>,
    required: true,
  },
  modules: {
    type: Array as PropType<App.Models.Module[]>,
    default: () => [],
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
