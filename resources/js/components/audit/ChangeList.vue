<template>
  <dl v-if="changes.length" class="space-y-1 text-sm">
    <div
      v-for="change in changes"
      :key="change.field"
      class="flex flex-wrap items-baseline gap-x-2"
    >
      <dt class="font-medium text-gray-700 dark:text-gray-300">
        {{ change.label }}:
      </dt>
      <dd class="flex flex-wrap items-baseline gap-x-1 break-all">
        <template v-if="showOld">
          <span
            class="text-red-700 line-through decoration-red-400 dark:text-red-400"
          >
            {{ change.old ?? "—" }}
          </span>
          <span class="text-gray-400" aria-label="geändert zu">&rarr;</span>
        </template>
        <span
          :class="
            showOld
              ? 'text-green-700 dark:text-green-400'
              : 'text-gray-900 dark:text-gray-100'
          "
        >
          {{ (showOld ? change.new : (change.new ?? change.old)) ?? "—" }}
        </span>
      </dd>
    </div>
  </dl>
  <span v-else class="text-sm text-gray-400">—</span>
</template>

<script setup lang="ts">
import { computed, PropType } from "vue";

const props = defineProps({
  changes: {
    type: Array as PropType<AuditLog.Change[]>,
    required: true,
  },
  action: {
    type: String,
    required: true,
  },
});

// created and deleted entries only have one side of the change
const showOld = computed(
  () => !["created", "deleted", "restored"].includes(props.action),
);
</script>
