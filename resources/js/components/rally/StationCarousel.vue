<template>
  <div v-if="stops.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
    Für diese Gruppe wurde noch kein Rundenplan erstellt.
  </div>
  <div v-else class="flex items-center gap-4">
    <button
      type="button"
      class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-30 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
      :disabled="index === 0"
      @click="index--"
    >
      ‹
    </button>

    <div class="flex-1 rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-800">
      <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
        Runde {{ current.round }}
        <span v-if="current.is_current"> · aktuell</span>
      </div>

      <div class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
        {{ current.station ? current.station.name : "???" }}
      </div>

      <div
        v-if="current.starts_at"
        class="mt-1 text-sm text-gray-600 dark:text-gray-300"
      >
        <UiTimeString :value="current.starts_at" withClockSuffix />
        <template v-if="current.ends_at">
          – <UiTimeString :value="current.ends_at" withClockSuffix />
        </template>
      </div>

      <div
        v-if="current.opponent_group_name"
        class="mt-2 text-sm text-gray-700 dark:text-gray-200"
      >
        Gegen <strong>{{ current.opponent_group_name }}</strong>
      </div>

      <div
        v-if="scoringEnabled && current.points !== null && current.points !== undefined"
        class="mt-2 text-sm font-semibold"
        :class="{
          'text-green-600 dark:text-green-400': current.points === 4,
          'text-yellow-600 dark:text-yellow-400': current.points === 2,
          'text-red-600 dark:text-red-400': current.points === 0,
        }"
      >
        {{ resultLabel(current) }}
        <span v-if="current.bonus" class="font-normal text-gray-500 dark:text-gray-400">
          (+{{ current.bonus }} Bonus)
        </span>
      </div>
    </div>

    <button
      type="button"
      class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-30 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
      :disabled="index === stops.length - 1"
      @click="index++"
    >
      ›
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, PropType } from "vue";

const { stops } = defineProps({
  stops: {
    type: Array as PropType<Array<any>>,
    required: true,
  },
  scoringEnabled: {
    type: Boolean,
    default: false,
  },
});

const index = ref(0);

function setInitialIndex() {
  const currentIndex = stops.findIndex((stop) => stop.is_current);
  index.value = currentIndex >= 0 ? currentIndex : 0;
}
setInitialIndex();

// jump to the new current round whenever it changes (e.g. after polling), but leave the
// index alone if the person has manually navigated away from the current round
watch(
  () => stops.map((stop) => stop.is_current).join(","),
  () => {
    const currentIndex = stops.findIndex((stop) => stop.is_current);
    if (currentIndex >= 0) {
      index.value = currentIndex;
    }
  },
);

const current = computed(() => stops[index.value] ?? stops[0]);

function resultLabel(stop: any): string {
  if (stop.points === 4) return "Gewonnen";
  if (stop.points === 0) return "Verloren";
  return "Unentschieden";
}
</script>
