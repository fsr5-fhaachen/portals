<template>
  <LayoutDashboardContent>
    <template #title>{{ slot.name }}</template>
    <CardContainer>
      <CardBase v-if="slot.telegram_group_link">
        <UiH2>Telegram Gruppe</UiH2>
        <div class="text-gray-900 dark:text-gray-100">
          Für diese Gruppe wurde eine Telegram Gruppe erstellt. Diesen Link
          sehen nur Gruppenmitglieder und Tutoren.
        </div>
        <AppLink :href="slot.telegram_group_link">
          {{ slot.telegram_group_link }}
        </AppLink>
      </CardBase>

      <RegistrationTable
        v-if="slot.event"
        :courses="courses"
        :event="slot.event"
        :hasFormResponses="hasFormResponses"
        :hideSlots="true"
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
  slot: {
    type: Object as PropType<App.Models.Slot>,
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
