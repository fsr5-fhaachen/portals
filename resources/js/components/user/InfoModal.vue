<template>
  <div>
    <TransitionRoot as="template" :show="true">
      <Dialog as="div" class="relative z-10" @close="close">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
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
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-sm sm:p-6"
              >
                <FormContainer>
                  <FormRow>
                    <UiH2>User anzeigen</UiH2>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Vorname</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ user.firstname }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Nachname</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ user.lastname }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">E-Mail</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ user.email }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Studiengang</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{
                          courses.find((c) => c.id === user.course_id)?.name || "–"
                        }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Rollen</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ user.roles.map((r) => r.name).join(", ") || "–" }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow v-if="user.avatarUrl">
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Avatar</label>
                      <img
                        :src="user.avatarUrl"
                        class="mx-auto rounded-full h-24 w-24 object-cover shadow-sm border border-gray-300 dark:border-gray-700"
                      />
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                        Registrierungen
                      </label>
                      <div
                        class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300 max-h-64 overflow-y-auto"
                      >
                        <template v-if="registrations.length">
                          <div v-for="reg in registrations" :key="reg.id" class="py-0.5">
                            {{ reg.event?.name || '–' }}<span v-if="reg.group"> - {{ reg.group.name }}</span>
                          </div>
                        </template>
                        <template v-else>
                          –
                        </template>
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <div class="flex flex-col w-full">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                      <div class="shadow-sm w-full sm:text-sm border border-gray-300 rounded-md px-3 py-2 dark:bg-gray-700 dark:border-gray-700 dark:text-gray-300">
                        {{ user.is_disabled ? "Deaktiviert" : "Aktiv" }}
                      </div>
                    </div>
                  </FormRow>

                  <FormRow>
                    <AppButton
                      @click="close"
                      theme="warning"
                      class="flex-1 text-center"
                    >
                      Schließen
                    </AppButton>
                  </FormRow>
                </FormContainer>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup lang="ts">
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { PropType, ref, onMounted } from "vue";

const { user, courses } = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    required: true,
  },
  courses: {
    type: Array as PropType<Models.Course[]>,
    required: true,
  },
});

const registrations = ref<App.Models.Registration[]>([]);

const emits = defineEmits<{
  close: [];
}>();

const fetchRegistrations = async () => {
  const response = await fetch(`/api/users/${user.id}/registrations`);
  const data = await response.json();
  registrations.value = data.registrations ?? [];
};

onMounted(fetchRegistrations);

const close = () => {
  emits("close");
};
</script>
