<template>
  <AppTable
    :columns="getColumns()"
    :links="getLinks()"
    :elements="events"
    :idFunction="(event) => event.id"
  />
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

const { events, user } = defineProps({
  events: {
    type: Object as PropType<App.Models.Event[]>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const registrations = ref({});
const isFetchingRegistrations = ref(false);
events.forEach((event) => {
  registrations.value[event.id] = {
    amount: event.registrations?.length || 0,
  };
});
const fetchRegistrations = async () => {
  if (isFetchingRegistrations.value) {
    return;
  }

  isFetchingRegistrations.value = true;

  const response = await fetch("/api/events/registrations-amount", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();
    data.forEach((event) => {
      registrations.value[event.id] = event;
    });
  }

  isFetchingRegistrations.value = false;
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});

function getColumns(): Array<TableColumn> {
  let columns = Array<TableColumn>();

  let nameCol = new TableColumn(
    "name",
    "Name",
    (event) => event.name,
    (event1, event2) => event1.name.localeCompare(event2.name),
  );
  columns.push(nameCol);

  let typeCol = new TableColumn(
    "type",
    "Typ",
    (event) => event.type,
    (event1, event2) => event1.type.localeCompare(event2.type),
  );
  columns.push(typeCol);

  if (user.permissionsArray.includes("view hidden event details")) {
    let alcoholCol = new TableColumn(
      "consider_alcohol",
      "Berücksichtigt Verzehr von Alkohol",
      (event) => (event.consider_alcohol ? "Ja" : "Nein"),
      (event1, event2) =>
        event1.consider_alcohol === event2.consider_alcohol
          ? 0
          : event1.consider_alcohol
            ? 1
            : -1,
    );
    columns.push(alcoholCol);

    let coursesCol = new TableColumn(
      "courses",
      "Studiengänge",
      (event) =>
        event.courses.length == 0
          ? "Alle"
          : event.courses.map((course) => course.abbreviation).join(", "),
      (event1, event2) =>
        event1.courses.length == 0 && event2.courses.length == 0
          ? 0
          : event1.courses
              .map((course) => course.abbreviation)
              .join(", ")
              .localeCompare(
                event2.courses.map((course) => course.abbreviation).join(", "),
              ),
    );
    columns.push(coursesCol);

    let requirementsCol = new TableColumn(
      "has_requirements",
      "Hat Voraussetzungen",
      (event) => (event.has_requirements ? "Ja" : "Nein"),
      (event1, event2) =>
        event1.has_requirements === event2.has_requirements
          ? 0
          : event1.has_requirements
            ? 1
            : -1,
    );
    columns.push(requirementsCol);
  }

  let registrationCol = new TableColumn(
    "registration",
    "Registrierung",
    (event) => {
      let regString = "";
      if (event.registration_from) {
        regString +=
          "<div>" +
          "Von: " +
          new Date(event.registration_from).toLocaleDateString("de-DE", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
          }) +
          " " +
          new Date(event.registration_from).toLocaleTimeString("de-DE", {
            hour: "2-digit",
            minute: "2-digit",
          }) +
          " Uhr" +
          "</div>";
      }

      if (event.registration_to) {
        regString +=
          "<div>" +
          "Bis: " +
          new Date(event.registration_to).toLocaleDateString("de-DE", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
          }) +
          " " +
          new Date(event.registration_to).toLocaleTimeString("de-DE", {
            hour: "2-digit",
            minute: "2-digit",
          }) +
          " Uhr" +
          "</div>";
      }

      return regString;
    },
    (event1, event2) =>
      event1.registration_from.localeCompare(event2.registration_from),
  );
  columns.push(registrationCol);

  let participantsCol = new TableColumn(
    "participants",
    "Teilnehmer",
    (event) => registrations.value[event.id].amount,
    (event1, event2) =>
      registrations.value[event1.id].amount -
      registrations.value[event2.id].amount,
  );
  columns.push(participantsCol);

  return columns;
}

function getLinks(): Array<TableLink> {
  let links = Array<TableLink>();

  let showLink = new TableLink(
    "show",
    "Anzeigen",
    (event) => "/dashboard/tutor/event/" + event.id,
    "default",
  );
  links.push(showLink);

  if (user.permissionsArray.includes("manage events")) {
    let adminLink = new TableLink(
      "admin",
      "Admin",
      (event) => "/dashboard/admin/event/" + event.id,
      "danger",
    );
    links.push(adminLink);
  }

  return links;
}
</script>
