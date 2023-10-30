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

const props = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  roles: {
    type: Array as PropType<Models.Role[]>,
    required: true,
  },
});
const users = ref<App.Models.User[] | null>(null);

const filteredUsers = computed(() => {
  if (!form.value.query) {
    return users.value;
  }

  if (!users.value) {
    return [];
  }

  return users.value.filter((user) => {
    return (
      user?.firstname.toLowerCase().includes(form.value.query.toLowerCase()) ||
      user?.lastname.toLowerCase().includes(form.value.query.toLowerCase()) ||
      user?.email.toLowerCase().includes(form.value.query.toLowerCase())
    );
  });
});

const fetchUsers = async () => {
  const response = await fetch("/api/users", {
    method: "GET",
    credentials: "include",
  });

  if (response.ok) {
    const data = await response.json();

    if (!users.value) {
      users.value = data.users;
      return;
    }

    // update users
    for (let i = 0; i < data?.users.length; i++) {
      const user = data.users[i];

      // get index of user by id
      const index = users.value.findIndex((u) => u.id === user.id);

      if (index === -1) {
        users.value.push(user);
        continue;
      } else {
        users.value[index] = user;
      }
    }
  }
};

const userInterval = setInterval(fetchUsers, 2500);
onBeforeUnmount(() => {
  clearInterval(userInterval);
});
</script>
