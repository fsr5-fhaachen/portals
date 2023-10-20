<template>
  <div
    class="flex flex-col gap-2 overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 text-center shadow dark:bg-gray-800 sm:px-6 sm:pt-6"
  >
    <p class="font-medium text-gray-500 dark:text-gray-400">
      {{ user.firstname }} {{ user.lastname }}
    </p>
    <div v-if="user.avatarUrl">
      <img :src="user.avatarUrl" class="mx-auto" />
    </div>
    <div v-if="user.course?.id">
      <span :class="[user.course.classes, 'rounded-md p-1 text-xs text-white']">
        {{ user.course.abbreviation }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  user: {
    type: Object as PropType<App.Models.Useer>,
    required: true,
  },
});
</script>
