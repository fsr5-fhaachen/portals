<template>
  <div class="flex flex-col gap-6">
    <div class="flex justify-center gap-4">
      <div
        v-for="index in pinLength"
        :key="index"
        :class="[
          index <= pin.length
            ? 'bg-fhac-mint-dark'
            : 'bg-gray-200 dark:bg-gray-700',
          'h-4 w-4 rounded-full',
        ]"
      />
    </div>

    <div class="grid grid-cols-3 gap-3">
      <AppButton
        v-for="digit in ['1', '2', '3', '4', '5', '6', '7', '8', '9']"
        :key="digit"
        theme="gray"
        class="py-4 text-center text-2xl"
        :disabled="disabled"
        @click="addDigit(digit)"
      >
        {{ digit }}
      </AppButton>
      <div />
      <AppButton
        theme="gray"
        class="py-4 text-center text-2xl"
        :disabled="disabled"
        @click="addDigit('0')"
      >
        0
      </AppButton>
      <AppButton
        theme="gray"
        class="py-4 text-center text-2xl"
        :disabled="disabled || !pin.length"
        @click="removeDigit"
      >
        <FontAwesomeIcon :icon="['fas', 'delete-left']" />
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits<{
  (e: "complete", pin: string): void;
}>();

const pinLength = 4;
const pin = ref("");

const addDigit = (digit: string) => {
  if (props.disabled || pin.value.length >= pinLength) {
    return;
  }

  pin.value += digit;

  if (pin.value.length === pinLength) {
    emit("complete", pin.value);
  }
};

const removeDigit = () => {
  pin.value = pin.value.slice(0, -1);
};

const clear = () => {
  pin.value = "";
};

defineExpose({ clear });
</script>
