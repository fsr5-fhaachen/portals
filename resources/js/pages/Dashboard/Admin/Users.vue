<template>
  <LayoutDashboardContent>
    <template #title>User Verwaltung</template>

    <CardContainer>
      <UserTable :courses="courses" :roles="roles" :user="user" />
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
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  roles: {
    type: Array as PropType<Models.Role[]>,
    required: true,
  },
});
</script>
