<template>
  {{ generatorData }}
</template>

<script setup lang="ts">
import { ref, PropType, onBeforeUnmount } from "vue";

const generator = defineProps({
  state: {
    type: String,
    required: true,
  },
  user: {
    type: Object as PropType<App.Models.User>,
    required: true,
  },
});

const generatorData = ref(generator);
const isFetchingGenerator = ref(false);

const fetchCourses = async () => {
  if (isFetchingGenerator.value) {
    return;
  }

  isFetchingGenerator.value = true;

  const response = await fetch("/api/random-generator/state", {
    method: "GET",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const data = await response.json();

    // map the data to the courses
    generatorData.value = ;
    generatorData.value = generator.map((generator) => {
      const generatorData = data.find(
        (generatorData: any) => generatorData == generator
      );

      return {
        ...generator,
        users: generatorData.amount,
      };
    });
  }

  isFetchingGenerator.value = false;
};

const generatorInterval = setInterval(fetchCourses, 500);

onBeforeUnmount(() => {
  clearInterval(generatorInterval);
});
</script>
