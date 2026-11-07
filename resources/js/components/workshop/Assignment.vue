<template>
  <div class="space-y-6">
    <CardBase v-for="block in blocks" :key="block.id">
      <div class="space-y-4">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <UiH2>{{ block.name }}</UiH2>
          <UiDateTimeString
            :value="block.starts_at"
            :withClockSuffix="true"
            class="text-sm text-gray-500 dark:text-gray-400"
          />
        </div>

        <div
          v-for="session in sessionsOfBlock(block.id)"
          :key="session.id"
          class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
        >
          <button
            type="button"
            class="flex w-full flex-wrap items-center gap-2 text-left"
            @click="toggle(`session-${session.id}`)"
          >
            <span class="grow font-medium text-gray-900 dark:text-gray-100">
              {{ workshopName(session.workshop_id) }}
              <span
                v-if="session.location"
                class="text-sm font-normal text-gray-500 dark:text-gray-400"
              >
                ({{ session.location }})
              </span>
            </span>
            <span
              v-if="session.is_cancelled"
              class="rounded-md bg-red-700 px-2 py-1 text-xs text-white"
            >
              Fällt aus
            </span>
            <span
              :class="
                isOutsideLimits(session)
                  ? 'bg-yellow-600 text-white'
                  : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
              "
              class="rounded-md px-2 py-1 text-xs"
              title="Teilnehmende (Minimum–Maximum)"
            >
              {{ participantsOf(session.id).length }}
              ({{ session.minimum_participants }}–{{
                session.maximum_participants ?? "∞"
              }})
            </span>
          </button>

          <ul
            v-if="openKeys.has(`session-${session.id}`)"
            class="mt-3 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <WorkshopAssignmentRow
              v-for="registration in participantsOf(session.id)"
              :key="registration.id"
              :registration="registration"
              :block="block"
              :options="sessionOptions(block.id, registration)"
              :priority="priorityOf(registration, session.workshop_id)"
              :priorityDetails="priorityDetails(registration, block.id)"
              @assign="assign"
            />
          </ul>
        </div>

        <div
          class="rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600"
        >
          <button
            type="button"
            class="flex w-full items-center gap-2 text-left"
            @click="toggle(`unassigned-${block.id}`)"
          >
            <span class="grow font-medium text-gray-900 dark:text-gray-100">
              Ohne Workshop
            </span>
            <span
              class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
              {{ unassignedOf(block.id).length }}
            </span>
          </button>

          <ul
            v-if="openKeys.has(`unassigned-${block.id}`)"
            class="mt-3 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <WorkshopAssignmentRow
              v-for="registration in unassignedOf(block.id)"
              :key="registration.id"
              :registration="registration"
              :block="block"
              :options="sessionOptions(block.id, registration)"
              :priorityDetails="priorityDetails(registration, block.id)"
              @assign="assign"
            />
          </ul>
        </div>
      </div>
    </CardBase>
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
  workshops: {
    type: Array as PropType<App.Models.Workshop[]>,
    required: true,
  },
  registrations: {
    type: Array as PropType<Workshops.RegistrationRow[]>,
    required: true,
  },
});

// the lists are only rendered when opened, there can be hundreds of registrations per block
const openKeys = ref(new Set<string>());
const toggle = (key: string) => {
  if (openKeys.value.has(key)) {
    openKeys.value.delete(key);
  } else {
    openKeys.value.add(key);
  }
};

const assignedSessionId = (
  registration: Workshops.RegistrationRow,
  blockId: number,
): number | undefined =>
  (registration.assignments as Record<number, number>)[blockId];

// lookups are built once per change, the rows of every block use them for every session
const sessionsByBlockId = computed(() => {
  const sessions: Record<number, App.Models.WorkshopSession[]> = {};
  for (const session of props.workshops.flatMap(
    (workshop) => workshop.sessions ?? [],
  )) {
    (sessions[session.workshop_block_id] ??= []).push(session);
  }

  return sessions;
});

const workshopNameById = computed(
  () =>
    Object.fromEntries(
      props.workshops.map((workshop) => [workshop.id, workshop.name]),
    ) as Record<number, string>,
);

const participantsBySessionId = computed(() => {
  const participants: Record<number, Workshops.RegistrationRow[]> = {};
  for (const registration of props.registrations) {
    for (const sessionId of Object.values(
      registration.assignments as Record<number, number>,
    )) {
      (participants[sessionId] ??= []).push(registration);
    }
  }

  return participants;
});

const sessionsOfBlock = (blockId: number) =>
  sessionsByBlockId.value[blockId] ?? [];

const workshopName = (workshopId: number) =>
  workshopNameById.value[workshopId] ?? "";

const participantsOf = (sessionId: number) =>
  participantsBySessionId.value[sessionId] ?? [];

const unassignedOf = (blockId: number) =>
  props.registrations.filter(
    (registration) => !assignedSessionId(registration, blockId),
  );

const isOutsideLimits = (session: App.Models.WorkshopSession) => {
  const count = participantsOf(session.id).length;

  return (
    !session.is_cancelled &&
    (count < session.minimum_participants ||
      (session.maximum_participants !== null &&
        count > session.maximum_participants))
  );
};

const priorityOf = (
  registration: Workshops.RegistrationRow,
  workshopId: number,
) => {
  const index = registration.priorities.indexOf(workshopId);

  return index === -1 ? null : index + 1;
};

const blockNameById = computed(
  () =>
    Object.fromEntries(
      props.blocks.map((block) => [block.id, block.name]),
    ) as Record<number, string>,
);

const sessionById = computed(
  () =>
    Object.fromEntries(
      props.workshops
        .flatMap((workshop) => workshop.sessions ?? [])
        .map((session) => [session.id, session]),
    ) as Record<number, App.Models.WorkshopSession>,
);

/**
 * Describe all priorities of the registration with their state in the given block, so the admin sees why a wish was
 * not fulfilled (e.g. full, cancelled or not offered in the block).
 */
const priorityDetails = (
  registration: Workshops.RegistrationRow,
  blockId: number,
): string[] => {
  if (!registration.priorities.length) {
    return ["Keine Prios abgegeben (von Hand eingetragen)"];
  }

  const assignedSessions = Object.values(
    registration.assignments as Record<number, number>,
  )
    .map((sessionId) => sessionById.value[sessionId])
    .filter(Boolean);

  return registration.priorities.map((workshopId, index) => {
    const prefix = `${index + 1}. ${workshopName(workshopId)}: `;

    const assignedSession = assignedSessions.find(
      (session) => session.workshop_id === workshopId,
    );
    if (assignedSession) {
      return `${prefix}zugeteilt (${blockNameById.value[assignedSession.workshop_block_id]})`;
    }

    const session = sessionsOfBlock(blockId).find(
      (session) => session.workshop_id === workshopId,
    );
    if (!session) {
      const otherBlocks = props.blocks
        .filter((block) =>
          sessionsOfBlock(block.id).some(
            (session) => session.workshop_id === workshopId,
          ),
        )
        .map((block) => block.name);

      return `${prefix}nicht in diesem Block (nur ${otherBlocks.join(", ") || "keinem Block"})`;
    }

    const count = participantsOf(session.id).length;
    const limit = `${count}/${session.maximum_participants ?? "∞"}`;
    if (session.is_cancelled) {
      return `${prefix}fällt in diesem Block aus`;
    }
    if (
      session.maximum_participants !== null &&
      count >= session.maximum_participants
    ) {
      return `${prefix}in diesem Block voll (${limit})`;
    }

    return `${prefix}in diesem Block frei (${limit})`;
  });
};

// the sessions of the block with the priority of the registration and the current amount of participants
const sessionOptions = (
  blockId: number,
  registration: Workshops.RegistrationRow,
) => [
  { label: "Kein Workshop", value: "" },
  ...sessionsOfBlock(blockId).map((session) => {
    const priority = priorityOf(registration, session.workshop_id);

    return {
      label:
        `${workshopName(session.workshop_id)} (${participantsOf(session.id).length}/${session.maximum_participants ?? "∞"})` +
        (priority ? `, Prio ${priority}` : "") +
        (session.is_cancelled ? ", fällt aus" : ""),
      value: session.id,
    };
  }),
];

const assign = (
  registration: Workshops.RegistrationRow,
  block: App.Models.WorkshopBlock,
  sessionId: number | null,
) => {
  router.post(
    `${props.baseUrl}/assign`,
    {
      registration_id: registration.id,
      workshop_block_id: block.id,
      workshop_session_id: sessionId,
    },
    { preserveScroll: true, preserveState: true },
  );
};
</script>
