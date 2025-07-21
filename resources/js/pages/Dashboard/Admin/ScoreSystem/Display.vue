<template>
  <div class="flex h-screen">
    <div
      class="m-auto bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-['EIGHTY_MILES'] text-transparent"
    >
      <div v-if="scoreSystemState.teams" class="flex flex-row gap-64">
        <div
          v-for="(team, index) in scoreSystemState.teams"
          :key="index"
          class="flex flex-col items-center"
        >
          <div class="text-[10rem] leading-[10rem]">{{ team.name }}</div>
          <div class="text-[15rem] leading-[15rem]">{{ team.score }}</div>
        </div>
      </div>
    </div>
    <audio autoplay v-if="isRunningSound">
      <source
        :src="soundFileNames[Math.floor(Math.random() * soundFileNames.length)]"
        type="audio/mpeg"
      />
    </audio>
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
const scoreSystemState = ref<{
  teams: {
    name: string;
    score: string;
  }[];
}>({
  teams: [],
});

const isFetchingScoreSystem = ref(false);

const isRunningSound = ref(false);

const soundFileNames = ref<string[]>([
  "/sounds/score-system/hp-level-up-mario-4253.mp3",
  "/sounds/score-system/mc-level-up-13367.mp3",
  "/sounds/score-system/final-fantasy-level-up-27603.mp3",
  "/sounds/score-system/fire-emblem-level-up.mp3",
]);

// functions
const fetchScoreSystemState = async () => {
  if (isFetchingScoreSystem.value) {
    return;
  }

  isFetchingScoreSystem.value = true;

  const apiPath = window.location.pathname.startsWith("/public")
    ? "/public/api/score-system/state"
    : "/api/score-system/state";

  const response = await fetch(apiPath, {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    if (
      JSON.stringify(scoreSystemState.value.teams) !==
      JSON.stringify(data.teams)
    ) {
      isRunningSound.value = true;
      setTimeout(() => {
        isRunningSound.value = false;
      }, 5000);
    }

    scoreSystemState.value = data;
  }

  isFetchingScoreSystem.value = false;
};

const scoreSystemInterval = setInterval(fetchScoreSystemState, 500);

onBeforeUnmount(() => {
  clearInterval(scoreSystemInterval);
});
</script>
