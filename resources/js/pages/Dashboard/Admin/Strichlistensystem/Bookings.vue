<template>
  <LayoutDashboardContent>
    <template #title>Strichliste – Buchungsliste</template>

    <CardContainer>
      <CardBase>
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="space-y-2">
            <UiH2>
              {{
                person
                  ? `Buchungen von ${person.firstname} ${person.lastname}`
                  : "Alle Buchungen"
              }}
            </UiH2>
            <p class="text-gray-500 dark:text-gray-400">
              Jede Zeile ist ein gebuchter Artikel mit dem Preis zum Zeitpunkt
              der Buchung, die neuesten zuerst. Stornierte Buchungen werden
              nicht berechnet.
            </p>
            <AppLink
              href="/dashboard/admin/strichlistensystem/billing"
              theme="gray"
            >
              &larr; Zur Abrechnung
            </AppLink>
          </div>
          <div v-if="person" class="flex shrink-0 flex-wrap gap-3">
            <AppButton
              theme="gray"
              class="text-center"
              @click="
                router.visit('/dashboard/admin/strichlistensystem/bookings')
              "
            >
              Alle Buchungen anzeigen
            </AppButton>
          </div>
        </div>
      </CardBase>

      <AppTable
        data="bookings"
        searchable
        :columns="columns"
        :idFunction="(booking) => booking.id"
        :rowClass="
          (booking) => ({
            'line-through opacity-60': booking.is_cancelled,
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

defineProps({
  // the person if the list is filtered by a person
  person: {
    type: Object as PropType<{
      id: number;
      firstname: string;
      lastname: string;
    } | null>,
    default: null,
  },
});

const formatDateTime = (value: string) =>
  new Date(value).toLocaleString("de-DE", {
    weekday: "short",
    day: "2-digit",
    month: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });

const columns = [
  new TableColumn({
    name: "created_at",
    sortKey: "created_at",
    text: "Zeit",
    valueFunction: (booking: Strichlistensystem.BookingRow) =>
      formatDateTime(booking.created_at),
  }),
  new TableColumn({
    name: "person",
    text: "Person",
    valueFunction: (booking: Strichlistensystem.BookingRow) =>
      booking.user
        ? useEscapeHtml(`${booking.user.firstname} ${booking.user.lastname}`)
        : "–",
  }),
  new TableColumn({
    name: "article",
    text: "Artikel",
    valueFunction: (booking: Strichlistensystem.BookingRow) =>
      booking.article ? useEscapeHtml(booking.article.name) : "–",
  }),
  new TableColumn({
    name: "price",
    text: "Preis",
    valueFunction: (booking: Strichlistensystem.BookingRow) =>
      booking.price === null ? "kein Preis" : formatPrice(booking.price),
  }),
  new TableColumn({
    name: "status",
    text: "Status",
    valueFunction: (booking: Strichlistensystem.BookingRow) =>
      booking.is_cancelled ? "storniert" : "gebucht",
  }),
];
</script>
