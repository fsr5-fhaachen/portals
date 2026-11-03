<template>
  <LayoutDashboardContent>
    <template #title>{{ competition.name }}</template>
    <template #subtitle>
      <AppLink href="/dashboard/admin/scoring" theme="gray">
        &larr; Alle Wettbewerbe
      </AppLink>
    </template>

    <CardContainer>
      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="space-y-1">
            <UiH2>Status</UiH2>
            <p class="text-gray-700 dark:text-gray-300">
              <template v-if="competition.is_open">
                <strong class="text-green-700 dark:text-green-400"
                  >Offen</strong
                >
                – Tutoren können Duelle eintragen und korrigieren.
              </template>
              <template v-else>
                <strong>Geschlossen</strong> – nur Admins können Duelle ändern.
              </template>
            </p>
          </div>
          <AppButton
            :theme="competition.is_open ? 'warning' : 'default'"
            class="text-center"
            @click="toggleOpen"
          >
            {{
              competition.is_open ? "Wettbewerb schließen" : "Wettbewerb öffnen"
            }}
          </AppButton>
        </div>
      </CardBase>

      <CardBase>
        <div class="space-y-6">
          <UiH2>Rangliste</UiH2>
        </div>
        <template #footer>
          <ScoringStandingsTable :standings="standings" class="py-4" />
        </template>
      </CardBase>

      <CardBase>
        <div class="space-y-6">
          <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
          >
            <UiH2>Duelle</UiH2>
            <AppButton
              :disabled="teams.length < 2"
              class="text-center"
              @click="showNewDuel = true"
            >
              Duell nachtragen
            </AppButton>
          </div>
          <ScoringDuelList
            :duels="duels"
            editable
            @edit="editingDuel = $event"
            @delete="duelToDelete = $event"
          />
        </div>
      </CardBase>

      <CardBase>
        <div class="space-y-6">
          <UiH2>Sonderpunkte</UiH2>
          <ScoringExtraPointManager
            :competition="competition"
            :teams="teams"
            :extra-points="extraPoints"
          />
        </div>
      </CardBase>

      <CardBase>
        <div class="space-y-6">
          <UiH2>Teams</UiH2>
          <ScoringTeamManager
            :competition="competition"
            :teams="teams"
            :importable-groups-count="importableGroupsCount"
          />
        </div>
      </CardBase>

      <CardBase>
        <div class="space-y-6">
          <UiH2>Einstellungen</UiH2>
          <ScoringCompetitionForm
            :key="competition.updated_at ?? competition.id"
            :competition="competition"
            :events="events"
            :submit-url="`/dashboard/admin/scoring/competition/${competition.id}`"
          />
        </div>
      </CardBase>

      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="space-y-1">
            <UiH2>Wettbewerb löschen</UiH2>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              Löscht alle Teams, Duelle und Sonderpunkte. Nur geschlossene
              Wettbewerbe können gelöscht werden.
            </p>
          </div>
          <AppButton
            theme="danger"
            :disabled="competition.is_open"
            class="text-center"
            @click="showDeleteCompetition = true"
          >
            Wettbewerb löschen
          </AppButton>
        </div>
      </CardBase>
    </CardContainer>

    <UiModal
      v-if="showNewDuel"
      title="Duell nachtragen"
      size="lg"
      @close="showNewDuel = false"
    >
      <ScoringDuelForm
        :competition="competition"
        :teams="teamOptions"
        :base-url="baseUrl"
        @saved="showNewDuel = false"
      />
    </UiModal>

    <UiModal
      v-if="editingDuel"
      title="Duell bearbeiten"
      size="lg"
      @close="editingDuel = null"
    >
      <ScoringDuelForm
        :competition="competition"
        :teams="teamOptions"
        :duel="editingDuel"
        :base-url="baseUrl"
        @cancel="editingDuel = null"
        @saved="editingDuel = null"
      />
    </UiModal>

    <UiConfirmModal
      v-if="duelToDelete"
      title="Duell löschen"
      :message="`Soll das Duell ${duelToDelete.team_a?.name} vs. ${duelToDelete.team_b?.name} gelöscht werden?`"
      :processing="processing"
      @close="duelToDelete = null"
      @confirm="deleteDuel"
    />

    <UiConfirmModal
      v-if="showDeleteCompetition"
      title="Wettbewerb löschen"
      :message="`Soll der Wettbewerb „${competition.name}“ mit allen Teams, Duellen und Sonderpunkten gelöscht werden? Das kann nicht rückgängig gemacht werden.`"
      confirm-label="Endgültig löschen"
      :processing="processing"
      @close="showDeleteCompetition = false"
      @confirm="deleteCompetition"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router, usePoll } from "@inertiajs/vue3";

const props = defineProps({
  competition: {
    type: Object as PropType<App.Models.Competition>,
    required: true,
  },
  events: {
    type: Array as PropType<Pick<App.Models.Event, "id" | "name">[]>,
    required: true,
  },
  teams: {
    type: Array as PropType<Scoring.TeamRow[]>,
    required: true,
  },
  duels: {
    type: Array as PropType<App.Models.Duel[]>,
    required: true,
  },
  extraPoints: {
    type: Array as PropType<App.Models.ExtraPoint[]>,
    required: true,
  },
  standings: {
    type: Array as PropType<Scoring.StandingRow[]>,
    required: true,
  },
  importableGroupsCount: {
    type: Number,
    required: true,
  },
});

const baseUrl = "/dashboard/admin/scoring";

// live standings while tutors enter duels
usePoll(10000, { only: ["standings", "duels", "teams"] });

const teamOptions = computed(() =>
  props.teams.map((team) => ({ id: team.id, name: team.name })),
);

const showNewDuel = ref(false);
const showDeleteCompetition = ref(false);
const editingDuel = ref<App.Models.Duel | null>(null);
const duelToDelete = ref<App.Models.Duel | null>(null);
const processing = ref(false);

const toggleOpen = () => {
  router.post(
    `${baseUrl}/competition/${props.competition.id}/toggle-open`,
    {},
    { preserveScroll: true },
  );
};

const deleteDuel = () => {
  if (!duelToDelete.value) {
    return;
  }

  processing.value = true;
  router.delete(`${baseUrl}/duel/${duelToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
      duelToDelete.value = null;
    },
  });
};

const deleteCompetition = () => {
  processing.value = true;
  router.delete(`${baseUrl}/competition/${props.competition.id}`, {
    onFinish: () => {
      processing.value = false;
      showDeleteCompetition.value = false;
    },
  });
};
</script>
