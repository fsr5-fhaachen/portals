<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <CardContainer>
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

          <template v-if="event.registration_to">
            <UiDt>Anmeldung bis</UiDt>
            <UiDd>
              <UiDateTimeString
                :value="event.registration_to"
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

      <CardBase v-if="event.description">
        <div class="prose !max-w-full" v-html="event.description"></div>
      </CardBase>

      <template v-if="userRegistration">
        <template v-if="event.type == 'group_phase'">
          <UiMessage
            v-if="userRegistration.group"
            type="success"
            :message="
              'Die Einteilung ist erfolgt. Du bist in ' +
              (userRegistration.group.name
                ? 'der Gruppe <strong>' +
                  userRegistration.group.name +
                  '</strong>'
                : '<strong>Gruppe ' + userRegistration.group.id + '</strong>') +
              '.'
            "
          />
          <UiMessage
            v-else
            message="Die Zuteilung in deine Gruppe folgt bald."
          />
        </template>

        <template v-else-if="event.type == 'slot_booking'">
          <UiMessage
            v-if="userRegistration.queue_position == -1"
            message="Die Zuteilung in deinen Slot folgt bald."
          />
          <UiMessage
            v-else-if="!userRegistration.queue_position && slotData"
            type="success"
            :message="
              'Die Einteilung ist erfolgt. Du bist im Slot <strong>' +
              slotData.name +
              '</strong>.'
            "
          />
        </template>

        <UiMessage
          v-if="
            userRegistration.queue_position &&
            userRegistration.queue_position > 0
          "
          type="warning"
          :message="
            'Die Einteilung ist erfolgt. Du bist in der Warteschlange an <strong>Position ' +
            userRegistration.queue_position +
            '</strong> als nachrückende Person eingetragen.'
          "
        />
      </template>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script lang="ts">
import DashboardCardLayout from "@/layouts/DashboardCardLayout.vue";

export default {
  layout: DashboardCardLayout,
};
</script>

<script setup lang="ts">
import { computed, ref, onBeforeUnmount, PropType } from "vue";

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

const userRegistration = ref(registration);
const fetchRegistration = async () => {
  const response = await fetch("/api/registrations/" + registration.id, {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();
    userRegistration.value = data;
  }
};
const registrationInterval = setInterval(fetchRegistration, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationInterval);
});
</script>
