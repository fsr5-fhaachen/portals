<template>
  <span class="inline-flex flex-wrap gap-1">
    <span
      v-for="role in sortedRoles"
      :key="role"
      :class="roleColors[role] ?? 'bg-slate-500'"
      class="rounded-md px-1.5 text-xs text-white"
    >
      {{ role }}
    </span>
  </span>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";
import { roleColors } from "../../composables/useRoleColors";

const props = defineProps({
  roles: {
    type: Array as PropType<string[]>,
    required: true,
  },
});

const sortedRoles = computed(() =>
  [...props.roles].sort((a, b) => a.localeCompare(b)),
);
</script>
