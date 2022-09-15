<template>
  <LayoutDashboardContent>
    <template #title>Event "{{ event.name }}" einteilen</template>

    <AppButton
      @click="submit"
      theme="danger"
      class="text-center text-2xl uppercase"
    >
      Ich bin mir sicher
    </AppButton>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";
import { Inertia } from "@inertiajs/inertia";

const { event } = defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});

const submit = () => {
  Inertia.post("/dashboard/admin/event/" + event.id + "/submit");
};
</script>
