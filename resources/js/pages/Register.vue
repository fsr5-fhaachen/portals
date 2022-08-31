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
            type="text"
            name="first_name"
            label="Vorname"
            :placeholder="randomPlaceholderPerson.first_name"
            validation="required"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="text"
            name="last_name"
            label="Nachname"
            :placeholder="randomPlaceholderPerson.last_name"
            validation="required"
          />
        </FormRow>
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
          <FormKit
            type="email"
            name="email_confirm"
            label="E-Mail bestätigen"
            :placeholder="randomPlaceholderPerson.email"
            validation="required|confirm"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="select"
            name="course"
            label="Studiengang"
            placeholder="Wähle einen Studiengang aus"
            validation="required"
            :options="selectFormCourseOptions"
          />
        </FormRow>
        <FormRow>
          <FormKit type="submit" label="Registrieren" />
        </FormRow>
      </FormContainer>
    </FormKit>
    <div v-if="submitted">
      <h2>Submission successful!</h2>
    </div>
  </div>
</template>

<script lang="ts">
import CardLayout from "../layouts/CardLayout.vue";

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
