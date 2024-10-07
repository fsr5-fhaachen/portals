<template>
  <div class="flex h-screen">
    <div
      class="m-auto bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-['EIGHTY_MILES'] text-transparent text-center"
    >
      <div
        v-if="countdownState.state === 'setup'"
        class="text-[10rem] leading-[10rem]"
      >
        Countdown
      </div>
      <div
        v-else-if="countdownState.state === 'idle'"
        class="text-[10rem] leading-[10rem]"
      ></div>
      <div v-else>
        <div v-if="countdownState.direction === 'up'">
          <div class="text-[4rem] leading-[4rem]">
            Maximum:
            {{ countdownState.time.hours.toString().padStart(2, "0") }}
            :
            {{ countdownState.time.minutes.toString().padStart(2, "0") }}
            :
            {{ countdownState.time.seconds.toString().padStart(2, "0") }}
          </div>
        </div>
        <div>
          <div class="text-[10rem] leading-[10rem]">
            {{ countdownTime.hours.toString().padStart(2, "0") }}
            :
            {{ countdownTime.minutes.toString().padStart(2, "0") }}
            :
            {{ countdownTime.seconds.toString().padStart(2, "0") }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import DisplayLayout from "@/layouts/DisplayLayout.vue";

export default {
  layout: DisplayLayout,
};
</script>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const countdownTime = ref<{
  seconds: number;
  minutes: number;
  hours: number;
}>({
  seconds: 0,
  minutes: 0,
  hours: 0,
});

// locale state variables
const countdownState = ref<{
  state: "setup" | "idle" | "running" | "stopped";
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

const isFetchingCountdown = ref(false);

// functions
const fetchCountdownState = async () => {
  if (isFetchingCountdown.value) {
    return;
  }

  isFetchingCountdown.value = true;

  const response = await fetch("/api/countdown/state", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    if (
      JSON.stringify(data.time) !== JSON.stringify(countdownState.value.time) ||
      data.state === "setup" ||
      data.state === "idle"
    ) {
      // time was updated or state is setup/idle, resetting local countdown time
      if (data.direction === "up") {
        countdownTime.value = {
          seconds: 0,
          minutes: 0,
          hours: 0,
        };
      } else {
        countdownTime.value = data.time;
      }
    }

    countdownState.value = data;
  }

  isFetchingCountdown.value = false;
};

const countdown = async () => {
  if (countdownState.value.state === "running") {
    if (countdownState.value.direction === "down") {
      if (countdownTime.value.seconds > 0) {
        countdownTime.value.seconds--;
      } else if (countdownTime.value.minutes > 0) {
        countdownTime.value.seconds = 59;
        countdownTime.value.minutes--;
      } else if (countdownTime.value.hours > 0) {
        countdownTime.value.seconds = 59;
        countdownTime.value.minutes = 59;
        countdownTime.value.hours--;
      } else {
        // nothing, countdown is at 0
      }
    } else if (countdownState.value.direction === "up") {
      if (countdownTime.value.hours >= countdownState.value.time.hours) {
        if (countdownTime.value.minutes >= countdownState.value.time.minutes) {
          if (
            countdownTime.value.seconds >= countdownState.value.time.seconds
          ) {
            // nothing, countdown has reached max value
            return;
          }
        }
      }
      if (countdownTime.value.seconds < 59) {
        countdownTime.value.seconds++;
      } else if (countdownTime.value.minutes < 59) {
        countdownTime.value.seconds = 0;
        countdownTime.value.minutes++;
      } else if (countdownTime.value.hours < 23) {
        countdownTime.value.seconds = 0;
        countdownTime.value.minutes = 0;
        countdownTime.value.hours++;
      } else {
        // nothing, countdown is at max value
      }
    } else {
      // nothing, unknown direction
    }
  } else {
    // nothing, countdown is not running
  }
};

const countdownFetchInterval = setInterval(fetchCountdownState, 500);
const countdownInterval = setInterval(countdown, 1000);

onBeforeUnmount(() => {
  clearInterval(countdownFetchInterval);
  clearInterval(countdownInterval);
});
</script>
