<template>
  <nav
    v-if="paginator.prev_page_url || paginator.next_page_url"
    class="flex items-center justify-between gap-4 px-4 py-3 sm:px-0"
    aria-label="Seitennavigation"
  >
    <InertiaLink
      v-if="paginator.prev_page_url"
      :href="paginator.prev_page_url"
      preserve-scroll
      class="rounded-md bg-gray-500 px-4 py-2 text-sm text-white hover:bg-gray-700"
    >
      &larr; Neuere
    </InertiaLink>
    <span v-else />

    <span class="text-sm text-gray-500 dark:text-gray-400">
      {{ paginator.from }}–{{ paginator.to }} von {{ paginator.total }}
    </span>

    <InertiaLink
      v-if="paginator.next_page_url"
      :href="paginator.next_page_url"
      preserve-scroll
      class="rounded-md bg-gray-500 px-4 py-2 text-sm text-white hover:bg-gray-700"
    >
      Ältere &rarr;
    </InertiaLink>
    <span v-else />
  </nav>
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { Link as InertiaLink } from "@inertiajs/vue3";

defineProps({
  paginator: {
    type: Object as PropType<Pagination.Paginator<unknown>>,
    required: true,
  },
});
</script>
