<template>
  <div
    class="flex h-fit w-[30rem] items-center justify-center overflow-hidden rounded-3xl border-[16px] border-white bg-[url('/images/random-generator/background/comic-blue.jpg')] bg-cover bg-center shadow-[0_40px_60px_-10px_rgba(0,0,0,0.55),0_80px_120px_-20px_rgba(0,0,0,0.4)]"
  >
    <img
      v-if="props.src"
      :src="props.src"
      loading="lazy"
      decoding="async"
      class="h-fit"
    />
    <div
      v-else
      class="flex h-[30rem] transform flex-col items-center justify-center text-center font-eighty-miles text-8xl text-white"
    >
      <span>{{ props.firstname }}</span>
      <span>{{ props.lastname }}</span>
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
