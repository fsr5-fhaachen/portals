<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <RegistrationTable
      v-if="registrations"
      :courses="courses"
      :event="event"
      :registrations="registrations"
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
});

const registrations = ref(event.registrations);
const fetchRegistrations = async () => {
  const response = await fetch("/api/events/" + event.id + "/registrations", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();
    registrations.value = data.registrations;
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
