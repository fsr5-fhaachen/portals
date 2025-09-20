<script setup lang="ts">
import {
  computed,
  ref,
  onMounted,
  onBeforeUnmount,
  watch,
  PropType,
} from "vue";
import Chart from "primevue/chart";
import colors from "tailwindcss/colors";

const { stats, chartType } = defineProps({
  stats: {
    type: [Object, Number] as PropType<object | number>,
    required: true,
  },
  chartType: {
    type: String,
    default: "pie",
  },
});

const backgroundColors = [
  "#609ffc",
  "#fc6060",
  "#77fc60",
  "#60fcef",
  "#e760fc",
  "#f2fc60",
  "#60eafc",
  "#fc60cb",
  "#fc9760",
];

const isDarkMode = ref(document.documentElement.classList.contains("dark"));
let observer: MutationObserver;

onMounted(() => {
  observer = new MutationObserver(() => {
    isDarkMode.value = document.documentElement.classList.contains("dark");
  });
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
  });
});

onBeforeUnmount(() => {
  observer.disconnect();
});

const chartKey = ref(0);
watch(isDarkMode, () => {
  chartKey.value++; // Force re-render when dark mode changes
});

const chartData = computed(() => {
  const labels = Object.keys(stats).filter((key) => key !== "name");
  const data = labels.map((key) => stats[key]);
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: backgroundColors,
      },
    ],
  };
});

const chartOptions = computed(() => ({
  animation: false,
  plugins: {
    title: {
      display: true,
      //@ts-ignore, when stats is a number, it doesn't have a name property, but we handle that in the template
      text: stats.name,
      color: isDarkMode.value ? colors.gray[400] : colors.gray[500],
    },
    legend: {
      labels: {
        usePointStyle: true,
        generateLabels: (chart: any) => {
          const data = chart.data;
          return data.labels.map((label: string, i: number) => ({
            text: `${label}: ${data.datasets[0].data[i]}`,
            fillStyle: data.datasets[0].backgroundColor[i],
            strokeStyle: data.datasets[0].backgroundColor[i],
            fontColor: isDarkMode.value ? "#fff" : "#000",
            index: i,
          }));
        },
      },
    },
    tooltip: {
      callbacks: {
        label: (context: any) => `${context.label}: ${context.parsed}`,
      },
    },
  },
}));
</script>

<template>
  <Chart
    v-if="chartType != 'total'"
    :key="chartKey"
    :type="chartType"
    :data="chartData"
    :options="chartOptions"
    class="w-full md:w-[15rem]"
  />
  <div class="flex flex-col items-center justify-center" v-else>
    <div
      class="border border-gray-400 border-solid p-2 rounded-lg bg-white dark:bg-gray-900 mt-5"
    >
      <p class="dark:text-grey-400 text-gray-500">Total User Count:</p>
      <p class="text-2xl font-bold text-center dark:text-white">{{ stats }}</p>
    </div>
  </div>
</template>
