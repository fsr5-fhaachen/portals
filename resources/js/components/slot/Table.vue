<template>
  <AppTable
    data="slots"
    :columns="getColumns()"
    :idFunction="(slot) => slot.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

function getColumns(): Array<TableColumn> {
  let columns: Array<TableColumn> = [];

  let nameCol = new TableColumn({
    name: "name",
    text: "Name",
    valueFunction: (slot) => slot.name,
    sortKey: "name",
  });
  columns.push(nameCol);

  let requirementsCol = new TableColumn({
    name: "has_requirements",
    text: "Hat Voraussetzungen",
    valueFunction: (slot) => (slot.has_requirements ? "Ja" : "Nein"),
    sortKey: "has_requirements",
  });
  columns.push(requirementsCol);

  let participantsCol = new TableColumn({
    name: "participants",
    text: "Teilnehmer",
    valueFunction: (slot) => {
      let val = (slot.registrations_count ?? 0).toString();

      if (slot.maximum_participants) {
        val += " / " + slot.maximum_participants;
      }

      return val;
    },
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
    linkFunction: (slot) => "/dashboard/tutor/slot/" + slot.id,
    theme: "default",
  });
  links.push(showLink);

  return links;
}
</script>
