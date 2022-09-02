<template>
  <LayoutDashboardContent>
    <template #title>Anmeldung zur Veranstaltung</template>

    <CardBase>
      <FormKit
        type="form"
        id="registration-example"
        :form-class="submitted ? 'hide' : 'show'"
        submit-label="Register"
        @submit="submitHandler"
        :actions="false"
        #default="{ value }"
      >
        <FormContainer>
          <FormRow>
            <UiH2>Eventdaten</UiH2>
          </FormRow>

          <FormRow>
            <UiDl>
              <UiDt>Event</UiDt>
              <UiDd>
                {{ event.name }}
              </UiDd>

              <template v-if="event.registration_from">
                <UiDt>Anmeldung ab</UiDt>
                <UiDd>
                  <UiDateTimeString
                    :value="event.registration_from"
                    :withClockSuffix="true"
                  />
                </UiDd>
              </template>

              <template v-if="event.registration_until">
                <UiDt>Anmeldung bis</UiDt>
                <UiDd>
                  <UiDateTimeString
                    :value="event.registration_until"
                    :withClockSuffix="true"
                  />
                </UiDd>
              </template>
            </UiDl>
          </FormRow>

          <template v-if="event.consider_alcohol">
            <FormDivider />

            <FormRow>
              <UiH2>Deine Daten</UiH2>
            </FormRow>

            <FormRow v-if="event.consider_alcohol">
              <FormKit
                type="checkbox"
                name="drinks_no_alcohol"
                label="Ich trinke kein Alkohol"
              />
            </FormRow>
          </template>

          <FormRow>
            <FormKit type="submit" label="Anmelden" />
          </FormRow>
        </FormContainer>
      </FormKit>
      <div v-if="submitted">
        <h2>Submission successful!</h2>
      </div>
    </CardBase>
  </LayoutDashboardContent>
</template>

<script lang="ts">
import DashboardCardLayout from "@/layouts/DashboardCardLayout.vue";

export default {
  layout: DashboardCardLayout,
};
</script>

<script setup lang="ts">
import { ref, PropType } from "vue";

defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const submitted = ref(false);
const submitHandler = async () => {
  // Let's pretend this is an ajax request:
  await new Promise((r) => setTimeout(r, 1000));
  submitted.value = true;
};
</script>
