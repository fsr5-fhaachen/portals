<template>
  <button
    type="button"
    :aria-pressed="selected"
    :disabled="disabled"
    :class="[
      selected
        ? 'border-fhac-mint-dark bg-fhac-mint-dark text-white'
        : 'border-gray-300 bg-white text-gray-900 hover:border-fhac-mint dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100',
      disabled ? 'cursor-not-allowed opacity-40' : 'cursor-pointer',
    ]"
    class="rounded-md border-2 px-3 py-3 text-center font-medium focus:outline-none focus:ring-2 focus:ring-fhac-mint focus:ring-offset-2 dark:focus:ring-offset-gray-900"
    @click="emits('select')"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
defineProps({
  selected: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emits = defineEmits<{
  select: [];
}>();
</script>
