<template>
  <div class="mb-6">
    <UiMessage v-if="message.error" type="error" :message="message.error" />
    <UiMessage
      v-if="message.success"
      type="success"
      :message="message.success"
    />
    <UiMessage
      v-if="message.warning"
      type="warning"
      :message="message.warning"
    />
    <UiMessage v-if="message.info" type="info" :message="message.info" />
  </div>
</template>

<script setup lang="ts">
defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
});
</script>
