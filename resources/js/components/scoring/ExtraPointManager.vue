<template>
  <div class="space-y-6">
    <FormKit
      type="form"
      :id="`extra-point-form-${competition.id}`"
      :actions="false"
      v-model="form"
      @submit="submit"
    >
      <FormContainer>
        <FormRow>
          <FormKit
            type="select"
            name="competition_team_id"
            label="Team"
            placeholder="Team wählen"
            validation="required"
            :options="teamOptions"
          />
          <FormKit
            type="number"
            name="points"
            label="Punkte"
            number="integer"
            help="Negative Werte für Strafpunkte."
            validation="required|min:-100|max:100|not:0"
            :validation-messages="{ not: 'Sonderpunkte dürfen nicht 0 sein.' }"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="text"
            name="reason"
            label="Grund"
            placeholder="z. B. Bingo"
            validation="required|length:1,255"
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
            :label="editing ? 'Änderung speichern' : 'Sonderpunkte vergeben'"
          />
          <AppButton
            v-if="editing"
            theme="gray"
            class="flex-1 text-center"
            @click="cancelEditing"
          >
            Abbrechen
          </AppButton>
        </FormRow>
      </FormContainer>
    </FormKit>

    <UiMessage
      v-if="!extraPoints.length"
      type="info"
      message="Noch keine Sonderpunkte vergeben."
    />
    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <li
        v-for="extraPoint in extraPoints"
        :key="extraPoint.id"
        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center"
      >
        <div class="grow">
          <p class="text-gray-900 dark:text-gray-100">
            <span
              :class="
                extraPoint.points > 0
                  ? 'text-green-700 dark:text-green-400'
                  : 'text-red-700 dark:text-red-400'
              "
              class="font-bold"
            >
              {{ extraPoint.points > 0 ? "+" : "" }}{{ extraPoint.points }}
            </span>
            {{ extraPoint.team?.name }} · {{ extraPoint.reason }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            vergeben von {{ personName(extraPoint.creator) }}
            <template v-if="extraPoint.updater">
              · geändert von {{ personName(extraPoint.updater) }}
            </template>
          </p>
        </div>
        <div class="flex gap-2">
          <AppButton theme="gray" @click="startEditing(extraPoint)">
            Bearbeiten
          </AppButton>
          <AppButton theme="danger" @click="extraPointToDelete = extraPoint">
            Löschen
          </AppButton>
        </div>
      </li>
    </ul>

    <UiConfirmModal
      v-if="extraPointToDelete"
      title="Sonderpunkte löschen"
      :message="`Sollen die Sonderpunkte „${extraPointToDelete.reason}“ für ${extraPointToDelete.team?.name} gelöscht werden?`"
      :processing="processing"
      @close="extraPointToDelete = null"
      @confirm="deleteExtraPoint"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { reset } from "@formkit/core";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
  competition: {
    type: Object as PropType<App.Models.Competition>,
    required: true,
  },
  teams: {
    type: Array as PropType<Scoring.TeamRow[]>,
    required: true,
  },
  extraPoints: {
    type: Array as PropType<App.Models.ExtraPoint[]>,
    required: true,
  },
});

const baseUrl = "/dashboard/admin/scoring";
const formId = `extra-point-form-${props.competition.id}`;

const emptyForm = () => ({
  competition_team_id: null as number | null,
  points: null as number | null,
  reason: "",
});

const form = ref(emptyForm());
const editing = ref<App.Models.ExtraPoint | null>(null);
const extraPointToDelete = ref<App.Models.ExtraPoint | null>(null);
const processing = ref(false);

const teamOptions = computed(() => useSelectFormTeamOptions(props.teams));

const extraPointFields = ["competition_team_id", "points", "reason"];
const page = usePage();
const serverErrors = computed(() =>
  Object.entries((page.props.errors ?? {}) as Record<string, string>)
    .filter(([field]) => extraPointFields.includes(field))
    .map(([, message]) => message),
);

const personName = (
  person: Pick<App.Models.User, "firstname" | "lastname"> | null | undefined,
) => (person ? `${person.firstname} ${person.lastname}` : "unbekannt");

const startEditing = (extraPoint: App.Models.ExtraPoint) => {
  editing.value = extraPoint;
  form.value = {
    competition_team_id: extraPoint.competition_team_id,
    points: extraPoint.points,
    reason: extraPoint.reason,
  };
};

const cancelEditing = () => {
  editing.value = null;
  reset(formId, emptyForm());
};

const submit = () =>
  new Promise<void>((resolve) => {
    const url = editing.value
      ? `${baseUrl}/extra-point/${editing.value.id}`
      : `${baseUrl}/competition/${props.competition.id}/extra-point`;

    router.post(url, form.value, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => cancelEditing(),
      onFinish: () => resolve(),
    });
  });

const deleteExtraPoint = () => {
  if (!extraPointToDelete.value) {
    return;
  }

  processing.value = true;
  router.delete(`${baseUrl}/extra-point/${extraPointToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
      extraPointToDelete.value = null;
    },
  });
};
</script>
