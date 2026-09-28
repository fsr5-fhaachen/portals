<template>
  <LayoutDashboardContent>
    <template #title>Duelle eintragen</template>
    <template #subtitle v-if="competition">{{ competition.name }}</template>

    <CardContainer>
      <UiMessage
        v-if="!competitions.length"
        type="info"
        message="Aktuell ist kein Wettbewerb geöffnet."
      />

      <CardBase v-if="competitions.length > 1">
        <div class="space-y-3">
          <UiH2>Wettbewerb wählen</UiH2>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <InertiaLink
              v-for="option in competitions"
              :key="option.id"
              :href="`/dashboard/tutor/scoring?competition=${option.id}`"
              :aria-current="option.id === competition?.id ? 'page' : undefined"
              :class="
                option.id === competition?.id
                  ? 'border-fhac-mint-dark bg-fhac-mint-dark text-white'
                  : 'border-gray-300 text-gray-900 hover:border-fhac-mint dark:border-gray-600 dark:text-gray-100'
              "
              class="rounded-md border-2 px-3 py-3 text-center font-medium"
            >
              {{ option.name }}
            </InertiaLink>
          </div>
        </div>
      </CardBase>

      <template v-if="competition">
        <CardBase ref="formCard">
          <div class="space-y-6">
            <UiH2>
              {{ editingDuel ? "Duell bearbeiten" : "Neues Duell" }}
            </UiH2>
            <UiMessage
              v-if="teams.length < 2"
              type="warning"
              message="Für diesen Wettbewerb gibt es noch nicht genug Teams. Sag einem Admin Bescheid."
            />
            <ScoringDuelForm
              v-else
              :key="editingDuel?.id ?? 'new'"
              :competition="competition"
              :teams="teams"
              :duel="editingDuel"
              base-url="/dashboard/tutor/scoring"
              @cancel="editingDuel = null"
              @saved="editingDuel = null"
            />
          </div>
        </CardBase>

        <div class="space-y-3 px-4 sm:px-0">
          <UiH2>Eingetragene Duelle</UiH2>
          <ScoringDuelList
            :duels="duels"
            editable
            @edit="edit"
            @delete="duelToDelete = $event"
          />
        </div>
      </template>
    </CardContainer>

    <UiConfirmModal
      v-if="duelToDelete"
      title="Duell löschen"
      :message="`Soll das Duell ${duelToDelete.team_a?.name} vs. ${duelToDelete.team_b?.name} gelöscht werden?`"
      :processing="processing"
      @close="duelToDelete = null"
      @confirm="deleteDuel"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { nextTick, PropType, ref } from "vue";
import { Link as InertiaLink, router } from "@inertiajs/vue3";

defineProps({
  competitions: {
    type: Array as PropType<App.Models.Competition[]>,
    required: true,
  },
  competition: {
    type: Object as PropType<App.Models.Competition | null>,
    default: null,
  },
  teams: {
    type: Array as PropType<Pick<App.Models.CompetitionTeam, "id" | "name">[]>,
    required: true,
  },
  duels: {
    type: Array as PropType<App.Models.Duel[]>,
    required: true,
  },
});

const formCard = ref<{ $el: HTMLElement } | null>(null);
const editingDuel = ref<App.Models.Duel | null>(null);
const duelToDelete = ref<App.Models.Duel | null>(null);
const processing = ref(false);

const edit = async (duel: App.Models.Duel) => {
  editingDuel.value = duel;
  await nextTick();
  formCard.value?.$el.scrollIntoView({ behavior: "smooth", block: "start" });
};

const deleteDuel = () => {
  if (!duelToDelete.value) {
    return;
  }

  const duelId = duelToDelete.value.id;
  processing.value = true;
  router.delete(`/dashboard/tutor/scoring/duel/${duelId}`, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      processing.value = false;
      duelToDelete.value = null;
      if (editingDuel.value?.id === duelId) {
        editingDuel.value = null;
      }
    },
  });
};
</script>
