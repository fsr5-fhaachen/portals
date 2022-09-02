<template>
  <div>
    <FormKit
      type="form"
      id="registration-example"
      :form-class="submitted ? 'hide' : 'show'"
      submit-label="Register"
      @submit="submitHandler"
      :actions="false"
      #default="{ value }"
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
    <div v-if="submitted">
      <h2>Submission successful!</h2>
    </div>

    <div class="mt-6 relative flex justify-center text-sm">
      <AppLink href="/register">
        Du hast noch keinen Account? Dann registriere dich hier.
      </AppLink>
    </div>
  </div>
</template>

<script lang="ts">
import CardLayout from "@/layouts/CardLayout.vue";

export default {
  layout: CardLayout,
};
</script>

<script setup lang="ts">
import { ref } from "vue";

const { courses } = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
});

const selectFormCourseOptions = useSelectFormCourseOptions(courses);

const placeholderPersons = ref([
  {
    first_name: "Max",
    last_name: "Mustermann",
    email: "max.mustermann@beispiel.de",
  },
  {
    first_name: "Erika",
    last_name: "Mustermann",
    email: "erika.mustermann@beispiel.de",
  },
]);

const randomPlaceholderPerson =
  placeholderPersons.value[
    Math.floor(Math.random() * placeholderPersons.value.length)
  ];

const submitted = ref(false);
const submitHandler = async () => {
  // Let's pretend this is an ajax request:
  await new Promise((r) => setTimeout(r, 1000));
  submitted.value = true;
};
</script>
