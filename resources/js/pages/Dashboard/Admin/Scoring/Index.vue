<template>
  <LayoutDashboardContent>
    <template #title>Scoring</template>
    <template #subtitle>Wettbewerbe mit Duellen zwischen Teams</template>

    <CardContainer>
      <UiMessage
        v-if="!competitions.length"
        type="info"
        message="Es gibt noch keine Wettbewerbe."
      />
      <ul v-else class="flex flex-col gap-3">
        <li v-for="competition in competitions" :key="competition.id">
          <InertiaLink
            :href="`/dashboard/admin/scoring/competition/${competition.id}`"
            class="flex flex-col gap-2 rounded-lg bg-white p-4 shadow hover:ring-2 hover:ring-fhac-mint dark:bg-gray-900 sm:flex-row sm:items-center"
          >
            <span
              class="grow text-lg font-medium text-gray-900 dark:text-gray-100"
            >
              {{ competition.name }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ competition.teams_count }} Teams ·
              {{ competition.duels_count }} Duelle
              <template v-if="competition.event">
                · {{ competition.event.name }}
              </template>
            </span>
            <span
              :class="
                competition.is_open
                  ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'
                  : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'
              "
              class="self-start rounded-md px-2 py-0.5 text-sm sm:self-auto"
            >
              {{ competition.is_open ? "offen" : "geschlossen" }}
            </span>
          </InertiaLink>
        </li>
      </ul>

      <CardBase>
        <div class="space-y-6">
          <UiH2>Neuer Wettbewerb</UiH2>
          <ScoringCompetitionForm
            :events="events"
            submit-url="/dashboard/admin/scoring/competition"
            submit-label="Wettbewerb anlegen"
          />
        </div>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { Link as InertiaLink } from "@inertiajs/vue3";

defineProps({
  competitions: {
    type: Array as PropType<App.Models.Competition[]>,
    required: true,
  },
  events: {
    type: Array as PropType<Pick<App.Models.Event, "id" | "name">[]>,
    required: true,
  },
});
</script>
