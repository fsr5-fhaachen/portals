<template>
  <div
    @click="toggleColorMode()"
    class="text-gray-500 hover:cursor-pointer hover:text-fhac-mint-dark"
  >
    <FontAwesomeIcon
      :icon="getColorMode() == 'dark' ? ['fas', 'sun'] : ['fas', 'moon']"
      class="mr-2 w-8"
    />
  </div>
</template>

<script setup lang="ts">
const { getColorMode, toggleColorMode } = useColorMode();
</script>
