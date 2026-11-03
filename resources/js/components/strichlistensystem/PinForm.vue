<template>
  <FormKit
    type="form"
    :id="formId"
    @submit="submitHandler"
    :actions="false"
    v-model="form"
  >
    <FormContainer>
      <FormRow>
        <FormKit
          type="password"
          name="pin"
          label="PIN"
          inputmode="numeric"
          autocomplete="off"
          maxlength="4"
          help="Genau 4 Ziffern"
          validation="required|matches:/^\d{4}$/"
          :validation-messages="{
            matches: 'Die PIN muss aus genau 4 Ziffern bestehen.',
          }"
        />
      </FormRow>
      <FormRow>
        <FormKit
          type="password"
          name="pin_confirmation"
          label="PIN wiederholen"
          inputmode="numeric"
          autocomplete="off"
          maxlength="4"
          validation="required|confirm:pin"
          :validation-messages="{
            confirm: 'Die PINs stimmen nicht überein.',
          }"
        />
      </FormRow>
      <FormRow>
        <FormKit type="submit" :label="submitLabel" />
      </FormRow>
    </FormContainer>
  </FormKit>
</template>

<script setup lang="ts">
import { ref, useId } from "vue";
import { router } from "@inertiajs/vue3";
import { reset } from "@formkit/core";

defineProps({
  submitLabel: {
    type: String,
    default: "Speichern",
  },
});

const formId = `strichlistensystem-pin-${useId()}`;

const form = ref({
  pin: "",
  pin_confirmation: "",
});

const submitHandler = () => {
  router.post("/strichlistensystem/pin", form.value, {
    preserveScroll: true,
    onFinish: () => {
      reset(formId);
    },
  });
};
</script>
