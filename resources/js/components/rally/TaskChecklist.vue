<template>
  <div>
    <div
      v-if="taskMode === 'race' && ranking.length > 0"
      class="mb-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800"
    >
      <div class="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
        Rangliste
      </div>
      <ol class="space-y-1">
        <li
          v-for="(entry, rankIndex) in ranking"
          :key="entry.group.id"
          class="flex items-center gap-2 text-sm text-gray-900 dark:text-gray-100"
        >
          <span class="font-bold">{{ rankIndex + 1 }}.</span>
          <span>{{ entry.group.name }}</span>
        </li>
      </ol>
    </div>

    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
      <li
        v-for="task in tasks"
        :key="task.id"
        class="flex items-center gap-3 py-3"
        :class="{ 'cursor-pointer': editable }"
        @click="editable ? toggle(task) : null"
      >
        <FontAwesomeIcon
          :icon="['fas', isDone(task) ? 'circle-check' : 'circle-xmark']"
          :class="isDone(task) ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'"
          class="h-5 w-5 flex-shrink-0"
        />
        <span
          class="flex-1 text-sm"
          :class="
            isDone(task)
              ? 'rounded bg-green-50 px-2 py-1 text-green-900 dark:bg-green-900 dark:text-green-100'
              : 'text-gray-900 dark:text-gray-100'
          "
        >
          {{ task.name }}
        </span>
        <span
          v-if="taskMode === 'points'"
          class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400"
        >
          {{ task.points }} Punkt{{ task.points === 1 ? "" : "e" }}
        </span>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount, PropType } from "vue";
import { router } from "@inertiajs/vue3";

const { group, event, initialTasks } = defineProps({
  group: {
    type: Number,
    required: true,
  },
  event: {
    type: Number,
    required: false,
    default: null,
  },
  initialTasks: {
    type: Array as PropType<Array<any>>,
    default: () => [],
  },
  editable: {
    type: Boolean,
    default: false,
  },
  taskMode: {
    type: String as PropType<"points" | "race">,
    default: "points",
  },
});

const tasks = ref(initialTasks);
const ranking = ref<Array<any>>([]);

function isDone(task: any): boolean {
  return !!task.completions?.[0]?.completed_at;
}

function toggle(task: any) {
  router.post(
    "/dashboard/tutor/group/" + group + "/task/" + task.id + "/toggle",
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: fetchTasks,
    },
  );
}

let isFetching = false;
const fetchTasks = async () => {
  if (isFetching) {
    return;
  }
  isFetching = true;

  const response = await fetch("/api/groups/" + group + "/tasks", {
    method: "GET",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
  });

  if (response.ok) {
    const data = await response.json();
    tasks.value = data.tasks;
  }

  isFetching = false;
};

const fetchRanking = async () => {
  if (!event || taskMode !== "race") {
    return;
  }

  const response = await fetch("/api/events/" + event + "/task-ranking", {
    method: "GET",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
  });

  if (response.ok) {
    const data = await response.json();
    ranking.value = data.ranking;
  }
};

fetchTasks();
fetchRanking();

const tasksInterval = setInterval(fetchTasks, 5000);
const rankingInterval = setInterval(fetchRanking, 5000);
onBeforeUnmount(() => {
  clearInterval(tasksInterval);
  clearInterval(rankingInterval);
});
</script>
