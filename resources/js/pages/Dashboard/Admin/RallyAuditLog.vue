<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }} · Audit-Log</template>

    <CardContainer>
      <CardBase v-if="audits.length === 0">
        <UiMessage message="Für dieses Event wurden noch keine Änderungen protokolliert." />
      </CardBase>

      <CardBase v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr class="text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                <th class="py-2 pr-4">Zeitpunkt</th>
                <th class="py-2 pr-4">Tutor</th>
                <th class="py-2 pr-4">Aktion</th>
                <th class="py-2 pr-4">Objekt</th>
                <th class="py-2 pr-4">Änderungen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="audit in audits"
                :key="audit.id"
                class="text-sm text-gray-900 dark:text-gray-100"
              >
                <td class="whitespace-nowrap py-2 pr-4">
                  <UiDateTimeString :value="audit.created_at" withClockSuffix />
                </td>
                <td class="whitespace-nowrap py-2 pr-4">
                  {{ audit.user ? audit.user.firstname + " " + audit.user.lastname : "—" }}
                </td>
                <td class="whitespace-nowrap py-2 pr-4">
                  {{ actionLabel(audit.event) }}
                </td>
                <td class="whitespace-nowrap py-2 pr-4">
                  {{ objectLabel(audit.auditable_type) }} #{{ audit.auditable_id }}
                </td>
                <td class="py-2 pr-4">
                  <ul class="space-y-0.5">
                    <li v-for="key in changedKeys(audit)" :key="key">
                      <strong>{{ key }}</strong>:
                      {{ audit.old_values?.[key] ?? "—" }} →
                      {{ audit.new_values?.[key] ?? "—" }}
                    </li>
                  </ul>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardBase>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  audits: {
    type: Array as PropType<Array<any>>,
    required: true,
  },
});

function actionLabel(action: string): string {
  return (
    { created: "Angelegt", updated: "Geändert", deleted: "Gelöscht" }[
      action
    ] || action
  );
}

function objectLabel(auditableType: string): string {
  return (
    {
      "App\\Models\\Stop": "Duell/Station-Termin",
      "App\\Models\\Station": "Station",
      "App\\Models\\StationTutor": "Stationstutor-Zuordnung",
      "App\\Models\\GroupTutor": "Gruppentutor-Zuordnung",
    }[auditableType] || auditableType
  );
}

function changedKeys(audit: any): string[] {
  const keys = new Set([
    ...Object.keys(audit.old_values || {}),
    ...Object.keys(audit.new_values || {}),
  ]);
  return Array.from(keys);
}
</script>
