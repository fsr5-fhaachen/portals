<template>
  <LayoutDashboardContent>
    <template #title>{{ event.name }}</template>

    <CardBase>
      Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
      eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
      voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet
      clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit
      amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam
      nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed
      diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.
      Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor
      sit amet.
    </CardBase>
  </LayoutDashboardContent>
</template>

<script setup lang="ts">
import { PropType } from "vue";

defineProps({
  event: {
    type: Object as PropType<App.Models.Event>,
    required: true,
  },
});
</script>
