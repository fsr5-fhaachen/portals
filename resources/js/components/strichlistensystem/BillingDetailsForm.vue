<template>
  <FormKit
    type="form"
    id="strichlistensystem-billing-details"
    @submit="submitHandler"
    :actions="false"
    v-model="form"
  >
    <FormContainer>
      <FormRow>
        <FormKit
          type="checkbox"
          name="wants_bank_transfer"
          label="Ich möchte per Überweisung zahlen"
          help="Die Orga erstellt dir dann eine Rechnung mit allen Posten und einem QR-Code für deine Banking-App."
        />
      </FormRow>

      <template v-if="form.wants_bank_transfer">
        <FormRow>
          <FormKit
            type="text"
            name="name"
            label="Vollständiger Name"
            autocomplete="name"
            validation="required|length:0,70"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="text"
            name="street"
            label="Straße und Hausnummer"
            autocomplete="street-address"
            validation="required|length:0,100"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="text"
            name="postal_code"
            label="PLZ"
            autocomplete="postal-code"
            inputmode="numeric"
            validation="required|length:0,20"
          />
          <FormKit
            type="text"
            name="city"
            label="Ort"
            autocomplete="address-level2"
            validation="required|length:0,100"
          />
        </FormRow>
      </template>

      <FormRow>
        <FormKit type="submit" label="Speichern" />
      </FormRow>
    </FormContainer>
  </FormKit>
</template>

<script setup lang="ts">
import { PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  billingDetails: {
    type: Object as PropType<Strichlistensystem.BillingDetails>,
    required: true,
  },
});

const form = ref({ ...props.billingDetails });

const submitHandler = () => {
  router.post("/strichlistensystem/billing-details", form.value, {
    preserveScroll: true,
  });
};
</script>
