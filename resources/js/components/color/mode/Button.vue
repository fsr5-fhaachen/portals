<template>
  <div
    @click="toggleColorMode()"
    class="text-gray-500 hover:cursor-pointer hover:text-fhac-mint-dark dark:text-gray-400 dark:hover:text-fhac-mint"
  >
    <FontAwesomeIcon
      :icon="getColorMode() == 'dark' ? ['fas', 'sun'] : ['fas', 'moon']"
    />
  </div>
</template>

<script setup lang="ts">
const { getColorMode, toggleColorMode } = useColorMode();
</script>
