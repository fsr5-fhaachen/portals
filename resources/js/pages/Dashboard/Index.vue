<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>

    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>

    <template v-if="user.is_tutor">
      <CardBase>
        <FormKit
          type="form"
          id="tutor-login"
          @submit="submitTutorPasswordFormHandler"
          :actions="false"
          v-model="tutorPasswordForm"
        >
          <FormContainer>
            <FormRow>
              <UiH2>Anmeldung zum geschützten Bereich</UiH2>
            </FormRow>

            <FormRow>
              <FormKit
                type="password"
                name="password"
                label="Tutorenpasswort"
                placeholder="Passwort"
                validation="required"
              />
            </FormRow>

            <FormRow>
              <FormKit type="submit" label="Anmelden" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
    </template>
    <template v-else>
      <GirdContainer v-if="events.length">
        <EventCard
          v-for="event in events"
          :key="event.id"
          :event="event"
          :userIsRegistered="userIsRegisteredForEvent(event)"
        />
      </GirdContainer>
    </template>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { Inertia } from "@inertiajs/inertia";

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

const tutorPasswordForm = ref({
  password: "",
});

const submitTutorPasswordFormHandler = async () => {
  Inertia.post("/dashboard/login-tutor", tutorPasswordForm.value);
};

const userIsRegisteredForEvent = (event: App.Models.Event) => {
  return registrations.some(
    (registration) => registration.event_id === event.id
  );
};
</script>
