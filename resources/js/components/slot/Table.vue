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
                    Hat Voraussetzungen
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
                <tr v-for="(slot, index) in slots" :key="slot.id">
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8',
                    ]"
                  >
                    {{ slot.name }}
                  </td>
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ slot.has_requirements ? "Ja" : "Nein" }}
                  </td>

                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    <span>{{ slot.registrations?.length ?? 0 }}</span>
                    <span v-if="slot.maximum_participants">
                      / {{ slot.maximum_participants }}</span
                    >
                  </td>
                  <td
                    :class="[
                      index !== slots.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'relative whitespace-nowrap py-4 pr-4 pl-3 text-right text-sm font-medium sm:pr-6 lg:pr-8',
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
import { PropType } from "vue";

defineProps({
  slots: {
    type: Object as PropType<App.Models.Slot[]>,
    required: true,
  },
});
</script>
