<template>
  <div class="flex flex-col gap-3">
    <p
      v-if="!visibleAudits.length"
      class="text-sm text-gray-500 dark:text-gray-400"
    >
      Keine Änderungen protokolliert.
    </p>

    <div
      v-for="audit in visibleAudits"
      :key="audit.id"
      class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700"
    >
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span
            :class="eventClasses[audit.event] ?? 'bg-slate-500'"
            class="rounded-md px-2 py-0.5 text-xs text-white"
          >
            {{ eventLabels[audit.event] ?? audit.event }}
          </span>
          <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ audit.label }}
          </span>
        </div>
        <div class="flex gap-1 text-xs text-gray-500 dark:text-gray-400">
          <UiDateTimeString :value="audit.created_at" />
          <span>· {{ audit.causer ?? "System" }}</span>
        </div>
      </div>

      <dl
        v-if="changedAttributes(audit).length"
        class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-[max-content_1fr]"
      >
        <template
          v-for="attribute in changedAttributes(audit)"
          :key="attribute"
        >
          <dt class="text-gray-600 dark:text-gray-400">
            {{ attributeLabels[attribute] ?? attribute }}
          </dt>
          <dd class="break-all text-gray-900 dark:text-gray-100">
            <template v-if="attribute in audit.old_values">
              <span class="text-red-600 line-through dark:text-red-400">{{
                formatValue(audit.old_values[attribute], attribute)
              }}</span>
              <span v-if="attribute in audit.new_values"> → </span>
            </template>
            <span
              v-if="attribute in audit.new_values"
              class="text-green-700 dark:text-green-400"
              >{{ formatValue(audit.new_values[attribute], attribute) }}</span
            >
          </dd>
        </template>
      </dl>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";

type UserAudit = {
  id: number;
  event: string;
  type: string;
  label: string;
  old_values: Record<string, unknown>;
  new_values: Record<string, unknown>;
  causer: string | null;
  created_at: string;
};

const props = defineProps({
  audits: {
    type: Array as PropType<UserAudit[]>,
    required: true,
  },
});

const eventLabels: Record<string, string> = {
  created: "Erstellt",
  updated: "Geändert",
  deleted: "Gelöscht",
  restored: "Wiederhergestellt",
};

const eventClasses: Record<string, string> = {
  created: "bg-green-500 dark:bg-green-700",
  updated: "bg-orange-500 dark:bg-orange-700",
  deleted: "bg-red-500 dark:bg-red-700",
  restored: "bg-blue-500 dark:bg-blue-700",
};

const attributeLabels: Record<string, string> = {
  firstname: "Vorname",
  lastname: "Nachname",
  email: "E-Mail",
  course_id: "Studiengang",
  avatar: "Avatar",
  is_disabled: "Deaktiviert",
  event_id: "Event",
  user_id: "User",
  group_id: "Gruppe",
  slot_id: "Slot",
  is_present: "Anwesend",
  drinks_alcohol: "Trinkt Alkohol",
  fulfils_requirements: "Erfüllt Voraussetzungen",
  queue_position: "Wartelistenposition",
  form_responses: "Formularantworten",
};

/**
 * Attributes whose displayed value really changed (e.g. 0 → false is no change)
 */
const changedAttributes = (audit: UserAudit) =>
  [
    ...new Set([
      ...Object.keys(audit.old_values),
      ...Object.keys(audit.new_values),
    ]),
  ].filter(
    (attribute) =>
      audit.event !== "updated" ||
      formatValue(audit.old_values[attribute], attribute) !==
        formatValue(audit.new_values[attribute], attribute),
  );

const visibleAudits = computed(() =>
  props.audits.filter(
    (audit) => audit.event !== "updated" || changedAttributes(audit).length,
  ),
);

const booleanAttributes = [
  "is_disabled",
  "is_present",
  "drinks_alcohol",
  "fulfils_requirements",
];

/**
 * Decode JSON strings (e.g. double encoded form responses) for display
 */
const decodeValue = (value: unknown): unknown => {
  if (typeof value === "string" && /^[[{"]/.test(value)) {
    try {
      return decodeValue(JSON.parse(value));
    } catch {
      return value;
    }
  }
  return value;
};

const formatValue = (value: unknown, attribute: string) => {
  value = decodeValue(value);

  if (value === null || value === undefined || value === "") {
    return "–";
  }
  if (typeof value === "boolean" || booleanAttributes.includes(attribute)) {
    return value && value !== "0" ? "Ja" : "Nein";
  }
  if (typeof value === "object") {
    return JSON.stringify(value);
  }
  return String(value);
};
</script>
