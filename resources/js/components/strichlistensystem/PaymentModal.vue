<template>
  <div>
    <TransitionRoot as="template" :show="true">
      <Dialog as="div" class="relative z-10" @close="close">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          />
        </TransitionChild>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
          <div
            class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
          >
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
              >
                <div class="flex flex-col gap-6">
                  <UiH2>
                    Zahlungen von {{ person.firstname }} {{ person.lastname }}
                  </UiH2>

                  <UiMessage
                    v-if="loadError"
                    type="error"
                    message="Die Zahlungen konnten nicht geladen werden."
                  />

                  <p
                    v-else-if="!details"
                    class="text-gray-500 dark:text-gray-400"
                  >
                    Wird geladen …
                  </p>

                  <template v-else>
                    <UiDl class="gap-2">
                      <UiDt>Gesamt</UiDt>
                      <UiDd>{{ formatPrice(details.balance.total) }}</UiDd>
                      <UiDt>Bezahlt</UiDt>
                      <UiDd>{{ formatPrice(details.balance.paid) }}</UiDd>
                      <UiDt>
                        {{ details.balance.open < 0 ? "Guthaben" : "Offen" }}
                      </UiDt>
                      <UiDd class="font-semibold">
                        {{ formatPrice(Math.abs(details.balance.open)) }}
                      </UiDd>
                    </UiDl>

                    <UiMessage
                      v-if="details.balance.bookings_without_price > 0"
                      type="info"
                      :message="`${details.balance.bookings_without_price} ${
                        details.balance.bookings_without_price === 1
                          ? 'Buchung hat'
                          : 'Buchungen haben'
                      } noch keinen Preis und ${
                        details.balance.bookings_without_price === 1
                          ? 'ist'
                          : 'sind'
                      } nicht in Gesamt enthalten.`"
                    />

                    <FormKit
                      v-if="details.balance.open > 0"
                      type="form"
                      id="payment"
                      :actions="false"
                      v-model="paymentForm"
                      @submit="submitPayment"
                    >
                      <FormContainer>
                        <FormRow>
                          <div class="w-full">
                            <p
                              class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                              Zahlungsart
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                              <button
                                v-for="option in paymentMethodOptions"
                                :key="option.value"
                                type="button"
                                :aria-pressed="paymentMethod === option.value"
                                :class="
                                  paymentMethod === option.value
                                    ? 'border-fhac-mint-dark bg-fhac-mint-dark/10 text-gray-900 ring-1 ring-fhac-mint-dark dark:border-fhac-mint dark:bg-fhac-mint/10 dark:text-gray-100 dark:ring-fhac-mint'
                                    : 'border-gray-300 text-gray-500 hover:border-gray-400 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200'
                                "
                                class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 font-medium transition"
                                @click="paymentMethod = option.value"
                              >
                                <FontAwesomeIcon
                                  :icon="option.icon"
                                  :class="
                                    paymentMethod === option.value
                                      ? 'text-fhac-mint-dark dark:text-fhac-mint'
                                      : ''
                                  "
                                />
                                {{ option.label }}
                              </button>
                            </div>
                          </div>
                        </FormRow>
                        <FormRow>
                          <FormKit
                            type="text"
                            name="amount"
                            label="Bezahlter Betrag in €"
                            inputmode="decimal"
                            autocomplete="off"
                            :help="`Höchstens der offene Betrag von ${formatPrice(details.balance.open)}.`"
                            :validation="[
                              ['required'],
                              ['matches', amountInputPattern],
                            ]"
                            :validation-messages="{
                              matches:
                                'Bitte gib einen Betrag mit höchstens 2 Nachkommastellen an, z. B. 12,50.',
                            }"
                          />
                        </FormRow>
                        <FormRow v-if="paymentMethod === 'cash'">
                          <FormKit
                            type="text"
                            name="given"
                            label="Gegebenes Geld in € (optional)"
                            inputmode="decimal"
                            autocomplete="off"
                            help="Nur zum Ausrechnen des Rückgelds, wird nicht gespeichert."
                            :validation="[['matches', amountInputPattern]]"
                            :validation-messages="{
                              matches:
                                'Bitte gib einen Betrag mit höchstens 2 Nachkommastellen an, z. B. 20 oder 20,50.',
                            }"
                          />
                        </FormRow>
                        <FormRow
                          v-if="paymentMethod === 'cash' && change !== null"
                        >
                          <UiMessage
                            v-if="change >= 0"
                            type="success"
                            :message="`Rückgeld: <strong>${formatPrice(change)}</strong>`"
                          />
                          <UiMessage
                            v-else
                            type="warning"
                            :message="`Das gegebene Geld reicht nicht, es fehlen <strong>${formatPrice(-change)}</strong>.`"
                          />
                        </FormRow>
                        <FormRow>
                          <UiMessage
                            v-if="paymentError"
                            type="error"
                            :message="paymentError"
                          />
                        </FormRow>
                        <FormRow>
                          <FormKit
                            type="submit"
                            label="Zahlung speichern"
                            :disabled="isSubmitting"
                          />
                        </FormRow>
                      </FormContainer>
                    </FormKit>

                    <UiMessage
                      v-else
                      type="success"
                      message="Es gibt keinen offenen Betrag."
                    />

                    <div
                      class="flex flex-col gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                    >
                      <h3
                        class="font-semibold text-gray-900 dark:text-gray-100"
                      >
                        Rechnung
                      </h3>

                      <template v-if="details.billing.wants_bank_transfer">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          Rechnungsadresse (bitte prüfen):
                        </p>
                        <address
                          class="not-italic text-gray-900 dark:text-gray-100"
                        >
                          {{ details.billing.details.name || "–" }}<br />
                          {{ details.billing.details.street || "–" }}<br />
                          {{ details.billing.details.postal_code }}
                          {{ details.billing.details.city }}
                        </address>
                      </template>

                      <UiMessage
                        v-for="problem in details.billing.problems"
                        :key="problem"
                        type="info"
                        :message="problem"
                      />

                      <template v-if="!details.billing.problems.length">
                        <p class="text-gray-700 dark:text-gray-300">
                          Neue Rechnung über
                          <strong>{{
                            formatPrice(details.billing.preview.amount_due)
                          }}</strong>
                          <template v-if="details.billing.preview.paid > 0">
                            ({{ formatPrice(details.billing.preview.subtotal) }}
                            abzüglich
                            {{ formatPrice(details.billing.preview.paid) }}
                            bereits bezahlt)
                          </template>
                          mit
                          {{ details.billing.preview.items.length }}
                          Posten.
                        </p>
                        <AppButton
                          class="text-center"
                          :disabled="isSubmitting"
                          @click="createInvoice"
                        >
                          Rechnung erstellen
                        </AppButton>
                      </template>

                      <ul
                        v-if="details.billing.invoices.length"
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                      >
                        <li
                          v-for="invoice in details.billing.invoices"
                          :key="invoice.id"
                          class="flex items-center justify-between gap-4 py-2"
                        >
                          <div class="flex flex-col">
                            <span
                              :class="[
                                invoice.is_cancelled
                                  ? 'text-gray-400 line-through dark:text-gray-500'
                                  : 'font-semibold text-gray-900 dark:text-gray-100',
                              ]"
                            >
                              {{ invoice.number }} ·
                              {{ formatPrice(invoice.amount_due) }}
                            </span>
                            <span
                              class="text-sm text-gray-500 dark:text-gray-400"
                            >
                              <UiDateString :value="invoice.issued_on" />
                              <template v-if="invoice.is_cancelled">
                                · storniert
                              </template>
                            </span>
                          </div>
                          <a
                            :href="`/dashboard/admin/strichlistensystem/invoice/${invoice.id}/pdf`"
                            target="_blank"
                            rel="noopener"
                            class="text-fhac-mint-dark hover:underline dark:text-fhac-mint"
                          >
                            PDF
                          </a>
                        </li>
                      </ul>
                    </div>

                    <div class="flex flex-col gap-2">
                      <h3
                        class="font-semibold text-gray-900 dark:text-gray-100"
                      >
                        Bisherige Zahlungen
                      </h3>
                      <p
                        v-if="!details.payments.length"
                        class="text-gray-500 dark:text-gray-400"
                      >
                        Noch keine Zahlungen.
                      </p>
                      <ul
                        v-else
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                      >
                        <li
                          v-for="payment in details.payments"
                          :key="payment.id"
                          class="flex items-center justify-between gap-4 py-2"
                        >
                          <div class="flex flex-col">
                            <span
                              :class="[
                                payment.deleted_at
                                  ? 'text-gray-400 line-through dark:text-gray-500'
                                  : 'font-semibold text-gray-900 dark:text-gray-100',
                              ]"
                            >
                              {{ formatPrice(payment.amount) }}
                            </span>
                            <span
                              class="text-sm text-gray-500 dark:text-gray-400"
                            >
                              {{
                                details.payment_methods[payment.method] ??
                                payment.method
                              }}
                            </span>
                            <span
                              class="text-sm text-gray-500 dark:text-gray-400"
                            >
                              <UiDateTimeString
                                :value="payment.created_at"
                                withClockSuffix
                              />
                            </span>
                            <span
                              v-if="payment.created_by"
                              class="text-sm text-gray-500 dark:text-gray-400"
                            >
                              {{ payment.created_by.firstname }}
                              {{ payment.created_by.lastname }}
                            </span>
                            <span
                              v-if="payment.deleted_at"
                              class="text-sm text-gray-500 dark:text-gray-400"
                            >
                              Rückgängig gemacht
                            </span>
                          </div>
                          <AppButton
                            v-if="payment.is_undoable"
                            theme="danger"
                            :disabled="isSubmitting"
                            @click="undoPayment(payment)"
                          >
                            Rückgängig
                          </AppButton>
                        </li>
                      </ul>
                    </div>
                  </template>

                  <AppButton theme="gray" class="text-center" @click="close">
                    Schließen
                  </AppButton>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { computed, onMounted, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

import {
  centsToPriceInput,
  formatPrice,
  parsePriceToCents,
} from "../../composables/usePrice";
const props = defineProps({
  person: {
    type: Object as PropType<Strichlistensystem.BillingRow>,
    required: true,
  },
});

const emits = defineEmits<{
  close: [];
  saved: [];
}>();

type PaymentDetails = {
  balance: Strichlistensystem.Balance;
  payments: Strichlistensystem.Payment[];
  billing: Strichlistensystem.PaymentBilling;
  // labels of the payment methods (see StrichlistensystemPayment::METHODS)
  payment_methods: Record<Strichlistensystem.PaymentMethod, string>;
};

// amount in euro with max. 2 decimals, comma or dot as decimal separator
const amountInputPattern = /^\d{1,6}([.,]\d{1,2})?$/;

const details = ref<PaymentDetails | null>(null);
const loadError = ref(false);
const paymentError = ref<string | null>(null);
const isSubmitting = ref(false);
const paymentMethodIcons: Record<Strichlistensystem.PaymentMethod, string> = {
  cash: "fa-money-bill-wave",
  bank_transfer: "fa-building-columns",
};
// the labels come from the server, so they are only defined once
const paymentMethodOptions = computed(() =>
  (
    Object.entries(details.value?.payment_methods ?? {}) as [
      Strichlistensystem.PaymentMethod,
      string,
    ][]
  ).map(([value, label]) => ({
    value,
    label,
    icon: paymentMethodIcons[value] ?? "fa-money-bill-wave",
  })),
);

// the payment method is chosen with buttons, so it is not part of the form values
const paymentMethod = ref<Strichlistensystem.PaymentMethod>("cash");
const paymentForm = ref({ amount: "", given: "" });

// change for the given cash, null until a valid amount and given cash are entered
const change = computed(() => {
  const amount = parsePriceToCents(paymentForm.value.amount);
  const given = parsePriceToCents(paymentForm.value.given);

  return amount === null || given === null ? null : given - amount;
});

const close = () => {
  emits("close");
};

const loadDetails = async () => {
  loadError.value = false;
  // the payment method is only suggested once, a reload (e.g. after a rejected payment) keeps the chosen one
  const isFirstLoad = details.value === null;

  try {
    const response = await fetch(
      `/dashboard/admin/strichlistensystem/billing/user/${props.person.id}`,
      {
        credentials: "include",
        headers: { Accept: "application/json" },
      },
    );
    if (!response.ok) {
      throw new Error(`Status ${response.status}`);
    }

    details.value = await response.json();
    // the open amount is suggested as paid amount, a bank transfer if the person wants to pay by bank transfer
    if (isFirstLoad) {
      paymentMethod.value = details.value?.billing.wants_bank_transfer
        ? "bank_transfer"
        : "cash";
    }
    paymentForm.value = {
      amount:
        details.value && details.value.balance.open > 0
          ? centsToPriceInput(details.value.balance.open).replace(".", ",")
          : "",
      given: "",
    };
  } catch (error) {
    console.error("Failed to load the payments", error);
    loadError.value = true;
  }
};

// reload the details and the table after an action (also after an error, because the balance may have changed)
const afterAction = () => {
  isSubmitting.value = false;
  loadDetails();
  emits("saved");
};

const submitPayment = () => {
  const amount = parsePriceToCents(paymentForm.value.amount);
  if (amount === null || amount < 1) {
    paymentError.value = "Der Betrag muss mindestens 0,01 € sein.";
    return;
  }

  paymentError.value = null;
  isSubmitting.value = true;

  // only the message is reloaded, the table updates the row by its live update
  router.post(
    `/dashboard/admin/strichlistensystem/billing/user/${props.person.id}/payment`,
    { amount, method: paymentMethod.value },
    {
      only: ["message"],
      preserveState: true,
      preserveScroll: true,
      onError: (errors) => {
        paymentError.value = errors.amount ?? Object.values(errors)[0] ?? null;
      },
      onFinish: afterAction,
    },
  );
};

const undoPayment = (payment: Strichlistensystem.Payment) => {
  if (
    !window.confirm(
      `Soll die Zahlung von ${formatPrice(payment.amount)} rückgängig gemacht werden?`,
    )
  ) {
    return;
  }

  isSubmitting.value = true;
  router.delete(
    `/dashboard/admin/strichlistensystem/billing/payment/${payment.id}`,
    {
      only: ["message"],
      preserveState: true,
      preserveScroll: true,
      onFinish: afterAction,
    },
  );
};

const createInvoice = () => {
  if (!details.value) {
    return;
  }

  const { name, street, postal_code, city } = details.value.billing.details;
  if (
    !window.confirm(
      `Soll die Rechnung über ${formatPrice(details.value.billing.preview.amount_due)} an folgende Adresse erstellt werden?\n\n${name}\n${street}\n${postal_code} ${city}`,
    )
  ) {
    return;
  }

  isSubmitting.value = true;
  router.post(
    `/dashboard/admin/strichlistensystem/billing/user/${props.person.id}/invoice`,
    {},
    {
      only: ["message"],
      preserveState: true,
      preserveScroll: true,
      onFinish: afterAction,
    },
  );
};

onMounted(loadDetails);
</script>
