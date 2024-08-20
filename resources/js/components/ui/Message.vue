<template>
  <div
    :class="{
      'bg-blue-100 dark:bg-blue-900': type == 'info',
      'bg-green-100 dark:bg-green-900': type == 'success',
      'bg-red-100 dark:bg-red-900': type == 'error',
      'bg-yellow-100 dark:bg-yellow-900': type == 'warning',
    }"
    class="rounded-md bg-gray-50 p-4 dark:bg-gray-800"
  >
    <div class="flex">
      <div class="flex flex-shrink-0 items-center">
        <FontAwesomeIcon
          :class="{
            'text-blue-400 dark:text-blue-600': type == 'info',
            'text-green-400 dark:text-green-600': type == 'success',
            'text-red-400 dark:text-red-600': type == 'error',
            'text-yellow-400 dark:text-yellow-600': type == 'warning',
            'text-gray-400 dark:text-gray-600': ![
              'info',
              'success',
              'error',
              'warning',
            ].includes(type),
          }"
          class="h-5 w-5"
          :icon="['fas', icon]"
        />
      </div>
      <div class="ml-3">
        <div
          :class="{
            'text-blue-800 dark:text-blue-100': type == 'info',
            'text-green-800 dark:text-green-100': type == 'success',
            'text-red-800 dark:text-red-100': type == 'error',
            'text-yellow-800 dark:text-yellow-100': type == 'warning',
            'text-gray-800 dark:text-gray-100': ![
              'info',
              'success',
              'error',
              'warning',
            ].includes(type),
          }"
          class="text-sm font-medium"
        >
          <slot name="message">
            <div v-html="message"></div>
          </slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const { type } = defineProps({
  message: {
    type: String,
  },
  type: {
    type: String,
    default: "info",
    validator: (value: string) =>
      ["info", "success", "error", "warning"].includes(value),
  },
});

defineSlots<{
  message?: HTMLElement;
}>();

const icon = computed(() => {
  if (type === "success") return "circle-check";
  if (type === "error") return "circle-xmark";
  if (type === "warning") return "triangle-exclamation";

  return "circle-info";
});
</script>
