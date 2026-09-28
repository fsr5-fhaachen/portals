<template>
  <FormKit
    type="form"
    :id="formId"
    :actions="false"
    v-model="form"
    @submit="submit"
  >
    <FormContainer>
      <FormRow>
        <FormKit
          type="select"
          name="team_a_id"
          label="Team A"
          placeholder="Team wählen"
          validation="required"
          :options="teamOptions"
          :validation-messages="{ required: 'Bitte wähle Team A aus.' }"
        />
        <FormKit
          type="select"
          name="team_b_id"
          label="Team B"
          placeholder="Team wählen"
          validation="required"
          :options="teamBOptions"
          :validation-messages="{ required: 'Bitte wähle Team B aus.' }"
        />
      </FormRow>

      <fieldset>
        <legend
          class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"
        >
          Ergebnis
        </legend>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <ScoringChoiceButton
            v-for="option in resultOptions"
            :key="option.value"
            :selected="form.result === option.value"
            @select="form.result = option.value"
          >
            <span class="block text-lg">{{ option.label }}</span>
            <span class="block text-sm opacity-80">{{ option.points }}</span>
          </ScoringChoiceButton>
        </div>
      </fieldset>

      <div v-if="competition.bonus_pool > 0" class="grid gap-4 sm:grid-cols-2">
        <fieldset v-for="side in bonusSides" :key="side.field">
          <legend
            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"
          >
            Bonus {{ side.label }}
          </legend>
          <div class="flex gap-2">
            <ScoringChoiceButton
              v-for="value in bonusValues"
              :key="value"
              class="flex-1"
              :selected="form[side.field] === value"
              :disabled="value > maxBonusFor(form[side.other])"
              @select="form[side.field] = value"
            >
              {{ value }}
            </ScoringChoiceButton>
          </div>
        </fieldset>
        <p class="text-sm text-gray-500 sm:col-span-2 dark:text-gray-400">
          <template v-if="competition.bonus_per_team">
            Jedes Team kann bis zu {{ competition.bonus_pool }} Bonuspunkte
            bekommen.
          </template>
          <template v-else>
            Bonus-Topf: {{ competition.bonus_pool }} Punkte pro Duell,
            aufgeteilt auf beide Teams.
          </template>
        </p>
      </div>

      <FormRow>
        <FormKit
          type="text"
          name="note"
          label="Notiz (optional)"
          validation="length:0,255"
          placeholder="z. B. Station am Dom"
        />
      </FormRow>

      <UiMessage v-if="serverErrors.length" type="error">
        <template #message>
          <p v-for="error in serverErrors" :key="error">{{ error }}</p>
        </template>
      </UiMessage>

      <FormRow>
        <FormKit
          type="submit"
          :label="duel ? 'Änderung speichern' : 'Duell eintragen'"
          :disabled="!form.result"
        />
        <AppButton
          v-if="duel"
          theme="gray"
          class="flex-1 text-center"
          @click="emits('cancel')"
        >
          Abbrechen
        </AppButton>
      </FormRow>
    </FormContainer>
  </FormKit>
</template>

<script setup lang="ts">
import { computed, PropType, ref, watch } from "vue";
import { reset } from "@formkit/core";
import { router, usePage } from "@inertiajs/vue3";

type BonusField = "bonus_a" | "bonus_b";

const props = defineProps({
  competition: {
    type: Object as PropType<App.Models.Competition>,
    required: true,
  },
  teams: {
    type: Array as PropType<Pick<App.Models.CompetitionTeam, "id" | "name">[]>,
    required: true,
  },
  duel: {
    type: Object as PropType<App.Models.Duel | null>,
    default: null,
  },
  // e.g. /dashboard/tutor/scoring
  baseUrl: {
    type: String,
    required: true,
  },
});

const emits = defineEmits<{
  cancel: [];
  saved: [];
}>();

const formId = `duel-form-${props.duel?.id ?? "new"}`;

const emptyForm = () => ({
  team_a_id: null as number | null,
  team_b_id: null as number | null,
  result: null as string | null,
  bonus_a: 0,
  bonus_b: 0,
  note: "",
});

const form = ref(
  props.duel
    ? {
        team_a_id: props.duel.team_a_id,
        team_b_id: props.duel.team_b_id,
        result: props.duel.result as string | null,
        bonus_a: props.duel.bonus_a,
        bonus_b: props.duel.bonus_b,
        note: props.duel.note ?? "",
      }
    : emptyForm(),
);

// fallback for errors the client side validation cannot catch
const duelFields = [
  "duel",
  "team_a_id",
  "team_b_id",
  "result",
  "bonus_a",
  "bonus_b",
  "note",
];
const page = usePage();
const serverErrors = computed(() =>
  Object.entries((page.props.errors ?? {}) as Record<string, string>)
    .filter(([field]) => duelFields.includes(field))
    .map(([, message]) => message),
);

const teamOptions = computed(() => useSelectFormTeamOptions(props.teams));
const teamBOptions = computed(() =>
  useSelectFormTeamOptions(
    props.teams.filter((team) => team.id !== Number(form.value.team_a_id)),
  ),
);

// team B must not be team A
watch(
  () => form.value.team_a_id,
  (teamAId) => {
    if (teamAId && Number(teamAId) === Number(form.value.team_b_id)) {
      form.value.team_b_id = null;
    }
  },
);

const teamName = (teamId: number | null, fallback: string) =>
  props.teams.find((team) => team.id === Number(teamId))?.name ?? fallback;

const resultOptions = computed(() => {
  const { points_win, points_draw, points_loss } = props.competition;

  return [
    {
      value: "team_a",
      label: `${teamName(form.value.team_a_id, "Team A")} gewinnt`,
      points: `${points_win}:${points_loss}`,
    },
    {
      value: "draw",
      label: "Unentschieden",
      points: `${points_draw}:${points_draw}`,
    },
    {
      value: "team_b",
      label: `${teamName(form.value.team_b_id, "Team B")} gewinnt`,
      points: `${points_loss}:${points_win}`,
    },
  ];
});

// mirrors Competition::maxBonusFor()
const maxBonusFor = (otherTeamBonus: number) =>
  props.competition.bonus_per_team
    ? props.competition.bonus_pool
    : Math.max(0, props.competition.bonus_pool - otherTeamBonus);

const bonusValues = computed(() =>
  Array.from({ length: props.competition.bonus_pool + 1 }, (_, i) => i),
);
const bonusSides = computed<
  { field: BonusField; other: BonusField; label: string }[]
>(() => [
  {
    field: "bonus_a",
    other: "bonus_b",
    label: teamName(form.value.team_a_id, "Team A"),
  },
  {
    field: "bonus_b",
    other: "bonus_a",
    label: teamName(form.value.team_b_id, "Team B"),
  },
]);

// returning a promise lets FormKit disable the form until the request is done
const submit = () =>
  new Promise<void>((resolve) => {
    const url = props.duel
      ? `${props.baseUrl}/duel/${props.duel.id}`
      : `${props.baseUrl}/competition/${props.competition.id}/duel`;

    router.post(url, form.value, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        if (!props.duel) {
          reset(formId, emptyForm());
        }
        emits("saved");
      },
      onFinish: () => resolve(),
    });
  });
</script>
