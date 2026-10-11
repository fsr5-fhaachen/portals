<template>
  <LayoutDashboardContent>
    <template #title>Strichliste</template>
    <template #subtitle>Tippe auf dein Bild, um Striche zu machen.</template>

    <CardContainer>
      <AppMessage :message="message" class="!mb-0" />

      <CardBase>
        <FormKit type="form" id="filter" :actions="false" v-model="filterForm">
          <FormContainer>
            <FormRow>
              <FormKit type="text" name="search" label="Suche" />
            </FormRow>
          </FormContainer>
        </FormKit>

        <div class="mt-6 flex flex-wrap gap-3">
          <AppButton
            :theme="filter === 'all' ? 'default' : 'gray'"
            @click="setFilter('all')"
          >
            Alle
          </AppButton>
          <AppButton
            v-for="course in courses"
            :key="course.id"
            :theme="filter === `course-${course.id}` ? 'default' : 'gray'"
            @click="setFilter(`course-${course.id}`)"
          >
            <FontAwesomeIcon :icon="course.icon" class="mr-1" />
            {{ course.abbreviation }}
          </AppButton>
          <AppButton
            :theme="filter === 'tutor' ? 'default' : 'gray'"
            @click="setFilter('tutor')"
          >
            Tutor*innen
          </AppButton>
          <AppButton
            :theme="filter === 'special' ? 'default' : 'gray'"
            @click="setFilter('special')"
          >
            Special
          </AppButton>
        </div>
      </CardBase>

      <InfiniteScroll
        data="persons"
        items-element="#kiosk-persons"
        preserve-url
        only-next
      >
        <div
          id="kiosk-persons"
          class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
        >
          <StrichlistensystemPersonCard
            v-for="person in persons.data"
            :key="person.id"
            :person="person"
            clickable
            @click="openPinDialog(person)"
          />
        </div>

        <template #loading>
          <div
            class="flex items-center justify-center gap-2 py-6 text-sm text-gray-500 dark:text-gray-400"
          >
            <div
              class="h-4 w-4 animate-pulse rounded-full bg-gray-200 dark:bg-gray-700"
            ></div>
            Lade weitere Personen …
          </div>
        </template>
      </InfiniteScroll>

      <p
        v-if="!persons.data.length"
        class="text-center text-gray-500 dark:text-gray-400"
      >
        Es wurden keine Personen gefunden.
      </p>
    </CardContainer>

    <TransitionRoot
      as="template"
      :show="isPinDialogOpen"
      @after-leave="clearSelectedPerson"
    >
      <Dialog as="div" class="relative z-10" @close="closePinDialog">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          />
        </TransitionChild>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
          <div
            class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0"
          >
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel
                class="relative w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:max-w-sm sm:p-6"
              >
                <div v-if="selectedPerson" class="flex flex-col gap-6">
                  <UiH2>
                    Hallo {{ selectedPerson.firstname }}
                    {{ selectedPerson.lastname }}
                  </UiH2>

                  <template v-if="selectedPerson.has_pin">
                    <p class="text-gray-500 dark:text-gray-400">
                      Bitte gib deine PIN ein.
                    </p>
                    <UiMessage
                      v-if="pinForm.errors.pin"
                      type="error"
                      :message="pinForm.errors.pin"
                    />
                    <StrichlistensystemPinPad
                      ref="pinPad"
                      :disabled="pinForm.processing"
                      @complete="submitPin"
                    />
                  </template>
                  <UiMessage v-else type="warning">
                    <template #message>
                      Du hast noch keine PIN gesetzt. Melde dich mit deinem
                      Handy unter
                      <strong>{{ selfServiceUrl }}</strong>
                      an und lege dort deine PIN fest.
                    </template>
                  </UiMessage>

                  <AppButton
                    theme="gray"
                    class="text-center"
                    @click="closePinDialog"
                  >
                    Abbrechen
                  </AppButton>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { onBeforeUnmount, PropType, ref, watch } from "vue";
import { InfiniteScroll, router, useForm } from "@inertiajs/vue3";
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

const props = defineProps({
  message: {
    type: Object,
    default: () => ({}),
  },
  // persons are loaded page by page (infinite scroll), search and filters are applied on the server
  persons: {
    type: Object as PropType<{ data: Strichlistensystem.Person[] }>,
    required: true,
  },
  // courses with active persons
  courses: {
    type: Array as PropType<App.Models.Course[]>,
    required: true,
  },
});

// close the pin dialog automatically if nobody enters a pin
const pinDialogTimeoutSeconds = 30;

const selfServiceUrl = `${window.location.origin}/strichlistensystem`;

const initialQuery = new URLSearchParams(window.location.search);

const filterForm = ref({
  search: initialQuery.get("search") ?? "",
});
const filter = ref(
  initialQuery.get("course")
    ? `course-${initialQuery.get("course")}`
    : (initialQuery.get("role") ?? "all"),
);

/**
 * Reload the persons from the first page with the current search and filter.
 */
const reloadPersons = () => {
  const query: Record<string, string> = {};
  if (filterForm.value.search) {
    query.search = filterForm.value.search;
  }
  if (filter.value.startsWith("course-")) {
    query.course = filter.value.replace("course-", "");
  } else if (filter.value !== "all") {
    query.role = filter.value;
  }

  router.get(window.location.pathname, query, {
    only: ["persons"],
    reset: ["persons"],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const setFilter = (newFilter: string) => {
  filter.value = newFilter;
  reloadPersons();
};

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(
  () => filterForm.value.search,
  () => {
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(reloadPersons, 300);
  },
);

// the selected person is kept until the leave transition of the dialog is finished,
// otherwise the dialog panel is removed during the transition and the dialog never closes
const isPinDialogOpen = ref(false);
const selectedPerson = ref<Strichlistensystem.Person | null>(null);
const pinPad = ref<{ clear: () => void } | null>(null);
const pinForm = useForm({
  pin: "",
});
let pinDialogTimeout: ReturnType<typeof setTimeout> | null = null;

const resetPinDialogTimeout = () => {
  if (pinDialogTimeout) {
    clearTimeout(pinDialogTimeout);
  }
  pinDialogTimeout = setTimeout(closePinDialog, pinDialogTimeoutSeconds * 1000);
};

const openPinDialog = (person: Strichlistensystem.Person) => {
  pinForm.reset();
  pinForm.clearErrors();
  selectedPerson.value = person;
  isPinDialogOpen.value = true;
  resetPinDialogTimeout();
};

const closePinDialog = () => {
  if (pinDialogTimeout) {
    clearTimeout(pinDialogTimeout);
    pinDialogTimeout = null;
  }
  isPinDialogOpen.value = false;
};

const clearSelectedPerson = () => {
  // a new person may have been selected while the dialog was closing
  if (isPinDialogOpen.value) {
    return;
  }

  selectedPerson.value = null;
  pinForm.reset();
  pinForm.clearErrors();
};

const submitPin = (pin: string) => {
  if (!isPinDialogOpen.value || !selectedPerson.value) {
    return;
  }

  resetPinDialogTimeout();
  pinForm.pin = pin;
  pinForm.post(
    `/strichlistensystem/kiosk/user/${selectedPerson.value.id}/pin`,
    {
      preserveScroll: true,
      preserveState: true,
      onError: () => {
        pinForm.reset();
        pinPad.value?.clear();
      },
    },
  );
};

onBeforeUnmount(() => {
  if (pinDialogTimeout) {
    clearTimeout(pinDialogTimeout);
  }
});
</script>
