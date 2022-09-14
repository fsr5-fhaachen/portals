<template>
  <div :class="rootClasses">
    <a>
      <slot />
    </a>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
const { theme } = defineProps({
  theme: {
    type: String,
    default: "default",
    validator: (value: string) => ["default", "gray", "danger"].includes(value),
  },
});

const rootClasses = computed(() => {
  const classes: string[] = [];

  if (theme === "default") {
    classes.push("bg-fhac-mint-dark hover:bg-fhac-mint");
  } else if (theme === "gray") {
    classes.push("bg-gray-500 hover:bg-gray-700");
  } else if (theme === "danger") {
    classes.push("bg-red-500 hover:bg-red-700");
  }

  classes.push(
    "text-white px-4 py-2 hover:cursor-pointer rounded-md select-none"
  );

  return classes.join(" ");
});
</script>
