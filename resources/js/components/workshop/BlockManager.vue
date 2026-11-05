<template>
  <div class="space-y-6">
    <FormKit
      type="form"
      id="workshop-block-form"
      :actions="false"
      v-model="form"
      @submit="saveBlock"
    >
      <FormContainer>
        <FormRow>
          <FormKit
            type="text"
            name="name"
            label="Name"
            placeholder="z. B. Block 1"
            validation="required|length:1,255"
          />
        </FormRow>
        <FormRow>
          <FormKit
            type="datetime-local"
            name="starts_at"
            label="Beginn"
            validation="required"
          />
          <FormKit type="datetime-local" name="ends_at" label="Ende" />
        </FormRow>
        <FormRow>
          <FormKit
            type="submit"
            :label="
              editingBlockId ? 'Zeitblock speichern' : 'Zeitblock anlegen'
            "
          />
          <AppButton
            v-if="editingBlockId"
            theme="gray"
            class="mb-1 flex-1 text-center"
            @click="resetForm"
          >
            Abbrechen
          </AppButton>
        </FormRow>
      </FormContainer>
    </FormKit>

    <UiMessage
      v-if="!blocks.length"
      type="info"
      message="Noch keine Zeitblöcke angelegt."
    />
    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <li
        v-for="block in blocks"
        :key="block.id"
        class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center"
      >
        <div class="grow text-gray-900 dark:text-gray-100">
          <strong>{{ block.name }}</strong>
          <UiDateTimeString
            :value="block.starts_at"
            :withClockSuffix="true"
            class="text-sm text-gray-500 dark:text-gray-400"
          />
        </div>
        <div class="flex gap-2">
          <AppButton theme="gray" @click="startEditing(block)">
            Bearbeiten
          </AppButton>
          <AppButton theme="danger" @click="blockToDelete = block">
            Löschen
          </AppButton>
        </div>
      </li>
    </ul>

    <UiConfirmModal
      v-if="blockToDelete"
      title="Zeitblock löschen"
      :message="deleteMessage"
      :processing="processing"
      @close="blockToDelete = null"
      @confirm="deleteBlock"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  baseUrl: {
    type: String,
    required: true,
  },
  blocks: {
    type: Array as PropType<App.Models.WorkshopBlock[]>,
    required: true,
  },
  isPublished: {
    type: Boolean,
    default: false,
  },
});

const emptyForm = () => ({ name: "", starts_at: "", ends_at: "" });

const form = ref(emptyForm());
const editingBlockId = ref<number | null>(null);
const blockToDelete = ref<App.Models.WorkshopBlock | null>(null);
const processing = ref(false);

const deleteMessage = computed(() => {
  const block = blockToDelete.value;
  if (!block) {
    return "";
  }

  return [
    `Soll der Zeitblock „${block.name}“ gelöscht werden? Alle Termine und Einteilungen in diesem Block werden ebenfalls gelöscht.`,
    useWorkshopChangeWarning(
      { assigned: block.assignments_count },
      props.isPublished,
    ),
  ].join(" ");
});

// the server stores utc, the input shows the local time of the browser
const toLocalInputValue = (value: string | null) => {
  if (!value) {
    return "";
  }

  const date = new Date(value);
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

  return date.toISOString().slice(0, 16);
};
const toUtc = (value: string) => (value ? new Date(value).toISOString() : null);

const resetForm = () => {
  form.value = emptyForm();
  editingBlockId.value = null;
};

const startEditing = (block: App.Models.WorkshopBlock) => {
  editingBlockId.value = block.id;
  form.value = {
    name: block.name,
    starts_at: toLocalInputValue(block.starts_at),
    ends_at: toLocalInputValue(block.ends_at),
  };
};

const saveBlock = () =>
  new Promise<void>((resolve) => {
    router.post(
      editingBlockId.value
        ? `${props.baseUrl}/block/${editingBlockId.value}`
        : `${props.baseUrl}/block`,
      {
        name: form.value.name,
        starts_at: toUtc(form.value.starts_at),
        ends_at: toUtc(form.value.ends_at),
      },
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: resetForm,
        onFinish: () => resolve(),
      },
    );
  });

const deleteBlock = () => {
  if (!blockToDelete.value) {
    return;
  }

  processing.value = true;
  router.delete(`${props.baseUrl}/block/${blockToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
      blockToDelete.value = null;
    },
  });
};
</script>
