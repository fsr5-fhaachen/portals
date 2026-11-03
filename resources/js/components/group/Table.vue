<template>
  <AppTable
    data="groups"
    :columns="getColumns()"
    :idFunction="(group) => group.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

const page = usePage();

// the courses column is only shown if any group belongs to a course
const showCourses = computed(() =>
  (
    (page.props.groups as { data?: App.Models.Group[] } | undefined)?.data ?? []
  ).some((group) => group.courses?.length),
);

function getColumns(): Array<TableColumn> {
  let columns: Array<TableColumn> = [];

  let nameCol = new TableColumn({
    name: "name",
    text: "Name",
    valueFunction: (group) => group.name,
    sortKey: "name",
  });
  columns.push(nameCol);

  if (showCourses.value) {
    let coursesCol = new TableColumn({
      name: "courses",
      text: "Studiengang",
      valueFunction: (group) =>
        group.courses.map((course) => course.abbreviation).join(" | "),
      sortKey: "courses",
    });
    columns.push(coursesCol);
  }

  let participantsCol = new TableColumn({
    name: "participants",
    text: "Teilnehmer",
    valueFunction: (group) => (group.registrations_count ?? 0).toString(),
    sortKey: "registrations_count",
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
