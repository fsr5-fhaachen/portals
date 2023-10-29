<template>
  <div>
    <div v-if="randomGeneratorState.state === 'setup'" class="flex h-screen">
      <div
        class="m-auto rounded-full bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-eighty-miles text-[15rem] text-transparent"
      >
        ZuFHallsgenerator
      </div>
    </div>
    <div
      v-else-if="randomGeneratorState.state === 'idle'"
      class="flex h-screen w-screen flex-col"
    />

    <Transition>
      <div
        v-if="randomGeneratorState.state === 'running'"
        class="flex h-screen w-screen flex-col overflow-hidden"
      >
        <div class="h-screen flex-1 overflow-hidden">
          <div
            class="grid h-fit w-screen animate-fly justify-items-center space-y-32 overflow-hidden py-20"
          >
            <RandomGeneratorUserAvatarCard
              class="animate-wiggle"
              v-for="user in users"
              :src="user.avatarUrl"
              :firstname="user.firstname"
              :lastname="user.lastname"
            />
          </div>

          <img
            class="absolute left-[10%] top-1/2 h-1/3 -translate-y-1/2 transform"
            src="/images/random-generator/gifs/cat.gif"
          />
          <img
            class="absolute right-[10%] top-1/2 h-1/3 -translate-y-1/2 scale-x-[-1] transform"
            src="/images/random-generator/gifs/cat.gif"
          />
        </div>
      </div>
    </Transition>

    <Transition name="winner">
      <div
        v-if="randomGeneratorState.state === 'stopped'"
        class="flex h-screen items-center justify-center"
      >
        <RandomGeneratorUserAvatarCard
          class="scale-[130%]"
          :src="randomGeneratorState.user?.avatarUrl"
          :firstname="randomGeneratorState.user?.firstname"
          :lastname="randomGeneratorState.user?.lastname"
        />

        <img
          class="absolute left-[10%] top-1/2 h-1/3 -translate-y-1/2 transform"
          src="/images/random-generator/gifs/cat.gif"
        />
        <img
          class="absolute right-[10%] top-1/2 h-1/3 -translate-y-1/2 scale-x-[-1] transform"
          src="/images/random-generator/gifs/cat.gif"
        />
      </div>
    </Transition>

    <audio autoplay v-if="randomGeneratorState.state === 'running'">
      <source src="/sounds/random-generator/running.mp3" type="audio/mpeg" />
    </audio>
    <audio autoplay v-if="randomGeneratorState.state === 'stopped'">
      <source src="/sounds/random-generator/airhorn.mp3" type="audio/mpeg" />
    </audio>
    <audio autoplay v-if="randomGeneratorState.state === 'stopped'">
      <source src="/sounds/random-generator/rise.mp3" type="audio/mpeg" />
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

const props = defineProps({
  users: {
    type: Array as PropType<App.Models.User[]>,
    required: true,
  },
});

// locale state variables
const randomGeneratorState = ref<{
  state: "setup" | "idle" | "running" | "stopped";
  user?: Models.User;
}>({
  state: "setup",
});

const isFetchingRandomGenerator = ref(false);

// functions
const fetchRandomGeneratorState = async () => {
  if (isFetchingRandomGenerator.value) {
    return;
  }

  isFetchingRandomGenerator.value = true;

  const response = await fetch("/api/random-generator/state", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    if (
      randomGeneratorState.value.state != "running" &&
      data.state == "running"
    ) {
      props.users.sort(() => Math.random() - 0.5);
    }

    randomGeneratorState.value = data;
  }

  isFetchingRandomGenerator.value = false;
};

const randomGeneratorInterval = setInterval(fetchRandomGeneratorState, 500);

onBeforeUnmount(() => {
  clearInterval(randomGeneratorInterval);
});
</script>

<style>
.v-enter-active,
.v-leave-active {
  transition: opacity 2s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

.winner-enter-active {
  transition: opacity 2s ease;
  transition-delay: 2s;
}

.winner-leave-active {
  transition: opacity 0.1s ease;
}

.winner-enter-from,
.winner-leave-to {
  opacity: 0;
}
</style>
