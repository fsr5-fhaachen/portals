<template>
  <div class="flex flex-col gap-6">
    <CardBase v-if="searchable">
      <FormKit type="form" :actions="false" v-model="searchForm">
        <FormContainer>
          <FormRow>
            <UiH2>Filter</UiH2>
          </FormRow>
          <FormRow>
            <FormKit type="text" name="search" label="Suche" />
          </FormRow>
        </FormContainer>
      </FormKit>
    </CardBase>

    <UiMessage v-if="newIds.size > 0" type="info">
      <template #message>
        <span>
          {{
            newIds.size === 1
              ? "Es gibt einen neuen Eintrag."
              : `Es gibt ${newIds.size} neue Einträge.`
          }}
        </span>
        <a
          class="ml-1 font-bold underline hover:cursor-pointer"
          @click="reload()"
        >
          Aktualisieren
        </a>
      </template>
    </UiMessage>

    <div class="px-4 sm:px-6 lg:px-8">
      <!-- no flex container here, otherwise infinite scroll detects the horizontal scroll container as scrollable parent -->
      <div>
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle">
            <div
              v-if="sortValues.length > 0"
              class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8 flex flex-col"
            >
              <span class="font-bold">Sortieren nach:</span>
              <div class="flex gap-3">
                <span
                  v-for="sortValue in sortValues"
                  :key="sortValue.name"
                  @click="sortBy(sortValue.sortKey)"
                  class="dark:bg-border-gray-700 bg-gray-50 bg-opacity-75 pl-2 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                >
                  <div class="flex items-center gap-2 w-full">
                    <span class="break-words">{{ sortValue.text }}</span>
                    <FontAwesomeIcon :icon="getSortIcon(sortValue.sortKey)" />
                  </div>
                </span>
              </div>
            </div>
            <div class="shadow-sm ring-1 ring-black ring-opacity-5">
              <InfiniteScroll
                :data="data"
                :items-element="`#${tbodyId}`"
                preserve-url
                only-next
              >
                <table
                  class="min-w-full border-separate"
                  style="border-spacing: 0"
                >
                  <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                      <th
                        v-for="column in columns"
                        :key="column.name"
                        scope="col"
                        :class="[
                          column.sortKey ? 'hover:cursor-pointer' : '',
                          'dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8',
                        ]"
                        @click="column.sortKey && sortBy(column.sortKey)"
                      >
                        <div class="flex items-center gap-2 w-full">
                          <span class="break-words">{{ column.header }}</span>
                          <FontAwesomeIcon
                            v-if="column.sortKey"
                            :icon="getSortIcon(column.sortKey)"
                          />
                        </div>
                      </th>
                      <th
                        v-if="functions.length > 0"
                        scope="col"
                        class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8"
                      >
                        <span class="sr-only">Funktionen</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody :id="tbodyId" class="bg-white dark:bg-gray-800">
                    <tr
                      v-for="(element, eIndex) in rows"
                      :key="idFunction(element)"
                      :class="rowClass(element)"
                    >
                      <td
                        v-for="column in columns"
                        :key="column.name"
                        :class="[
                          eIndex !== rows.length - 1
                            ? 'border-b border-gray-200 dark:border-gray-700'
                            : '',
                          'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8',
                        ]"
                      >
                        <span v-html="column.valueFunction(element)"></span>
                      </td>
                      <td
                        v-if="functions.length > 0"
                        :class="[
                          eIndex !== rows.length - 1
                            ? 'border-b border-gray-200 dark:border-gray-700'
                            : '',
                          'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                        ]"
                      >
                        <div class="flex gap-4">
                          <template v-for="func in functions" :key="func.name">
                            <AppLink
                              v-if="func instanceof TableLink"
                              :href="(func as TableLink).linkFunction(element)"
                              :theme="(func as TableLink).theme"
                            >
                              {{ (func as TableLink).text }}
                            </AppLink>
                            <AppButton
                              v-if="func instanceof TableButton"
                              :theme="(func as TableButton).theme"
                              :disabled="
                                (func as TableButton).disabledFunction(element)
                              "
                              @click="
                                (func as TableButton).buttonFunction(element)
                              "
                            >
                              {{ (func as TableButton).text }}
                            </AppButton>
                            <AppButton
                              v-if="func instanceof TableStateButton"
                              :theme="
                                getButtonState(
                                  func as TableStateButton,
                                  element,
                                ).theme
                              "
                              :disabled="
                                getButtonState(
                                  func as TableStateButton,
                                  element,
                                ).disabledFunction(element)
                              "
                              @click="
                                getButtonState(
                                  func as TableStateButton,
                                  element,
                                ).buttonFunction(element)
                              "
                            >
                              {{
                                getButtonState(
                                  func as TableStateButton,
                                  element,
                                ).text
                              }}
                            </AppButton>
                          </template>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!rows.length">
                      <td
                        :colspan="columns.length + (functions.length ? 1 : 0)"
                        class="py-4 pl-4 pr-3 text-sm text-gray-500 dark:text-gray-400 sm:pl-6 lg:pl-8"
                      >
                        Keine Einträge gefunden.
                      </td>
                    </tr>
                  </tbody>
                </table>

                <template #loading>
                  <div
                    class="flex items-center justify-center gap-2 bg-white py-4 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                  >
                    <div
                      class="h-4 w-4 animate-pulse rounded-full bg-gray-200 dark:bg-gray-700"
                    ></div>
                    Lade weitere Einträge …
                  </div>
                </template>
              </InfiniteScroll>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useId, watch } from "vue";
import { InfiniteScroll, router, usePage } from "@inertiajs/vue3";
import { TableColumn } from "../../types/table-column";
import { TableFunction } from "../../types/table-function";
import { TableLink } from "../../types/table-link";
import { TableButton } from "../../types/table-button";
import { TableStateButton } from "../../types/table-state-button";
import { TableSortValue } from "../../types/table-sort-value";

type Row = Record<string, any> & { _version?: number };

const props = defineProps({
  // name of the infinite scroll prop of the page (see app/Helpers/Table)
  data: {
    type: String,
    required: true,
  },
  columns: {
    type: Array<TableColumn>,
    required: true,
  },
  idFunction: {
    type: Function,
    default: (element: any) => element.id,
  },
  functions: {
    type: Array<TableFunction>,
    default: [],
  },
  sortValues: {
    type: Array<TableSortValue>,
    default: [],
  },
  rowClass: {
    type: Function,
    default: () => {
      return {};
    },
  },
  // show a search field (searched on the server)
  searchable: {
    type: Boolean,
    default: false,
  },
  // update the loaded rows periodically with the changes on the server
  live: {
    type: Boolean,
    default: true,
  },
  // interval of the live update
  liveInterval: {
    type: Number,
    default: 2500,
  },
});

const page = usePage();
const tbodyId = `table-${useId()}`;

const getId = (row: Row): number => props.idFunction(row);

// rows of the infinite scroll prop (all loaded pages)
const loadedRows = computed<Row[]>(
  () => (page.props[props.data] as { data?: Row[] } | undefined)?.data ?? [],
);

// local changes of the loaded rows (live update and own actions)
const patchedRows = ref(new Map<number, Row>());
const removedIds = ref(new Set<number>());
const newIds = ref(new Set<number>());

const isNewerOrEqual = (row: Row, otherRow: Row) =>
  (row._version ?? 0) >= (otherRow._version ?? 0);

const rows = computed<Row[]>(() => {
  // rows can be loaded twice (e.g. if rows were added while scrolling), the position of the
  // first occurrence and the newest version of the row is used
  const rowsById = new Map<number, Row>();
  for (const row of loadedRows.value) {
    const id = getId(row);
    const existingRow = rowsById.get(id);
    if (!existingRow || isNewerOrEqual(row, existingRow)) {
      rowsById.set(id, row);
    }
  }

  const result: Row[] = [];
  for (const [id, row] of rowsById) {
    if (removedIds.value.has(id)) {
      continue;
    }

    const patchedRow = patchedRows.value.get(id);
    result.push(
      patchedRow && isNewerOrEqual(patchedRow, row) ? patchedRow : row,
    );
  }

  return result;
});

const clearLocalChanges = () => {
  patchedRows.value = new Map();
  removedIds.value = new Set();
  newIds.value = new Set();
};

/**
 * Get the current query parameters of the page without the page parameter of the infinite scroll.
 */
const getQuery = (): Record<string, string> => {
  const query = Object.fromEntries(
    new URLSearchParams(window.location.search).entries(),
  );
  delete query.page;

  return query;
};

/**
 * Reload the table from the first page with changed query parameters (sorting, search).
 */
const reload = (changes: Record<string, string | null> = {}) => {
  const query = getQuery();
  for (const [key, value] of Object.entries(changes)) {
    if (value === null || value === "") {
      delete query[key];
    } else {
      query[key] = value;
    }
  }

  router.get(window.location.pathname, query, {
    only: [props.data],
    reset: [props.data],
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onSuccess: clearLocalChanges,
  });
};

// sorting (on the server): ascending, descending, unsorted
const initialQuery = getQuery();
const sortKey = ref<string | null>(initialQuery.sort ?? null);
const sortDirection = ref<"asc" | "desc">(
  initialQuery.direction === "desc" ? "desc" : "asc",
);

const sortBy = (key: string) => {
  if (sortKey.value !== key) {
    sortKey.value = key;
    sortDirection.value = "asc";
  } else if (sortDirection.value === "asc") {
    sortDirection.value = "desc";
  } else {
    sortKey.value = null;
    sortDirection.value = "asc";
  }

  reload({
    sort: sortKey.value,
    direction: sortKey.value ? sortDirection.value : null,
  });
};

const getSortIcon = (key: string): Array<string> => {
  if (sortKey.value === key) {
    return sortDirection.value === "asc"
      ? ["fas", "arrow-down-short-wide"]
      : ["fas", "arrow-up-wide-short"];
  }

  return ["fas", "sort"];
};

// search (on the server)
const searchForm = ref({
  search: initialQuery.search ?? "",
});
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
  () => searchForm.value.search,
  (search) => {
    // no reload if the search is already applied (e.g. synced from the url)
    if ((search ?? "") === (getQuery().search ?? "")) {
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
      return;
    }

    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => reload({ search: search ?? null }), 300);
  },
);

// keep sorting and search in sync with the url (e.g. after a redirect to the page without parameters)
watch(
  () => page.url,
  () => {
    const query = getQuery();
    sortKey.value = query.sort ?? null;
    sortDirection.value = query.direction === "desc" ? "desc" : "asc";
    if ((query.search ?? "") !== (searchForm.value.search ?? "")) {
      searchForm.value.search = query.search ?? "";
    }
  },
);

// live update: only the changes of the loaded rows are fetched
let changesSince = Number(
  page.props.serverTime ?? Math.floor(Date.now() / 1000),
);

const fetchChanges = async () => {
  const url = new URL(window.location.href);
  url.searchParams.delete("page");
  url.searchParams.set("changes_since", String(changesSince));
  url.searchParams.set(
    "loaded_ids",
    loadedRows.value.map((row) => getId(row)).join(","),
  );

  const response = await fetch(url, {
    method: "GET",
    credentials: "include",
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    return;
  }

  const changes: {
    time: number;
    rows: Row[];
    removed_ids: number[];
    new_ids: number[];
  } = await response.json();

  changesSince = changes.time;

  if (changes.rows.length) {
    const nextPatchedRows = new Map(patchedRows.value);
    changes.rows.forEach((row) => nextPatchedRows.set(getId(row), row));
    patchedRows.value = nextPatchedRows;
  }

  if (changes.removed_ids.length) {
    removedIds.value = new Set([...removedIds.value, ...changes.removed_ids]);
  }

  const loadedIds = new Set(loadedRows.value.map((row) => getId(row)));
  const unknownNewIds = changes.new_ids.filter((id) => !loadedIds.has(id));
  if (unknownNewIds.length) {
    newIds.value = new Set([...newIds.value, ...unknownNewIds]);
  }
};

const polling = props.live
  ? usePolling(fetchChanges, props.liveInterval)
  : null;

/**
 * Update fields of a loaded row immediately (e.g. after an own action).
 */
const patchRow = (id: number, fields: Row) => {
  const currentRow = rows.value.find((row) => getId(row) === id);
  if (!currentRow) {
    return;
  }

  const nextPatchedRows = new Map(patchedRows.value);
  nextPatchedRows.set(id, { ...currentRow, ...fields });
  patchedRows.value = nextPatchedRows;
};

/**
 * Remove a loaded row immediately (e.g. after an own action).
 */
const removeRow = (id: number) => {
  removedIds.value = new Set([...removedIds.value, id]);
};

/**
 * Fetch the changes of the loaded rows immediately.
 */
const refresh = () => polling?.run();

const getButtonState = (
  stateButton: TableStateButton,
  element: any,
): TableButton => {
  for (const state of stateButton.states) {
    if (state.condition(element)) {
      return state.button;
    }
  }

  return stateButton.defaultState;
};

defineExpose({
  rows,
  patchRow,
  removeRow,
  refresh,
  reload,
});
</script>
