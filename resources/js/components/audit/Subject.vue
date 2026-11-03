<template>
  <div class="flex flex-col items-start">
    <span
      class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400"
    >
      {{ entry.subject_type_label }}
    </span>
    <button
      type="button"
      class="text-left text-gray-900 hover:underline dark:text-gray-100"
      title="Nur Einträge dieses Objekts anzeigen"
      @click="emits('filter', entry.subject_type, entry.subject_id)"
    >
      {{ entry.subject_label }}
      <span class="text-gray-400">#{{ entry.subject_id }}</span>
    </button>
    <span
      v-if="entry.subject_deleted"
      class="text-xs text-red-600 dark:text-red-400"
    >
      gelöscht
    </span>
  </div>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  entry: {
    type: Object as PropType<AuditLog.Entry>,
    required: true,
  },
});

const emits = defineEmits<{
  filter: [subjectType: string, subjectId: number];
}>();
</script>
