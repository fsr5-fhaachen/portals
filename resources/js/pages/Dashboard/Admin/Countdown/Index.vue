<template>
  <LayoutDashboardContent>
    <template #title>Countdown</template>

    <CardContainer>
      <CardBase>
        <UiH2>Aktionen</UiH2>

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

const form = ref<{
  seconds: number;
  minutes: number;
  hours: number;
  direction: "up" | "down";
}>({
  seconds: 0,
  minutes: 0,
  hours: 0,
  direction: "down",
});

const state = ref<{
  state: "setup" | "running" | "stopped";
  direction: "up" | "down";
  time: {
    seconds: number;
    minutes: number;
    hours: number;
  };
}>({
  state: "setup",
  direction: "down",
  time: {
    seconds: 0,
    minutes: 0,
    hours: 0,
  },
});

const updateStateAndSubmit = (newState: "setup" | "running" | "stopped") => {
  state.value.state = newState;
  console.log(state.value);
  submitCountdownHandler();
};

const updateTimeAndSubmit = () => {
  state.value.time.seconds = form.value.seconds;
  state.value.time.minutes = form.value.minutes;
  state.value.time.hours = form.value.hours;
  state.value.direction = form.value.direction;
  console.log(state.value);
  console.log(state.value.time);
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
    body: JSON.stringify(state.value),
  });
};
</script>
