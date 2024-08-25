<template>
  <LayoutDashboardContent>
    <template #title>Anmeldung zur Veranstaltung</template>

    <CardContainer>
      <CardBase>
        <FormKit
          type="form"
          id="event-register"
          @submit="submitHandler"
          :actions="false"
          v-model="form"
        >
          <FormContainer>
            <FormRow>
              <UiH2>Eventdaten</UiH2>
            </FormRow>

            <FormRow>
              <UiDl>
                <UiDt>Event</UiDt>
                <UiDd>
                  {{ event.name }}
                </UiDd>

                <template v-if="event.registration_from">
                  <UiDt>Anmeldung ab</UiDt>
                  <UiDd>
                    <UiDateTimeString
                      :value="event.registration_from"
                      :withClockSuffix="true"
                    />
                  </UiDd>
                </template>

                <template v-if="event.registration_to">
                  <UiDt>Anmeldung bis</UiDt>
                  <UiDd>
                    <UiDateTimeString
                      :value="event.registration_to"
                      :withClockSuffix="true"
                    />
                  </UiDd>
                </template>

                <template v-if="slotData">
                  <template v-if="slotData.maximum_participants">
                    <UiDt>Maximale Teilnehmerzahl</UiDt>
                    <UiDd>{{ slotData.maximum_participants }}</UiDd>
                  </template>
                </template>
              </UiDl>
            </FormRow>

            <FormRow v-if="selectFormSlotOptions.length">
              <FormKit
                type="select"
                name="slot"
                label="Slot"
                placeholder="Wähle einen Slot aus"
                validation="required"
                :options="selectFormSlotOptions"
              />
            </FormRow>

            <template v-if="event.consider_alcohol || dynamicFormSchema.length">
              <FormDivider />

              <FormRow>
                <UiH2>Deine Daten</UiH2>
              </FormRow>

              <FormRow v-if="event.consider_alcohol">
                <FormKit
                  type="checkbox"
                  name="drinks_no_alcohol"
                  label="Ich trinke keinen Alkohol"
                />
              </FormRow>
              <FormSchema v-if="dynamicFormSchema">
                <FormKitSchema :schema="dynamicFormSchema" />
              </FormSchema>
            </template>

            <FormRow>
              <FormKit type="submit" label="Anmelden" />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase v-if="event.description">
        <div
          class="prose !max-w-full dark:prose-invert"
          v-html="event.description"
        ></div>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script lang="ts">
import DashboardCardLayout from "@/layouts/DashboardCardLayout.vue";

export default {
  layout: DashboardCardLayout,
};
</script>

<script setup lang="ts">
import { computed, ref, PropType } from "vue";
import { FormKitSchemaNode } from "@formkit/core";
import { Inertia } from "@inertiajs/inertia";

const { event } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const selectFormSlotOptions = useSelectFormSlotOptions(
  event.slots as App.Models.Slot[],
);

const form = ref({
  slot: 0,
  drinks_no_alcohol: false,
});

const slotData = computed(() => {
  if (event.slots?.length && form.value.slot) {
    return event.slots.find((s) => s.id === form.value.slot);
  }

  return null;
});

const dynamicFormSchema = computed(() => {
  const result: FormKitSchemaNode[] = [];

  // get event form
  if (event.form) {
    result.push(...(JSON.parse(event.form) as FormKitSchemaNode[]));
  }

  // get slot form
  if (slotData.value && slotData.value.form) {
    result.push(...(JSON.parse(slotData.value.form) as FormKitSchemaNode[]));
  }

  return result;
});

const submitHandler = async () => {
  Inertia.post("/dashboard/event/" + event.id + "/register", form.value);
};
</script>
