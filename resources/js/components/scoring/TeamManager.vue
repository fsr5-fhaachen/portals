<template>
  <div class="space-y-6">
    <div
      v-if="competition.event_id"
      class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <p class="text-sm text-gray-700 dark:text-gray-300">
        Veranstaltung: <strong>{{ competition.event?.name }}</strong>
      </p>
      <AppButton
        :disabled="!importableGroupsCount"
        class="text-center"
        @click="importTeams"
      >
        Teams aus Event übernehmen ({{ importableGroupsCount }})
      </AppButton>
    </div>

    <FormKit
      type="form"
      :id="`team-form-${competition.id}`"
      :actions="false"
      v-model="newTeam"
      @submit="storeTeam"
    >
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <FormKit
          type="text"
          name="name"
          label="Neues Team"
          placeholder="z. B. Gruppe 1"
          validation="required|length:1,255"
        />
        <FormKit type="submit" label="Team anlegen" />
      </div>
    </FormKit>
    <UiMessage v-if="nameError" type="error">
      <template #message>{{ nameError }}</template>
    </UiMessage>

    <UiMessage
      v-if="!teams.length"
      type="info"
      message="Noch keine Teams angelegt."
    />
    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <li
        v-for="team in teams"
        :key="team.id"
        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center"
      >
        <template v-if="editingTeamId === team.id">
          <input
            v-model="editingName"
            type="text"
            maxlength="255"
            aria-label="Teamname"
            class="block w-full grow rounded-md border-gray-300 shadow-sm focus:border-fhac-mint focus:ring-fhac-mint dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 sm:text-sm"
            @keyup.enter="updateTeam(team)"
            @keyup.esc="editingTeamId = null"
          />
          <div class="flex gap-2">
            <AppButton
              :disabled="!editingName.trim()"
              @click="updateTeam(team)"
            >
              Speichern
            </AppButton>
            <AppButton theme="gray" @click="editingTeamId = null">
              Abbrechen
            </AppButton>
          </div>
        </template>
        <template v-else>
          <span class="grow text-gray-900 dark:text-gray-100">
            {{ team.name }}
            <span v-if="team.group_id" class="text-xs text-gray-500">
              (aus Gruppe)
            </span>
          </span>
          <div class="flex gap-2">
            <AppButton theme="gray" @click="startEditing(team)">
              Umbenennen
            </AppButton>
            <AppButton
              theme="danger"
              :disabled="team.is_used"
              :title="
                team.is_used
                  ? 'Das Team hat schon Duelle oder Sonderpunkte.'
                  : undefined
              "
              @click="teamToDelete = team"
            >
              Löschen
            </AppButton>
          </div>
        </template>
      </li>
    </ul>

    <UiConfirmModal
      v-if="teamToDelete"
      title="Team löschen"
      :message="`Soll das Team „${teamToDelete.name}“ gelöscht werden?`"
      :processing="processing"
      @close="teamToDelete = null"
      @confirm="deleteTeam"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
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
  importableGroupsCount: {
    type: Number,
    default: 0,
  },
});

const baseUrl = "/dashboard/admin/scoring";

const newTeam = ref({ name: "" });
const editingTeamId = ref<number | null>(null);
const editingName = ref("");
const teamToDelete = ref<Scoring.TeamRow | null>(null);
const processing = ref(false);

const page = usePage();
const nameError = computed(
  () => (page.props.errors as Record<string, string> | undefined)?.name,
);

const storeTeam = () =>
  new Promise<void>((resolve) => {
    router.post(
      `${baseUrl}/competition/${props.competition.id}/team`,
      newTeam.value,
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          newTeam.value = { name: "" };
        },
        onFinish: () => resolve(),
      },
    );
  });

const startEditing = (team: Scoring.TeamRow) => {
  editingTeamId.value = team.id;
  editingName.value = team.name;
};

const updateTeam = (team: Scoring.TeamRow) => {
  if (!editingName.value.trim()) {
    return;
  }

  router.post(
    `${baseUrl}/team/${team.id}`,
    { name: editingName.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        editingTeamId.value = null;
      },
    },
  );
};

const deleteTeam = () => {
  if (!teamToDelete.value) {
    return;
  }

  processing.value = true;
  router.delete(`${baseUrl}/team/${teamToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
      teamToDelete.value = null;
    },
  });
};

const importTeams = () => {
  router.post(
    `${baseUrl}/competition/${props.competition.id}/import-teams`,
    {},
    { preserveScroll: true },
  );
};
</script>
