<template>
  <div v-if="actor" class="flex flex-col items-start gap-1">
    <button
      type="button"
      class="text-left font-medium text-fhac-mint-dark hover:underline dark:text-fhac-mint"
      title="Nur Einträge dieses Nutzers anzeigen"
      @click="emits('filter', actor.id)"
    >
      {{ actor.name }}
    </button>
    <UserRoleBadges v-if="actor.roles.length" :roles="actor.roles" />
  </div>
  <span v-else class="text-gray-500 dark:text-gray-400">System</span>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  actor: {
    type: Object as PropType<AuditLog.Actor | null>,
    default: null,
  },
});

const emits = defineEmits<{
  filter: [userId: number];
}>();
</script>
