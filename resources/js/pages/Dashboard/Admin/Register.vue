<template>
  <LayoutDashboardContent>
    <template #title>User registrieren/zuweisen</template>

    <CardContainer>
      <CardBase>
        <FormKit
          type="form"
          id="register"
          @submit="registerSubmitHandler"
          :actions="false"
          v-model="registerForm"
        >
          <FormContainer>
            <FormRow>
              <UiH2>User registrieren</UiH2>
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
                type="file"
                name="avatar"
                label="Avatar hochladen"
                validation="file|image|mimes:jpeg,jpg,png,gif"
              />
            </FormRow>
            <FormRow>
              <FormKit type="submit" label="Registrieren" />
            </FormRow>
            <FormRow>
              <AppButton
                @click="registerForm = {}"
                theme="danger"
                class="flex-1 text-center"
              >
                Formular zurücksetzen
              </AppButton>
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase>
        <FormKit
          type="form"
          id="assign"
          @submit="assignSubmitHandler"
          :actions="false"
          v-model="assignForm"
        >
          <FormContainer>
            <FormRow>
              <UiH2>
                User zuweisen (Event & Slot Formulare werden nicht
                berücksichtigt)
              </UiH2>
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
                type="select"
                name="event_id"
                label="Event"
                placeholder="Wähle ein Event aus"
                validation="required"
                :options="selectFormEventOptions"
              />
            </FormRow>
            <FormRow v-if="getEventById(assignForm.event_id)?.slots?.length">
              <FormKit
                type="select"
                name="slot_id"
                label="Slot"
                placeholder="Wähle einen Slot aus"
                validation="required"
                :options="selectFormSlotOptions"
              />
            </FormRow>

            <template
              v-if="
                getEventById(assignForm.event_id) &&
                getEventById(assignForm.event_id)?.consider_alcohol
              "
            >
              <FormDivider />

              <FormRow>
                <UiH2>Deine Daten</UiH2>
              </FormRow>

              <FormRow
                v-if="getEventById(assignForm.event_id)?.consider_alcohol"
              >
                <FormKit
                  type="checkbox"
                  name="drinks_no_alcohol"
                  label="Ich trinke keinen Alkohol"
                />
              </FormRow>
            </template>
            <FormRow>
              <FormKit type="submit" label="Zuweisen" />
            </FormRow>
            <FormRow>
              <AppButton
                @click="
                  assignForm = {
                    event_id: 0,
                  }
                "
                theme="danger"
                class="flex-1 text-center"
              >
                Formular zurücksetzen
              </AppButton>
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { computed, ref, PropType } from "vue";

const registerForm = ref({});
const assignForm = ref({
  event_id: 0,
});

const { courses, events } = defineProps({
  courses: {
    type: Object as PropType<App.Models.Course[]>,
    required: true,
  },
  events: {
    type: Object as PropType<App.Models.Event[]>,
    required: true,
  },
});

const getEventById = (id: number) => {
  return events.find((event) => event.id === id);
};

const selectFormCourseOptions = useSelectFormCourseOptions(courses, true);
const selectFormEventOptions = useSelectFormEventOptions(events);

const { uploadFileByPresignedUrl } = useS3();

const selectFormSlotOptions = computed(() => {
  const event = getEventById(assignForm.value.event_id);

  if (!event || !event.slots) {
    return [];
  }

  return useSelectFormSlotOptions(event.slots);
});
const randomPlaceholderPerson = usePlaceholderPerson();

const registerSubmitHandler = async () => {
  const avatarPath = ref<string | undefinded>();

  if (registerForm.value.avatar?.length) {
    const formData = new FormData();
    formData.append("avatar", registerForm.value.avatar[0].file);

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
        data.presignedUrl.url
      );
      avatarPath.value = data.path;
    } catch (error) {
      console.error("Failed to upload avatar", error);
      return;
    }
  }

  Inertia.post("/dashboard/admin/register", {
    ...registerForm.value,
    avatar: avatarPath.value,
  });
};

const assignSubmitHandler = async () => {
  Inertia.post("/dashboard/admin/assign", assignForm.value);
};
</script>
