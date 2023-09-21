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

const form = ref({
  query: "",
});

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
    type: Object as PropType<App.Models.User>,
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
