<template>
  <UiMessage
    v-if="!standings.length"
    type="info"
    message="Lege zuerst Teams an, um die Rangliste zu sehen."
  />
  <AppTable
    v-else
    :columns="columns"
    :elements="standings"
    :idFunction="(row: Scoring.StandingRow) => row.team_id"
  />
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { TableColumn } from "../../types/table-column";

defineProps({
  standings: {
    type: Array as PropType<Scoring.StandingRow[]>,
    required: true,
  },
});

const compareNumbers =
  (key: keyof Scoring.StandingRow) =>
  (a: Scoring.StandingRow, b: Scoring.StandingRow) =>
    (a[key] as number) - (b[key] as number);

const numberColumn = (name: keyof Scoring.StandingRow, text: string) =>
  new TableColumn({
    name,
    text,
    valueFunction: (row: Scoring.StandingRow) => String(row[name]),
    compareFunction: compareNumbers(name),
  });

const columns = [
  new TableColumn({
    name: "rank",
    text: "Platz",
    valueFunction: (row: Scoring.StandingRow) =>
      `<strong>${row.rank}.</strong>`,
    compareFunction: compareNumbers("rank"),
  }),
  new TableColumn({
    name: "team_name",
    text: "Team",
    valueFunction: (row: Scoring.StandingRow) => useEscapeHtml(row.team_name),
    compareFunction: (a: Scoring.StandingRow, b: Scoring.StandingRow) =>
      a.team_name.localeCompare(b.team_name),
  }),
  numberColumn("duels", "Duelle"),
  new TableColumn({
    name: "record",
    text: "S/U/N",
    valueFunction: (row: Scoring.StandingRow) =>
      `${row.wins}/${row.draws}/${row.losses}`,
    compareFunction: compareNumbers("wins"),
  }),
  numberColumn("duel_points", "Duellpunkte"),
  numberColumn("bonus", "Bonus"),
  numberColumn("extra_points", "Sonderpunkte"),
  new TableColumn({
    name: "total",
    text: "Gesamt",
    valueFunction: (row: Scoring.StandingRow) =>
      `<strong>${row.total}</strong>`,
    compareFunction: compareNumbers("total"),
  }),
];
</script>
