<template>
  <div>
    <div
      class="h-screen bg-[url('/images/random-generator/background/comic-yellow.jpg')] bg-cover"
    >
      <div v-if="generatorState.state === 'setup'" class="flex h-screen">
        <div
          class="m-auto rounded-full bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text font-eighty-miles text-[15rem] text-transparent"
        >
          ZuFHallsgenerator
        </div>
      </div>
      <div
        v-if="generatorState.state === 'idle'"
        class="flex h-screen w-screen flex-col"
      ></div>
      <Transition>
        <div
          v-if="generatorState.state === 'running'"
          class="flex h-screen w-screen flex-col overflow-hidden"
        >
          <div class="h-screen flex-1 overflow-hidden">
            <div
              class="grid h-fit w-screen animate-fly justify-items-center space-y-32 overflow-hidden pt-20"
            >
              <Avatar
                class="animate-wiggle"
                v-for="user in users"
                :src="user.avatarUrl"
                :firstname="user.firstname"
                :lastname="user.lastname"
              />
            </div>
            <audio autoplay>
              <source
                src="/sounds/random-generator/running.mp3"
                type="audio/mpeg"
              />
            </audio>
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
          v-if="generatorState.state === 'stopped'"
          class="flex h-screen items-center justify-center"
        >
          <Avatar
            class="scale-[130%]"
            :src="generatorState.user?.avatarUrl"
            :firstname="generatorState.user?.firstname"
            :lastname="generatorState.user?.lastname"
          />
          <audio autoplay>
            <source
              src="/sounds/random-generator/airhorn.mp3"
              type="audio/mpeg"
            />
          </audio>
          <img
            class="absolute left-0 top-1/2 h-2/3 -translate-y-1/2 transform"
            src="/images/random-generator/gifs/trumpet.gif"
          />
          <img
            class="absolute right-0 top-1/2 h-2/3 -translate-y-1/2 scale-x-[-1] transform"
            src="/images/random-generator/gifs/trumpet.gif"
          />
        </div>
      </Transition>
    </div>
  </div>
</template>

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

<script lang="ts">
import RandomGeneratorLayout from "@/layouts/RandomGeneratorLayout.vue";

export default {
  layout: RandomGeneratorLayout,
};
</script>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount, render, createElement } from "vue";
import Avatar from "@/components/card/Avatar.vue";

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

    if (generatorState.value.state != "running" && data.state == "running") {
      props.users.sort(() => Math.random() - 0.5);
    }

    generatorState.value = data;
  }

  isFetchingGenerator.value = false;
};

const generatorInterval = setInterval(fetchRandomGeneratorState, 500);

onBeforeUnmount(() => {
  clearInterval(generatorInterval);
});
</script>
