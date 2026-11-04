<template>
  <div
    class="flex select-none flex-col gap-2 overflow-hidden rounded-lg bg-white px-4 pb-6 pt-5 text-center shadow hover:cursor-pointer dark:bg-gray-800 sm:px-6 sm:pt-6"
    :class="{
      'ring-2 ring-fhac-mint': isSelected,
      'opacity-60': !isSelected && !isInPool,
    }"
    @click="emit('select')"
  >
    <p class="font-medium text-gray-500 dark:text-gray-400">
      {{ user.firstname }} {{ user.lastname }}
    </p>
    <div v-if="user.avatarUrl">
      <img :src="user.avatarUrl" class="mx-auto" />
    </div>
    <div class="flex flex-wrap justify-center gap-1">
      <span
        v-if="user.course?.id"
        :class="[user.course.classes, 'rounded-md p-1 text-xs text-white']"
      >
        {{ user.course.abbreviation }}
      </span>
      <span
        v-if="isSelected"
        class="rounded-md bg-fhac-mint-dark p-1 text-xs text-white"
      >
        Vorausgewählt
      </span>
      <span
        v-if="user.random_generator_opt_out"
        class="rounded-md bg-red-600 p-1 text-xs text-white"
      >
        Opt-out
      </span>
      <span
        v-if="!isInSelectedCourses"
        class="rounded-md bg-gray-500 p-1 text-xs text-white"
      >
        Anderer Fachbereich
      </span>
      <span
        v-if="user.random_generator_drawn_at"
        class="rounded-md bg-yellow-600 p-1 text-xs text-white"
      >
        Gezogen um
        <UiTimeString
          :value="user.random_generator_drawn_at"
          :withClockSuffix="true"
        />
      </span>
    </div>
    <AppButton
      v-if="user.random_generator_drawn_at"
      theme="gray"
      class="text-center"
      @click.stop="emit('resetDrawn')"
    >
      Zurücksetzen
    </AppButton>
  </div>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";

const props = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
  isInSelectedCourses: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits<{
  select: [];
  resetDrawn: [];
}>();

// only users of the pool can be drawn randomly, a preselected user is drawn in any case
const isInPool = computed(
  () =>
    props.isInSelectedCourses &&
    !props.user.random_generator_opt_out &&
    !props.user.random_generator_drawn_at,
);
</script>
