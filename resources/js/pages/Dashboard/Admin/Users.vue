<template>
  <LayoutDashboardContent>
    <template #title>User Verwaltung</template>

    <CardContainer>
      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <UiH2>Export</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Alle User als CSV, sortiert nach Kurs, dann Tutoren, Erstis und
              Sonstige. Über die Spalten Kurs und Typ lassen sie sich in Excel
              auf Blätter aufteilen.
            </p>
          </div>
          <AppButton @click="downloadExport">Export herunterladen</AppButton>
        </div>
      </CardBase>

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

const downloadExport = () => {
  window.open("/dashboard/admin/users/export", "_blank");
};
</script>
