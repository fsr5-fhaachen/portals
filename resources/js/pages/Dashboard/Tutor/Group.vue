<template>
  <LayoutDashboardContent>
    <template #title>{{ group.name }}</template>
    <CardContainer>
      <CardBase v-if="group.telegram_group_link">
        <UiH2>Telegram Gruppe</UiH2>
        <div class="text-gray-900 dark:text-gray-100">
          Für diese Gruppe wurde eine Telegram Gruppe erstellt. Diesen Link
          sehen nur Gruppenmitglieder und Tutoren.
        </div>
        <AppLink :href="group.telegram_group_link">
          {{ group.telegram_group_link }}
        </AppLink>
      </CardBase>

      <RegistrationTable
        v-if="group.event"
        :courses="courses"
        :event="group.event"
        :hasFormResponses="hasFormResponses"
        :hideGroups="true"
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
  group: {
    type: Object as PropType<App.Models.Group>,
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
