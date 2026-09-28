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
                :class="size === 'lg' ? 'sm:max-w-lg' : 'sm:max-w-sm'"
                class="relative w-full transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-gray-900 sm:my-8 sm:w-full sm:p-6"
              >
                <div class="mb-6 flex items-start justify-between gap-4">
                  <DialogTitle as="div">
                    <UiH2>{{ title }}</UiH2>
                  </DialogTitle>
                  <button
                    type="button"
                    class="rounded-md p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-fhac-mint dark:hover:text-gray-200"
                    @click="close"
                  >
                    <span class="sr-only">Schließen</span>
                    <FontAwesomeIcon class="h-4 w-4" :icon="['fas', 'x']" />
                  </button>
                </div>

                <slot />
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
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

defineProps({
  title: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    default: "sm",
    validator: (value: string) => ["sm", "lg"].includes(value),
  },
});

const emits = defineEmits<{
  close: [];
}>();

const close = () => {
  emits("close");
};
</script>
