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
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:max-w-sm sm:p-6"
              >
                <FormKit
                  type="form"
                  id="delete"
                  @submit="deleteSubmitHandler"
                  :actions="false"
                  v-model="deleteForm"
                >
                  <FormContainer>
                    <FormRow>
                      <UiH2>User löschen</UiH2>
                    </FormRow>

                    <FormRow>
                      <UiMessage
                        type="warning"
                        :message="`Sie sind dabei den User ${user.firstname} ${user.lastname} zu löschen. Sind Sie sicher?`"
                      />
                    </FormRow>
                    <FormRow>
                      <FormKit type="submit" label="Löschen" />
                    </FormRow>
                  </FormContainer>
                </FormKit>
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
import { PropType, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const { user } = defineProps({
  user: {
    type: Object as PropType<Models.User>,
    default: null,
  },
});

const emits = defineEmits<{
  close: [];
  submit: [];
}>();

const deleteForm = ref({});

const close = () => {
  emits("close");
};
const deleteSubmitHandler = async () => {
  Inertia.delete(`/dashboard/admin/user/${user.id}`, deleteForm.value);
  emits("submit");
};
</script>
