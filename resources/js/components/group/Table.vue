<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col">
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
                    v-if="showCourses"
                    scope="col"
                    class="border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter"
                  >
                    Studiengang
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
                <tr v-for="(group, index) in groups" :key="group.id">
                  <td
                    :class="[
                      index !== groups.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 lg:pl-8',
                    ]"
                  >
                    {{ group.name }}
                  </td>
                  <td
                    v-if="showCourses"
                    :class="[
                      index !== groups.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    <template
                      v-if="group.course_id && getCourseById(group.course_id)"
                    >
                      {{ getCourseById(group.course_id)?.name }}
                    </template>
                  </td>

                  <td
                    :class="[
                      index !== groups.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'whitespace-nowrap px-3 py-4 text-sm text-gray-500',
                    ]"
                  >
                    {{ registrations[group.id] || 0 }}
                  </td>
                  <td
                    :class="[
                      index !== groups.length - 1
                        ? 'border-b border-gray-200'
                        : '',
                      'relative whitespace-nowrap py-4 pr-4 pl-3 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                    ]"
                  >
                    <AppLink :href="'/dashboard/tutor/group/' + group.id">
                      Anzeigen
                      <span class="sr-only">, {{ group.name }}</span>
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

const { courses, groups } = defineProps({
  groups: {
    type: Object as PropType<App.Models.Group[]>,
    required: true,
  },
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: false,
  },
});

const getCourseById = (id: number) => {
  if (!courses) return null;
  return courses.find((course) => course.id === id);
};
const showCourses = computed(() => {
  if (!courses) return false;
  return groups.some((group) => group.course_id);
});

const registrations = ref({});
groups.forEach((group) => {
  registrations.value[group.id] = group.registrations?.length || 0;
});
const fetchRegistrations = async () => {
  const response = await fetch(
    "/api/events/" + groups[0].event_id + "/registrations-amount",
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
    groups.forEach((group) => {
      registrations.value[group.id] = data.groups[group.id];
    });
  }
};
const registrationsInterval = setInterval(fetchRegistrations, 1000);
onBeforeUnmount(() => {
  clearInterval(registrationsInterval);
});
</script>
