<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>

    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>

    <EventTable :user="user" />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
</script>
