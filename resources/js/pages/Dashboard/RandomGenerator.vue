<template>
  <LayoutDashboardContent>
    <template #title>Zufallsgenerator</template>

    <CardContainer>
      <CardBase>
        <FormKit
          type="form"
          id="random-generator-participation"
          @submit="submitHandler"
          :actions="false"
          v-model="form"
        >
          <FormContainer>
            <FormRow>
              <p class="text-gray-500 dark:text-gray-400">
                Beim Zufallsgenerator wird auf der Bühne zufällig eine Person
                gezogen, um am aktuellen Spiel teilzunehmen. Wenn du nicht
                teilnehmen möchtest, entferne den Haken und speichere.
              </p>
            </FormRow>
            <FormRow>
              <FormKit
                type="checkbox"
                name="is_participating"
                label="Ich nehme am Zufallsgenerator teil"
              />
            </FormRow>
            <FormRow>
              <FormKit type="submit" label="Speichern" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const { isParticipating, submitUrl } = defineProps({
  isParticipating: {
    type: Boolean,
    required: true,
  },
  // the first-year students and the tutors have their own url
  submitUrl: {
    type: String,
    required: true,
  },
});

const form = ref({
  is_participating: isParticipating,
});

const submitHandler = () => {
  router.post(submitUrl, form.value, {
    preserveScroll: true,
  });
};
</script>
