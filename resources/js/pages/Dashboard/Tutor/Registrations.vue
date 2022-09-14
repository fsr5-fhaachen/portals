<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <RegistrationTable
      v-if="event.registrations"
      :courses="courses"
      :event="event"
      :registrations="event.registrations"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

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
</script>
