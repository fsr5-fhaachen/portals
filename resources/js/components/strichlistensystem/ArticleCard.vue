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
        :theme="bookingState === 'booked' ? 'success' : 'default'"
        class="mt-auto text-center text-lg font-semibold"
        :disabled="bookingState !== 'idle' || disabled || isLimitReached"
        @click="buy"
      >
        <span
          v-if="bookingState === 'booked'"
          class="flex items-center justify-center gap-2"
        >
          <FontAwesomeIcon :icon="['fas', 'check']" />
          {{ bookedAmount }}× Gebucht
        </span>
        <template v-else>Buchen</template>
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
  // the parent books the article and calls onBooked with the result
  (
    e: "buy",
    article: App.Models.Article,
    amount: number,
    onBooked: (isBooked: boolean) => void,
  ): void;
  (e: "limitReset", article: App.Models.Article): void;
}>();

// the button stays green after a booking, this also prevents accidental double bookings
const bookedSeconds = 3;

// the remaining amount is calculated by the server (limit per kiosk login or per time window)
const remainingAmount = computed(() =>
  Math.max(0, props.article.remaining_amount ?? props.article.max_order_amount),
);
const isLimitReached = computed(() => remainingAmount.value < 1);

const amount = ref(1);
const bookingState = ref<"idle" | "booking" | "booked">("idle");
// the amount of the last booking, shown on the button after the booking
const bookedAmount = ref(1);
let bookedTimeout: ReturnType<typeof setTimeout> | null = null;

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

  bookingState.value = "booking";
  bookedAmount.value = amount.value;
  emit("buy", props.article, amount.value, onBooked);
  amount.value = 1;
};

// after a failed booking the button is available again at once, the error is shown in the banner
const onBooked = (isBooked: boolean) => {
  if (!isBooked) {
    bookingState.value = "idle";
    return;
  }

  bookingState.value = "booked";
  bookedTimeout = setTimeout(() => {
    bookingState.value = "idle";
  }, bookedSeconds * 1000);
};

onBeforeUnmount(() => {
  clearInterval(nowInterval);
  if (bookedTimeout) {
    clearTimeout(bookedTimeout);
  }
});
</script>
