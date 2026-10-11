<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>
    <CardContainer>
      <RegistrationTable
        :courses="courses"
        :event="event"
        :hasFormResponses="hasFormResponses"
        :user="user"
      />
    </CardContainer>
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
  hasFormResponses: {
    type: Boolean,
    default: false,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
</script>
