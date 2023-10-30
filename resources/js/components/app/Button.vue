<template>
  <div :class="rootClasses">
    <a>
      <slot />
    </a>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
const { disabled, theme } = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  theme: {
    type: String,
    default: "default",
    validator: (value: string) =>
      ["default", "gray", "warning", "danger"].includes(value),
  },
});

const rootClasses = computed(() => {
  const classes: string[] = [];

  if (theme === "default") {
    classes.push("bg-fhac-mint-dark");

    if (!disabled) {
      classes.push("hover:bg-fhac-mint");
    }
  } else if (theme === "gray") {
    classes.push("bg-gray-500");

    if (!disabled) {
      classes.push("hover:bg-gray-700");
    }
  } else if (theme === "danger") {
    classes.push("bg-red-500 dark:bg-red-700");

    if (!disabled) {
      classes.push("hover:bg-red-700 dark:hover:bg-red-900");
    }
  } else if (theme === "warning") {
    classes.push("bg-orange-500 dark:bg-orange-700");

    if (!disabled) {
      classes.push("hover:bg-orange-700 dark:hover:bg-orange-900");
    }
  }

  if (disabled) {
    classes.push("opacity-50 hover:cursor-not-allowed");
  } else {
    classes.push("hover:cursor-pointer");
  }

  classes.push("text-white px-4 py-2 rounded-md select-none");

  return classes.join(" ");
});
</script>
