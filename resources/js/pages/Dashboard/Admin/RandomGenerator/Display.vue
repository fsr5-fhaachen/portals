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
            ref="flyContainer"
            class="grid h-fit w-screen translate-y-[100vh] justify-items-center space-y-32 overflow-hidden py-20 will-change-transform"
          >
            <!-- users outside of the screen are not rendered (content-visibility), so only the visible users cost performance -->
            <RandomGeneratorUserAvatarCard
              class="animate-wiggle [contain-intrinsic-size:auto_30rem_38rem] [content-visibility:auto]"
              v-for="user in flyingUsers"
              :key="user.id"
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
          class="absolute left-0 top-1/2 h-2/3 -translate-y-1/2 transform"
          src="/images/random-generator/gifs/trumpet.gif"
        />
        <img
          class="absolute right-0 top-1/2 h-2/3 -translate-y-1/2 scale-x-[-1] transform"
          src="/images/random-generator/gifs/trumpet.gif"
        />
      </div>
    </Transition>

    <audio
      autoplay
      v-if="randomGeneratorRunningSoundState != 'stopped'"
      id="running-sound"
    >
      <source src="/sounds/random-generator/running.mp3" type="audio/mpeg" />
    </audio>
    <template v-if="randomGeneratorState.state === 'stopped'">
      <audio autoplay>
        <source src="/sounds/random-generator/airhorn.mp3" type="audio/mpeg" />
      </audio>
      <audio autoplay>
        <source src="/sounds/random-generator/rise.mp3" type="audio/mpeg" />
      </audio>
    </template>
  </div>
</template>

<script lang="ts">
import DisplayLayout from "@/layouts/DisplayLayout.vue";

export default {
  layout: DisplayLayout,
};
</script>

<script setup lang="ts">
import { nextTick, ref, PropType, onBeforeUnmount } from "vue";

const props = defineProps({
  users: {
    type: Array as PropType<App.Models.User[]>,
    required: true,
  },
});

// locale state variables
const randomGeneratorRunningSoundState = ref<
  "running" | "fade-out" | "stopped"
>("stopped");
const randomGeneratorState = ref<{
  state: "setup" | "idle" | "running" | "stopped";
  user?: Models.User;
  // ids of the users in the pool, only these users are flying while running
  userIds?: number[] | null;
}>({
  state: "setup",
});

const isFetchingRandomGenerator = ref(false);

// speed of the flying users in pixels per second, so the speed does not depend on the amount of users
const FLY_SPEED_PIXELS_PER_SECOND = 450;

const flyingUsers = ref<App.Models.User[]>([]);
const flyContainer = ref<HTMLElement | null>(null);
let flyAnimationFrame: number | null = null;
let flyResizeObserver: ResizeObserver | null = null;

// move the users from the bottom of the screen until the end of the list and back again
const startFlying = () => {
  stopFlying();

  // vue clears the template ref directly on stop, but the container stays in the dom during the leave transition
  const container = flyContainer.value;
  if (!container) {
    return;
  }

  let offset = window.innerHeight;
  let direction = -1;
  let lastTimestamp: number | null = null;

  // reading the height in every frame forces a layout, so the height is only updated if it changes
  let containerHeight = container.offsetHeight;
  flyResizeObserver = new ResizeObserver(() => {
    containerHeight = container.offsetHeight;
  });
  flyResizeObserver.observe(container);

  const step = (timestamp: number) => {
    // the container is removed after the leave transition
    if (!container.isConnected) {
      stopFlying();
      return;
    }

    if (lastTimestamp !== null) {
      offset +=
        (direction *
          FLY_SPEED_PIXELS_PER_SECOND *
          (timestamp - lastTimestamp)) /
        1000;

      if (offset <= -containerHeight) {
        offset = -containerHeight;
        direction = 1;
      } else if (offset >= window.innerHeight) {
        offset = window.innerHeight;
        direction = -1;
      }
    }

    container.style.transform = `translateY(${offset}px)`;
    lastTimestamp = timestamp;

    flyAnimationFrame = requestAnimationFrame(step);
  };

  flyAnimationFrame = requestAnimationFrame(step);
};

const stopFlying = () => {
  if (flyAnimationFrame !== null) {
    cancelAnimationFrame(flyAnimationFrame);
    flyAnimationFrame = null;
  }

  flyResizeObserver?.disconnect();
  flyResizeObserver = null;
};

// functions
const fetchRandomGeneratorState = async () => {
  if (isFetchingRandomGenerator.value) {
    return;
  }

  isFetchingRandomGenerator.value = true;

  const apiPath = window.location.pathname.startsWith("/public")
    ? "/public/api/random-generator/state"
    : "/api/random-generator/state";

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
      randomGeneratorState.value.state != "running" &&
      data.state == "running"
    ) {
      randomGeneratorRunningSoundState.value = "running";

      const userIds = Array.isArray(data.userIds) ? data.userIds : null;
      flyingUsers.value = props.users
        .filter((user) => !userIds || userIds.includes(user.id))
        .sort(() => Math.random() - 0.5);

      nextTick(startFlying);
    } else if (
      randomGeneratorState.value.state == "running" &&
      data.state == "stopped"
    ) {
      const runningSound = document.getElementById(
        "running-sound",
      ) as HTMLAudioElement;

      if (runningSound) {
        const fadeOutRunningSoundInterval = setInterval(() => {
          if (runningSound.volume > 0) {
            runningSound.volume -= 0.1;
          } else {
            randomGeneratorRunningSoundState.value = "stopped";
          }
        }, 200);

        setTimeout(() => {
          clearInterval(fadeOutRunningSoundInterval);
          randomGeneratorRunningSoundState.value = "stopped";
          runningSound.volume = 1;
        }, 2000);
      }
    }

    // a new state object re-renders all flying users, so only replace the state if it changed
    if (JSON.stringify(data) !== JSON.stringify(randomGeneratorState.value)) {
      randomGeneratorState.value = data;
    }
  }

  isFetchingRandomGenerator.value = false;
};

const randomGeneratorInterval = setInterval(fetchRandomGeneratorState, 500);

onBeforeUnmount(() => {
  clearInterval(randomGeneratorInterval);
  stopFlying();
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
