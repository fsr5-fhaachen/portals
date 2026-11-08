<template>
  <LayoutDashboardContent>
    <template #title>Strichliste – Abrechnung</template>

    <CardContainer>
      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="space-y-2">
            <UiH2>Abrechnung</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Gesamt ist die Summe aller Buchungen (ohne Stornierungen) mit dem
              Preis zum Zeitpunkt der Buchung. Offen ist Gesamt abzüglich der
              Zahlungen.
            </p>
            <AppLink href="/dashboard/admin/strichlistensystem" theme="gray">
              &larr; Zur Artikelverwaltung
            </AppLink>
          </div>
          <div class="flex shrink-0 flex-wrap gap-3">
            <AppButton
              theme="gray"
              class="text-center"
              @click="
                router.visit('/dashboard/admin/strichlistensystem/bookings')
              "
            >
              Buchungsliste
            </AppButton>
            <AppButton
              theme="gray"
              class="text-center"
              @click="
                router.visit('/dashboard/admin/strichlistensystem/invoices')
              "
            >
              Rechnungen
            </AppButton>
            <AppButton class="text-center" @click="downloadExport">
              Export herunterladen
            </AppButton>
          </div>
        </div>
      </CardBase>

      <UiMessage
        v-if="articlesWithoutPriceCount > 0"
        type="warning"
        :message="`${articlesWithoutPriceCount} ${
          articlesWithoutPriceCount === 1 ? 'Artikel hat' : 'Artikel haben'
        } noch keinen Preis. Buchungen dieser Artikel werden erst berechnet, wenn ein Preis festgelegt ist.`"
      />

      <CardBase>
        <FormKit
          type="checkbox"
          v-model="onlyOpen"
          label="Nur Personen mit offenem Betrag anzeigen"
          help="Die Suche findet weiterhin alle Personen."
        />
      </CardBase>

      <AppTable
        ref="table"
        data="balances"
        searchable
        :columns="getColumns()"
        :idFunction="(balance) => balance.id"
        :functions="getFunctions()"
        :rowClass="
          (balance) => ({
            'bg-red-100 dark:bg-red-900': balance.is_disabled,
          })
        "
      />
    </CardContainer>

    <StrichlistensystemPaymentModal
      v-if="personToPay"
      :person="personToPay"
      @close="personToPay = null"
      @saved="refreshTable"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { formatPrice } from "../../../../composables/usePrice";
import { TableColumn } from "../../../../types/table-column";
import { TableFunction } from "../../../../types/table-function";
import { TableButton } from "../../../../types/table-button";
import { TableLink } from "../../../../types/table-link";

defineProps({
  articlesWithoutPriceCount: {
    type: Number,
    required: true,
  },
});

const table = ref<{
  refresh: () => void;
  reload: (changes: Record<string, string | null>) => void;
} | null>(null);
const personToPay = ref<Strichlistensystem.BillingRow | null>(null);

const refreshTable = () => {
  table.value?.refresh();
};

// the filter is applied on the server and kept in the url, so the live update and reloads use it too
const onlyOpen = ref(
  new URLSearchParams(window.location.search).get("open") === "1",
);
watch(onlyOpen, (value) => {
  table.value?.reload({ open: value ? "1" : null });
});

const downloadExport = () => {
  window.open("/dashboard/admin/strichlistensystem/export", "_blank");
};

const escapeHtml = (value: string) => {
  const element = document.createElement("span");
  element.textContent = value;
  return element.innerHTML;
};

const getColumns = (): TableColumn[] => [
  new TableColumn({
    name: "lastname",
    sortKey: "lastname",
    text: "Nachname",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      escapeHtml(balance.lastname),
  }),
  new TableColumn({
    name: "firstname",
    sortKey: "firstname",
    text: "Vorname",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      escapeHtml(balance.firstname),
  }),
  new TableColumn({
    name: "article_action_logs_count",
    sortKey: "article_action_logs_count",
    text: "Buchungen",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      `${balance.article_action_logs_count}`,
  }),
  new TableColumn({
    name: "articles",
    text: "Artikel",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      balance.articles
        .map((article) => `${article.amount}×&nbsp;${escapeHtml(article.name)}`)
        .join("<br>") || "–",
  }),
  new TableColumn({
    name: "total",
    sortKey: "total",
    text: "Gesamt",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      formatPrice(balance.total),
  }),
  new TableColumn({
    name: "paid",
    sortKey: "paid",
    text: "Bezahlt",
    valueFunction: (balance: Strichlistensystem.BillingRow) =>
      formatPrice(balance.paid),
  }),
  new TableColumn({
    name: "open",
    sortKey: "open",
    text: "Offen",
    valueFunction: (balance: Strichlistensystem.BillingRow) => {
      if (balance.open > 0) {
        return `<span class="font-semibold text-red-600 dark:text-red-400">${formatPrice(balance.open)}</span>`;
      }

      if (balance.open < 0) {
        return `<span class="text-green-700 dark:text-green-400">${formatPrice(-balance.open)} Guthaben</span>`;
      }

      return formatPrice(0);
    },
  }),
];

const getFunctions = (): TableFunction[] => [
  new TableLink({
    name: "bookings",
    text: "Buchungen",
    theme: "gray",
    linkFunction: (balance: Strichlistensystem.BillingRow) =>
      `/dashboard/admin/strichlistensystem/bookings?user=${balance.id}`,
  }),
  new TableButton({
    name: "payment",
    text: "Zahlungen",
    buttonFunction: (balance: Strichlistensystem.BillingRow) => {
      personToPay.value = balance;
    },
  }),
];
</script>
