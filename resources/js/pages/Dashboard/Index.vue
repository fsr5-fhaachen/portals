<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>
    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>
    <GirdContainer v-if="events.length">
      <EventCard
        v-for="event in events"
        :key="event.id"
        :event="event"
        :userIsRegistered="userIsRegisteredForEvent(event)"
      />
    </GirdContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

const { registrations } = defineProps({
  events: {
    type: Array as PropType<App.Models.Event[]>,
    required: true,
  },
  registrations: {
    type: Array as PropType<App.Models.Registration[]>,
    required: true,
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const userIsRegisteredForEvent = (event: App.Models.Event) => {
  return registrations.some(
    (registration) => registration.event_id === event.id
  );
};
</script>
