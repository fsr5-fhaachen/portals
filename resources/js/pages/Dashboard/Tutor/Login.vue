<template>
  <LayoutDashboardContent>
    <template v-if="user.rolesArray.some((role) => role === 'admin')" #title>
      Admin Login
    </template>
    <template v-else #title> Tutor Login </template>

    <CardBase>
      <FormKit
        type="form"
        id="tutor-login"
        @submit="submitTutorPasswordFormHandler"
        :actions="false"
        v-model="tutorPasswordForm"
      >
        <FormContainer>
          <FormRow>
            <UiH2>Geschützter Bereich</UiH2>
          </FormRow>

          <FormRow>
            <FormKit
              v-if="user.rolesArray.some((role) => ['admin'].includes(role))"
              type="password"
              name="password"
              label="Adminpasswort"
              placeholder="Passwort"
              validation="required"
            />
            <FormKit
              v-else
              type="password"
              name="password"
              label="Tutorenpasswort"
              placeholder="Passwort"
              validation="required"
            />
          </FormRow>

          <FormRow>
            <FormKit type="submit" label="Anmelden" />
          </FormRow>
        </FormContainer>
      </FormKit>
    </CardBase>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const tutorPasswordForm = ref({
  password: "",
});

const props = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const submitTutorPasswordFormHandler = async () => {
  Inertia.post("/dashboard/tutor/login", tutorPasswordForm.value);
};
</script>
