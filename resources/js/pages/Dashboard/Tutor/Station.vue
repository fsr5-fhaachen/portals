<template>
  <LayoutDashboardContent>
    <template #title>{{ station.name }}</template>

    <CardContainer>
      <CardBase v-if="!currentRound">
        <UiMessage type="success">
          <template #message>
            Für diese Station sind aktuell keine offenen Duelle vorhanden.
            Entweder wurde noch kein Rundenplan erstellt, oder alle Ergebnisse
            wurden bereits eingetragen.
          </template>
        </UiMessage>
      </CardBase>

      <template v-else>
        <CardBase>
          <UiH2>Runde {{ currentRound }}</UiH2>

          <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div
              v-for="(stop, stopIndex) in currentStops"
              :key="stop.id"
              class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
            >
              <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                Team {{ stopIndex === 0 ? "A" : "B" }}
              </div>
              <div class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">
                {{ stop.group.name }}
              </div>
              <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <li
                  v-for="registration in stop.group.registrations"
                  :key="registration.id"
                >
                  {{ registration.user?.firstname }}
                  {{ registration.user?.lastname }}
                </li>
              </ul>
            </div>
          </div>
        </CardBase>

        <CardBase v-if="isStationTutor">
          <FormKit
            type="form"
            id="station-result"
            :actions="false"
            v-model="form"
            @submit="submitHandler"
          >
            <FormContainer>
              <FormRow>
                <UiH2>Ergebnis eintragen</UiH2>
              </FormRow>
              <FormRow>
                <FormKit
                  type="radio"
                  name="winner"
                  validation="required"
                  :options="{
                    a: currentStops[0]?.group.name + ' gewinnt (4:0)',
                    draw: 'Unentschieden (2:2)',
                    b: currentStops[1]?.group.name + ' gewinnt (0:4)',
                  }"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="number"
                  name="bonus_a"
                  :label="'Bonuspunkte für ' + currentStops[0]?.group.name + ' (0-2)'"
                  min="0"
                  max="2"
                  value="0"
                />
                <FormKit
                  type="number"
                  name="bonus_b"
                  :label="'Bonuspunkte für ' + currentStops[1]?.group.name + ' (0-2)'"
                  min="0"
                  max="2"
                  value="0"
                />
              </FormRow>
              <FormRow>
                <FormKit type="submit" label="Ergebnis speichern" />
              </FormRow>
            </FormContainer>
          </FormKit>
        </CardBase>
        <CardBase v-else>
          <UiMessage
            type="warning"
            message="Du bist kein Stationstutor dieser Station und kannst daher kein Ergebnis eintragen."
          />
        </CardBase>
      </template>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";
import { router } from "@inertiajs/vue3";

const { station, currentRound } = defineProps({
  station: {
    type: Object as PropType<App.Models.Station>,
    required: true,
  },
  currentRound: {
    type: Number,
    required: false,
    default: null,
  },
  currentStops: {
    type: Array as PropType<Array<any>>,
    required: true,
  },
  isStationTutor: {
    type: Boolean,
    default: false,
  },
});

const form = ref({});

const submitHandler = () => {
  router.post("/dashboard/tutor/station/" + station.id + "/result", {
    ...form.value,
    round: currentRound,
  });
};
</script>
