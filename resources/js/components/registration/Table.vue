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
                    Vorname
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Nachname
                  </th>
                  <th
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Studiengang
                  </th>
                  <th
                    v-if="!hideSlots && event.type == 'slot_booking'"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Slot
                  </th>
                  <th
                    v-if="!hideGroups && event.type == 'group_phase'"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Gruppe
                  </th>
                  <th
                    v-if="event.consider_alcohol"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Trinkt Alkohol
                  </th>
                  <th
                    v-if="event.type == 'slot_booking'"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Warteschlangenposition
                  </th>
                </tr>
              </thead>
              <tbody v-if="registrations" class="bg-white">
                <template
                  v-for="(registration, index) in registrations"
                  :key="registration.id"
                >
                  <tr v-if="registration.user">
                    <td
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8',
                      ]"
                    >
                      {{ registration.user.firstname }}
                    </td>
                    <td
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      {{ registration.user.lastname }}
                    </td>
                    <td
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      <template
                        v-if="getCourseById(registration.user.course_id)"
                      >
                        {{ getCourseById(registration.user.course_id)?.name }}
                      </template>
                    </td>
                    <td
                      v-if="!hideSlots && event.type == 'slot_booking'"
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      <template
                        v-if="
                          registration.slot_id &&
                          getSlotById(registration.slot_id)
                        "
                      >
                        {{ getSlotById(registration.slot_id)?.name }}
                      </template>
                    </td>
                    <td
                      v-if="!hideGroups && event.type == 'group_phase'"
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      <template
                        v-if="
                          registration.group_id &&
                          getGroupById(registration.group_id)
                        "
                      >
                        {{ getGroupById(registration.group_id)?.name }}
                      </template>
                    </td>

                    <td
                      v-if="event.consider_alcohol"
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      {{ registration.drinks_alcohol ? "Ja" : "Nein" }}
                    </td>
                    <td
                      v-if="event.type == 'slot_booking'"
                      :class="[
                        index !== registrations.length - 1
                          ? 'border-b border-gray-200'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                      ]"
                    >
                      <span
                        v-if="
                          registration.queue_position &&
                          registration.queue_position > 0
                        "
                      >
                        {{ registration.queue_position }}
                      </span>
                      <span v-else> - </span>
                    </td>
                  </tr>
                </template>
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

const { courses, event } = defineProps({
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
  registrations: {
    type: Array as PropType<App.Models.Registration[]>,
    required: true,
  },
  hideGroups: {
    type: Boolean,
    default: false,
  },
  hideSlots: {
    type: Boolean,
    default: false,
  },
});

const getCourseById = (id: number) => {
  return courses.find((course) => course.id === id);
};
const getSlotById = (id: number) => {
  if (!event.slots) return null;
  return event.slots.find((slot) => slot.id === id);
};
const getGroupById = (id: number) => {
  if (!event.groups) return null;
  return event.groups.find((group) => group.id === id);
};
</script>
