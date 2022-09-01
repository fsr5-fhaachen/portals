<template>
  <LayoutDashboardContent>
    <template #title>Veranstaltungen</template>

    <GirdContainer v-if="events.length">
      <EventCard v-for="event in events" :key="event.id" :event="event" />
    </GirdContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  events: {
    type: Array as PropType<App.Models.Event[]>,
    required: true,
  },
});
</script>
