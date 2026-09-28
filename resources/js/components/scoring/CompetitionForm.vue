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
          type="text"
          name="name"
          label="Name"
          placeholder="z. B. Stadtrallye"
          validation="required|length:1,255"
        />
      </FormRow>
      <FormRow>
        <FormKit
          type="select"
          name="event_id"
          label="Veranstaltung"
          help="Ihre Gruppen können als Teams übernommen werden."
          :options="eventOptions"
        />
      </FormRow>
      <FormRow>
        <FormKit
          type="number"
          name="points_win"
          label="Punkte Sieg"
          number="integer"
          validation="required|min:0|max:100"
        />
        <FormKit
          type="number"
          name="points_draw"
          label="Punkte Unentschieden"
          number="integer"
          validation="required|min:0|max:100"
        />
        <FormKit
          type="number"
          name="points_loss"
          label="Punkte Niederlage"
          number="integer"
          validation="required|min:0|max:100"
        />
      </FormRow>
      <SwitchGroup as="div" class="flex items-center justify-between gap-4">
        <span class="flex grow flex-col">
          <SwitchLabel
            as="span"
            class="text-sm font-medium text-gray-700 dark:text-gray-200"
            passive
          >
            Bonus pro Team
          </SwitchLabel>
          <SwitchDescription
            as="span"
            class="text-sm text-gray-500 dark:text-gray-400"
          >
            {{
              bonusPerTeam
                ? "An: Jedes Team kann pro Duell bis zum Maximum bekommen."
                : "Aus: Beide Teams teilen sich einen Bonus-Topf pro Duell."
            }}
          </SwitchDescription>
        </span>
        <Switch
          v-model="bonusPerTeam"
          :class="
            bonusPerTeam ? 'bg-fhac-mint-dark' : 'bg-gray-300 dark:bg-gray-600'
          "
          class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-fhac-mint focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        >
          <span class="sr-only">Bonus pro Team</span>
          <span
            aria-hidden="true"
            :class="bonusPerTeam ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
          />
        </Switch>
      </SwitchGroup>
      <FormRow>
        <FormKit
          type="number"
          name="bonus_pool"
          :label="bonusPerTeam ? 'Max. Bonus pro Team' : 'Bonus-Topf pro Duell'"
          help="0 schaltet den Bonus ab."
          number="integer"
          validation="required|min:0|max:100"
        />
      </FormRow>
      <UiMessage v-if="serverErrors.length" type="error">
        <template #message>
          <p v-for="error in serverErrors" :key="error">{{ error }}</p>
        </template>
      </UiMessage>

      <FormRow>
        <FormKit type="submit" :label="submitLabel" />
      </FormRow>
    </FormContainer>
  </FormKit>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import {
  Switch,
  SwitchDescription,
  SwitchGroup,
  SwitchLabel,
} from "@headlessui/vue";

const props = defineProps({
  competition: {
    type: Object as PropType<App.Models.Competition | null>,
    default: null,
  },
  events: {
    type: Array as PropType<Pick<App.Models.Event, "id" | "name">[]>,
    required: true,
  },
  submitUrl: {
    type: String,
    required: true,
  },
  submitLabel: {
    type: String,
    default: "Speichern",
  },
});

const formId = `competition-form-${props.competition?.id ?? "new"}`;

const form = ref({
  name: props.competition?.name ?? "",
  event_id: props.competition?.event_id ?? "",
  points_win: props.competition?.points_win ?? 4,
  points_draw: props.competition?.points_draw ?? 2,
  points_loss: props.competition?.points_loss ?? 0,
  bonus_pool: props.competition?.bonus_pool ?? 2,
});

// kept outside the FormKit form, which only tracks values of FormKit inputs
const bonusPerTeam = ref(props.competition?.bonus_per_team ?? true);

const competitionFields = [
  "name",
  "event_id",
  "points_win",
  "points_draw",
  "points_loss",
  "bonus_pool",
  "bonus_per_team",
];
const page = usePage();
const serverErrors = computed(() =>
  Object.entries((page.props.errors ?? {}) as Record<string, string>)
    .filter(([field]) => competitionFields.includes(field))
    .map(([, message]) => message),
);

const eventOptions = computed(() => [
  { value: "", label: "Keine" },
  ...useSelectFormEventOptions(props.events as App.Models.Event[]),
]);

const submit = () =>
  new Promise<void>((resolve) => {
    router.post(
      props.submitUrl,
      {
        ...form.value,
        event_id: form.value.event_id || null,
        bonus_per_team: bonusPerTeam.value,
      },
      { preserveScroll: true, onFinish: () => resolve() },
    );
  });
</script>
