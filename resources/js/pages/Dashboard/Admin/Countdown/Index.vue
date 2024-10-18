<template>
  <LayoutDashboardContent>
    <template #title>Countdown</template>

    <CardContainer>
      <CardBase>
        <UiH2>Aktionen</UiH2>

        <FormRow>
          <div>Aktueller Zustand: {{ state.state }}</div>
        </FormRow>

        <FormRow>
          <AppButton
            @click="updateStateAndSubmit('running')"
            class="flex-1 text-center"
          >
            Countdown starten
          </AppButton>
          <AppButton
            @click="updateStateAndSubmit('stopped')"
            theme="danger"
            class="flex-1 text-center"
          >
            Countdown stoppen
          </AppButton>
        </FormRow>
        <FormRow>
          <AppButton
            @click="updateStateAndSubmit('idle')"
            theme="gray"
            class="flex-1 text-center"
          >
            Countdown idle
          </AppButton>
        </FormRow>
      </CardBase>
      <CardBase>
        <FormKit
          type="form"
          id="countdown"
          @submit="updateTimeAndSubmit"
          :actions="false"
          v-model="form"
        >
          <FormContainer>
            <FormRow>
              <UiH2>Einstellungen</UiH2>
            </FormRow>
            <FormRow>
              <div>
                Wenn der Countdown in Richtung "Aufwärts" zählt, wird die
                festgelegte Zeit als Maximum gewertet. Wenn der Countdown in
                Richtung "Abwärts" zählt, wird die festgelegte Zeit als
                Startwert gewertet und der Countdown zählt bis 0. Wird der
                Countdown auf "idle" gestellt, wird nichts mehr angezeigt und
                die Zeit wird im Hintergrund zurückgesetzt. "Stopped" stoppt nur
                die Anzeige, die Zeit wird nicht zurückgesetzt.
              </div>
            </FormRow>
            <FormRow>
              <FormKit
                type="number"
                name="hours"
                label="Stunden"
                placeholder="0"
                min="0"
                max="23"
                validation="required"
              />
              <FormKit
                type="number"
                name="minutes"
                label="Minuten"
                placeholder="0"
                min="0"
                max="59"
                validation="required"
              />
              <FormKit
                type="number"
                name="seconds"
                label="Sekunden"
                placeholder="0"
                min="0"
                max="59"
                validation="required"
              />
            </FormRow>
            <FormRow>
              <FormKit
                type="select"
                name="direction"
                label="Zählrichtung"
                placeholder="Wähle eine Zählrichtung"
                :options="{
                  up: 'Aufwärts',
                  down: 'Abwärts',
                }"
                validation="required"
              />
            </FormRow>
            <FormRow>
              <FormKit type="submit" label="Countdown updaten" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { options } from "prettier-plugin-tailwindcss";
import { computed, ref, PropType, onBeforeUnmount } from "vue";

const { state } = defineProps({
  state: {
    type: Object as PropType<{
      time: {
        seconds: number;
        minutes: number;
        hours: number;
      };
      direction: "up" | "down";
      state: "setup" | "idle" | "running" | "stopped";
    }>,
    required: true,
  },
});

const form = ref<{
  seconds: number;
  minutes: number;
  hours: number;
  direction: "up" | "down";
}>({
  seconds: state?.time.seconds || 0,
  minutes: state?.time.minutes || 0,
  hours: state?.time.hours || 0,
  direction: state?.direction || "down",
});

const updateStateAndSubmit = (
  newState: "setup" | "idle" | "running" | "stopped",
) => {
  state.state = newState;
  submitCountdownHandler();
};

const updateTimeAndSubmit = () => {
  state.time.seconds = form.value.seconds;
  state.time.minutes = form.value.minutes;
  state.time.hours = form.value.hours;
  state.direction = form.value.direction;
  submitCountdownHandler();
};

const submitCountdownHandler = async () => {
  const response = await fetch("/dashboard/admin/countdown", {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
    body: JSON.stringify(state),
  });
};
</script>
