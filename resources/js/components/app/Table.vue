<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle">
          <div
            v-if="sortValues.length > 0"
            class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8 flex flex-col"
          >
            <span class="font-bold">Sortieren nach:</span>
            <div class="flex gap-3">
              <span
                v-for="(sortValue, index) in sortValues"
                @click="orderElementsByValue(index)"
                class="dark:bg-border-gray-700 bg-gray-50 bg-opacity-75 pl-2 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
              >
                <div class="flex items-center gap-2 w-full">
                  <span class="break-words">{{ sortValue.text }}</span>
                  <FontAwesomeIcon :icon="getSortValueIcon(index)" />
                </div>
              </span>
            </div>
          </div>
          <div class="shadow-sm ring-1 ring-black ring-opacity-5">
            <table class="min-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    v-for:="(column, index) in columns"
                    :key="column.name"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8 hover:cursor-pointer"
                    @click="orderElementsByColumn(index)"
                  >
                    <div class="flex items-center gap-2 w-full">
                      <span class="break-words">{{ column.header }}</span>
                      <FontAwesomeIcon :icon="getColumnSortIcon(index)" />
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
              <tbody class="bg-white dark:bg-gray-800">
                <tr
                  v-for="(element, eIndex) in elementsOrdered"
                  :key="idFunction(element)"
                  :class="rowClass(element)"
                >
                  <td
                    v-for="(column, cIndex) in columns"
                    :key="column.name"
                    :class="[
                      eIndex !== elementsOrdered.length - 1
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
                      eIndex !== elementsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                    ]"
                  >
                    <div class="flex gap-4">
                      <template
                        v-for="(func, fIndex) in functions"
                        :key="func.name"
                      >
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
                          @click="(func as TableButton).buttonFunction(element)"
                        >
                          {{ (func as TableButton).text }}
                        </AppButton>
                        <AppButton
                          v-if="func instanceof TableStateButton"
                          :theme="
                            getButtonState(func as TableStateButton, element)
                              .value.theme
                          "
                          :disabled="
                            getButtonState(
                              func as TableStateButton,
                              element,
                            ).value.disabledFunction(element)
                          "
                          @click="
                            getButtonState(
                              func as TableStateButton,
                              element,
                            ).value.buttonFunction(element)
                          "
                        >
                          {{
                            getButtonState(func as TableStateButton, element)
                              .value.text
                          }}
                        </AppButton>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableFunction } from "../../types/table-function";
import { TableLink } from "../../types/table-link";
import { TableButton } from "../../types/table-button";
import { TableStateButton } from "../../types/table-state-button";
import { TableSortValue } from "../../types/table-sort-value";

const props = defineProps({
  columns: {
    type: Array<TableColumn>,
    required: true,
  },
  elements: {
    type: Array<Object>,
    required: true,
  },
  idFunction: {
    type: Function,
    required: true,
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
    default: (element: any) => {
      return {};
    },
  },
});

const elementsOrdered = ref<Object[]>(props.elements.slice());

watch(
  () => props.elements,
  (newElements) => {
    if (
      (orderedColumn === -1 && orderedValue === -1) ||
      sortDirection === SortDirection.None
    ) {
      elementsOrdered.value = newElements.slice();
    } else {
      if (orderedColumn >= 0) {
        elementsOrdered.value =
          sortDirection === SortDirection.Ascending
            ? newElements
                .slice()
                .sort((e1, e2) =>
                  props.columns[orderedColumn].compareFunction(e1, e2),
                )
            : newElements
                .slice()
                .sort((e1, e2) =>
                  props.columns[orderedColumn].compareFunction(e2, e1),
                );
      } else if (orderedValue >= 0) {
        elementsOrdered.value =
          sortDirection === SortDirection.Ascending
            ? newElements
                .slice()
                .sort((e1, e2) =>
                  props.sortValues[orderedValue].compareFunction(e1, e2),
                )
            : newElements
                .slice()
                .sort((e1, e2) =>
                  props.sortValues[orderedValue].compareFunction(e2, e1),
                );
      }
    }
  },
);

enum SortDirection {
  None,
  Ascending,
  Descending,
}

let orderedColumn = -1;
let orderedValue = -1;
let sortDirection = SortDirection.None;

function orderElementsByColumn(columnIndex: number): void {
  if (columnIndex == orderedColumn) {
    if (sortDirection == SortDirection.Descending) {
      // undo ordering
      elementsOrdered.value = props.elements.slice();
      orderedColumn = -1;
      sortDirection = SortDirection.None;
      return;
    } else if (sortDirection == SortDirection.Ascending) {
      sortDirection = SortDirection.Descending;
    } else {
      sortDirection = SortDirection.Ascending;
    }
  } else {
    sortDirection = SortDirection.Ascending;
  }

  orderedColumn = columnIndex;
  orderedValue = -1;

  elementsOrdered.value =
    sortDirection == SortDirection.Ascending
      ? props.elements
          .slice()
          .sort((e1, e2) => props.columns[columnIndex].compareFunction(e1, e2))
      : props.elements
          .slice()
          .sort((e1, e2) => props.columns[columnIndex].compareFunction(e2, e1));
}

function orderElementsByValue(sortValueIndex: number): void {
  if (sortValueIndex == orderedValue) {
    if (sortDirection == SortDirection.Descending) {
      // undo ordering
      elementsOrdered.value = props.elements.slice();
      orderedValue = -1;
      sortDirection = SortDirection.None;
      return;
    } else if (sortDirection == SortDirection.Ascending) {
      sortDirection = SortDirection.Descending;
    } else {
      sortDirection = SortDirection.Ascending;
    }
  } else {
    sortDirection = SortDirection.Ascending;
  }

  orderedValue = sortValueIndex;
  orderedColumn = -1;

  elementsOrdered.value =
    sortDirection == SortDirection.Ascending
      ? props.elements
          .slice()
          .sort((e1, e2) =>
            props.sortValues[sortValueIndex].compareFunction(e1, e2),
          )
      : props.elements
          .slice()
          .sort((e1, e2) =>
            props.sortValues[sortValueIndex].compareFunction(e2, e1),
          );
}

function getColumnSortIcon(columnIndex: number): Array<string> {
  if (columnIndex === orderedColumn) {
    if (sortDirection === SortDirection.Ascending) {
      return ["fas", "arrow-down-short-wide"];
    } else if (sortDirection === SortDirection.Descending) {
      return ["fas", "arrow-up-wide-short"];
    }
  }

  return ["fas", "sort"];
}

function getSortValueIcon(sortValueIndex: number): Array<string> {
  if (sortValueIndex === orderedValue) {
    if (sortDirection === SortDirection.Ascending) {
      return ["fas", "arrow-down-short-wide"];
    } else if (sortDirection === SortDirection.Descending) {
      return ["fas", "arrow-up-wide-short"];
    }
  }

  return ["fas", "sort"];
}

function getButtonState(stateButton: TableStateButton, element: any) {
  return computed<TableButton>(() => {
    for (const state of stateButton.states) {
      if (state.condition(element)) {
        return state.button;
      }
    }
    return stateButton.defaultState;
  });
}
</script>
