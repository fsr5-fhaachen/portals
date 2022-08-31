<template>
  <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <img
        class="mx-auto h-48 w-auto"
        src="/images/logo.png"
        alt="FSR 5 Logo"
        loading="lazy"
      />
      <h2
        class="mt-6 text-center text-3xl tracking-tight font-bold text-gray-900"
      >
        Herzlich Willkommen bei der Erstiwoche des FB5!
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div>
          <slot />
        </div>

        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
              <AppLink
                :href="packageRepositoryUrl"
                theme="gray"
                class="px-2 bg-white"
              >
                Powered by {{ packageName }}
              </AppLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const packageName = __PACKAGE_NAME__;
const packageRepositoryUrl = __PACKAGE_REPOSITORY_URL__;
</script>
