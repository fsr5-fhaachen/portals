<template>
  <AppTable
    :columns="getColumns()"
    :elements="slots"
    :idFunction="(slot) => slot.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

const { slots } = defineProps({
  slots: {
    type: Object as PropType<App.Models.Slot[]>,
    required: true,
  },
});

const registrations = ref({});
slots.forEach((slot) => {
  registrations.value[slot.id] = slot.registrations?.length || 0;
});
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + slots[0].event_id + "/registrations-amount",
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
    slots.forEach((slot) => {
      registrations.value[slot.id] = data.slots[slot.id];
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
    valueFunction: (slot) => slot.name,
    compareFunction: (slot1, slot2) => slot1.name.localeCompare(slot2.name),
  });
  columns.push(nameCol);

  let requirementsCol = new TableColumn({
    name: "has_requirements",
    text: "Hat Voraussetzungen",
    valueFunction: (slot) => (slot.has_requirements ? "Ja" : "Nein"),
    compareFunction: (slot1, slot2) =>
      slot1.has_requirements === slot2.has_requirements
        ? 0
        : slot1.has_requirements
          ? 1
          : -1,
  });
  columns.push(requirementsCol);

  let participantsCol = new TableColumn({
    name: "participants",
    text: "Teilnehmer",
    valueFunction: (slot) => {
      let val = (registrations.value[slot.id] || 0).toString();

      if (slot.maximum_participants) {
        val += " / " + slot.maximum_participants;
      }

      return val;
    },
    compareFunction: (slot1, slot2) =>
      (registrations.value[slot1.id] || 0) -
      (registrations.value[slot2.id] || 0),
  });
  columns.push(participantsCol);

  return columns;
}

function getLinks(): Array<TableLink> {
  let links: Array<TableLink> = [];

  let showLink = new TableLink({
    name: "show",
    text: "Anzeigen",
    linkFunction: (slot) => "/dashboard/tutor/slot/" + slot.id,
    theme: "default",
  });
  links.push(showLink);

  return links;
}
</script>
