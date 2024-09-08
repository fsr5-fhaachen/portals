<template>
  <LayoutDashboardContent>
    <template #title>{{ group.name }}</template>
    <CardContainer>
      <CardBase v-if="group.telegram_group_link">
        <UiH2>Telegram Gruppe</UiH2>
        <div class="text-gray-900 dark:text-gray-100">
          Für diese Gruppe wurde eine Telegram Gruppe erstellt. Diesen Link
          sehen nur Gruppenmitglieder und Tutoren.
        </div>
        <AppLink :href="group.telegram_group_link">
          {{ group.telegram_group_link }}
        </AppLink>
      </CardBase>
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
        v-if="group.event && filteredRegistrations"
        :courses="courses"
        :event="group.event"
        :registrations="filteredRegistrations"
        :hideGroups="true"
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

const { group } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  group: {
    type: Object as PropType<App.Models.Group>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});
const registrations = ref(group.registrations);

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

const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + group.event_id + "/registrations",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    },
  );

  if (response.ok) {
    const data = await response.json();
    registrations.value = data.groups[group.id];
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
