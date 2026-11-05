<template>
  <LayoutDashboardContent>
    <template #title>Workshops</template>
    <template #subtitle>{{ event.name }}</template>

    <CardContainer>
      <CardBase>
        <FormContainer>
          <div class="flex flex-wrap items-center justify-between gap-3">
            <UiH2>Einteilung</UiH2>
            <span
              :class="
                event.workshops_published_at
                  ? 'bg-fhac-mint-dark'
                  : 'bg-gray-500'
              "
              class="rounded-md px-2 py-1 text-sm font-medium text-white"
            >
              {{
                event.workshops_published_at
                  ? "Veröffentlicht"
                  : "Nicht veröffentlicht"
              }}
            </span>
          </div>

          <UiDl>
            <UiDt>Anmeldungen</UiDt>
            <UiDd>{{ registrations.length }}</UiDd>
            <template
              v-for="(count, priority) in summary.priorities"
              :key="priority"
            >
              <UiDt>Prio {{ priority }}</UiDt>
              <UiDd>{{ count }}</UiDd>
            </template>
            <UiDt>Ohne Wunsch-Workshop</UiDt>
            <UiDd>{{ summary.withoutPriority }}</UiDd>
            <UiDt>Ohne Workshop</UiDt>
            <UiDd>{{ summary.unassigned }}</UiDd>
            <UiDt>Ausgefallene Termine</UiDt>
            <UiDd>{{ summary.cancelledSessions }}</UiDd>
          </UiDl>

          <FormRow>
            <AppButton
              :disabled="!!event.workshops_published_at"
              theme="danger"
              class="flex-1 text-center"
              @click="showRun = true"
            >
              Einteilung starten
            </AppButton>
            <AppButton
              v-if="!event.workshops_published_at"
              class="flex-1 text-center"
              @click="post('publish')"
            >
              Veröffentlichen
            </AppButton>
            <AppButton
              v-else
              theme="gray"
              class="flex-1 text-center"
              @click="post('unpublish')"
            >
              Veröffentlichung zurücknehmen
            </AppButton>
          </FormRow>
        </FormContainer>
      </CardBase>

      <CardBase>
        <FormKit
          type="form"
          id="workshop-settings"
          :actions="false"
          v-model="settings"
          @submit="post('settings', settings)"
        >
          <FormContainer>
            <UiH2>Einstellungen</UiH2>
            <UiMessage
              v-if="event.workshop_priority_count < blocks.length"
              type="warning"
              message="Es gibt weniger Prioritäten als Zeitblöcke. Niemand kann in jedem Block einen Workshop bekommen."
            />
            <UiMessage
              v-if="workshops.length < event.workshop_priority_count"
              type="warning"
              :message="`Es gibt weniger Workshops als Prioritäten. Die Anmeldung ist erst möglich, wenn es mindestens ${event.workshop_priority_count} Workshops gibt.`"
            />
            <FormRow>
              <FormKit
                type="number"
                name="workshop_priority_count"
                label="Anzahl der Prioritäten"
                help="Mehr Prioritäten als Zeitblöcke sind Ersatzwünsche. Nach der ersten Anmeldung nicht mehr änderbar."
                number="integer"
                min="1"
                max="20"
                validation="required|min:1|max:20"
                :disabled="registrations.length > 0"
              />
            </FormRow>
            <FormRow>
              <FormKit
                type="submit"
                label="Speichern"
                :disabled="registrations.length > 0"
              />
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <CardBase>
        <FormContainer>
          <UiH2>Zeitblöcke</UiH2>
          <WorkshopBlockManager
            :baseUrl="baseUrl"
            :blocks="blocks"
            :isPublished="!!event.workshops_published_at"
          />
        </FormContainer>
      </CardBase>

      <CardBase>
        <FormContainer>
          <UiH2>Workshops</UiH2>
          <WorkshopManager
            :baseUrl="baseUrl"
            :blocks="blocks"
            :workshops="workshops"
            :isPublished="!!event.workshops_published_at"
          />
        </FormContainer>
      </CardBase>

      <WorkshopAssignment
        :baseUrl="baseUrl"
        :blocks="blocks"
        :workshops="workshops"
        :registrations="registrations"
      />
    </CardContainer>

    <UiConfirmModal
      v-if="showRun"
      title="Einteilung starten"
      message="Die bisherige Einteilung inklusive aller manuellen Änderungen wird ersetzt. Nur Personen ohne Wünsche (z. B. von einem Admin zugewiesen) behalten ihre Workshops."
      confirmLabel="Einteilen"
      :processing="processing"
      @close="showRun = false"
      @confirm="run"
    />
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
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
  registrations: {
    type: Array as PropType<Workshops.RegistrationRow[]>,
    required: true,
  },
});

const baseUrl = `/dashboard/admin/event/${props.event.id}/workshops`;

const settings = ref({
  workshop_priority_count: props.event.workshop_priority_count,
});
const showRun = ref(false);
const processing = ref(false);

// summary of the current assignment, including the manual changes
const summary = computed(() => {
  const sessions = props.workshops.flatMap(
    (workshop) => workshop.sessions ?? [],
  );
  const workshopIdBySessionId = Object.fromEntries(
    sessions.map((session) => [session.id, session.workshop_id]),
  );

  const priorities: Record<number, number> = {};
  let withoutPriority = 0;
  let unassigned = 0;

  for (const registration of props.registrations) {
    const assignments = registration.assignments as Record<number, number>;

    for (const block of props.blocks) {
      const sessionId = assignments[block.id];
      if (!sessionId) {
        unassigned++;
        continue;
      }

      const priority =
        registration.priorities.indexOf(workshopIdBySessionId[sessionId]) + 1;
      if (priority) {
        priorities[priority] = (priorities[priority] ?? 0) + 1;
      } else {
        withoutPriority++;
      }
    }
  }

  return {
    priorities,
    withoutPriority,
    unassigned,
    cancelledSessions: sessions.filter((session) => session.is_cancelled)
      .length,
  };
});

const post = (path: string, data: Record<string, unknown> = {}) => {
  router.post(`${baseUrl}/${path}`, data, {
    preserveScroll: true,
    preserveState: true,
  });
};

const run = () => {
  processing.value = true;
  router.post(
    `${baseUrl}/run`,
    {},
    {
      preserveScroll: true,
      preserveState: true,
      onFinish: () => {
        processing.value = false;
        showRun.value = false;
      },
    },
  );
};
</script>
