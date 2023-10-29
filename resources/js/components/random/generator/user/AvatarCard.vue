<template>
  <div
    class="flex h-fit w-[30rem] items-center justify-center border-[16px] border-white bg-[url('/images/random-generator/background/comic-blue.jpg')] bg-cover bg-center"
  >
    <img v-if="props.src" :src="props.src" class="h-fit" />
    <div
      v-else
      class="flex h-[30rem] transform items-center justify-center text-center font-eighty-miles text-8xl text-white"
    >
      <span>{{ props.firstname }} {{ props.lastname }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  src: {
    type: String,
    required: false,
  },
  firstname: {
    type: String,
    required: true,
  },
  lastname: {
    type: String,
    required: true,
  },
});
</script>
