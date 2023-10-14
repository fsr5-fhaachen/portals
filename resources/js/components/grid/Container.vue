<template>
  <div
    :class="{
      'sm:grid-cols-2 ': cols === 2,
      'sm:grid-cols-2 lg:grid-cols-3': cols === 3,
    }"
    class="grid grid-cols-1 gap-6"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps({
  cols: {
    type: Number,
    default: 3,
    validator: (value: number) => {
      return [1, 2, 3].includes(value);
    },
  },
});
</script>
