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
      v-else-if="event.type == 'event_registration' && event.registrations"
      :courses="courses"
      :event="event"
      :registrations="event.registrations"
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
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});
</script>
