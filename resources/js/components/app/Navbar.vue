<template>
  <Disclosure
    as="nav"
    class="bg-white shadow-sm dark:bg-black"
    v-slot="{ open }"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 justify-between">
        <div class="w-10 sm:hidden">
          <div class="flex h-full items-center justify-start px-4">
            <ColorModeButton class="block h-6 w-6" />
          </div>
        </div>

        <div class="flex grow justify-center sm:justify-start">
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
          <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
            <InertiaLink
              v-for="item in navigation"
              :key="item.title"
              :href="item.href"
              :class="{
                'border-fhac-mint text-gray-900 dark:text-gray-100':
                  $page.url == item.href,
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-100':
                  $page.url != item.href,
              }"
              class="inline-flex items-center border-b-[3px] px-1 pt-1 text-sm font-medium"
            >
              {{ item.title }}
            </InertiaLink>
          </div>
          <div
            class="hidden grow justify-end sm:-my-px sm:ml-6 sm:flex sm:space-x-8"
          >
            <InertiaLink
              v-if="user.permissionsArray.includes('view statistics')"
              href="/dashboard/admin"
              :class="{
                'border-red-500 text-red-900': $page.url == '/dashboard/admin',
                'border-transparent text-red-500 hover:border-red-300 hover:text-red-700':
                  $page.url != '/dashboard/admin',
              }"
              class="inline-flex items-center border-b-[3px] px-1 pt-1 text-sm font-medium"
            >
              Statistik
            </InertiaLink>
            <InertiaLink
              v-if="
                modules['randomGenerator']?.active &&
                user.permissionsArray.includes('manage random generator')
              "
              href="/dashboard/admin/random-generator"
              :class="{
                'border-red-500 text-red-900':
                  $page.url == '/dashboard/admin/random-generator',
                'border-transparent text-red-500 hover:border-red-300 hover:text-red-700':
                  $page.url != '/dashboard/admin/random-generator',
              }"
              class="inline-flex items-center border-b-[3px] px-1 pt-1 text-sm font-medium"
            >
              Zufallsgenerator
            </InertiaLink>
            <InertiaLink
              v-if="user.permissionsArray.includes('manage users')"
              href="/dashboard/admin/users"
              :class="{
                'border-red-500 text-red-900':
                  $page.url == '/dashboard/admin/users',
                'border-transparent text-red-500 hover:border-red-300 hover:text-red-700':
                  $page.url != '/dashboard/admin/users',
              }"
              class="inline-flex items-center border-b-[3px] px-1 pt-1 text-sm font-medium"
            >
              User Verwaltung
            </InertiaLink>
            <InertiaLink
              v-if="user.permissionsArray.includes('manage users')"
              href="/dashboard/admin/register"
              :class="{
                'border-red-500 text-red-900':
                  $page.url == '/dashboard/admin/register',
                'border-transparent text-red-500 hover:border-red-300 hover:text-red-700':
                  $page.url != '/dashboard/admin/register',
              }"
              class="inline-flex items-center border-b-[3px] px-1 pt-1 text-sm font-medium"
            >
              User registrieren/zuweisen
            </InertiaLink>
          </div>
          <div class="hidden h-full items-center justify-end px-4 sm:flex">
            <ColorModeButton />
          </div>
        </div>

        <div class="-mr-2 flex w-10 items-center sm:hidden">
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
      <div class="space-y-1 pb-3 pt-2">
        <DisclosureButton
          v-for="item in navigation"
          :key="item.title"
          :as="InertiaLink"
          :href="item.href"
          :class="{
            'border-fhac-mint bg-gray-100 text-fhac-mint-dark dark:bg-black dark:text-gray-100':
              $page.url == item.href,
            'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-gray-100':
              $page.url != item.href,
          }"
          class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
        >
          {{ item.title }}
        </DisclosureButton>
        <DisclosureButton
          v-if="user.permissionsArray.includes('view statistics')"
          :as="InertiaLink"
          href="/dashboard/admin"
          :class="{
            'border-red-500 bg-red-100 text-red-900 dark:bg-black dark:text-red-500':
              $page.url == '/dashboard/admin',
            'border-transparent text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-900 dark:hover:text-red-100':
              $page.url != '/dashboard/admin',
          }"
          class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
        >
          Statistik
        </DisclosureButton>
        <DisclosureButton
          v-if="
            modules['randomGenerator']?.active &&
            user.permissionsArray.includes('manage random generator')
          "
          :as="InertiaLink"
          href="/dashboard/admin/random-generator"
          :class="{
            'border-red-500 bg-red-100 text-red-900 dark:bg-black dark:text-red-500':
              $page.url == '/dashboard/admin/random-generator',
            'border-transparent text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-900 dark:hover:text-red-100':
              $page.url != '/dashboard/admin/random-generator',
          }"
          class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
        >
          Zufallsgenerator
        </DisclosureButton>
        <DisclosureButton
          v-if="user.permissionsArray.includes('manage users')"
          :as="InertiaLink"
          href="/dashboard/admin/users"
          :class="{
            'border-red-500 bg-red-100 text-red-900 dark:bg-black dark:text-red-500':
              $page.url == '/dashboard/admin/users',
            'border-transparent text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-900 dark:hover:text-red-100':
              $page.url != '/dashboard/admin/users',
          }"
          class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
        >
          User Verwaltung
        </DisclosureButton>
        <DisclosureButton
          v-if="user.permissionsArray.includes('manage users')"
          :as="InertiaLink"
          href="/dashboard/admin/register"
          :class="{
            'border-red-500 bg-red-100 text-red-900 dark:bg-black dark:text-red-500':
              $page.url == '/dashboard/admin/register',
            'border-transparent text-red-600 hover:border-red-300 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-gray-900 dark:hover:text-red-100':
              $page.url != '/dashboard/admin/register',
          }"
          class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
        >
          User registrieren/zuweisen
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
  user: {
    type: Object as () => Models.User,
    default: false,
  },
  modules: {
    type: Array as () => App.Models.Module[],
    default: () => [],
  },
});
</script>
