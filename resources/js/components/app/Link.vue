<template>
  <div
    :class="{
      'text-fhac-mint-dark': theme === 'default',
      'text-gray-500': theme === 'gray',
    }"
    class="hover:underline"
  >
    <a v-if="isExternal" :href="href" target="_blank"><slot /></a>
    <InertiaLink v-else :href="href">
      <slot />
    </InertiaLink>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Link as InertiaLink } from "@inertiajs/inertia-vue3";

const { href } = defineProps({
  href: {
    type: String,
    required: true,
  },
  theme: {
    type: String,
    default: "default",
    validator: (value: string) => ["default", "gray"].includes(value),
  },
});

const isExternal = computed(() => {
  return /^(http(s)?:\/\/)/.test(href);
});
</script>
