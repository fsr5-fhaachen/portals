<template>
  <LayoutDashboardContent>
    <template #title>Punktesystem</template>

    <CardContainer>
      <CardBase>
        <FormKit
          type="form"
          id="scoreSystem"
          @submit="submitScoreSystemHandler"
          :actions="false"
          v-model="form"
        >
          <FormContainer>
            <FormRow>
              <UiH2>Einstellungen</UiH2>
            </FormRow>
            <FormKit type="list" name="teams">
              <GridContainer :cols="teamAmount">
                <FormKit v-for="index in teamAmount" type="group" :key="index">
                  <FormContainer>
                    <FormRow>
                      <FormKit
                        type="text"
                        name="name"
                        :label="`Teamname von Team ${index}`"
                        :placeholder="`Team ${index}`"
                        validation="required"
                      />
                    </FormRow>

                    <FormRow>
                      <FormKit
                        type="text"
                        name="score"
                        :label="`Punkte von Team ${index}`"
                        placeholder="0"
                        validation="required"
                      />
                    </FormRow>
                  </FormContainer>
                </FormKit>
              </GridContainer>
            </FormKit>
            <FormRow>
              <FormKit type="submit" label="Punktesystem updaten" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref, PropType } from "vue";

const { state } = defineProps({
  state: {
    type: Object as PropType<{
      teams: {
        name: string;
        score: string;
      }[];
    }>,
    required: true,
  },
});

const teamAmount = ref(2);

const form = ref<{
  teams: {
    name: string;
    score: string;
  }[];
}>({
  teams: state?.teams || [],
});

const submitScoreSystemHandler = async () => {
  const response = await fetch("/dashboard/admin/score-system", {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
    body: JSON.stringify(form.value),
  });
};
</script>
