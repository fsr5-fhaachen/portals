<template>
  <div :class="rootClasses">
    <a v-if="disabled" :class="class">
      <slot />
    </a>
    <a v-else-if="isExternal" :href="href" target="_blank" :class="class">
      <slot />
    </a>
    <InertiaLink v-else :href="href" :class="class">
      <slot />
    </InertiaLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Link as InertiaLink } from "@inertiajs/inertia-vue3";

const { href, rootClass, theme } = defineProps({
  class: {
    type: [String, Object],
    default: "",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  href: {
    type: String,
    required: true,
  },
  rootClass: {
    type: String,
    default: "",
  },
  theme: {
    type: String,
    default: "default",
    validator: (value: string) =>
      ["default", "gray", "danger", "none"].includes(value),
  },
});

const isExternal = computed(() => {
  return /^(http(s)?:\/\/)/.test(href);
});

const rootClasses = computed(() => {
  const classes: string[] = [];

  if (theme === "default") {
    classes.push("text-fhac-mint-dark");
  } else if (theme === "gray") {
    classes.push("text-gray-500");
  } else if (theme === "danger") {
    classes.push("text-red-500");
  }

  if (theme !== "none") {
    classes.push("hover:underline");
  }

  if (rootClass) {
    classes.push(rootClass);
  }

  return classes.join(" ");
});
</script>
