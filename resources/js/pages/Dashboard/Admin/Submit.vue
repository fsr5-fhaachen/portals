<template>
  <LayoutDashboardContent>
    <template #title>Event "{{ event.name }}" einteilen</template>

    <AppButton theme="danger" class="text-center text-2xl uppercase">
      Ich bin mir sicher
    </AppButton>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});
</script>
