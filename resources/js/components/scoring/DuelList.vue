<template>
  <UiMessage
    v-if="!duels.length"
    type="info"
    message="Es wurden noch keine Duelle eingetragen."
  />
  <ul v-else class="flex flex-col gap-3">
    <li
      v-for="duel in duels"
      :key="duel.id"
      class="rounded-lg bg-white p-4 shadow dark:bg-gray-900"
    >
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="grow space-y-1">
          <div
            class="flex flex-wrap items-baseline gap-x-2 text-lg font-medium text-gray-900 dark:text-gray-100"
          >
            <span :class="{ 'font-bold': duel.result === 'team_a' }">
              {{ duel.team_a?.name }}
            </span>
            <span class="text-sm text-gray-500">vs.</span>
            <span :class="{ 'font-bold': duel.result === 'team_b' }">
              {{ duel.team_b?.name }}
            </span>
          </div>
          <div class="flex flex-wrap gap-2 text-sm">
            <span
              :class="
                duel.result === 'draw'
                  ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'
                  : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'
              "
              class="rounded-md px-2 py-0.5"
            >
              {{ resultLabel(duel) }}
            </span>
            <span
              v-if="duel.bonus_a || duel.bonus_b"
              class="rounded-md bg-yellow-100 px-2 py-0.5 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100"
            >
              Bonus {{ duel.bonus_a }}:{{ duel.bonus_b }}
            </span>
          </div>
          <p
            v-if="duel.note"
            class="text-sm italic text-gray-700 dark:text-gray-300"
          >
            {{ duel.note }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            eingetragen von {{ personName(duel.creator) }}
            <template v-if="duel.created_at">
              · <UiTimeString :value="duel.created_at" with-clock-suffix />
            </template>
            <template v-if="duel.updater">
              <br />
              geändert von {{ personName(duel.updater) }}
              <template v-if="duel.updated_at">
                · <UiTimeString :value="duel.updated_at" with-clock-suffix />
              </template>
            </template>
          </p>
        </div>
        <div v-if="editable" class="flex gap-2">
          <AppButton class="flex-1 text-center" @click="emits('edit', duel)">
            Bearbeiten
          </AppButton>
          <AppButton
            theme="danger"
            class="flex-1 text-center"
            @click="emits('delete', duel)"
          >
            Löschen
          </AppButton>
        </div>
      </div>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  duels: {
    type: Array as PropType<App.Models.Duel[]>,
    required: true,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

const emits = defineEmits<{
  edit: [duel: App.Models.Duel];
  delete: [duel: App.Models.Duel];
}>();

const personName = (
  person: Pick<App.Models.User, "firstname" | "lastname"> | null | undefined,
) => (person ? `${person.firstname} ${person.lastname}` : "unbekannt");

const resultLabel = (duel: App.Models.Duel) => {
  if (duel.result === "team_a") {
    return `Sieg ${duel.team_a?.name ?? "Team A"}`;
  }
  if (duel.result === "team_b") {
    return `Sieg ${duel.team_b?.name ?? "Team B"}`;
  }

  return "Unentschieden";
};
</script>
