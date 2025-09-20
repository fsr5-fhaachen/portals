<template>
  <AppTable
    :columns="getColumns()"
    :elements="groups"
    :idFunction="(group) => group.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { computed, ref, PropType, onBeforeUnmount } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

const { groups } = defineProps({
  groups: {
    type: Object as PropType<App.Models.Group[]>,
    required: true,
  },
});

const showCourses = computed(() => {
  return groups.some((group) => group.courses.length > 0);
});

const registrations = ref({});
groups.forEach((group) => {
  registrations.value[group.id] = group.registrations?.length || 0;
});
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + groups[0].event_id + "/registrations-amount",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    },
  );

  if (response.ok) {
    const data = await response.json();
    groups.forEach((group) => {
      registrations.value[group.id] = data.groups[group.id];
    });
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});

function getColumns(): Array<TableColumn> {
  let columns: Array<TableColumn> = [];

  let nameCol = new TableColumn({
    name: "name",
    text: "Name",
    valueFunction: (group) => group.name,
    compareFunction: (group1, group2) => group1.name.localeCompare(group2.name),
  });
  columns.push(nameCol);

  if (showCourses) {
    let coursesCol = new TableColumn({
      name: "courses",
      text: "Studiengang",
      valueFunction: (group) =>
        group.courses.map((course) => course.abbreviation).join(" | "),
      compareFunction: (group1, group2) =>
        group1.courses
          .map((course) => course.abbreviation)
          .join("")
          .localeCompare(
            group2.courses.map((course) => course.abbreviation).join(""),
          ),
    });
    columns.push(coursesCol);
  }

  let participantsCol = new TableColumn({
    name: "participants",
    text: "Teilnehmer",
    valueFunction: (group) => (registrations.value[group.id] || 0).toString(),
    compareFunction: (group1, group2) =>
      (registrations.value[group1.id] || 0) -
      (registrations.value[group2.id] || 0),
  });
  columns.push(participantsCol);

  return columns;
}

function getLinks(): Array<TableLink> {
  let links: Array<TableLink> = [];

  let showLink = new TableLink({
    name: "show",
    text: "Anzeigen",
    linkFunction: (group) => "/dashboard/tutor/group/" + group.id,
    theme: "default",
  });
  links.push(showLink);

  return links;
}
</script>
