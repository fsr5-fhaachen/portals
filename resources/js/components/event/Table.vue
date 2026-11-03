<template>
  <AppTable
    data="events"
    :columns="getColumns()"
    :idFunction="(event) => event.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

const { user } = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const formatDateTime = (value: string) =>
  new Date(value).toLocaleDateString("de-DE", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }) +
  " " +
  new Date(value).toLocaleTimeString("de-DE", {
    hour: "2-digit",
    minute: "2-digit",
  }) +
  " Uhr";

function getColumns(): Array<TableColumn> {
  let columns: Array<TableColumn> = [];

  let nameCol = new TableColumn({
    name: "name",
    text: "Name",
    valueFunction: (event) => event.name,
    sortKey: "name",
  });
  columns.push(nameCol);

  let typeCol = new TableColumn({
    name: "type",
    text: "Typ",
    valueFunction: (event) => event.type,
    sortKey: "type",
  });
  columns.push(typeCol);

  if (user.permissionsArray.includes("view hidden event details")) {
    let alcoholCol = new TableColumn({
      name: "consider_alcohol",
      text: "Berücksichtigt Verzehr von Alkohol",
      valueFunction: (event) => (event.consider_alcohol ? "Ja" : "Nein"),
      sortKey: "consider_alcohol",
    });
    columns.push(alcoholCol);

    let coursesCol = new TableColumn({
      name: "courses",
      text: "Studiengänge",
      valueFunction: (event) =>
        event.courses.length == 0
          ? "Alle"
          : event.courses.map((course) => course.abbreviation).join(", "),
      sortKey: "courses",
    });
    columns.push(coursesCol);

    let requirementsCol = new TableColumn({
      name: "has_requirements",
      text: "Hat Voraussetzungen",
      valueFunction: (event) => (event.has_requirements ? "Ja" : "Nein"),
      sortKey: "has_requirements",
    });
    columns.push(requirementsCol);
  }

  let registrationCol = new TableColumn({
    name: "registration",
    text: "Registrierung",
    valueFunction: (event) => {
      let regString = "";
      if (event.registration_from) {
        regString +=
          "<div>" +
          "Von: " +
          formatDateTime(event.registration_from) +
          "</div>";
      }

      if (event.registration_to) {
        regString +=
          "<div>" + "Bis: " + formatDateTime(event.registration_to) + "</div>";
      }

      return regString;
    },
    sortKey: "registration_from",
  });
  columns.push(registrationCol);

  let participantsCol = new TableColumn({
    name: "participants",
    text: "Teilnehmer",
    valueFunction: (event) => (event.registrations_count ?? 0).toString(),
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
    linkFunction: (event) => "/dashboard/tutor/event/" + event.id,
    theme: "default",
  });
  links.push(showLink);

  if (user.permissionsArray.includes("manage events")) {
    let adminLink = new TableLink({
      name: "admin",
      text: "Admin",
      linkFunction: (event) => "/dashboard/admin/event/" + event.id,
      theme: "danger",
    });
    links.push(adminLink);
  }

  return links;
}
</script>
