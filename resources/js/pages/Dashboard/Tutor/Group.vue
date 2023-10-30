<template>
  <LayoutDashboardContent>
    <template #title>{{ group.name }}</template>

    <RegistrationTable
      v-if="group.event && registrations"
      :courses="courses"
      :event="group.event"
      :registrations="registrations"
      :hideGroups="true"
      :user="user"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const { group } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  group: {
    type: Object as PropType<App.Models.Group>,
    required: true,
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const registrations = ref(group.registrations);
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + group.event_id + "/registrations",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    }
  );

  if (response.ok) {
    const data = await response.json();
    registrations.value = data.groups[group.id];
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
