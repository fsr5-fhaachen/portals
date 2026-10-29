<template>
  <div
    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
    :class="{
      'border-green-400 dark:border-green-700':
        registration.fulfils_requirements,
    }"
  >
    <FormKit
      type="form"
      :id="`registration-${registration.id}`"
      @submit="submitHandler"
      :actions="false"
      v-model="registrationForm"
    >
      <FormContainer>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
              {{ registration.event?.name || "–" }}
            </h3>
            <div
              v-if="registration.created_at"
              class="flex gap-1 text-xs text-gray-500 dark:text-gray-400"
            >
              <span>Angemeldet am</span>
              <UiDateTimeString :value="registration.created_at" />
            </div>
          </div>
          <span
            :class="queueStatus.classes"
            class="rounded-md px-2 py-1 text-xs text-white"
          >
            {{ queueStatus.text }}
          </span>
        </div>

        <div
          class="grid grid-cols-1 items-start gap-x-4 gap-y-6 sm:grid-cols-2"
        >
          <div v-if="registration.event?.groups?.length">
            <FormKit
              type="select"
              name="group_id"
              label="Gruppe"
              :options="selectFormGroupOptions"
            />
          </div>
          <div v-if="registration.event?.slots?.length">
            <FormKit
              type="select"
              name="slot_id"
              label="Slot"
              :options="selectFormSlotOptions"
            />
          </div>
          <div>
            <FormKit
              type="number"
              name="queue_position"
              label="Wartelistenposition"
              number="integer"
              validation="min:-1"
              help="leer = angemeldet, -1 = wartet auf Zuteilung, ab 1 = Position auf der Warteliste"
            />
          </div>
        </div>

        <div class="flex flex-wrap gap-x-6">
          <FormKit type="checkbox" name="is_present" label="Anwesend" />
          <FormKit
            v-if="registration.event?.consider_alcohol"
            type="checkbox"
            name="drinks_alcohol"
            label="Trinkt Alkohol"
          />
          <FormKit
            v-if="hasRequirements"
            type="checkbox"
            name="fulfils_requirements"
            label="Erfüllt Voraussetzungen"
          />
        </div>

        <template v-if="formSchema.length">
          <FormDivider />
          <FormRow>
            <UiH2>Formular</UiH2>
          </FormRow>
          <FormKit type="group" name="form_responses">
            <FormSchema>
              <FormKitSchema :schema="formSchema" />
            </FormSchema>
          </FormKit>
        </template>

        <FormRow v-if="unknownFormResponses.length">
          <div class="flex w-full flex-col">
            <label
              class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
            >
              Weitere Formularantworten
            </label>
            <div
              class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 sm:text-sm"
            >
              <div v-for="[key, value] in unknownFormResponses" :key="key">
                <span class="font-medium">{{ key }}:</span>
                {{ formatValue(value) }}
              </div>
            </div>
          </div>
        </FormRow>

        <FormRow v-if="message">
          <UiMessage :type="message.type" :message="message.text" />
        </FormRow>

        <div class="flex flex-wrap gap-2">
          <FormKit type="submit" label="Anmeldung speichern" />
          <AppButton
            @click="deleteHandler"
            theme="danger"
            class="mb-1 flex-1 text-center text-sm font-medium"
          >
            {{ confirmDelete ? "Wirklich löschen?" : "Anmeldung löschen" }}
          </AppButton>
        </div>
      </FormContainer>
    </FormKit>
  </div>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { FormKitSchemaNode } from "@formkit/core";

const { registration, userId } = defineProps({
  registration: {
    type: Object as PropType<App.Models.Registration>,
    required: true,
  },
  userId: {
    type: Number,
    required: true,
  },
});

const emits = defineEmits<{
  updated: [];
  deleted: [];
}>();

/**
 * Form responses may be stored as JSON string (e.g. seeded data)
 */
const parseFormResponses = (responses: unknown): Record<string, unknown> => {
  if (typeof responses === "string") {
    try {
      return parseFormResponses(JSON.parse(responses));
    } catch {
      return {};
    }
  }
  if (responses && typeof responses === "object" && !Array.isArray(responses)) {
    return responses as Record<string, unknown>;
  }
  return {};
};

const initialFormResponses = parseFormResponses(registration.form_responses);

const registrationForm = ref({
  group_id: registration.group_id ?? "",
  slot_id: registration.slot_id ?? "",
  queue_position: registration.queue_position ?? undefined,
  is_present: !!registration.is_present,
  drinks_alcohol: !!registration.drinks_alcohol,
  fulfils_requirements: !!registration.fulfils_requirements,
  form_responses: { ...initialFormResponses },
});

const message = ref<{ type: string; text: string } | null>(null);
const confirmDelete = ref(false);

const noneOption = { value: "", label: "– keine –" };
const selectFormGroupOptions = [
  noneOption,
  ...useSelectFormGroupOptions(registration.event?.groups ?? []),
];
const selectFormSlotOptions = [
  noneOption,
  ...useSelectFormSlotOptions(registration.event?.slots ?? []),
];

const selectedSlot = computed(() =>
  registration.event?.slots?.find(
    (slot) => slot.id === Number(registrationForm.value.slot_id),
  ),
);

const hasRequirements = computed(
  () =>
    !!registration.event?.has_requirements ||
    !!selectedSlot.value?.has_requirements,
);

const parseSchema = (form: unknown): FormKitSchemaNode[] => {
  if (!form) {
    return [];
  }
  if (typeof form !== "string") {
    return form as FormKitSchemaNode[];
  }
  try {
    return JSON.parse(form) as FormKitSchemaNode[];
  } catch {
    return [];
  }
};

const formSchema = computed(() => [
  ...parseSchema(registration.event?.form),
  ...parseSchema(selectedSlot.value?.form),
]);

/**
 * Responses that are not covered by the current event or slot form
 */
const unknownFormResponses = computed(() => {
  const schemaNames = formSchema.value.map(
    (node) => (node as { name?: string }).name,
  );

  return Object.entries(initialFormResponses).filter(
    ([key]) => !schemaNames.includes(key),
  );
});

const queueStatus = computed(() => {
  if (registration.queue_position && registration.queue_position > 0) {
    return {
      text: `Warteliste: ${registration.queue_position}`,
      classes: "bg-yellow-500 dark:bg-yellow-700",
    };
  }
  if (registration.queue_position === -1) {
    return {
      text: "Wartet auf Zuteilung",
      classes: "bg-red-500 dark:bg-red-700",
    };
  }
  return { text: "Angemeldet", classes: "bg-green-500 dark:bg-green-700" };
});

const formatValue = (value: unknown) =>
  typeof value === "object" ? JSON.stringify(value) : String(value);

const toNullableId = (value: unknown) =>
  value === "" || value === null || value === undefined ? null : Number(value);

const requestHeaders = () => ({
  "Content-Type": "application/json",
  Accept: "application/json",
  "X-CSRF-TOKEN":
    document
      .querySelector("meta[name='csrf-token']")
      ?.getAttribute("content") || "",
});

const submitHandler = async () => {
  message.value = null;

  const queuePosition = registrationForm.value.queue_position;

  const response = await fetch(
    `/api/users/${userId}/registrations/${registration.id}`,
    {
      method: "PUT",
      credentials: "include",
      headers: requestHeaders(),
      body: JSON.stringify({
        ...registrationForm.value,
        form_responses: {
          ...initialFormResponses,
          ...registrationForm.value.form_responses,
        },
        group_id: toNullableId(registrationForm.value.group_id),
        slot_id: toNullableId(registrationForm.value.slot_id),
        queue_position:
          queuePosition === undefined ||
          queuePosition === null ||
          Number.isNaN(Number(queuePosition)) ||
          String(queuePosition) === ""
            ? null
            : Number(queuePosition),
        drinks_alcohol: registration.event?.consider_alcohol
          ? registrationForm.value.drinks_alcohol
          : registration.drinks_alcohol,
        fulfils_requirements: hasRequirements.value
          ? registrationForm.value.fulfils_requirements
          : registration.fulfils_requirements,
      }),
    },
  );

  if (!response.ok) {
    const data = await response.json().catch(() => ({}));
    message.value = {
      type: "error",
      text: data.message ?? "Die Anmeldung konnte nicht gespeichert werden.",
    };
    return;
  }

  message.value = { type: "success", text: "Anmeldung gespeichert." };
  emits("updated");
};

const deleteHandler = async () => {
  if (!confirmDelete.value) {
    confirmDelete.value = true;
    return;
  }

  const response = await fetch(
    `/api/users/${userId}/registrations/${registration.id}`,
    {
      method: "DELETE",
      credentials: "include",
      headers: requestHeaders(),
    },
  );

  confirmDelete.value = false;

  if (!response.ok) {
    message.value = {
      type: "error",
      text: "Die Anmeldung konnte nicht gelöscht werden.",
    };
    return;
  }

  emits("deleted");
};
</script>
