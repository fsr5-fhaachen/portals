<template>
  <CardBase>
    <div class="flex flex-col gap-4">
      <UiH2>Deine Kosten</UiH2>

      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-if="loginTotal !== null"
          class="rounded-md bg-gray-100 p-4 dark:bg-gray-900"
        >
          <dt class="text-sm text-gray-500 dark:text-gray-400">
            Diese Anmeldung
          </dt>
          <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            {{ formatPrice(loginTotal) }}
          </dd>
        </div>
        <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-900">
          <dt class="text-sm text-gray-500 dark:text-gray-400">
            {{ balance.open < 0 ? "Guthaben" : "Noch zu bezahlen" }}
          </dt>
          <dd
            :class="[
              balance.open > 0
                ? 'text-red-600 dark:text-red-400'
                : 'text-green-700 dark:text-green-400',
              'text-2xl font-semibold',
            ]"
          >
            {{ formatPrice(Math.abs(balance.open)) }}
          </dd>
        </div>
        <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-900">
          <dt class="text-sm text-gray-500 dark:text-gray-400">
            Insgesamt ausgegeben
          </dt>
          <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            {{ formatPrice(balance.total) }}
          </dd>
        </div>
        <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-900">
          <dt class="text-sm text-gray-500 dark:text-gray-400">
            Bereits bezahlt
          </dt>
          <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            {{ formatPrice(balance.paid) }}
          </dd>
        </div>
      </dl>

      <UiMessage
        v-if="balance.bookings_without_price > 0"
        type="info"
        :message="`${balance.bookings_without_price} ${
          balance.bookings_without_price === 1
            ? 'Buchung hat'
            : 'Buchungen haben'
        } noch keinen Preis und ${
          balance.bookings_without_price === 1 ? 'wird' : 'werden'
        } erst berechnet, wenn der Preis festgelegt ist.`"
      />
    </div>
  </CardBase>
</template>

<script setup lang="ts">
import { PropType } from "vue";

import { formatPrice } from "../../composables/usePrice";
defineProps({
  balance: {
    type: Object as PropType<Strichlistensystem.Balance>,
    required: true,
  },
  // sum of the bookings of the current kiosk login, null outside of the kiosk
  loginTotal: {
    type: Number as PropType<number | null>,
    default: null,
  },
});
</script>
