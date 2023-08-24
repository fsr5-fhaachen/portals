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
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pl-6 lg:pl-8"
                  >
                    Name
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Hat Voraussetzungen
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Teilnehmer
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
                <tr v-for="(slot, index) in slots" :key="slot.id">
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8',
                    ]"
                  >
                    {{ slot.name }}
                  </td>
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    {{ slot.has_requirements ? "Ja" : "Nein" }}
                  </td>

                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                    ]"
                  >
                    <span>{{ registrations[slot.id] || 0 }}</span>
                    <span v-if="slot.maximum_participants">
                      / {{ slot.maximum_participants }}</span
                    >
                  </td>
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200 dark:border-gray-700'
                        : '',
                      'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                    ]"
                  >
                    <AppLink :href="'/dashboard/tutor/slot/' + slot.id">
                      Anzeigen
                      <span class="sr-only">, {{ slot.name }}</span>
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
import { computed, ref, PropType, onBeforeUnmount } from "vue";

const { slots } = defineProps({
  slots: {
    type: Object as PropType<App.Models.Slot[]>,
    required: true,
  },
});

const registrations = ref({});
slots.forEach((slot) => {
  registrations.value[slot.id] = slot.registrations?.length || 0;
});
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + slots[0].event_id + "/registrations-amount",
    {
      method: "GET",
      credentials: "include",
      headers: {
        "Content-Type": "application/json",
      },
    }
  );

  if (response.ok) {
    const data = await response.json();
    slots.forEach((slot) => {
      registrations.value[slot.id] = data.slots[slot.id];
    });
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
