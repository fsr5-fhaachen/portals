<template>
  <CardBase>
    <div class="flex flex-col gap-4">
      <UiH2>Deine letzten Buchungen</UiH2>

      <p
        v-if="!articleActionLogs.length"
        class="text-gray-500 dark:text-gray-400"
      >
        Du hast noch nichts gebucht.
      </p>

      <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
        <li
          v-for="articleActionLog in articleActionLogs"
          :key="articleActionLog.id"
          class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
            <UiDateTimeString
              v-if="articleActionLog.created_at"
              class="text-sm text-gray-500 dark:text-gray-400 sm:w-40"
              :value="articleActionLog.created_at"
              withClockSuffix
            />
            <span
              v-if="articleActionLog.deleted_at"
              class="text-red-600 dark:text-red-400"
            >
              <span class="font-semibold">{{
                articleActionLog.article?.name
              }}</span>
              storniert
            </span>
            <span v-else class="text-green-700 dark:text-green-400">
              <span class="font-semibold">{{
                articleActionLog.article?.name
              }}</span>
              gebucht
            </span>
            <span
              :class="[
                articleActionLog.deleted_at
                  ? 'text-gray-400 line-through dark:text-gray-500'
                  : 'text-gray-900 dark:text-gray-100',
              ]"
            >
              {{
                articleActionLog.price !== null
                  ? formatPrice(articleActionLog.price)
                  : "noch ohne Preis"
              }}
            </span>
          </div>

          <AppButton
            v-if="isCancellable(articleActionLog)"
            theme="danger"
            class="text-center"
            @click="emit('cancel', articleActionLog)"
          >
            Stornieren
          </AppButton>
        </li>
      </ul>
    </div>
  </CardBase>
</template>

<script setup lang="ts">
import { onBeforeUnmount, PropType, ref } from "vue";

import { formatPrice } from "../../composables/usePrice";
defineProps({
  articleActionLogs: {
    type: Array as PropType<App.Models.ArticleActionLog[]>,
    required: true,
  },
});

const emit = defineEmits<{
  (e: "cancel", articleActionLog: App.Models.ArticleActionLog): void;
}>();

// update the current time so that the cancel buttons disappear after the cancel window
const now = ref(Date.now());
const nowInterval = setInterval(() => {
  now.value = Date.now();
}, 1000);
onBeforeUnmount(() => {
  clearInterval(nowInterval);
});

const isCancellable = (articleActionLog: App.Models.ArticleActionLog) => {
  if (articleActionLog.deleted_at || !articleActionLog.cancel_until) {
    return false;
  }

  return new Date(articleActionLog.cancel_until).getTime() >= now.value;
};
</script>
