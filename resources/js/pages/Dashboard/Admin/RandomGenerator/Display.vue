<template>
  <div>
    TEST
    {{ generatorState }}

    <div v-if="generatorState.state === 'idle'">NICHTS</div>
    <div v-else-if="generatorState.state === 'running'">
      {{ users[currentUserIndex].firstname }}
      {{ users[currentUserIndex].lastname }}
    </div>
    <div v-else-if="generatorState.state === 'stopped'">
      STOPPT <img :src="generatorState.user?.avatarUrl" />
    </div>
  </div>
</template>

<script lang="ts">
import RandomGeneratorLayout from "@/layouts/RandomGeneratorLayout.vue";

export default {
  layout: RandomGeneratorLayout,
};
</script>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const props = defineProps({
  users: {
    type: Array as PropType<App.Models.User[]>,
    required: true,
  },
});

// locale state variables
const generatorState = ref<{
  state: "setup" | "idle" | "running" | "stopped";
  user?: Models.User;
}>({
  state: "setup",
});
const isFetchingGenerator = ref(false);
const currentUserIndex = ref(0);

// functions
const fetchRandomGeneratorState = async () => {
  if (isFetchingGenerator.value) {
    return;
  }

  isFetchingGenerator.value = true;

  const response = await fetch("/api/random-generator/state", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    generatorState.value = data;
  }

  isFetchingGenerator.value = false;
};

const generatorInterval = setInterval(fetchRandomGeneratorState, 500);
const uglyAnimationIndex = setInterval(() => {
  if (generatorState.value.state === "running") {
    currentUserIndex.value = Math.floor(Math.random() * props.users.length);
  }
}, 100);

onBeforeUnmount(() => {
  clearInterval(generatorInterval);
  clearInterval(uglyAnimationIndex);
});
</script>
