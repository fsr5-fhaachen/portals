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
                    Vorname
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Nachname
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Studiengang
                  </th>
                  <th
                    v-if="!hideSlots && event.type == 'slot_booking'"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Slot
                  </th>
                  <th
                    v-if="!hideGroups && event.type == 'group_phase'"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Gruppe
                  </th>
                  <th
                    v-if="event.consider_alcohol"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Trinkt Alkohol
                  </th>
                  <th
                    v-if="event.type == 'slot_booking'"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Warteschlangenposition
                  </th>
                  <th
                    v-if="showFormColomn"
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300"
                  >
                    Rückmeldung
                  </th>
                  <th
                    scope="col"
                    class="dark:bg-border-gray-700 border-b border-gray-300 bg-gray-50 bg-opacity-75 py-3.5 pl-3 pr-4 backdrop-blur backdrop-filter dark:bg-gray-900 dark:text-gray-300 sm:pr-6 lg:pr-8"
                  >
                    <span class="sr-only">Ist anwesend</span>
                  </th>
                </tr>
              </thead>
              <tbody v-if="registrationsData" class="bg-white dark:bg-gray-800">
                <template
                  v-for="(registration, index) in registrationsData"
                  :key="registration.id"
                >
                  <tr
                    v-if="registration.user"
                    :class="{
                      'bg-yellow-100 dark:bg-yellow-900':
                        registration.queue_position &&
                        registration.queue_position > 0,
                      'bg-red-100 dark:bg-red-900':
                        registration.queue_position &&
                        registration.queue_position == -1,
                      'bg-green-100 dark:bg-green-900':
                        registration.fulfils_requirements,
                    }"
                  >
                    <td
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-100 sm:pl-6 lg:pl-8',
                      ]"
                    >
                      {{ registration.user.firstname }}
                    </td>
                    <td
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 dark:text-gray-100',
                      ]"
                    >
                      {{ registration.user.lastname }}
                    </td>
                    <td
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
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
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
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
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
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
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                      ]"
                    >
                      {{ registration.drinks_alcohol ? "Ja" : "Nein" }}
                    </td>
                    <td
                      v-if="event.type == 'slot_booking'"
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
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
                      <span
                        v-else-if="
                          registration.queue_position &&
                          registration.queue_position == -1
                        "
                      >
                        Wartet auf Zuteilung
                      </span>
                      <span v-else>Angemeldet</span>
                    </td>
                    <td
                      v-if="showFormColomn"
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300',
                      ]"
                    >
                      <code v-if="registration.form_responses">
                        {{ registration.form_responses }}
                      </code>
                    </td>
                    <td
                      :class="[
                        index !== registrationsData.length - 1
                          ? 'border-b border-gray-200 dark:border-gray-700'
                          : '',
                        'relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8',
                      ]"
                    >
                      <div class="flex gap-4">
                        <div>
                          <AppButton
                            v-if="registration.is_present"
                            @click="toggleIsPresent(registration.id)"
                          >
                            <span class="sr-only"
                              >{{ registration.user.firstname }}
                              {{ registration.user.lastname }}</span
                            >
                            ist anwesend
                          </AppButton>
                          <AppButton
                            v-else
                            theme="gray"
                            @click="toggleIsPresent(registration.id)"
                          >
                            <span class="sr-only"
                              >{{ registration.user.firstname }}
                              {{ registration.user.lastname }}</span
                            >
                            ist nicht anwesend
                          </AppButton>
                        </div>
                        <template v-if="user && user.is_admin">
                          <div>
                            <AppButton
                              v-if="registration.fulfils_requirements"
                              @click="
                                toggleFulfilsRequirements(registration.id)
                              "
                            >
                              <span class="sr-only"
                                >{{ registration.user.firstname }}
                                {{ registration.user.lastname }}</span
                              >
                              erfüllt die Anforderungen
                            </AppButton>
                            <AppButton
                              v-else
                              theme="gray"
                              @click="
                                toggleFulfilsRequirements(registration.id)
                              "
                            >
                              <span class="sr-only"
                                >{{ registration.user.firstname }}
                                {{ registration.user.lastname }}</span
                              >
                              erfüllt nicht die Anforderungen
                            </AppButton>
                          </div>
                          <div>
                            <AppButton
                              v-if="!registration.fulfils_requirements"
                              theme="danger"
                              @click="destory(registration.id)"
                            >
                              <span class="sr-only"
                                >{{ registration.user.firstname }}
                                {{ registration.user.lastname }}</span
                              >
                              löschen
                            </AppButton>
                            <AppButton
                              v-else
                              theme="gray"
                              :disabled="true"
                              @click="destory(registration.id)"
                            >
                              <span class="sr-only"
                                >{{ registration.user.firstname }}
                                {{ registration.user.lastname }}</span
                              >
                              löschen
                            </AppButton>
                          </div>
                        </template>
                      </div>
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
import { computed, ref, PropType, watch } from "vue";

const props = defineProps({
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
  user: {
    type: Object as PropType<App.Models.User>,
    default: null,
  },
});

const getCourseById = (id: number) => {
  return props.courses.find((course) => course.id === id);
};
const getSlotById = (id: number) => {
  if (!props.event.slots) return null;
  return props.event.slots.find((slot) => slot.id === id);
};
const getGroupById = (id: number) => {
  if (!props.event.groups) return null;
  return props.event.groups.find((group) => group.id === id);
};

const registrationsData = ref(props.registrations);
watch(props, (props) => {
  registrationsData.value = props.registrations;
});

const showFormColomn = computed(() => {
  // check if any registrationsData has the attribute "form_responses"
  return registrationsData.value.some((registration) => {
    return registration.form_responses;
  });
});

const toggleIsPresent = async (registrationId: number) => {
  const response = await fetch(
    "/api/registrations/" + registrationId + "/toggle-is-present",
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

    registrationsData.value = registrationsData.value.map((registration) => {
      if (registration.id === registrationId) {
        registration.is_present = data.is_present;
      }
      return registration;
    });
  }
};
const toggleFulfilsRequirements = async (registrationId: number) => {
  const response = await fetch(
    "/api/registrations/" + registrationId + "/toggle-fulfils-requirements",
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

    registrationsData.value = registrationsData.value.map((registration) => {
      if (registration.id === registrationId) {
        registration.fulfils_requirements = data.fulfils_requirements;
      }
      return registration;
    });
  }
};

const destory = async (registrationId: number) => {
  const response = await fetch("/api/registrations/" + registrationId, {
    method: "DELETE",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN":
        document
          .querySelector("meta[name='csrf-token']")
          ?.getAttribute("content") || "",
    },
  });

  if (response.ok) {
    registrationsData.value = registrationsData.value.filter(
      (registration) => registration.id !== registrationId
    );
  }
};
</script>
