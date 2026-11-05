<template>
  <li class="flex flex-col gap-2 py-2 sm:flex-row sm:items-center">
    <div class="flex grow flex-wrap items-center gap-2">
      <span class="text-gray-900 dark:text-gray-100">
        {{ registration.user.firstname }} {{ registration.user.lastname }}
      </span>
      <span
        v-if="registration.user.course"
        :class="[
          registration.user.course.classes,
          'rounded-md p-1 text-xs text-white',
        ]"
      >
        {{ registration.user.course.abbreviation }}
      </span>
      <span
        v-if="priority !== undefined"
        class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"
      >
        {{ priority ? `Prio ${priority}` : "Keine Prio" }}
      </span>
    </div>
    <select
      :value="currentSessionId ?? ''"
      :aria-label="`Workshop von ${registration.user.firstname} ${registration.user.lastname} in ${block.name}`"
      class="block rounded-md border-gray-300 shadow-sm focus:border-fhac-mint focus:ring-fhac-mint dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 sm:w-80 sm:text-sm"
      @change="change"
    >
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
  </li>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";

const props = defineProps({
  registration: {
    type: Object as PropType<Workshops.RegistrationRow>,
    required: true,
  },
  block: {
    type: Object as PropType<App.Models.WorkshopBlock>,
    required: true,
  },
  options: {
    type: Array as PropType<{ label: string; value: number | "" }[]>,
    required: true,
  },
  // priority of the workshop for the registration, null without priority, undefined if the registration has no workshop
  priority: {
    type: Number as PropType<number | null>,
    default: undefined,
  },
});

const emit = defineEmits<{
  assign: [
    registration: Workshops.RegistrationRow,
    block: App.Models.WorkshopBlock,
    sessionId: number | null,
  ];
}>();

const currentSessionId = computed(
  () =>
    (props.registration.assignments as Record<number, number>)[props.block.id],
);

// the select keeps showing the stored session until the server accepted the change, a rejected change is not shown
const change = (event: Event) => {
  const select = event.target as HTMLSelectElement;
  const value = select.value;
  select.value = String(currentSessionId.value ?? "");

  emit("assign", props.registration, props.block, value ? Number(value) : null);
};
</script>
