<template>
  <LayoutDashboardContent>
    <template #title>{{ page.title }}</template>

    <CardBase>
      <div
        class="prose !max-w-full dark:prose-invert"
        v-html="page.content"
      ></div>
    </CardBase>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  page: {
    type: Object as PropType<App.Models.Page>,
    required: true,
  },
});
</script>
