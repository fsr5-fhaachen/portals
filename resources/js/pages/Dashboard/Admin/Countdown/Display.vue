<template>
  <div class="flex h-screen">
    <div
      class="m-auto bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-['EIGHTY_MILES'] text-transparent"
    >
      <div v-if="countdownState.time" class="flex flex-row gap-64">
        <div class="text-[10rem] leading-[10rem]">
          {{ countdownState.time.hours }}
        </div>
        <div class="text-[10rem] leading-[10rem]">
          {{ countdownState.time.minutes }}
        </div>
        <div class="text-[10rem] leading-[10rem]">
          {{ countdownState.time.seconds }}
        </div>
        <div class="text-[10rem] leading-[10rem]">
          {{ countdownState.direction }}
        </div>
        <div class="text-[10rem] leading-[10rem]">
          {{ countdownState.state }}
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

// locale state variables
const countdownState = ref<{
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

    countdownState.value = data;
  }

  isFetchingCountdown.value = false;
};

const countdownInterval = setInterval(fetchCountdownState, 1000);

onBeforeUnmount(() => {
  clearInterval(countdownInterval);
});
</script>
