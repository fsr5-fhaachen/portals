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
            <FormRow>
              <FormKit
                type="number"
                v-model="teamAmount"
                :ignore="true"
                number="integer"
                label="Anzahl der Teams"
                :min="1"
                :max="maximumTeamAmount"
                :validation="`required|min:1|max:${maximumTeamAmount}`"
              />
            </FormRow>
            <FormKit type="list" name="teams">
              <GridContainer :cols="Math.min(shownTeamAmount, 3)">
                <FormKit
                  v-for="index in shownTeamAmount"
                  type="group"
                  :key="index"
                >
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
import { computed, ref, PropType } from "vue";

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

// keep in sync with the validation in DashboardAdminScoreSystemController
const maximumTeamAmount = 8;

// the amount of the saved teams, three teams for a new score system
const teamAmount = ref<number | undefined>(state?.teams?.length || 3);

// the input can be empty or out of range while typing
const shownTeamAmount = computed(() =>
  Math.min(Math.max(teamAmount.value || 1, 1), maximumTeamAmount),
);

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
    // the values of removed teams are not saved
    body: JSON.stringify({
      teams: form.value.teams.slice(0, shownTeamAmount.value),
    }),
  });
};
</script>
