<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <GroupTable
      v-if="event.type == 'group_phase' && event.groups"
      :courses="courses"
      :event="event"
      :groups="event.groups"
    />
    <SlotTable
      v-else-if="event.type == 'slot_booking' && event.slots"
      :slots="event.slots"
    />
    <RegistrationTable
      v-else-if="event.type == 'event_registration' && registrations"
      :courses="courses"
      :event="event"
      :registrations="registrations"
      :user="user"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const { event } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
const registrations = ref(event.registrations);

// TODO: Optimize this
// const fetchRegistrations = async () => {
//   const response = await fetch("/api/events/" + event.id + "/registrations", {
//     method: "GET",
//     credentials: "include",
//     headers: {
//       "Content-Type": "application/json",
//     },
//   });

//   if (response.ok) {
//     const data = await response.json();
//     registrations.value = data.registrations;
//   }
// };
// if (event.type == "event_registration") {
//   const registrationsInterval = setInterval(fetchRegistrations, 5000);
//   onBeforeUnmount(() => {
//     clearInterval(registrationsInterval);
//   });
// }
</script>
