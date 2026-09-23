<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }} · Stationen &amp; Rallye</template>

    <CardContainer>
      <CardBase>
        <div class="flex items-center justify-between">
          <UiH2>Audit-Log</UiH2>
          <AppLink :href="'/dashboard/admin/event/' + event.id + '/audit-log'">
            Wer hat wann was eingetragen? →
          </AppLink>
        </div>
      </CardBase>

      <!-- Stationen -->
      <CardBase>
        <UiH2>Stationen</UiH2>

        <div
          v-for="station in event.stations"
          :key="station.id"
          class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
        >
          <FormKit
            :id="'station-' + station.id"
            type="form"
            :actions="false"
            :value="{
              name: station.name,
              latitude: station.latitude,
              longitude: station.longitude,
            }"
            @submit="(data) => updateStation(station.id, data)"
          >
            <FormContainer>
              <FormRow>
                <FormKit type="text" name="name" label="Name" validation="required" />
              </FormRow>
              <FormRow>
                <FormKit type="text" name="latitude" label="Breitengrad (Latitude)" />
                <FormKit type="text" name="longitude" label="Längengrad (Longitude)" />
              </FormRow>
              <FormRow>
                <FormKit type="submit" label="Speichern" />
                <AppButton theme="danger" @click.prevent="deleteStation(station.id)">
                  Löschen
                </AppButton>
              </FormRow>
            </FormContainer>
          </FormKit>

          <FormKit
            :id="'station-tutors-' + station.id"
            type="form"
            :actions="false"
            :value="{ user_id: station.tutors.map((t) => t.id) }"
            @submit="(data) => syncStationTutors(station.id, data)"
          >
            <FormContainer>
              <FormRow>
                <FormKit
                  type="select"
                  name="user_id"
                  label="Stationstutoren"
                  multiple
                  :options="tutorOptions"
                />
                <FormKit type="submit" label="Zuordnen" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>

        <div class="mt-6 rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600">
          <FormKit
            id="station-create"
            type="form"
            :actions="false"
            v-model="newStationForm"
            @submit="createStation"
          >
            <FormContainer>
              <FormRow>
                <UiH2>Neue Station</UiH2>
              </FormRow>
              <FormRow>
                <FormKit type="text" name="name" label="Name" validation="required" />
              </FormRow>
              <FormRow>
                <FormKit type="text" name="latitude" label="Breitengrad (Latitude)" />
                <FormKit type="text" name="longitude" label="Längengrad (Longitude)" />
              </FormRow>
              <FormRow>
                <FormKit type="submit" label="Anlegen" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>
      </CardBase>

      <!-- Gruppentutoren -->
      <CardBase>
        <UiH2>Gruppentutoren</UiH2>
        <div
          v-for="group in event.groups"
          :key="group.id"
          class="mt-6 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
        >
          <FormKit
            :id="'group-tutors-' + group.id"
            type="form"
            :actions="false"
            :value="{ user_id: group.tutors.map((t) => t.id) }"
            @submit="(data) => syncGroupTutors(group.id, data)"
          >
            <FormContainer>
              <FormRow>
                <UiH2>{{ group.name }}</UiH2>
              </FormRow>
              <FormRow>
                <FormKit
                  type="select"
                  name="user_id"
                  label="Gruppentutoren"
                  multiple
                  :options="tutorOptions"
                />
                <FormKit type="submit" label="Zuordnen" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>
      </CardBase>

      <!-- Rundenplan generieren -->
      <CardBase>
        <UiH2>Rundenplan generieren</UiH2>
        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Erzeugt den kompletten Rundenplan neu (Paarungen bzw. Stationsfolge).
          Bereits eingetragene Ergebnisse und Rundenzeiten gehen dabei verloren.
        </div>
        <FormKit
          id="schedule-generate"
          type="form"
          :actions="false"
          v-model="scheduleForm"
          @submit="generateSchedule"
        >
          <FormContainer>
            <FormRow>
              <FormKit
                type="number"
                name="rounds"
                label="Anzahl Runden"
                validation="required|min:1"
              />
              <FormKit type="submit" label="Rundenplan generieren" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <!-- Rundenzeiten -->
      <CardBase v-if="maxRound">
        <UiH2>Rundenzeiten</UiH2>
        <div
          v-for="round in maxRound"
          :key="round"
          class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
        >
          <FormKit
            :id="'round-times-' + round"
            type="form"
            :actions="false"
            @submit="(data) => setScheduleTimes(round, data)"
          >
            <FormContainer>
              <FormRow>
                <UiH2>Runde {{ round }}</UiH2>
              </FormRow>
              <FormRow>
                <FormKit type="datetime-local" name="starts_at" label="Start" />
                <FormKit type="datetime-local" name="ends_at" label="Ende" />
                <FormKit type="submit" label="Speichern" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>
      </CardBase>

      <!-- Aufgaben -->
      <CardBase>
        <UiH2>Aufgaben</UiH2>
        <div
          v-for="task in event.tasks"
          :key="task.id"
          class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
        >
          <FormKit
            :id="'task-' + task.id"
            type="form"
            :actions="false"
            :value="{ name: task.name, points: task.points }"
            @submit="(data) => updateTask(task.id, data)"
          >
            <FormContainer>
              <FormRow>
                <FormKit type="text" name="name" label="Aufgabe" validation="required" />
                <FormKit type="number" name="points" label="Punkte" min="0" />
                <FormKit type="submit" label="Speichern" />
                <AppButton theme="danger" @click.prevent="deleteTask(task.id)">
                  Löschen
                </AppButton>
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>

        <div class="mt-6 rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600">
          <FormKit
            id="task-create"
            type="form"
            :actions="false"
            v-model="newTaskForm"
            @submit="createTask"
          >
            <FormContainer>
              <FormRow>
                <UiH2>Neue Aufgabe</UiH2>
              </FormRow>
              <FormRow>
                <FormKit type="text" name="name" label="Aufgabe" validation="required" />
                <FormKit type="number" name="points" label="Punkte" min="0" value="1" />
                <FormKit type="submit" label="Anlegen" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </div>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, ref, PropType } from "vue";
import { router } from "@inertiajs/vue3";

const { event, tutors } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  tutors: {
    type: Array as PropType<Models.User[]>,
    required: true,
  },
  tasks: {
    type: Array as PropType<Array<any>>,
    default: () => [],
  },
  maxRound: {
    type: Number,
    required: false,
    default: null,
  },
});

const tutorOptions = computed(() =>
  Object.fromEntries(
    tutors.map((tutor) => [tutor.id, tutor.firstname + " " + tutor.lastname]),
  ),
);

const newStationForm = ref({});
const createStation = (data: any) => {
  router.post("/dashboard/admin/event/" + event.id + "/stations", data, {
    onSuccess: () => (newStationForm.value = {}),
  });
};
const updateStation = (stationId: number, data: any) => {
  router.post("/dashboard/admin/station/" + stationId, data);
};
const deleteStation = (stationId: number) => {
  if (!confirm("Diese Station wirklich löschen?")) {
    return;
  }
  router.delete("/dashboard/admin/station/" + stationId);
};
const syncStationTutors = (stationId: number, data: any) => {
  router.post("/dashboard/admin/station/" + stationId + "/tutors", data);
};

const syncGroupTutors = (groupId: number, data: any) => {
  router.post("/dashboard/admin/group/" + groupId + "/tutors", data);
};

const scheduleForm = ref({});
const generateSchedule = (data: any) => {
  if (
    !confirm(
      "Der Rundenplan wird komplett neu generiert. Bestehende Ergebnisse und Rundenzeiten gehen verloren. Fortfahren?",
    )
  ) {
    return;
  }
  router.post("/dashboard/admin/event/" + event.id + "/schedule/generate", data);
};

const setScheduleTimes = (round: number, data: any) => {
  router.post("/dashboard/admin/event/" + event.id + "/schedule/times", {
    ...data,
    round,
  });
};

const newTaskForm = ref({});
const createTask = (data: any) => {
  router.post("/dashboard/admin/event/" + event.id + "/tasks", data, {
    onSuccess: () => (newTaskForm.value = {}),
  });
};
const updateTask = (taskId: number, data: any) => {
  router.post("/dashboard/admin/task/" + taskId, data);
};
const deleteTask = (taskId: number) => {
  if (!confirm("Diese Aufgabe wirklich löschen?")) {
    return;
  }
  router.delete("/dashboard/admin/task/" + taskId);
};
</script>
