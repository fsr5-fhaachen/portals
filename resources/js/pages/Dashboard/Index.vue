<template>
  <LayoutDashboardContent>
    <template #title>Hallo {{ user.firstname }},</template>

    <template #subtitle>
      hier findest du eine Übersicht über alle Veranstaltungen.
    </template>

    <template
      v-if="
        user.rolesArray.some((role) =>
          ['admin', 'esa', 'stage tutor', 'tutor'].includes(role)
        )
      "
    >
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
      <GridContainer v-if="events.length">
        <EventCard
          v-for="event in events"
          :key="event.id"
          :event="event"
          :registration="getUserRegistrationForEvent(event)"
        />
      </GridContainer>
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
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const tutorPasswordForm = ref({
  password: "",
});

const submitTutorPasswordFormHandler = async () => {
  Inertia.post("/dashboard/login-tutor", tutorPasswordForm.value);
};

const getUserRegistrationForEvent = (event: App.Models.Event) => {
  return registrations.find(
    (registration) => registration.event_id === event.id
  );
};
</script>
