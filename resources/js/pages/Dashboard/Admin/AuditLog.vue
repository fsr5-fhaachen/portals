<template>
  <LayoutDashboardContent>
    <template #title>Audit-Log</template>
    <template #subtitle>Wer hat wann was geändert?</template>

    <CardContainer>
      <CardBase>
        <FormKit
          type="form"
          id="audit-log-filter"
          :actions="false"
          v-model="filterForm"
          @submit="applyFilters"
        >
          <FormContainer>
            <FormRow>
              <UiH2>Filter</UiH2>
            </FormRow>
            <FormRow>
              <FormKit
                type="select"
                name="user_id"
                label="Nutzer"
                :options="userOptions"
              />
              <FormKit
                type="select"
                name="action"
                label="Aktion"
                :options="[{ value: '', label: 'Alle' }, ...actions]"
              />
            </FormRow>
            <FormRow>
              <FormKit
                type="select"
                name="subject_type"
                label="Objekt"
                :options="[{ value: '', label: 'Alle' }, ...subjectTypes]"
              />
              <FormKit
                type="number"
                name="subject_id"
                label="Objekt-ID"
                min="1"
                placeholder="z. B. 42"
              />
            </FormRow>
            <FormRow>
              <FormKit type="date" name="from" label="Von" />
              <FormKit type="date" name="to" label="Bis" />
            </FormRow>
            <FormRow>
              <FormKit
                type="checkbox"
                name="staff_only"
                label="Nur Tutoren/Admins"
                help="Blendet Aktionen von Studierenden aus, z. B. eigene Anmeldungen."
              />
            </FormRow>
            <FormRow>
              <FormKit type="submit" label="Filtern" />
              <AppButton
                theme="gray"
                class="flex-1 text-center"
                @click="resetFilters"
              >
                Filter zurücksetzen
              </AppButton>
            </FormRow>
          </FormContainer>
        </FormKit>
      </CardBase>

      <UiMessage
        v-if="!audits.data.length"
        type="info"
        message="Keine Einträge für diese Filter gefunden."
      />

      <template v-else>
        <UiPagination :paginator="audits" />

        <!-- table from sm upwards -->
        <div
          class="hidden overflow-hidden shadow-sm ring-1 ring-black ring-opacity-5 sm:block"
        >
          <table
            class="min-w-full divide-y divide-gray-300 dark:divide-gray-700"
          >
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th
                  v-for="header in [
                    'Zeit',
                    'Nutzer',
                    'Aktion',
                    'Objekt',
                    'Änderungen',
                  ]"
                  :key="header"
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 first:pl-6 dark:text-gray-300"
                >
                  {{ header }}
                </th>
              </tr>
            </thead>
            <tbody
              class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
            >
              <tr
                v-for="entry in audits.data"
                :key="entry.id"
                class="align-top"
              >
                <td
                  class="whitespace-nowrap py-4 pl-6 pr-3 text-sm text-gray-500 dark:text-gray-400"
                >
                  <UiDateTimeString
                    v-if="entry.created_at"
                    :value="entry.created_at"
                  />
                </td>
                <td class="px-3 py-4 text-sm">
                  <AuditActor :actor="entry.actor" @filter="filterByUser" />
                </td>
                <td class="px-3 py-4 text-sm">
                  <span
                    :class="actionClasses(entry.action)"
                    class="whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium"
                  >
                    {{ entry.action_label }}
                  </span>
                </td>
                <td class="px-3 py-4 text-sm">
                  <AuditSubject :entry="entry" @filter="filterBySubject" />
                </td>
                <td class="px-3 py-4">
                  <AuditChangeList
                    :changes="entry.changes"
                    :action="entry.action"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- cards on mobile -->
        <div class="flex flex-col gap-4 sm:hidden">
          <CardBase v-for="entry in audits.data" :key="entry.id">
            <div class="space-y-3">
              <div class="flex items-center justify-between gap-2">
                <span
                  :class="actionClasses(entry.action)"
                  class="rounded-md px-2 py-0.5 text-xs font-medium"
                >
                  {{ entry.action_label }}
                </span>
                <UiDateTimeString
                  v-if="entry.created_at"
                  :value="entry.created_at"
                  class="text-sm text-gray-500 dark:text-gray-400"
                />
              </div>
              <AuditSubject :entry="entry" @filter="filterBySubject" />
              <AuditActor :actor="entry.actor" @filter="filterByUser" />
              <AuditChangeList
                :changes="entry.changes"
                :action="entry.action"
              />
            </div>
          </CardBase>
        </div>

        <UiPagination :paginator="audits" />
      </template>
    </CardContainer>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { computed, PropType, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  audits: {
    type: Object as PropType<Pagination.Paginator<AuditLog.Entry>>,
    required: true,
  },
  filters: {
    type: Object as PropType<AuditLog.Filters>,
    required: true,
  },
  users: {
    type: Array as PropType<
      Pick<App.Models.User, "id" | "firstname" | "lastname">[]
    >,
    required: true,
  },
  subjectTypes: {
    type: Array as PropType<AuditLog.Option[]>,
    required: true,
  },
  actions: {
    type: Array as PropType<AuditLog.Option[]>,
    required: true,
  },
});

const filterForm = ref({
  user_id: props.filters.user_id ?? "",
  action: props.filters.action ?? "",
  subject_type: props.filters.subject_type ?? "",
  subject_id: props.filters.subject_id ?? "",
  from: props.filters.from ?? "",
  to: props.filters.to ?? "",
  staff_only: props.filters.staff_only,
});

const userOptions = computed(() => [
  { value: "", label: "Alle" },
  ...props.users.map((user) => ({
    value: user.id,
    label: `${user.firstname} ${user.lastname}`,
  })),
]);

const visit = (filters: Record<string, unknown>) => {
  const query: Record<string, string> = {};
  for (const [key, value] of Object.entries(filters)) {
    if (key === "staff_only") {
      query[key] = value ? "1" : "0";
    } else if (value !== "" && value !== null && value !== undefined) {
      query[key] = String(value);
    }
  }

  router.get("/dashboard/admin/audit-log", query, {
    preserveState: true,
    preserveScroll: true,
  });
};

const applyFilters = () => {
  visit(filterForm.value);
};

const resetFilters = () => {
  filterForm.value = {
    user_id: "",
    action: "",
    subject_type: "",
    subject_id: "",
    from: "",
    to: "",
    staff_only: true,
  };
  visit(filterForm.value);
};

const filterByUser = (userId: number) => {
  filterForm.value = {
    ...filterForm.value,
    user_id: userId,
    staff_only: false,
  };
  visit(filterForm.value);
};

const filterBySubject = (subjectType: string, subjectId: number) => {
  filterForm.value = {
    ...filterForm.value,
    subject_type: subjectType,
    subject_id: subjectId,
  };
  visit(filterForm.value);
};

const actionClasses = (action: string) => {
  if (action === "created") {
    return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100";
  }
  if (action === "deleted") {
    return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100";
  }
  if (action === "rolesUpdated") {
    return "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100";
  }
  if (action.endsWith("LoginFailed")) {
    return "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100";
  }
  if (action.endsWith("Login")) {
    return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100";
  }

  return "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100";
};
</script>
