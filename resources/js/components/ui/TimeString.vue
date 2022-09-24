<template>
  <span>
    {{
      new Date(value).toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
      })
    }}
    <template v-if="withClockSuffix"> Uhr</template>
  </span>
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
