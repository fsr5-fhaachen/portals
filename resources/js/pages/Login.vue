<template>
  <div>
    <FormKit
      type="form"
      id="login"
      @submit="submitHandler"
      :actions="false"
      v-model="form"
    >
      <FormContainer>
        <FormRow>
          <FormKit
            type="email"
            name="email"
            label="E-Mail"
            :placeholder="randomPlaceholderPerson.email"
            validation="required|email"
          />
        </FormRow>
        <FormRow>
          <FormKit type="submit" label="Anmelden" />
        </FormRow>
      </FormContainer>
    </FormKit>

    <div class="relative mt-6 flex justify-center text-sm">
      <AppLink href="/register">
        Du hast noch keinen Account? Dann registriere dich hier.
      </AppLink>
    </div>
  </div>
</template>

<script lang="ts">
import { Inertia } from "@inertiajs/inertia";
import CardLayout from "@/layouts/CardLayout.vue";

export default {
  layout: CardLayout,
};
</script>

<script setup lang="ts">
import { ref } from "vue";

defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
});

const form = ref({});

const randomPlaceholderPerson = usePlaceholderPerson();

const submitHandler = async () => {
  Inertia.post("/login", form.value);
};
</script>
