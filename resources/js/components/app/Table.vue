<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle">
          <div class="shadow-sm ring-1 ring-black ring-opacity-5">
            <table class="min-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th
                    v-for:="(column, index) in columns"
                    :key="column.name"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8 hover:cursor-pointer"
                    @click="orderElements(index)"
                  >
                    <div class="flex items-center gap-2 w-full">
                      <span class="break-words">{{ column.text }}</span>
                      <FontAwesomeIcon :icon="getSortIcon(index)" />
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
                          :disabled="(func as TableButton).disabled"
                          @click="(func as TableButton).buttonFunction(element)"
                        >
                          {{ (func as TableButton).text }}
                        </AppButton>
                        <AppButton
                          v-if="func instanceof TableStateButton"
                          :theme="
                            getButtonState(func as TableStateButton, element)
                              .theme
                          "
                          :disabled="
                            getButtonState(func as TableStateButton, element)
                              .disabled
                          "
                          @click="
                            getButtonState(
                              func as TableStateButton,
                              element,
                            ).buttonFunction(element)
                          "
                        >
                          {{
                            getButtonState(func as TableStateButton, element)
                              .text
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
import { ref, PropType, onBeforeUnmount } from "vue";
import { TableColumn } from "../../types/table-column";
import { TableFunction } from "../../types/table-function";
import { TableLink } from "../../types/table-link";
import { TableButton } from "../../types/table-button";
import { TableStateButton, ButtonState } from "../../types/table-state-button";

const { columns, elements, idFunction, functions } = defineProps({
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
});

const elementsOrdered = ref<Object[]>(elements.slice());

enum SortDirection {
  None,
  Ascending,
  Descending,
}

let orderedCol = -1;
let sortDirection = SortDirection.None;

function orderElements(columnIndex: number): void {
  if (columnIndex == orderedCol) {
    if (sortDirection == SortDirection.Descending) {
      // undo ordering
      elementsOrdered.value = elements.slice();
      orderedCol = -1;
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

  orderedCol = columnIndex;

  elementsOrdered.value =
    sortDirection == SortDirection.Ascending
      ? elements
          .slice()
          .sort((e1, e2) => columns[columnIndex].compareFunction(e1, e2))
      : elements
          .slice()
          .sort((e1, e2) => columns[columnIndex].compareFunction(e2, e1));
}

function getSortIcon(columnIndex: number): Array<string> {
  if (columnIndex === orderedCol) {
    if (sortDirection === SortDirection.Ascending) {
      return ["fas", "arrow-down-short-wide"];
    } else if (sortDirection === SortDirection.Descending) {
      return ["fas", "arrow-up-wide-short"];
    }
  }

  return ["fas", "sort"];
}

function getButtonState(
  stateButton: TableStateButton,
  element: any,
): TableButton {
  for (const state of stateButton.states) {
    if (state.condition(element)) {
      return state.button;
    }
  }

  return stateButton.defaultState;
}
</script>
