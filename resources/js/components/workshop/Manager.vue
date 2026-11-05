<template>
  <div class="space-y-6">
    <UiMessage
      v-for="(error, field) in errors"
      :key="field"
      type="error"
      :message="error"
    />

    <WorkshopForm :baseUrl="baseUrl" :blocks="blocks" />

    <UiMessage
      v-if="!workshops.length"
      type="info"
      message="Noch keine Workshops angelegt."
    />
    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
      <li v-for="workshop in workshops" :key="workshop.id" class="py-4">
        <WorkshopForm
          v-if="editingWorkshopId === workshop.id"
          :baseUrl="baseUrl"
          :blocks="blocks"
          :workshop="workshop"
          :isPublished="isPublished"
          @saved="editingWorkshopId = null"
          @cancel="editingWorkshopId = null"
        />
        <div v-else class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="grow">
            <p class="font-medium text-gray-900 dark:text-gray-100">
              {{ workshop.name }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              <template v-if="workshop.sessions?.length">
                <span
                  v-for="(session, index) in workshop.sessions"
                  :key="session.id"
                >
                  <template v-if="index">, </template>
                  {{ blockName(session.workshop_block_id) }}
                  ({{ session.minimum_participants }}–{{
                    session.maximum_participants ?? "∞"
                  }}<template v-if="session.location"
                    >, {{ session.location }}</template
                  >)
                </span>
              </template>
              <template v-else>Findet in keinem Zeitblock statt.</template>
            </p>
          </div>
          <div class="flex gap-2">
            <AppButton theme="gray" @click="editingWorkshopId = workshop.id">
              Bearbeiten
            </AppButton>
            <AppButton theme="danger" @click="workshopToDelete = workshop">
              Löschen
            </AppButton>
          </div>
        </div>
      </li>
    </ul>

    <UiConfirmModal
      v-if="workshopToDelete"
      title="Workshop löschen"
      :message="deleteMessage"
      :processing="processing"
      @close="workshopToDelete = null"
      @confirm="deleteWorkshop"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
  baseUrl: {
    type: String,
    required: true,
  },
  blocks: {
    type: Array as PropType<App.Models.WorkshopBlock[]>,
    required: true,
  },
  workshops: {
    type: Array as PropType<App.Models.Workshop[]>,
    required: true,
  },
  isPublished: {
    type: Boolean,
    default: false,
  },
});

const editingWorkshopId = ref<number | null>(null);
const workshopToDelete = ref<App.Models.Workshop | null>(null);
const processing = ref(false);

const page = usePage();
const errors = computed(
  () => (page.props.errors ?? {}) as Record<string, string>,
);

const deleteMessage = computed(() => {
  const workshop = workshopToDelete.value;
  if (!workshop) {
    return "";
  }

  return [
    `Soll der Workshop „${workshop.name}“ gelöscht werden? Seine Termine, Einteilungen und die Prioritäten der Teilnehmenden für diesen Workshop werden ebenfalls gelöscht.`,
    useWorkshopChangeWarning(
      {
        assigned: (workshop.sessions ?? []).reduce(
          (sum, session) => sum + (session.assignments_count ?? 0),
          0,
        ),
        prioritized: workshop.priorities_count,
      },
      props.isPublished,
    ),
  ].join(" ");
});

const blockName = (blockId: number) =>
  props.blocks.find((block) => block.id === blockId)?.name ?? "";

const deleteWorkshop = () => {
  if (!workshopToDelete.value) {
    return;
  }

  processing.value = true;
  router.delete(`${props.baseUrl}/workshop/${workshopToDelete.value.id}`, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false;
      workshopToDelete.value = null;
    },
  });
};
</script>
