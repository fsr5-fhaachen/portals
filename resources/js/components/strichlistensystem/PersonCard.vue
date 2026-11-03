<template>
  <div
    :class="[
      clickable ? 'hover:cursor-pointer hover:ring-2 hover:ring-fhac-mint' : '',
      'flex h-full flex-col items-center gap-3 overflow-hidden rounded-lg bg-white px-4 pb-6 pt-5 text-center shadow dark:bg-gray-800 sm:px-6 sm:pt-6',
    ]"
  >
    <img
      v-if="person.avatar_url && !hasAvatarError"
      :src="person.avatar_url"
      :alt="`${person.firstname} ${person.lastname}`"
      class="h-24 w-24 rounded-full object-cover"
      loading="lazy"
      @error="hasAvatarError = true"
    />
    <div
      v-else
      class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-200 text-4xl text-gray-400 dark:bg-gray-700 dark:text-gray-500"
    >
      <FontAwesomeIcon :icon="['fas', 'user']" />
    </div>
    <p class="font-medium text-gray-900 dark:text-gray-100">
      {{ person.firstname }} {{ person.lastname }}
    </p>
    <div v-if="person.course?.id">
      <span
        :class="[person.course.classes, 'rounded-md p-1 text-xs text-white']"
      >
        {{ person.course.abbreviation }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType, ref } from "vue";

defineProps({
  person: {
    type: Object as PropType<Strichlistensystem.Person>,
    required: true,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
});

const hasAvatarError = ref(false);
</script>
