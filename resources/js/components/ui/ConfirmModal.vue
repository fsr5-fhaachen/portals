<template>
  <UiModal :title="title" @close="close">
    <div class="space-y-6">
      <UiMessage type="warning">
        <template #message>{{ message }}</template>
      </UiMessage>

      <div class="flex flex-col gap-3 sm:flex-row-reverse">
        <AppButton
          :theme="theme"
          :disabled="processing"
          class="flex-1 text-center"
          @click="confirm"
        >
          {{ confirmLabel }}
        </AppButton>
        <AppButton theme="gray" class="flex-1 text-center" @click="close">
          Abbrechen
        </AppButton>
      </div>
    </div>
  </UiModal>
</template>

<script setup lang="ts">
defineProps({
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmLabel: {
    type: String,
    default: "Löschen",
  },
  theme: {
    type: String,
    default: "danger",
  },
  processing: {
    type: Boolean,
    default: false,
  },
});

const emits = defineEmits<{
  close: [];
  confirm: [];
}>();

const close = () => {
  emits("close");
};
const confirm = () => {
  emits("confirm");
};
</script>
