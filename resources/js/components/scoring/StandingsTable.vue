<template>
  <UiMessage
    v-if="!standings.length"
    type="info"
    message="Lege zuerst Teams an, um die Rangliste zu sehen."
  />
  <div v-else class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th
            v-for="header in headers"
            :key="header"
            scope="col"
            class="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-gray-900 first:pl-6 last:pr-6 dark:text-gray-300"
          >
            {{ header }}
          </th>
        </tr>
      </thead>
      <tbody
        class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
      >
        <tr v-for="row in standings" :key="row.team_id">
          <td
            class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-bold text-gray-900 dark:text-gray-100"
          >
            {{ row.rank }}.
          </td>
          <td
            class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100"
          >
            {{ row.team_name }}
          </td>
          <td :class="cellClass">{{ row.duels }}</td>
          <td :class="cellClass">
            {{ row.wins }}/{{ row.draws }}/{{ row.losses }}
          </td>
          <td :class="cellClass">{{ row.duel_points }}</td>
          <td :class="cellClass">{{ row.bonus }}</td>
          <td :class="cellClass">{{ row.extra_points }}</td>
          <td
            class="whitespace-nowrap py-4 pl-3 pr-6 text-sm font-bold text-gray-900 dark:text-gray-100"
          >
            {{ row.total }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

// the standings are already ordered by rank on the server (see app/Helpers/ScoringStandings)
defineProps({
  standings: {
    type: Array as PropType<Scoring.StandingRow[]>,
    required: true,
  },
});

const headers = [
  "Platz",
  "Team",
  "Duelle",
  "S/U/N",
  "Duellpunkte",
  "Bonus",
  "Sonderpunkte",
  "Gesamt",
];

const cellClass =
  "whitespace-nowrap px-3 py-4 text-sm text-gray-700 dark:text-gray-300";
</script>
