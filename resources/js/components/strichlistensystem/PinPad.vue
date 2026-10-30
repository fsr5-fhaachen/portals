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

    <!-- no AppButton: touchscreens keep the hover state of the last tapped key, which would show the pin to others -->
    <div class="grid grid-cols-3 gap-3">
      <button
        v-for="digit in ['1', '2', '3', '4', '5', '6', '7', '8', '9']"
        :key="digit"
        type="button"
        :class="keyClass"
        :disabled="disabled"
        @click="addDigit(digit, $event)"
      >
        {{ digit }}
      </button>
      <div />
      <button
        type="button"
        :class="keyClass"
        :disabled="disabled"
        @click="addDigit('0', $event)"
      >
        0
      </button>
      <button
        type="button"
        :class="keyClass"
        :disabled="disabled || !pin.length"
        aria-label="Letzte Ziffer löschen"
        @click="removeDigit($event)"
      >
        <FontAwesomeIcon :icon="['fas', 'delete-left']" />
      </button>
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

// only highlighted while pressed (active), never by hover or focus
const keyClass =
  "touch-manipulation select-none rounded-md bg-gray-500 py-4 text-center text-2xl text-white focus:outline-none active:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50";

// a tapped button keeps the focus, which some browsers show
const releaseKey = (event: MouseEvent) => {
  (event.currentTarget as HTMLElement | null)?.blur();
};

const addDigit = (digit: string, event: MouseEvent) => {
  releaseKey(event);

  if (props.disabled || pin.value.length >= pinLength) {
    return;
  }

  pin.value += digit;

  if (pin.value.length === pinLength) {
    emit("complete", pin.value);
  }
};

const removeDigit = (event: MouseEvent) => {
  releaseKey(event);
  pin.value = pin.value.slice(0, -1);
};

const clear = () => {
  pin.value = "";
};

defineExpose({ clear });
</script>
