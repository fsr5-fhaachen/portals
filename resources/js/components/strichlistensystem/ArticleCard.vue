<template>
  <CardBase>
    <div class="flex flex-col gap-6">
      <div class="flex items-center gap-4">
        <!-- without a colored background, a transparent png fits the light and the dark mode -->
        <img
          v-if="article.image_url"
          :src="article.image_url"
          :alt="article.name"
          class="h-14 w-14 flex-shrink-0 object-contain"
        />
        <div v-else class="rounded-md bg-fhac-mint-dark p-3">
          <div
            class="flex h-8 w-8 items-center justify-center text-2xl text-white"
          >
            <FontAwesomeIcon :icon="['fas', article.icon]" />
          </div>
        </div>
        <div>
          <UiH2>{{ article.name }}</UiH2>
          <p class="font-semibold text-gray-900 dark:text-gray-100">
            {{
              article.price !== null
                ? formatPrice(article.price)
                : "Preis noch nicht festgelegt"
            }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Max. {{ article.max_order_amount }} {{ limitLabel }}
          </p>
        </div>
      </div>

      <div
        v-if="article.max_order_amount > 1"
        class="flex items-center justify-between gap-4"
      >
        <AppButton
          theme="gray"
          class="w-16 text-center text-2xl"
          :disabled="isLimitReached || amount <= 1"
          @click="removeAmount"
        >
          −
        </AppButton>
        <span class="text-3xl font-semibold text-gray-900 dark:text-gray-100">
          {{ amount }}
        </span>
        <AppButton
          theme="gray"
          class="w-16 text-center text-2xl"
          :disabled="isLimitReached || amount >= remainingAmount"
          @click="addAmount"
        >
          +
        </AppButton>
      </div>

      <p
        v-if="!isLimitReached && article.price !== null"
        class="text-center text-gray-700 dark:text-gray-300"
      >
        {{ amount }} × {{ formatPrice(article.price) }} =
        <span class="font-semibold text-gray-900 dark:text-gray-100">
          {{ formatPrice(amount * article.price) }}
        </span>
      </p>

      <UiMessage v-if="isLimitReached" type="info" :message="limitMessage" />

      <AppButton
        class="mt-auto text-center text-lg font-semibold"
        :disabled="isDisabled || disabled || isLimitReached"
        @click="buy"
      >
        Buchen
      </AppButton>
    </div>
  </CardBase>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, PropType, ref, watch } from "vue";

import { formatPrice } from "../../composables/usePrice";
const props = defineProps({
  article: {
    type: Object as PropType<App.Models.Article>,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  // e.g. "pro Anmeldung" or "pro 60 Sekunden"
  limitLabel: {
    type: String,
    required: true,
  },
});

const emit = defineEmits<{
  (e: "buy", article: App.Models.Article, amount: number): void;
  (e: "limitReset", article: App.Models.Article): void;
}>();

// cooldown after a booking to prevent accidental double bookings
const cooldownSeconds = 3;

// the remaining amount is calculated by the server (limit per kiosk login or per time window)
const remainingAmount = computed(() =>
  Math.max(0, props.article.remaining_amount ?? props.article.max_order_amount),
);
const isLimitReached = computed(() => remainingAmount.value < 1);

const amount = ref(1);
const isDisabled = ref(false);
let cooldownTimeout: ReturnType<typeof setTimeout> | null = null;

// keep the amount within the remaining amount after bookings and cancellations
watch(remainingAmount, (newRemainingAmount) => {
  amount.value = Math.max(1, Math.min(amount.value, newRemainingAmount));
});

// countdown until the limit resets (only in the self service with a time window)
const now = ref(Date.now());
const nowInterval = setInterval(() => {
  now.value = Date.now();
}, 1000);
let hasEmittedLimitReset = false;

const secondsUntilLimitReset = computed(() => {
  if (!isLimitReached.value || !props.article.limit_resets_at) {
    return null;
  }

  return Math.max(
    0,
    Math.ceil(
      (new Date(props.article.limit_resets_at).getTime() - now.value) / 1000,
    ),
  );
});

watch(
  () => props.article.limit_resets_at,
  () => {
    hasEmittedLimitReset = false;
  },
);

watch(secondsUntilLimitReset, (seconds) => {
  if (seconds === 0 && !hasEmittedLimitReset) {
    hasEmittedLimitReset = true;
    emit("limitReset", props.article);
  }
});

const limitMessage = computed(() => {
  if (secondsUntilLimitReset.value !== null) {
    return `Maximale Anzahl erreicht. Wieder buchbar in ${secondsUntilLimitReset.value} ${
      secondsUntilLimitReset.value === 1 ? "Sekunde" : "Sekunden"
    }.`;
  }

  return "Maximale Anzahl für diese Anmeldung erreicht.";
});

const addAmount = () => {
  if (amount.value < remainingAmount.value) {
    amount.value++;
  }
};

const removeAmount = () => {
  if (amount.value > 1) {
    amount.value--;
  }
};

const buy = () => {
  if (isLimitReached.value || amount.value > remainingAmount.value) {
    return;
  }

  isDisabled.value = true;
  emit("buy", props.article, amount.value);
  amount.value = 1;

  cooldownTimeout = setTimeout(() => {
    isDisabled.value = false;
  }, cooldownSeconds * 1000);
};

onBeforeUnmount(() => {
  clearInterval(nowInterval);
  if (cooldownTimeout) {
    clearTimeout(cooldownTimeout);
  }
});
</script>
