<template>
  <div>
    <FormKit
      type="form"
      id="registration-example"
      :form-class="submitted ? 'hide' : 'show'"
      submit-label="Register"
      @submit="submitHandler"
      :actions="false"
      v-model="form"
    >
      <FormContainer>
        <FormRow>
          <FormKit
            type="text"
            name="firstname"
            label="Vorname"
            :placeholder="randomPlaceholderPerson.firstname"
            validation="required|length:2,255"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="text"
            name="lastname"
            label="Nachname"
            :placeholder="randomPlaceholderPerson.lastname"
            validation="required|length:2,255"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="email"
            name="email"
            label="E-Mail"
            :placeholder="randomPlaceholderPerson.email"
            validation="required|email|length:3,255"
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
            name="course_id"
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

    <div class="mt-6 relative flex justify-center text-sm">
      <AppLink href="/login">
        Du hast bereits einen Account? Dann melde dich hier an.
      </AppLink>
    </div>
  </div>
</template>

<script lang="ts">
import CardLayout from "@/layouts/CardLayout.vue";
import { Inertia } from "@inertiajs/inertia";

export default {
  layout: CardLayout,
};
</script>

<script setup lang="ts">
import { ref } from "vue";

const form = ref({});

const { courses } = defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
});

const selectFormCourseOptions = useSelectFormCourseOptions(courses);
const randomPlaceholderPerson = usePlaceholderPerson();

const submitted = ref(false);
const submitHandler = async () => {
  Inertia.post("/register", form.value);
};
</script>
