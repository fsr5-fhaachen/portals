<template>
  <LayoutDashboardContent>
    <template #title>Abmeldung zur Veranstaltung</template>

    <CardBase>
      <FormKit
        type="form"
        id="registration-example"
        submit-label="Register"
        @submit="submitHandler"
        :actions="false"
        v-model="form"
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

          <FormRow>
            <FormKit type="submit" label="Abmeldung" />
          </FormRow>
        </FormContainer>
      </FormKit>
    </CardBase>
  </LayoutDashboardContent>
</template>

<script lang="ts">
import { Inertia } from "@inertiajs/inertia";
import DashboardCardLayout from "@/layouts/DashboardCardLayout.vue";

export default {
  layout: DashboardCardLayout,
};
</script>

<script setup lang="ts">
import { ref, PropType } from "vue";

const { event } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const form = ref({});

const submitHandler = async () => {
  Inertia.post("/dashboard/event/" + event.id + "/unregister", form.value);
};
</script>
