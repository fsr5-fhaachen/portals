<template>
  <!-- same look as the course toggle box -->
  <button
    type="button"
    :aria-pressed="selected"
    :class="selected ? 'ring-2 ring-fhac-mint' : 'opacity-50 hover:opacity-75'"
    class="flex items-center gap-4 overflow-hidden rounded-lg bg-white p-4 text-left shadow transition dark:bg-gray-800"
    @click="emit('toggle')"
  >
    <div :class="[iconClasses, 'shrink-0 rounded-md p-3']">
      <div class="flex h-8 w-8 items-center justify-center text-2xl text-white">
        <FontAwesomeIcon :icon="icon" />
      </div>
    </div>
    <p class="truncate font-medium text-gray-500 dark:text-gray-400">
      {{ label }}
    </p>
  </button>
</template>

<script setup lang="ts">
defineProps({
  label: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  iconClasses: {
    type: String,
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
