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
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8 hover:cursor-pointer"
                    @click="orderEvents(Column.Name)"
                  >
                    <span>Name&nbsp;</span>
                    <FontAwesomeIcon :icon="getSortIcon(Column.Name)" />
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                    @click="orderEvents(Column.Type)"
                  >
                    <span>Type&nbsp;</span>
                    <FontAwesomeIcon :icon="getSortIcon(Column.Type)" />
                  </th>
                  <th
                    v-if="
                      user.permissionsArray.includes(
                        'view hidden event details',
                      )
                    "
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                  >
                    <span>Berücksichtigt Verzehr von Alkohol&nbsp;</span>
                    <FontAwesomeIcon
                      :icon="getSortIcon(Column.Consider_Alcohol)"
                    />
                  </th>
                  <th
                    v-if="
                      user.permissionsArray.includes(
                        'view hidden event details',
                      )
                    "
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                  >
                    <span>Hat Voraussetzungen&nbsp;</span>
                    <FontAwesomeIcon
                      :icon="getSortIcon(Column.Has_Requirements)"
                    />
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                  >
                    <span>Registrierung&nbsp;</span>
                    <FontAwesomeIcon :icon="getSortIcon(Column.Registration)" />
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 hover:cursor-pointer"
                  >
                    <span>Teilnehmer&nbsp;</span>
                    <FontAwesomeIcon :icon="getSortIcon(Column.Participants)" />
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8"
                  >
                    <span class="sr-only">Anzeigen</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800">
                <tr v-for="(event, index) in eventsOrdered" :key="event.id">
                  <td
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8',
                    ]"
                  >
                    {{ event.name }}
                  </td>
                  <td
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    {{ event.type }}
                  </td>
                  <td
                    v-if="
                      user.permissionsArray.includes(
                        'view hidden event details',
                      )
                    "
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    {{ event.consider_alcohol ? "Ja" : "Nein" }}
                  </td>
                  <td
                    v-if="
                      user.permissionsArray.includes(
                        'view hidden event details',
                      )
                    "
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    {{ event.has_requirements ? "Ja" : "Nein" }}
                  </td>
                  <td
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    <div v-if="event.registration_from" class="flex">
                      Von:&nbsp;
                      <UiDateTimeString
                        :value="event.registration_from"
                        :withClockSuffix="true"
                      />
                    </div>
                    <div v-if="event.registration_to" class="flex">
                      Bis:&nbsp;
                      <UiDateTimeString
                        :value="event.registration_to"
                        :withClockSuffix="true"
                      />
                    </div>
                  </td>

                  <td
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    {{ registrations[event.id].amount }}
                  </td>
                  <td
                    :class="[
                      index !== eventsOrdered.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                    ]"
                  >
                    <div class="flex gap-4">
                      <AppLink :href="'/dashboard/tutor/event/' + event.id">
                        Anzeigen
                        <span class="sr-only">, {{ event.name }}</span>
                      </AppLink>
                      <AppLink
                        v-if="user.permissionsArray.includes('manage events')"
                        theme="danger"
                        :href="'/dashboard/admin/event/' + event.id"
                      >
                        Admin
                        <span class="sr-only">, {{ event.name }}</span>
                      </AppLink>
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

const { events } = defineProps({
  events: {
    type: Object as PropType<App.Models.Event[]>,
    required: true,
  },
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
});

const eventsOrdered = ref<Event[]>(events.slice());

const registrations = ref({});
const isFetchingRegistrations = ref(false);
events.forEach((event) => {
  registrations.value[event.id] = {
    amount: event.registrations?.length || 0,
  };
});
const fetchRegistrations = async () => {
  if (isFetchingRegistrations.value) {
    return;
  }

  isFetchingRegistrations.value = true;

  const response = await fetch("/api/events/registrations-amount", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();
    data.forEach((event) => {
      registrations.value[event.id] = event;
    });
  }

  isFetchingRegistrations.value = false;
};
const registrationsInterval = setInterval(fetchRegistrations, 5000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});

enum Column {
  None,
  Name,
  Type,
  Consider_Alcohol,
  Has_Requirements,
  Registration,
  Participants,
}

enum SortDirection {
  None,
  Ascending,
  Descending,
}

let orderedCol = Column.None;
let sortDirection = SortDirection.None;

function orderEvents(column: Column): void {
  if (column == orderedCol) {
    if (sortDirection == SortDirection.Descending) {
      // undo ordering
      eventsOrdered.value = events.slice();
      orderedCol = Column.None;
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

  orderedCol = column;

  switch (column) {
    case Column.None:
      eventsOrdered.value = events.slice();
      sortDirection = SortDirection.None;
      break;

    case Column.Name:
      eventsOrdered.value =
        sortDirection == SortDirection.Ascending
          ? events
              .slice()
              .sort((event1, event2) => event1.name.localeCompare(event2.name))
          : events
              .slice()
              .sort((event1, event2) => event2.name.localeCompare(event1.name));
      break;

    case Column.Type:
      eventsOrdered.value =
        sortDirection == SortDirection.Ascending
          ? events
              .slice()
              .sort((event1, event2) => event1.type.localeCompare(event2.type))
          : events
              .slice()
              .sort((event1, event2) => event2.type.localeCompare(event1.type));
      break;
  }
}

function getSortIcon(column: Column): Array<string> {
  if (column === orderedCol) {
    if (sortDirection === SortDirection.Ascending) {
      return ["fas", "arrow-down-short-wide"];
    } else if (sortDirection === SortDirection.Descending) {
      return ["fas", "arrow-up-wide-short"];
    }
  }

  return ["fas", "sort"];
}
</script>
