<template>
  <button
    type="button"
    :aria-pressed="selected"
    :class="selected ? 'ring-2 ring-fhac-mint' : 'opacity-50 hover:opacity-75'"
    class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-4 text-left shadow transition dark:bg-gray-800"
    @click="emit('toggle')"
  >
    <div :class="[course.classes, 'shrink-0 rounded-md p-3']">
      <div class="flex h-8 w-8 items-center justify-center text-2xl text-white">
        <FontAwesomeIcon :icon="course.icon" />
      </div>
    </div>
    <p class="truncate font-medium text-gray-500 dark:text-gray-400">
      {{ course.name }}
    </p>
  </button>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  course: {
    type: Object as PropType<App.Models.Course>,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits<{
  toggle: [];
}>();
</script>
