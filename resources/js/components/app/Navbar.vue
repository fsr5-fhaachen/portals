<template>
  <!-- the inertia links overwrite the click handler that closes the menus, so the menus are recreated (closed) after every visit -->
  <Disclosure
    :key="page.url"
    as="nav"
    class="bg-white shadow-sm dark:bg-black"
    v-slot="{ open }"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="relative flex h-16 justify-between">
        <div class="flex grow justify-start">
          <div class="flex flex-shrink-0 items-center">
            <InertiaLink href="/">
              <img
                class="h-12 w-auto dark:hidden"
                src="/images/logo.png"
                alt="FSR 5 Logo"
                loading="lazy"
              />
              <img
                class="hidden h-12 w-auto dark:block"
                src="/images/logo-dark.png"
                alt="FSR 5 Logo"
                loading="lazy"
              />
            </InertiaLink>
          </div>
        </div>

        <p
          class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-lg font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-2xl"
        >
          {{ eventTitle }}
        </p>

        <!-- Desktop menu -->
        <div class="hidden items-center gap-4 sm:flex">
          <ColorModeButton />

          <Popover class="relative" v-slot="{ open: isMenuOpen }">
            <PopoverButton
              class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-fhac-mint focus:ring-offset-2 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100"
            >
              <span class="sr-only">Menü öffnen</span>
              <FontAwesomeIcon
                class="block h-6 w-6"
                :icon="['fas', isMenuOpen ? 'x' : 'bars']"
              />
            </PopoverButton>

            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="scale-95 opacity-0"
              enter-to-class="scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="scale-100 opacity-100"
              leave-to-class="scale-95 opacity-0"
            >
              <PopoverPanel
                class="absolute right-0 z-20 mt-2 max-w-[calc(100vw-2rem)] origin-top-right rounded-lg bg-white p-6 shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-900 dark:ring-gray-700"
                :class="adminGroups.length > 0 ? 'w-[44rem]' : 'w-64'"
              >
                <div class="flex flex-col gap-6 md:flex-row">
                  <div
                    class="flex flex-col gap-6"
                    :class="{ 'md:w-48 md:shrink-0': adminGroups.length > 0 }"
                  >
                    <div
                      v-for="group in generalGroups"
                      :key="group.title"
                      class="flex flex-col gap-2"
                    >
                      <p
                        class="text-xs font-semibold uppercase tracking-wide text-fhac-mint-dark dark:text-fhac-mint"
                      >
                        {{ group.title }}
                      </p>
                      <div class="flex flex-col gap-1">
                        <PopoverButton
                          v-for="link in group.links"
                          :key="link.href"
                          :as="InertiaLink"
                          :href="link.href"
                          :class="desktopLinkClass(group, link)"
                          class="rounded-md px-3 py-2 text-sm font-medium"
                        >
                          {{ link.title }}
                        </PopoverButton>
                      </div>
                    </div>
                  </div>

                  <div
                    v-if="adminGroups.length > 0"
                    class="grow border-t border-gray-200 pt-6 dark:border-gray-700 md:border-l md:border-t-0 md:pl-6 md:pt-0"
                  >
                    <div class="grid grid-cols-2 gap-6">
                      <div
                        v-for="group in adminGroups"
                        :key="group.title"
                        class="flex flex-col gap-2"
                      >
                        <p
                          class="text-xs font-semibold uppercase tracking-wide text-fhac-mint-dark dark:text-fhac-mint"
                        >
                          {{ group.title }}
                        </p>
                        <div class="flex flex-col gap-1">
                          <PopoverButton
                            v-for="link in group.links"
                            :key="link.href"
                            :as="InertiaLink"
                            :href="link.href"
                            :class="desktopLinkClass(group, link)"
                            class="rounded-md px-3 py-2 text-sm font-medium"
                          >
                            {{ link.title }}
                          </PopoverButton>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </PopoverPanel>
            </transition>
          </Popover>
        </div>

        <div class="-mr-2 flex items-center gap-4 sm:hidden">
          <ColorModeButton />

          <!-- Mobile menu button -->
          <DisclosureButton
            class="inline-flex items-center justify-center rounded-md bg-white p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-fhac-mint focus:ring-offset-2 dark:bg-black dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100"
          >
            <span class="sr-only">Open main menu</span>
            <FontAwesomeIcon
              v-if="!open"
              class="block h-6 w-6"
              :icon="['fas', 'bars']"
            />
            <FontAwesomeIcon
              v-else
              class="block h-6 w-6"
              :icon="['fas', 'x']"
            />
          </DisclosureButton>
        </div>
      </div>
    </div>

    <DisclosurePanel class="sm:hidden">
      <div class="flex flex-col gap-1 pb-3 pt-2">
        <template v-for="group in groups" :key="group.title">
          <p
            class="px-4 pt-3 text-xs font-semibold uppercase tracking-wide text-fhac-mint-dark dark:text-fhac-mint"
          >
            {{ group.title }}
          </p>
          <DisclosureButton
            v-for="link in group.links"
            :key="link.href"
            :as="InertiaLink"
            :href="link.href"
            :class="mobileLinkClass(group, link)"
            class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
          >
            {{ link.title }}
          </DisclosureButton>
        </template>
      </div>
    </DisclosurePanel>
  </Disclosure>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Link as InertiaLink, usePage } from "@inertiajs/vue3";
import {
  Disclosure,
  DisclosureButton,
  DisclosurePanel,
  Popover,
  PopoverButton,
  PopoverPanel,
} from "@headlessui/vue";

const { groups, isLinkActive } = useNavigation();

const page = usePage();
const eventTitle = computed(() => {
  const appEventType = useAppEventType(
    page.props.appEventType as Parameters<typeof useAppEventType>[0],
  );

  return `${appEventType.title} ${new Date().getFullYear()}`;
});

const generalGroups = computed(() =>
  groups.value.filter((group) => !group.isAdmin),
);
const adminGroups = computed(() =>
  groups.value.filter((group) => group.isAdmin),
);

const desktopLinkClass = (group: NavbarGroup, link: NavbarLink) => {
  const isActive = isLinkActive(link);

  if (group.isAdmin) {
    return isActive
      ? "bg-red-100 text-red-900 dark:bg-gray-800 dark:text-red-400"
      : "text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-800 dark:hover:text-red-300";
  }

  return isActive
    ? "bg-gray-100 text-fhac-mint-dark dark:bg-gray-800 dark:text-gray-100"
    : "text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100";
};

const mobileLinkClass = (group: NavbarGroup, link: NavbarLink) => {
  const isActive = isLinkActive(link);

  if (group.isAdmin) {
    return isActive
      ? "border-red-500 bg-red-100 text-red-900 dark:bg-black dark:text-red-500"
      : "border-transparent text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-900 dark:hover:text-red-100";
  }

  return isActive
    ? "border-fhac-mint bg-gray-100 text-fhac-mint-dark dark:bg-black dark:text-gray-100"
    : "border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100";
};
</script>
