<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }} – nicht angemeldet</template>

    <CardContainer>
      <CardBase>
        <div class="space-y-2">
          <UiH2>Noch nicht angemeldet</UiH2>
          <p class="text-gray-500 dark:text-gray-400">
            Alle Erstis (User ohne Rolle), die sich noch nicht für dieses Event
            angemeldet haben. Wer sich anmeldet, verschwindet automatisch aus
            der Liste.
          </p>
          <AppLink :href="`/dashboard/admin/event/${event.id}`" theme="gray">
            &larr; Zurück zum Event
          </AppLink>
        </div>
      </CardBase>

      <AppTable
        data="users"
        searchable
        :columns="columns"
        :idFunction="(user) => user.id"
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { TableColumn } from "../../../types/table-column";

defineProps({
  event: {
    type: Object as PropType<Pick<App.Models.Event, "id" | "name">>,
    required: true,
  },
});

type NotRegisteredRow = {
  id: number;
  firstname: string;
  lastname: string;
  email: string;
  course: Pick<
    App.Models.Course,
    "id" | "name" | "abbreviation" | "classes"
  > | null;
};

const columns = [
  new TableColumn({
    name: "lastname",
    sortKey: "lastname",
    text: "Nachname",
    valueFunction: (user: NotRegisteredRow) => useEscapeHtml(user.lastname),
  }),
  new TableColumn({
    name: "firstname",
    sortKey: "firstname",
    text: "Vorname",
    valueFunction: (user: NotRegisteredRow) => useEscapeHtml(user.firstname),
  }),
  new TableColumn({
    name: "email",
    sortKey: "email",
    text: "E-Mail",
    valueFunction: (user: NotRegisteredRow) => useEscapeHtml(user.email),
  }),
  new TableColumn({
    name: "course",
    sortKey: "course",
    text: "Studiengang",
    valueFunction: (user: NotRegisteredRow) =>
      user.course
        ? `<span class="${useEscapeHtml(user.course.classes)} rounded-md p-1 text-xs text-white" title="${useEscapeHtml(user.course.name)}">${useEscapeHtml(user.course.abbreviation)}</span>`
        : "–",
  }),
];
</script>
