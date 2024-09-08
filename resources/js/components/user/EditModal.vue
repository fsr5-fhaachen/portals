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
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-sm sm:p-6"
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
                      <UiH2>User bearbeiten</UiH2>
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

                    <FormRow
                      v-if="
                        !user.roles.length ||
                        !user.roles
                          .map((role) => role.name)
                          .includes('super admin')
                      "
                    >
                      <FormKit
                        type="select"
                        name="role_id"
                        label="Rollen"
                        placeholder="Wähle eine oder mehrere Rollen aus"
                        :options="selectFormRoleOptions"
                        multiple
                      />
                    </FormRow>

                    <div v-if="user.avatarUrl">
                      <img :src="user.avatarUrl" class="mx-auto" />
                    </div>

                    <FormRow v-if="user.avatarUrl">
                      <FormKit
                        type="checkbox"
                        name="remove_avatar"
                        label="Avatar entfernen"
                      />
                    </FormRow>

                    <FormRow v-if="!editForm.remove_avatar">
                      <FormKit
                        type="file"
                        name="avatar"
                        label="Avatar hochladen"
                        validation="file|image|mimes:jpeg,jpg,png,gif"
                      />
                    </FormRow>

                    <FormRow>
                      <FormKit
                        type="checkbox"
                        name="is_disabled"
                        label="Deaktiviert"
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
  remove_avatar: false,
  avatar: null,
  is_disabled: user.is_disabled == 1 ? true : false,
});

const selectFormCourseOptions = useSelectFormCourseOptions(courses, true);
const selectFormRoleOptions = useSelectFormRoleOptions(roles);
const randomPlaceholderPerson = usePlaceholderPerson();

const { uploadFileByPresignedUrl } = useS3();

const close = () => {
  emits("close");
};
const editSubmitHandler = async () => {
  const avatarPath = ref<string | undefinded>();

  if (editForm.value.avatar?.length) {
    const formData = new FormData();
    formData.append("avatar", editForm.value.avatar[0].file);

    const response = await fetch(`/api/user/presigned-avatar-url`, {
      method: "POST",
      credentials: "include",
      headers: {
        "X-CSRF-TOKEN":
          document
            .querySelector("meta[name='csrf-token']")
            ?.getAttribute("content") || "",
      },
      body: formData,
    });

    if (!response.ok) {
      console.error("Failed to get presigned URL for avatar upload");
      return;
    }

    const data = await response.json();

    try {
      await uploadFileByPresignedUrl(
        formData.get("avatar"),
        data.presignedUrl.url,
      );
      avatarPath.value = data.path;
    } catch (error) {
      console.error("Failed to upload avatar", error);
      return;
    }
  }

  Inertia.post(`/dashboard/admin/user/${user.id}`, {
    ...editForm.value,
    avatar: avatarPath.value,
  });
  emits("submit");
};
</script>
