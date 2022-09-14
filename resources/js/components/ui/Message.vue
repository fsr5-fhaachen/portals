<template>
  <div
    :class="{
      'bg-blue-100': type === 'info',
      'bg-green-100': type == 'success',
      'bg-red-100': type == 'error',
      'bg-yellow-100': type == 'warning',
    }"
    class="rounded-md bg-gray-50 p-4"
  >
    <div class="flex">
      <div class="flex flex-shrink-0 items-center">
        <FontAwesomeIcon
          :class="{
            'text-blue-400': type === 'info',
            'text-green-400': type == 'success',
            'text-red-400': type == 'error',
            'text-yellow-400': type == 'warning',
          }"
          class="h-5 w-5 text-gray-400"
          :icon="['fas', icon]"
        />
      </div>
      <div class="ml-3">
        <div
          :class="{
            'text-blue-800': type === 'info',
            'text-green-800': type == 'success',
            'text-red-800': type == 'error',
            'text-yellow-800': type == 'warning',
          }"
          class="text-sm font-medium text-gray-800"
        >
          <div v-html="message"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CheckCircleIcon, XMarkIcon } from "@heroicons/vue/20/solid";

const { type } = defineProps({
  message: {
    type: String,
    required: true,
  },
  type: {
    type: String,
    default: "info",
    validator: (value: string) =>
      ["info", "success", "error", "warning"].includes(value),
  },
});

const icon = computed(() => {
  if (type === "success") return "circle-check";
  if (type === "error") return "circle-xmark";
  if (type === "warning") return "triangle-exclamation";

  return "circle-info";
});
</script>
