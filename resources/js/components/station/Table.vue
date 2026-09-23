<template>
  <AppTable
    :columns="getColumns()"
    :elements="stations"
    :idFunction="(station) => station.id"
    :functions="getLinks()"
  />
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableLink } from "../../types/table-link";

defineProps({
  stations: {
    type: Object as PropType<App.Models.Station[]>,
    required: true,
  },
});

function getColumns(): Array<TableColumn> {
  return [
    new TableColumn({
      name: "name",
      text: "Name",
      valueFunction: (station) => station.name,
      compareFunction: (station1, station2) =>
        station1.name.localeCompare(station2.name),
    }),
  ];
}

function getLinks(): Array<TableLink> {
  return [
    new TableLink({
      name: "show",
      text: "Anzeigen",
      linkFunction: (station) => "/dashboard/tutor/station/" + station.id,
      theme: "default",
    }),
  ];
}
</script>
