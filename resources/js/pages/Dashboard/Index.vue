<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>

    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>
    <GridContainer v-if="events.length">
      <template v-for="event in events">
        <EventCard
          v-if="isUserAllowedToRegister(event, user)"
          :key="event.id"
          :event="event"
          :registration="getUserRegistrationForEvent(event)"
        />
      </template>
    </GridContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { router } from "@inertiajs/vue3";

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
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const tutorPasswordForm = ref({
  password: "",
});

const submitTutorPasswordFormHandler = async () => {
  router.post("/dashboard/login-tutor", tutorPasswordForm.value);
};

const getUserRegistrationForEvent = (event: App.Models.Event) => {
  return registrations.find(
    (registration) => registration.event_id === event.id,
  );
};

const isUserAllowedToRegister = (
  event: App.Models.Event,
  user: Models.User,
) => {
  return (
    event.courses.length === 0 ||
    event.courses.some((course) => {
      return user.course_id === course.id;
    })
  );
};
</script>
