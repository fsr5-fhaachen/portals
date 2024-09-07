<template>
  <div class="flex h-full flex-col">
    <div class="flex-grow">
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
const { initColorMode } = useColorMode();
initColorMode();

const packageName = __PACKAGE_NAME__;
const packageRepositoryUrl = __PACKAGE_REPOSITORY_URL__;
</script>
