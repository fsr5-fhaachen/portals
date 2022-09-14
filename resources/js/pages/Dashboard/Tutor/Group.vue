<template>
  <LayoutDashboardContent>
    <template #title>{{ group.name }}</template>

    <RegistrationTable
      v-if="group.event && group.registrations"
      :courses="courses"
      :event="group.event"
      :registrations="group.registrations"
      :hideGroups="true"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  group: {
    type: Object as PropType<App.Models.Group>,
    required: true,
  },
});
</script>
