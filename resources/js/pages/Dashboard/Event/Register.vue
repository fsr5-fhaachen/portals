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

            <template v-if="event.type == 'workshop_booking'">
              <FormDivider />

              <FormRow>
                <UiH2>Deine Workshop-Wünsche</UiH2>
              </FormRow>
              <UiMessage
                v-for="(error, field) in priorityErrors"
                :key="field"
                type="error"
                :message="error"
              />
              <UiMessage
                v-if="!hasEnoughWorkshops"
                type="warning"
                message="Für dieses Event gibt es noch nicht genug Workshops. Die Anmeldung ist bald möglich."
              />
              <template v-else>
                <FormRow>
                  <p class="text-gray-500 dark:text-gray-400">
                    Wähle {{ event.workshop_priority_count }} verschiedene
                    Workshops nach Priorität aus. Wir teilen dich pro Zeitblock
                    in einen Workshop ein und versuchen dabei, deine höchsten
                    Wünsche zu erfüllen.
                  </p>
                </FormRow>
                <FormKit type="list" name="priorities">
                  <FormRow
                    v-for="index in event.workshop_priority_count"
                    :key="index"
                  >
                    <FormKit
                      type="select"
                      :label="`Priorität ${index}`"
                      placeholder="Wähle einen Workshop aus"
                      validation="required"
                      :options="workshopOptions(index - 1)"
                    />
                  </FormRow>
                </FormKit>
              </template>
            </template>

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
              <FormKit
                type="submit"
                label="Anmelden"
                :disabled="
                  event.type == 'workshop_booking' && !hasEnoughWorkshops
                "
              />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase v-if="event.type == 'workshop_booking'">
        <FormContainer>
          <UiH2>Workshops</UiH2>

          <div v-for="workshop in event.workshops" :key="workshop.id">
            <p class="font-medium text-gray-900 dark:text-gray-100">
              {{ workshop.name }}
            </p>
            <p
              v-if="workshop.description"
              class="text-sm text-gray-500 dark:text-gray-400"
            >
              {{ workshop.description }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{
                workshop.sessions
                  ?.map((session) => session.block?.name)
                  .join(", ")
              }}
            </p>
          </div>
        </FormContainer>
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
import { router, usePage } from "@inertiajs/vue3";

const { event } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const selectFormSlotOptions = useSelectFormSlotOptions(
  event.slots as App.Models.Slot[],
);

const form = ref<{
  slot: number;
  drinks_no_alcohol: boolean;
  // workshop ids ordered by priority, only for a workshop booking
  priorities?: (number | undefined)[];
}>({
  slot: 0,
  drinks_no_alcohol: false,
});

// every priority needs another workshop
const hasEnoughWorkshops = computed(
  () => (event.workshops?.length ?? 0) >= event.workshop_priority_count,
);

const page = usePage();
const priorityErrors = computed(() =>
  Object.fromEntries(
    Object.entries((page.props.errors ?? {}) as Record<string, string>).filter(
      ([field]) => field.startsWith("priorities"),
    ),
  ),
);

// every workshop can only be chosen once, so the workshops of the other priorities are disabled. They are not left
// out: the options are reused by their position, so a shorter list would shift the selection of the other priorities
const workshopOptions = (priorityIndex: number) =>
  (event.workshops ?? []).map((workshop) => {
    const otherIndex = (form.value.priorities ?? []).findIndex(
      (workshopId, index) =>
        index !== priorityIndex && workshopId === workshop.id,
    );

    return {
      label:
        otherIndex === -1
          ? workshop.name
          : `${workshop.name} (schon Prio ${otherIndex + 1})`,
      value: workshop.id,
      attrs: { disabled: otherIndex !== -1 },
    };
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
  router.post("/dashboard/event/" + event.id + "/register", form.value);
};
</script>
