<template>
  <div>
    <UiDateString :value="value" />
    <span>&nbsp;</span>
    <UiTimeString :value="value" :withClockSuffix="withClockSuffix" />
  </div>
</template>

<script setup lang="ts">
defineProps({
  value: {
    type: String,
    required: true,
  },
  withClockSuffix: {
    type: Boolean,
    default: false,
  },
});
</script>
