<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="mt-8 flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle">
          <div class="shadow-sm ring-1 ring-black ring-opacity-5">
            <table class="min-w-full border-separate" style="border-spacing: 0">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter sm:pl-6 lg:pl-8"
                  >
                    Name
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Type
                  </th>
                  <th
                    v-if="user.is_admin"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Berücksichtigt Verzehr von Alkohol
                  </th>
                  <th
                    v-if="user.is_admin"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Hat Voraussetzungen
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Registrierung
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Anmeldungen
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pr-4 pl-3 backdrop-blur backdrop-filter sm:pr-6 lg:pr-8"
                  >
                    <span class="sr-only">Anzeigen</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white">
                <tr v-for="(event, index) in events" :key="event.id">
                  <td
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8',
                    ]"
                  >
                    {{ event.name }}
                  </td>
                  <td
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ event.type }}
                  </td>
                  <td
                    v-if="user.is_admin"
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ event.consider_alcohol ? "Ja" : "Nein" }}
                  </td>
                  <td
                    v-if="user.is_admin"
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ event.has_requirements ? "Ja" : "Nein" }}
                  </td>
                  <td
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
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
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ registrations[event.id].amount }}
                  </td>
                  <td
                    :class="[
                      index !== events.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'relative whitespace-nowrap py-4 pr-4 pl-3 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                    ]"
                  >
                    <AppLink :href="'/dashboard/tutor/event/' + event.id">
                      Anzeigen
                      <span class="sr-only">, {{ event.name }}</span>
                    </AppLink>
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
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const registrations = ref({});
events.forEach((event) => {
  registrations.value[event.id] = {
    amount: event.registrations?.length || 0,
  };
});
const fetchRegistrations = async () => {
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
};
const registrationsInterval = setInterval(fetchRegistrations, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
