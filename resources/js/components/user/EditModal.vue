<template>
  <div>
    <TransitionRoot as="template" :show="true">
      <Dialog as="div" class="relative z-10" @close="close">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          />
        </TransitionChild>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
          <div
            class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
          >
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6"
              >
                <FormKit
                  type="form"
                  id="edit"
                  @submit="editSubmitHandler"
                  :actions="false"
                  v-model="editForm"
                >
                  <FormContainer>
                    <FormRow>
                      <UiH2>User beearbeiten</UiH2>
                    </FormRow>
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
                      <FormKit
                        type="select"
                        name="role_id"
                        label="Rollen"
                        placeholder="Wähle eine oder mehrere Rollen aus"
                        :options="selectFormRoleOptions"
                        multiple
                      />
                    </FormRow>
                    <FormRow>
                      <FormKit type="submit" label="Speichern" />
                    </FormRow>
                  </FormContainer>
                </FormKit>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { PropType, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const { user, courses, roles } = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    default: null,
  },
  courses: {
    type: Array as PropType<Models.Course[]>,
    required: true,
  },
  roles: {
    type: Array as PropType<Models.Role[]>,
    required: true,
  },
});

const emits = defineEmits<{
  close: [];
  submit: [];
}>();

const editForm = ref({
  firstname: user.firstname,
  lastname: user.lastname,
  email: user.email,
  email_confirm: user.email,
  course_id: user.course_id,
  role_id: user.roles.map((role) => role.id),
});

const selectFormCourseOptions = useSelectFormCourseOptions(courses, true);
const selectFormRoleOptions = useSelectFormRoleOptions(roles);
const randomPlaceholderPerson = usePlaceholderPerson();

const close = () => {
  emits("close");
};
const editSubmitHandler = async () => {
  Inertia.post(`/dashboard/admin/user/${user.id}`, editForm.value);
  emits("submit");
};
</script>
