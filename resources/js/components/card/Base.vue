<template>
  <div
    class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow dark:divide-gray-700 dark:bg-gray-900"
  >
    <div class="p-6">
      <slot />
    </div>
    <div v-if="$slots.footer">
      <slot name="footer" />
    </div>
  </div>
</template>
