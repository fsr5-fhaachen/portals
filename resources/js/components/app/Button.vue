<template>
  <div :class="rootClasses" @click="handleClick">
    <a>
      <slot />
    </a>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  theme: {
    type: String,
    default: "default",
    validator: (value: string) =>
      ["default", "gray", "warning", "danger", "success"].includes(value),
  },
});

const emit = defineEmits<{
  (e: "click", event: MouseEvent): void;
}>();

function handleClick(event: MouseEvent) {
  if (props.disabled) {
    return;
  }
  emit("click", event);
}

const rootClasses = computed(() => {
  const classes: string[] = [];

  if (props.theme === "default") {
    classes.push("bg-fhac-mint-dark");

    if (!props.disabled) {
      classes.push("hover:bg-fhac-mint");
    }
  } else if (props.theme === "gray") {
    classes.push("bg-gray-500");

    if (!props.disabled) {
      classes.push("hover:bg-gray-700");
    }
  } else if (props.theme === "danger") {
    classes.push("bg-red-500 dark:bg-red-700");

    if (!props.disabled) {
      classes.push("hover:bg-red-700 dark:hover:bg-red-900");
    }
  } else if (props.theme === "success") {
    classes.push("bg-green-600 dark:bg-green-700");

    if (!props.disabled) {
      classes.push("hover:bg-green-700 dark:hover:bg-green-800");
    }
  } else if (props.theme === "warning") {
    classes.push("bg-orange-500 dark:bg-orange-700");

    if (!props.disabled) {
      classes.push("hover:bg-orange-700 dark:hover:bg-orange-900");
    }
  }

  if (props.disabled) {
    // a disabled success button confirms an action, so it is not faded
    classes.push(
      props.theme === "success"
        ? "hover:cursor-not-allowed"
        : "opacity-50 hover:cursor-not-allowed",
    );
  } else {
    classes.push("hover:cursor-pointer");
  }

  classes.push("text-white px-4 py-2 rounded-md select-none");

  return classes.join(" ");
});
</script>
