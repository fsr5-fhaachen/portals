<template>
  <LayoutDashboardContent>
    <template #title>{{ slot.name }}</template>

    <RegistrationTable
      v-if="slot.event && slot.registrations"
      :courses="courses"
      :event="slot.event"
      :registrations="slot.registrations"
      :hideSlots="true"
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
  slot: {
    type: Object as PropType<App.Models.Slot>,
    required: true,
  },
});
</script>
