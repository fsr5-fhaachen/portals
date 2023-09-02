<template>
  <div class="flex min-h-full flex-col py-8 sm:px-6 lg:px-8 lg:py-16">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <img
        class="mx-auto h-48 w-auto dark:hidden"
        src="/images/logo.png"
        alt="FSR 5 Logo"
        loading="lazy"
      />
      <img
        class="mx-auto hidden h-48 w-auto dark:block"
        src="/images/logo-dark.png"
        alt="FSR 5 Logo"
        loading="lazy"
      />
      <h2
        class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100"
      >
        Herzlich Willkommen in der {{ title }} {{ new Date().getFullYear() }}
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div
        class="bg-white px-4 py-8 shadow dark:bg-gray-900 sm:rounded-lg sm:px-10"
      >
        <div class="flex justify-end">
          <ColorModeButton />
        </div>

        <AppMessage :message="message" />

        <div>
          <slot />
        </div>

        <div class="mt-6 border-t border-gray-300 dark:border-gray-700">
          <div class="mt-4 flex items-center justify-center px-2 text-sm">
            <AppLink :href="packageRepositoryUrl" theme="gray">
              Powered by {{ packageName }}
            </AppLink>
          </div>
          <div class="mt-6 px-2 text-sm text-gray-500 dark:text-gray-400">
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
    </div>
  </div>
</template>

<script setup lang="ts">
const packageName = __PACKAGE_NAME__;
const packageRepositoryUrl = __PACKAGE_REPOSITORY_URL__;

const { initColorMode } = useColorMode();
initColorMode();

const props = defineProps({
  appEventType: {
    type: String,
    default: "demo",
  },
  message: {
    type: Object,
    default: () => ({}),
  },
});

const { title } = useAppEventType(props.appEventType);
</script>
