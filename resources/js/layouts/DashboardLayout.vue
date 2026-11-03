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
      <div
        class="mt-4 flex items-center justify-center px-2 text-sm text-gray-500"
      >
        <AppLink href="https://fsr5.de/impressum" theme="gray">
          Impressum
        </AppLink>
      </div>
      <div
        class="mt-1 flex items-center justify-center px-2 text-sm text-gray-500"
      >
        <AppLink href="https://fsr5.de/datenschutzerklaerung" theme="gray">
          Datenschutzerklärung
        </AppLink>
      </div>
      <div class="mt-6 px-2 text-sm text-gray-500">
        <AppLink
          href="https://www.hetzner.com/?mtm_campaign=fh_aachen-ersti26&mtm_medium=referral&mtm_content=sponsoring_link"
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
import { usePage } from "@inertiajs/vue3";

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
    type: Object as PropType<Record<string, App.Models.Module>>,
    default: () => ({}),
  },
});

const page = usePage();

const isTutorPage = computed(() => page.url.startsWith("/dashboard/tutor"));
const isTutor = computed(() =>
  ["admin", "esa", "stage tutor", "tutor"].some((role) =>
    user.rolesArray?.includes(role),
  ),
);

const navigation = computed<NavbarLink[]>(() => [
  {
    title: "Veranstaltungen",
    href: "/dashboard" + (isTutorPage.value ? "/tutor" : ""),
  },
  ...(page.props.modules?.["scoring"]?.active && isTutor.value
    ? [{ title: "Scoring", href: "/dashboard/tutor/scoring" }]
    : []),
  ...usePagesAsNavigation(pages, "/dashboard/"),
]);
</script>
