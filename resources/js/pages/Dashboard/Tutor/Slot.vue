<template>
  <LayoutDashboardContent>
    <template #title>{{ slot.name }}</template>
    <RegistrationTable
      v-if="slot.event && registrations"
      :courses="courses"
      :event="slot.event"
      :registrations="registrations"
      :hideSlots="true"
      :user="user"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const { slot } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  slot: {
    type: Object as PropType<App.Models.Slot>,
    required: true,
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const registrations = ref(slot.registrations);
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + slot.event_id + "/registrations",
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
    registrations.value = data.slots[slot.id];
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
