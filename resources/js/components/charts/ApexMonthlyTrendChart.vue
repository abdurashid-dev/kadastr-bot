<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
  >
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{
          selectedPeriod === "day"
            ? "Soatlik yuklash trendi"
            : selectedPeriod === "week"
            ? "Kunlik yuklash trendi"
            : "Oylik yuklash trendi"
        }}
      </h3>

      <!-- Filter Buttons -->
      <div class="flex gap-2">
        <button
          v-for="period in periods"
          :key="period.value"
          @click="selectedPeriod = period.value"
          :class="[
            'px-3 py-1 text-sm font-medium rounded-md transition-colors',
            selectedPeriod === period.value
              ? 'bg-primary text-primary-foreground'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
          ]"
        >
          {{ period.label }}
        </button>
      </div>
    </div>

    <div class="h-64">
      <apexchart type="area" :options="chartOptions" :series="chartSeries" height="256" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const selectedPeriod = ref("month");

const periods = [
  { value: "day", label: "Kun" },
  { value: "week", label: "Hafta" },
  { value: "month", label: "Oy" },
];

const chartData = computed(() => {
  let dataSource;

  if (selectedPeriod.value === "day") {
    // For day view, show hourly data (last 24 hours)
    dataSource = props.data.hourly || {};
  } else if (selectedPeriod.value === "week") {
    // For week view, show daily data (last 30 days)
    dataSource = props.data.daily || {};
  } else {
    // For month view, show monthly data (last 12 months)
    dataSource = props.data.monthly || {};
  }

  return Object.entries(dataSource).sort(([a], [b]) => a.localeCompare(b));
});

const chartSeries = computed(() => [
  {
    name: "Files Uploaded",
    data: chartData.value.map(([, count]) => count),
  },
]);

const chartOptions = computed(() => ({
  chart: {
    type: "area",
    fontFamily: "Inter, sans-serif",
    toolbar: {
      show: false,
    },
    zoom: {
      enabled: false,
    },
  },
  dataLabels: {
    enabled: false,
  },
  stroke: {
    curve: "smooth",
    width: 3,
  },
  colors: ["#3B82F6"],
  fill: {
    type: "gradient",
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.1,
      stops: [0, 90, 100],
    },
  },
  xaxis: {
    categories: chartData.value.map(([dateTime]) => {
      const dateObj = new Date(dateTime);

      // Format as dd.mm.yyyy h:i
      const day = dateObj.getDate().toString().padStart(2, "0");
      const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
      const year = dateObj.getFullYear();
      const hours = dateObj.getHours().toString().padStart(2, "0");
      const minutes = dateObj.getMinutes().toString().padStart(2, "0");

      return `${day}.${month}.${year} ${hours}:${minutes}`;
    }),
    labels: {
      style: {
        fontSize: "12px",
        fontWeight: 500,
        colors: "#6B7280",
      },
    },
    axisBorder: {
      show: false,
    },
    axisTicks: {
      show: false,
    },
  },
  yaxis: {
    labels: {
      style: {
        fontSize: "12px",
        fontWeight: 500,
        colors: "#6B7280",
      },
    },
  },
  grid: {
    borderColor: "#E5E7EB",
    strokeDashArray: 3,
    xaxis: {
      lines: {
        show: false,
      },
    },
  },
  tooltip: {
    x: {
      formatter: function (value, { dataPointIndex }) {
        const dateTime = chartData.value[dataPointIndex][0];
        const dateObj = new Date(dateTime);

        // Format as dd.mm.yyyy h:i
        const day = dateObj.getDate().toString().padStart(2, "0");
        const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
        const year = dateObj.getFullYear();
        const hours = dateObj.getHours().toString().padStart(2, "0");
        const minutes = dateObj.getMinutes().toString().padStart(2, "0");

        return `${day}.${month}.${year} ${hours}:${minutes}`;
      },
    },
    y: {
      formatter: function (value) {
        return `${value} files`;
      },
    },
  },
  markers: {
    size: 4,
    colors: ["#3B82F6"],
    strokeColors: "#FFFFFF",
    strokeWidth: 2,
    hover: {
      size: 6,
    },
  },
}));
</script>
