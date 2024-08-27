<template>
  <LayoutDashboardContent>
    <template #title>Zufallsgenerator</template>

    <CardContainer>
      <CardBase>
        <UiH2>Aktionen</UiH2>

        <FormRow>
          <AppButton
            @click="updateStateAndSubmit('running')"
            class="flex-1 text-center"
          >
            Zufallsgenerator starten
          </AppButton>
          <AppButton
            @click="updateStateAndSubmit('stopped')"
            theme="danger"
            class="flex-1 text-center"
          >
            Zufallsgenerator stoppen
          </AppButton>
        </FormRow>
        <FormRow>
          <AppButton
            @click="updateStateAndSubmit('idle')"
            theme="gray"
            class="flex-1 text-center"
          >
            Zufallsgenerator idle
          </AppButton>
        </FormRow>
      </CardBase>

      <CardBase>
        <UiH2>DEBUG</UiH2>

        <FormKit type="form" id="assign" :actions="false" v-model="form">
          <FormContainer>
            <FormRow>
              <FormKit type="text" name="query" label="Suche" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
      <GridContainer :cols="3">
        <UserBox
          v-for="filteredUser in filteredUsers"
          @click="selectedDebugUser(filteredUser.id)"
          :user="filteredUser"
          :class="{
            'ring-2 ring-fhac-mint': filteredUser.id === selectedDebugUserId,
          }"
          class="select-none hover:cursor-pointer"
        >
        </UserBox>
      </GridContainer>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, ref, PropType, onBeforeUnmount } from "vue";

const selectedDebugUserId = ref<number | null>(null);

const form = ref({
  query: "",
});

const { users } = defineProps({
  users: {
    type: Array as PropType<Models.User[]>,
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

const state = ref<{
  state: "setup" | "idle" | "running" | "stopped";
  user_id?: number;
}>({
  state: "setup",
});

const selectedDebugUser = (userId: number) => {
  selectedDebugUserId.value = userId;
};

const updateStateAndSubmit = (
  newState: "setup" | "idle" | "running" | "stopped",
) => {
  state.value.state = newState;

  // check if new state is "stopped" and if so get random user
  if (newState === "stopped") {
    state.value.user_id =
      selectedDebugUserId.value ??
      users[Math.floor(Math.random() * users.length)].id;
  }

  submitRandomGeneratorHandler();
};

const submitRandomGeneratorHandler = async () => {
  const response = await fetch("/dashboard/admin/random-generator", {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
    body: JSON.stringify(state.value),
  });

  if (response.ok && state.value.state === "stopped") {
    setTimeout(() => {
      selectedDebugUserId.value = null;
    }, 5000);
  }
};
</script>
