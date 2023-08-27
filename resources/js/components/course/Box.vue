<template>
  <div
    class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow dark:bg-gray-800 sm:px-6 sm:pt-6"
  >
    <dt>
      <div :class="[course.classes, 'absolute rounded-md p-3']">
        <div
          class="flex h-8 w-8 items-center justify-center text-2xl text-white"
        >
          <FontAwesomeIcon :icon="course.icon" />
        </div>
      </div>
      <p class="ml-20 truncate font-medium text-gray-500 dark:text-gray-400">
        {{ course.name }}
      </p>
    </dt>
    <dd class="ml-20 flex items-baseline">
      <slot />
    </dd>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  course: {
    type: Object as PropType<App.Models.Course>,
    required: true,
  },
});
</script>
