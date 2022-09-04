<template>
  <Disclosure as="nav" class="bg-white shadow-sm" v-slot="{ open }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 justify-between">
        <div class="w-10 sm:hidden"></div>

        <div class="flex grow justify-center sm:justify-start">
          <div class="flex flex-shrink-0 items-center">
            <img
              class="h-12 w-auto"
              src="/images/logo.png"
              alt="FSR 5 Logo"
              loading="lazy"
            />
          </div>
          <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
            <InertiaLink
              v-for="item in navigation"
              :key="item.title"
              :href="item.href"
              :class="{
                'border-fhac-mint text-gray-900': $page.url == item.href,
                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300':
                  $page.url != item.href,
              }"
              class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
            >
              {{ item.title }}
            </InertiaLink>
          </div>
        </div>

        <div class="-mr-2 flex items-center sm:hidden w-10">
          <!-- Mobile menu button -->
          <DisclosureButton
            class="inline-flex items-center justify-center rounded-md bg-white p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-fhac-mint focus:ring-offset-2"
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
      <div class="space-y-1 pt-2 pb-3">
        <DisclosureButton
          v-for="item in navigation"
          :key="item.title"
          :as="InertiaLink"
          :href="item.href"
          :class="{
            'bg-gray-100 border-fhac-mint text-fhac-mint-dark':
              $page.url == item.href,
            'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800':
              $page.url != item.href,
          }"
          class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium"
        >
          {{ item.title }}
        </DisclosureButton>
      </div>
    </DisclosurePanel>
  </Disclosure>
</template>

<script setup lang="ts">
import { Link as InertiaLink } from "@inertiajs/inertia-vue3";
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";

defineProps({
  navigation: {
    type: Array as () => NavbarLink[],
    required: true,
  },
});
</script>
