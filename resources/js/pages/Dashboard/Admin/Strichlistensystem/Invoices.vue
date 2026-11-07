<template>
  <LayoutDashboardContent>
    <template #title>Strichliste – Rechnungen</template>

    <CardContainer>
      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="space-y-2">
            <UiH2>Rechnungen</UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Rechnungen werden in der Abrechnung im Zahlungsdialog einer Person
              erstellt. Bezahlt ist eine Rechnung, sobald die Zahlungen der
              Person sie abdecken. Eine stornierte Rechnung behält ihre Nummer.
            </p>
            <AppLink
              href="/dashboard/admin/strichlistensystem/billing"
              theme="gray"
            >
              &larr; Zur Abrechnung
            </AppLink>
          </div>
          <div class="flex shrink-0 flex-wrap gap-3">
            <AppButton
              theme="gray"
              class="text-center"
              @click="
                router.visit(
                  '/dashboard/admin/strichlistensystem/invoice-settings',
                )
              "
            >
              Einstellungen
            </AppButton>
          </div>
        </div>
      </CardBase>

      <UiMessage
        v-if="missingSettings.length"
        type="warning"
        :message="`Die Rechnungseinstellungen sind unvollständig, es fehlen: ${missingSettings.join(', ')}.`"
      />

      <AppTable
        data="invoices"
        searchable
        :columns="columns"
        :functions="functions"
        :idFunction="(invoice) => invoice.id"
        :rowClass="
          (invoice) => ({
            'opacity-60': invoice.status === 'cancelled',
          })
        "
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { router } from "@inertiajs/vue3";
import { formatPrice } from "../../../../composables/usePrice";
import { TableColumn } from "../../../../types/table-column";
import { TableButton } from "../../../../types/table-button";
import { TableFunction } from "../../../../types/table-function";

defineProps({
  missingSettings: {
    type: Array as PropType<string[]>,
    required: true,
  },
});

const formatDate = (value: string) =>
  new Date(value).toLocaleDateString("de-DE", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

const statusBadges: Record<Strichlistensystem.InvoiceRow["status"], string> = {
  open: '<span class="rounded-md bg-yellow-100 px-2 py-1 text-xs text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100">offen</span>',
  paid: '<span class="rounded-md bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-100">bezahlt</span>',
  cancelled:
    '<span class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-800 dark:bg-gray-700 dark:text-gray-100">storniert</span>',
};

const columns = [
  new TableColumn({
    name: "number",
    sortKey: "number",
    text: "Nummer",
    valueFunction: (invoice: Strichlistensystem.InvoiceRow) =>
      `<strong>${useEscapeHtml(invoice.number)}</strong>`,
  }),
  new TableColumn({
    name: "issued_on",
    sortKey: "issued_on",
    text: "Datum",
    valueFunction: (invoice: Strichlistensystem.InvoiceRow) =>
      formatDate(invoice.issued_on),
  }),
  new TableColumn({
    name: "person",
    text: "Person",
    valueFunction: (invoice: Strichlistensystem.InvoiceRow) => {
      const address = `${invoice.recipient.street}, ${invoice.recipient.postal_code} ${invoice.recipient.city}`;

      return `${useEscapeHtml(invoice.recipient.name)}<br><span class="text-xs text-gray-500 dark:text-gray-400">${useEscapeHtml(address)}</span>`;
    },
  }),
  new TableColumn({
    name: "amount_due",
    sortKey: "amount_due",
    text: "Betrag",
    valueFunction: (invoice: Strichlistensystem.InvoiceRow) =>
      formatPrice(invoice.amount_due) +
      (invoice.paid > 0
        ? `<br><span class="text-xs text-gray-500 dark:text-gray-400">${formatPrice(invoice.subtotal)} − ${formatPrice(invoice.paid)} bezahlt</span>`
        : ""),
  }),
  new TableColumn({
    name: "status",
    text: "Status",
    valueFunction: (invoice: Strichlistensystem.InvoiceRow) =>
      statusBadges[invoice.status],
  }),
];

const functions: TableFunction[] = [
  new TableButton({
    name: "pdf",
    text: "PDF",
    buttonFunction: (invoice: Strichlistensystem.InvoiceRow) => {
      window.open(
        `/dashboard/admin/strichlistensystem/invoice/${invoice.id}/pdf`,
        "_blank",
      );
    },
  }),
  new TableButton({
    name: "cancel",
    text: "Stornieren",
    theme: "danger",
    disabledFunction: (invoice: Strichlistensystem.InvoiceRow) =>
      invoice.status === "cancelled",
    buttonFunction: (invoice: Strichlistensystem.InvoiceRow) => {
      if (
        !window.confirm(
          `Soll die Rechnung ${invoice.number} über ${formatPrice(invoice.amount_due)} storniert werden? Die Nummer wird nicht neu vergeben, die Buchungen können neu abgerechnet werden.`,
        )
      ) {
        return;
      }

      router.post(
        `/dashboard/admin/strichlistensystem/invoice/${invoice.id}/cancel`,
        {},
        { preserveScroll: true },
      );
    },
  }),
];
</script>
