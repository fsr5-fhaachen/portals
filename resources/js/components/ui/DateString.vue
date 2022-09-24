<template>
  <span>{{
    new Date(value).toLocaleDateString("de-DE", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    })
  }}</span>
</template>

<script setup lang="ts">
defineProps({
  value: {
    type: String,
    required: true,
  },
});
</script>
