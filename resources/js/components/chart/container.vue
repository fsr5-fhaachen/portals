<script setup lang="ts">
import { ref, onMounted } from "vue";
import Chart from "primevue/chart";

const { stats, chartType } = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  chartType: {
    type: String,
    default: "pie",
  },
});

const chartData = ref();
const chartOptions = ref();

onMounted(() => {
  chartData.value = setChartData();
  chartOptions.value = setChartOptions();
});

const labels = ref(<String>[]);
const data = ref(<Number>[]);

for (let stat in stats) {
  if (stat !== "name"){
    labels.value.push(stat);
    data.value.push(stats[stat]);
  }
}

const setChartData = () => {
  return {
    labels: labels,
    datasets: [
      {
        data: data,
        backgroundColor:['#609ffc', '#fc6060','#77fc60',
          '#60fcef','#e760fc','#f2fc60',
          '#60eafc','#fc60cb','#fc9760'],
      },
    ],
  };
};

const setChartOptions = () => {
  const documentStyle = getComputedStyle(document.documentElement);
  const textColor = documentStyle.getPropertyValue("--p-text-color");

  return {
    plugins: {
      title: {
        display: true,
        text: stats.name,
      },
      legend: {
        labels: {
          usePointStyle: true,
          color: textColor,
          // Show value in legend label
          generateLabels: (chart) => {
            const data = chart.data;
            return data.labels.map((label, i) => ({
              text: `${label}: ${data.datasets[0].data[i]}`,
              fillStyle: data.datasets[0].backgroundColor[i],
              strokeStyle: data.datasets[0].backgroundColor[i],
              index: i,
            }));
          },
        },
      },
      tooltip: {
        callbacks: {
          label: (context) => {
            // Only show label and value in tooltip
            return `${context.label}: ${context.parsed}`;
          },
        },
      },
    },
  };
};
//
</script>

<template>
  <chart
    :type="chartType"
    :data="chartData"
    :options="chartOptions"
    class="w-full md:w-[15rem]"
  ></chart>
</template>

<style scoped></style>
