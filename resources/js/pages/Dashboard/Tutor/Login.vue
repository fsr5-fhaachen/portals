<template>
  <LayoutDashboardContent>
    <template v-if="isAdmin" #title>Admin Login</template>
    <template v-else #title>Tutor Login</template>

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
            <div class="relative w-full">
              <FormKit
                :type="showPassword ? 'text' : 'password'"
                name="password"
                :label="isAdmin ? 'Adminpasswort' : 'Tutorenpasswort'"
                placeholder="Passwort"
                validation="required"
                outer-class="!mb-0"
              />
              <FontAwesomeIcon
                class="absolute right-4 top-1/2 text-gray-500 cursor-pointer h-5 w-5"
                :icon="showPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']"
                @click="togglePassword"
              />
            </div>
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
import { ref, computed, PropType } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

const tutorPasswordForm = ref({
  password: "",
});

const showPassword = ref(false);
const togglePassword = () => (showPassword.value = !showPassword.value);

const props = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const isAdmin = computed(() =>
  props.user.rolesArray.some((role) => role === "admin"),
);

const submitTutorPasswordFormHandler = async () => {
  Inertia.post("/dashboard/tutor/login", tutorPasswordForm.value);
};
</script>
