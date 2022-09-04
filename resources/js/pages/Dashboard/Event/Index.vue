<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <CardBase>
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

        <template v-if="slotData">
          <UiDt>Slot</UiDt>
          <UiDd>{{ slotData.name }}</UiDd>

          <template v-if="slotData.maximum_participants">
            <UiDt>Maximale Teilnehmerzahl</UiDt>
            <UiDd>{{ slotData.maximum_participants }}</UiDd>
          </template>
        </template>
      </UiDl>
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
import { computed, PropType } from "vue";

const { event, registration } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  registration: {
    type: Object as PropType<App.Models.Registration>,
    required: true,
  },
});

const slotData = computed(() => {
  if (event.slots && registration && registration.slot_id) {
    return event.slots.find((s) => s.id === registration.slot_id);
  }

  return null;
});
</script>
