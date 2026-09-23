<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>
    <CardContainer>
      <CardBase>
        <FormKit type="form" id="assign" :actions="false" v-model="form">
          <FormContainer>
            <FormRow>
              <UiH2>Filter</UiH2>
            </FormRow>
            <FormRow>
              <FormKit type="text" name="query" label="Suche" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase v-if="event.type == 'station_rally'">
        <UiH2>Registrierung in andere Gruppe verschieben</UiH2>
        <FormKit
          id="move-group"
          type="form"
          :actions="false"
          v-model="moveForm"
          @submit="moveRegistration"
        >
          <FormContainer>
            <FormRow>
              <FormKit
                type="select"
                name="registration_id"
                label="Person"
                placeholder="Wähle eine Registrierung aus"
                validation="required"
                :options="registrationOptions"
              />
              <FormKit
                type="select"
                name="group_id"
                label="Neue Gruppe"
                placeholder="Wähle eine Gruppe aus"
                validation="required"
                :options="groupOptions"
              />
              <FormKit type="submit" label="Verschieben" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <RegistrationTable
        v-if="filteredRegistrations"
        :courses="courses"
        :event="event"
        :registrations="filteredRegistrations"
        :user="user"
      />
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, ref, PropType, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

const form = ref({
  query: "",
});

const moveForm = ref({});
const registrationOptions = computed(() =>
  Object.fromEntries(
    (event.registrations || []).map((registration) => [
      registration.id,
      registration.user?.firstname + " " + registration.user?.lastname,
    ]),
  ),
);
const groupOptions = computed(() =>
  Object.fromEntries(
    (event.groups || []).map((group) => [group.id, group.name]),
  ),
);
const moveRegistration = (data: any) => {
  router.post(
    "/dashboard/admin/registration/" + data.registration_id + "/group",
    data,
    { onSuccess: () => (moveForm.value = {}) },
  );
};

const { event } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const registrations = ref(event.registrations);

const filteredRegistrations = computed(() => {
  if (!form.value.query) {
    return registrations.value;
  }

  if (!registrations.value) {
    return [];
  }

  return registrations.value.filter((registration) => {
    return (
      registration.user?.firstname
        .toLowerCase()
        .includes(form.value.query.toLowerCase()) ||
      registration.user?.lastname
        .toLowerCase()
        .includes(form.value.query.toLowerCase()) ||
      registration.user?.email
        .toLowerCase()
        .includes(form.value.query.toLowerCase())
    );
  });
});

// TODO: Optimize this
// const fetchRegistrations = async () => {
//   const response = await fetch("/api/events/" + event.id + "/registrations", {
//     method: "GET",
//     credentials: "include",
//     headers: {
//       "Content-Type": "application/json",
//     },
//   });

//   if (response.ok) {
//     const data = await response.json();
//     registrations.value = data.registrations;
//   }
// };
// const registrationsInterval = setInterval(fetchRegistrations, 2500);
// onBeforeUnmount(() => {
//   clearInterval(registrationsInterval);
// });
</script>
