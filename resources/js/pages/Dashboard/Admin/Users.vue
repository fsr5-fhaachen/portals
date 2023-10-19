<template>
  <LayoutDashboardContent>
    <template #title>User Verwaltung</template>
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

      <UserTable
        v-if="filteredUsers"
        :courses="courses"
        :roles="roles"
        :users="filteredUsers"
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

const { users } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  users: {
    type: Array as PropType<Models.User[]>,
    required: true,
  },
  roles: {
    type: Array as PropType<Models.Role[]>,
    required: true,
  },
});

const filteredUsers = computed(() => {
  if (!form.value.query) {
    return users;
  }

  if (!users) {
    return [];
  }

  return users.filter((user) => {
    return (
      user?.firstname.toLowerCase().includes(form.value.query.toLowerCase()) ||
      user?.lastname.toLowerCase().includes(form.value.query.toLowerCase()) ||
      user?.email.toLowerCase().includes(form.value.query.toLowerCase())
    );
  });
});
</script>
