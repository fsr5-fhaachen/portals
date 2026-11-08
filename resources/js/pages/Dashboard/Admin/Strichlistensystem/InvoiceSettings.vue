<template>
  <LayoutDashboardContent>
    <template #title>Strichliste – Rechnungseinstellungen</template>
    <template #subtitle>
      <AppLink href="/dashboard/admin/strichlistensystem/invoices" theme="gray">
        &larr; Zu den Rechnungen
      </AppLink>
    </template>

    <CardContainer>
      <UiMessage
        v-for="error in Object.values(errors)"
        :key="error"
        type="error"
        :message="error"
      />
      <UiMessage
        v-if="missingSettings.length"
        type="warning"
        :message="`Bevor Rechnungen erstellt werden können, fehlen noch: ${missingSettings.join(', ')}.`"
      />

      <FormKit
        type="form"
        id="invoice-settings"
        :actions="false"
        v-model="form"
        @submit="submitHandler"
      >
        <div class="flex flex-col gap-6">
          <CardBase>
            <FormContainer>
              <FormRow>
                <UiH2>Verein</UiH2>
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="club_name"
                  label="Name des Vereins"
                  validation="required"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="club_street"
                  label="Straße und Hausnummer"
                  validation="required"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="club_postal_code"
                  label="PLZ"
                  validation="required"
                />
                <FormKit
                  type="text"
                  name="club_city"
                  label="Ort"
                  validation="required"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="chairperson"
                  label="Vorstand"
                  validation="required"
                />
                <FormKit
                  type="text"
                  name="deputy_chairperson"
                  label="Stellvertretender Vorstand"
                  validation="required"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="email"
                  name="email"
                  label="Kontakt-E-Mail (optional)"
                  help="Steht in der Fußzeile der Rechnung."
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="checkbox"
                  name="charges_vat"
                  label="Mehrwertsteuer ausweisen"
                  help="Die Preise sind Bruttopreise, die enthaltene Mehrwertsteuer wird mit dem Satz des Artikels ausgewiesen. Ohne Haken wird keine Mehrwertsteuer ausgewiesen, z. B. als Kleinunternehmer (§ 19 UStG)."
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="tax_number"
                  label="Steuernummer"
                  autocomplete="off"
                  help="z. B. 201/123/45678. Mit Mehrwertsteuer ist die Steuernummer oder die Umsatzsteuer-ID nötig, ohne sind beide optional."
                />
                <FormKit
                  type="text"
                  name="vat_id"
                  label="Umsatzsteuer-ID"
                  autocomplete="off"
                  help="z. B. DE123456789. Steht wie die Steuernummer im Kopf und in der Fußzeile der Rechnung."
                />
              </FormRow>
            </FormContainer>
          </CardBase>

          <CardBase>
            <FormContainer>
              <FormRow>
                <UiH2>Bankverbindung</UiH2>
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="account_holder"
                  label="Kontoinhaber (optional)"
                  :help="`Ohne Angabe wird der Name des Vereins verwendet. Steht als Empfänger im Zahlungs-QR-Code.`"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="iban"
                  label="IBAN"
                  autocomplete="off"
                  validation="required"
                />
                <FormKit
                  type="text"
                  name="bic"
                  label="BIC (optional)"
                  autocomplete="off"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="bank_name"
                  label="Name der Bank (optional)"
                />
              </FormRow>
            </FormContainer>
          </CardBase>

          <CardBase>
            <FormContainer>
              <FormRow>
                <UiH2>Rechnungsnummer und Titel</UiH2>
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="number_prefix"
                  label="Präfix"
                  help="z. B. GER26-"
                  validation="required"
                />
                <FormKit
                  type="number"
                  number="integer"
                  name="number_digits"
                  label="Anzahl Stellen"
                  :min="1"
                  :max="10"
                  validation="required|min:1|max:10"
                />
                <FormKit
                  type="number"
                  number="integer"
                  name="start_number"
                  label="Startnummer"
                  :min="1"
                  help="Falls es schon andere Rechnungen gibt."
                  validation="required|min:1"
                />
              </FormRow>
              <FormRow>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Nächste Rechnungsnummer:
                  <strong class="text-gray-900 dark:text-gray-100">{{
                    previewNumber
                  }}</strong>
                  <template v-if="lastSequence">
                    (die letzte vergebene laufende Nummer ist
                    {{ lastSequence }}, eine kleinere Startnummer wird
                    übersprungen)
                  </template>
                </p>
              </FormRow>
              <FormRow>
                <FormKit
                  type="text"
                  name="title_template"
                  label="Titel der Rechnung"
                  validation="required"
                />
              </FormRow>
              <FormRow>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  <p>
                    Platzhalter:
                    <template
                      v-for="(
                        description, placeholder, index
                      ) in titlePlaceholders"
                      :key="placeholder"
                    >
                      <code class="text-gray-900 dark:text-gray-100">{{
                        placeholder
                      }}</code>
                      ({{ description }})<template
                        v-if="index < Object.keys(titlePlaceholders).length - 1"
                        >,
                      </template>
                    </template>
                  </p>
                  <p class="mt-1">
                    Beispiel:
                    <strong class="text-gray-900 dark:text-gray-100">{{
                      previewTitle
                    }}</strong>
                  </p>
                </div>
              </FormRow>
              <FormRow>
                <FormKit
                  type="number"
                  number="integer"
                  name="payment_term_days"
                  label="Zahlungsziel in Tagen"
                  :min="0"
                  :max="365"
                  validation="required|min:0|max:365"
                />
              </FormRow>
              <FormRow>
                <FormKit
                  type="textarea"
                  name="note"
                  label="Hinweis unter der Rechnung (optional)"
                  help="z. B. ein Hinweis zur Umsatzsteuer oder für Rückfragen."
                />
              </FormRow>
            </FormContainer>
          </CardBase>

          <CardBase>
            <FormKit type="submit" label="Einstellungen speichern" />
          </CardBase>
        </div>
      </FormKit>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

type InvoiceSettings = {
  club_name: string;
  club_street: string;
  club_postal_code: string;
  club_city: string;
  chairperson: string;
  deputy_chairperson: string;
  email: string;
  charges_vat: boolean;
  tax_number: string;
  vat_id: string;
  account_holder: string;
  iban: string;
  bic: string;
  bank_name: string;
  number_prefix: string;
  number_digits: number;
  start_number: number;
  title_template: string;
  payment_term_days: number;
  note: string;
};

const props = defineProps({
  errors: {
    type: Object as PropType<Record<string, string>>,
    default: () => ({}),
  },
  settings: {
    type: Object as PropType<InvoiceSettings>,
    required: true,
  },
  titlePlaceholders: {
    type: Object as PropType<Record<string, string>>,
    required: true,
  },
  missingSettings: {
    type: Array as PropType<string[]>,
    required: true,
  },
  nextNumber: {
    type: String,
    required: true,
  },
  lastSequence: {
    type: Number,
    required: true,
  },
});

const form = ref<InvoiceSettings>({ ...props.settings });

// the next number with the entered prefix, digits and start number (the server uses the same rule)
const previewNumber = computed(() => {
  const sequence = Math.max(
    Number(form.value.start_number) || 1,
    props.lastSequence + 1,
  );

  return `${form.value.number_prefix ?? ""}${String(sequence).padStart(
    Math.max(1, Number(form.value.number_digits) || 1),
    "0",
  )}`;
});

const previewTitle = computed(() =>
  (form.value.title_template ?? "")
    .replaceAll("{vorname}", "Anna")
    .replaceAll("{nachname}", "Müller")
    .replaceAll("{name}", "Anna Müller")
    .replaceAll("{nummer}", previewNumber.value)
    .replaceAll("{datum}", new Date().toLocaleDateString("de-DE")),
);

const submitHandler = () => {
  router.post("/dashboard/admin/strichlistensystem/invoice-settings", {
    ...form.value,
  });
};
</script>
