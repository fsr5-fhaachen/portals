<template>
  <FormKit
    type="form"
    :id="formId"
    :actions="false"
    v-model="form"
    @submit="submit"
  >
    <FormContainer>
      <FormRow>
        <FormKit
          type="text"
          name="name"
          label="Name"
          placeholder="z. B. Löten für Einsteiger"
          validation="required|length:1,255"
        />
      </FormRow>
      <FormRow>
        <FormKit type="textarea" name="description" label="Beschreibung" />
      </FormRow>

      <UiMessage
        v-if="!blocks.length"
        type="warning"
        message="Lege zuerst Zeitblöcke an, damit der Workshop stattfinden kann."
      />
      <div
        v-for="block in blocks"
        :key="block.id"
        class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700"
      >
        <FormKit
          type="checkbox"
          :label="`Findet in ${block.name} statt`"
          :ignore="true"
          v-model="sessions[block.id].offered"
        />
        <UiMessage
          v-if="!sessions[block.id].offered && removalWarning(block.id)"
          type="warning"
          :message="`Der Termin wird beim Speichern gelöscht. ${removalWarning(block.id)}`"
        />
        <!-- not a FormRow, its extra margin as last row is meant for the submit button -->
        <div
          v-if="sessions[block.id].offered"
          class="flex flex-col gap-6 md:flex-row"
        >
          <FormKit
            type="text"
            label="Ort"
            placeholder="z. B. Raum 101"
            :ignore="true"
            v-model="sessions[block.id].location"
          />
          <FormKit
            type="number"
            label="Minimum"
            number="integer"
            min="0"
            validation="required|min:0"
            :ignore="true"
            v-model="sessions[block.id].minimum_participants"
          />
          <FormKit
            type="number"
            label="Maximum"
            placeholder="unbegrenzt"
            number="integer"
            min="1"
            :ignore="true"
            v-model="sessions[block.id].maximum_participants"
          />
        </div>
      </div>

      <FormRow>
        <FormKit
          type="submit"
          :label="workshop ? 'Workshop speichern' : 'Workshop anlegen'"
        />
        <AppButton
          v-if="workshop"
          theme="gray"
          class="mb-1 flex-1 text-center"
          @click="emit('cancel')"
        >
          Abbrechen
        </AppButton>
      </FormRow>
    </FormContainer>
  </FormKit>
</template>

<script setup lang="ts">
import { PropType, ref, useId, watch } from "vue";
import { router } from "@inertiajs/vue3";

type SessionForm = {
  offered: boolean;
  location: string;
  minimum_participants: number;
  maximum_participants: number | null | "";
};

const props = defineProps({
  baseUrl: {
    type: String,
    required: true,
  },
  blocks: {
    type: Array as PropType<App.Models.WorkshopBlock[]>,
    required: true,
  },
  workshop: {
    type: Object as PropType<App.Models.Workshop | null>,
    default: null,
  },
  isPublished: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits<{
  saved: [];
  cancel: [];
}>();

const formId = `workshop-form-${useId()}`;

const emptyForm = () => ({
  name: props.workshop?.name ?? "",
  description: props.workshop?.description ?? "",
});

// a session per block, a new workshop takes place in every block
const emptySessions = () =>
  Object.fromEntries(
    props.blocks.map((block) => {
      const session = props.workshop?.sessions?.find(
        (session) => session.workshop_block_id === block.id,
      );

      return [
        block.id,
        {
          offered: props.workshop ? !!session : true,
          location: session?.location ?? "",
          minimum_participants: session?.minimum_participants ?? 0,
          maximum_participants: session?.maximum_participants ?? null,
        } as SessionForm,
      ];
    }),
  );

// warning if the existing session of the block has participants, which lose their place when the session is removed
const removalWarning = (blockId: number) =>
  useWorkshopChangeWarning(
    {
      assigned: props.workshop?.sessions?.find(
        (session) => session.workshop_block_id === blockId,
      )?.assignments_count,
    },
    props.isPublished,
  );

const form = ref(emptyForm());
const sessions = ref<Record<number, SessionForm>>(emptySessions());

// blocks can be added while the form is open
watch(
  () => props.blocks,
  () => {
    sessions.value = { ...emptySessions(), ...sessions.value };
  },
);

const submit = () =>
  new Promise<void>((resolve) => {
    router.post(
      props.workshop
        ? `${props.baseUrl}/workshop/${props.workshop.id}`
        : `${props.baseUrl}/workshop`,
      {
        ...form.value,
        sessions: props.blocks
          .filter((block) => sessions.value[block.id].offered)
          .map((block) => ({
            workshop_block_id: block.id,
            location: sessions.value[block.id].location || null,
            minimum_participants:
              sessions.value[block.id].minimum_participants || 0,
            maximum_participants:
              sessions.value[block.id].maximum_participants || null,
          })),
      },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          if (!props.workshop) {
            form.value = emptyForm();
            sessions.value = emptySessions();
          }
          emit("saved");
        },
        onFinish: () => resolve(),
      },
    );
  });
</script>
