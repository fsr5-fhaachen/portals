<template>
  <LayoutDashboardContent>
    <template #title>{{ group.name }}</template>
    <CardContainer>
      <CardBase v-if="group.event?.type == 'station_rally' && isGroupTutor">
        <UiH2>Gruppenname</UiH2>
        <FormKit
          type="form"
          id="group-name"
          :actions="false"
          v-model="nameForm"
          @submit="submitNameHandler"
        >
          <FormContainer>
            <FormRow>
              <FormKit
                type="text"
                name="name"
                validation="required|length:1,255"
              />
              <FormKit type="submit" label="Speichern" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase v-if="group.event?.type == 'station_rally'">
        <UiH2>Rundenplan</UiH2>
        <div class="mt-4">
          <RallyStationCarousel
            :stops="rallyStops"
            :scoringEnabled="rallyScoringEnabled"
          />
        </div>
      </CardBase>

      <CardBase v-if="group.event?.type == 'station_rally'">
        <UiH2>Stationen</UiH2>
        <div class="mt-4">
          <RallyStationMap
            :stops="rallyStops"
            :scoringEnabled="rallyScoringEnabled"
          />
        </div>
      </CardBase>

      <CardBase v-if="group.event?.type == 'station_rally' && tasks.length">
        <UiH2>Aufgaben</UiH2>
        <div class="mt-4">
          <RallyTaskChecklist
            :group="group.id"
            :event="group.event_id"
            :initialTasks="tasks"
            :taskMode="rallyTaskMode"
            :editable="isGroupTutor"
          />
        </div>
      </CardBase>

      <CardBase v-if="group.telegram_group_link">
        <UiH2>Telegram Gruppe</UiH2>
        <div class="text-gray-900 dark:text-gray-100">
          Für diese Gruppe wurde eine Telegram Gruppe erstellt. Diesen Link
          sehen nur Gruppenmitglieder und Tutoren.
        </div>
        <AppLink :href="group.telegram_group_link">
          {{ group.telegram_group_link }}
        </AppLink>
      </CardBase>
      <CardBase>
        <FormKit type="form" id="assign" :actions="false" v-model="form">
          <FormContainer>
            <FormRow>
              <UiH2>Filter</UiH2>
            </FormRow>
            <FormRow>
              <FormKit type="text" name="query" label="Suche" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <RegistrationTable
        v-if="group.event && filteredRegistrations"
        :courses="courses"
        :event="group.event"
        :registrations="filteredRegistrations"
        :hideGroups="true"
        :user="user"
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, ref, onBeforeUnmount, PropType } from "vue";
import { router } from "@inertiajs/vue3";

const form = ref({
  query: "",
});

const { group, tasks } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  group: {
    type: Object as PropType<App.Models.Group>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  isGroupTutor: {
    type: Boolean,
    default: false,
  },
  tasks: {
    type: Array as PropType<Array<any>>,
    default: () => [],
  },
});
const registrations = ref(group.registrations);

const nameForm = ref({ name: group.name });
const submitNameHandler = () => {
  router.post("/dashboard/tutor/group/" + group.id + "/name", nameForm.value);
};

const rallyConfig = group.event?.rally_config || {};
const rallyScoringEnabled = rallyConfig.scoring_enabled || false;
const rallyTaskMode = rallyConfig.task_mode || "points";

const rallyStops = ref<Array<any>>([]);
let isFetchingRallyStops = false;
const fetchRallyStops = async () => {
  if (isFetchingRallyStops) {
    return;
  }
  isFetchingRallyStops = true;

  const response = await fetch("/api/groups/" + group.id + "/current-stop", {
    method: "GET",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
  });

  if (response.ok) {
    const data = await response.json();
    rallyStops.value = data.stops;
  }

  isFetchingRallyStops = false;
};

let rallyStopsInterval: ReturnType<typeof setInterval> | null = null;
if (group.event?.type == "station_rally") {
  fetchRallyStops();
  rallyStopsInterval = setInterval(fetchRallyStops, 5000);
}

onBeforeUnmount(() => {
  if (rallyStopsInterval) {
    clearInterval(rallyStopsInterval);
  }
});

const filteredRegistrations = computed(() => {
  if (!form.value.query) {
    return registrations.value;
  }

  if (!registrations.value) {
    return [];
  }

  return registrations.value.filter((registration) => {
    return (
      registration.user?.firstname
        .toLowerCase()
        .includes(form.value.query.toLowerCase()) ||
      registration.user?.lastname
        .toLowerCase()
        .includes(form.value.query.toLowerCase()) ||
      registration.user?.email
        .toLowerCase()
        .includes(form.value.query.toLowerCase())
    );
  });
});

// const fetchRegistrations = async () => {
//   const response = await fetch(
//     "/api/events/" + group.event_id + "/registrations",
//     {
//       method: "GET",
//       credentials: "include",
//       headers: {
//         "Content-Type": "application/json",
//       },
//     },
//   );

//   if (response.ok) {
//     const data = await response.json();
//     registrations.value = data.groups[group.id];
//   }
// };
// const registrationsInterval = setInterval(fetchRegistrations, 5000);
// onBeforeUnmount(() => {
//   clearInterval(registrationsInterval);
// });
</script>
