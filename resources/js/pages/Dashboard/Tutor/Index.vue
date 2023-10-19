<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>

    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>

    <EventTable :events="events" :user="user" />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  events: {
    type: Array as PropType<App.Models.Event[]>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
</script>
