<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
  >
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Fayllar holati bo'yicha
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
      <apexchart
        type="donut"
        :options="chartOptions"
        :series="chartSeries"
        height="256"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const selectedPeriod = ref('month');

const periods = [
  { value: "day", label: "Kun" },
  { value: "week", label: "Hafta" },
  { value: "month", label: "Oy" },
];

// Watch for period changes and update data
watch(selectedPeriod, (newPeriod) => {
  router.get('/dashboard', {
    status_period: newPeriod
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['statusData']
  });
});

const statusLabels = {
  pending: "Jarayonda",
  waiting: "Bino inshoatga yuborildi",
  accepted: "Tasdiqlangan",
  rejected: "Rad etilgan",
};

const statusColors = {
  pending: "#F59E0B", // Yellow
  accepted: "#10B981", // Green
  rejected: "#EF4444", // Red
};

const chartSeries = computed(() => {
  return Object.entries(props.data)
    .filter(([status, count]) => count > 0 && status !== 'waiting')
    .map(([, count]) => count);
});

const chartLabels = computed(() => {
  return Object.entries(props.data)
    .filter(([status, count]) => count > 0 && status !== 'waiting')
    .map(([status]) => statusLabels[status] || status);
});

const chartColors = computed(() => {
  return Object.entries(props.data)
    .filter(([status, count]) => count > 0 && status !== 'waiting')
    .map(([status]) => statusColors[status] || '#6B7280');
});

const chartOptions = computed(() => ({
  chart: {
    type: "donut",
    fontFamily: "Inter, sans-serif",
    toolbar: {
      show: false,
    },
  },
  labels: chartLabels.value,
  colors: chartColors.value,
  legend: {
    position: "bottom",
    fontSize: "12px",
    fontWeight: 500,
    markers: {
      width: 8,
      height: 8,
      radius: 4,
    },
    itemMargin: {
      horizontal: 12,
      vertical: 8,
    },
  },
  plotOptions: {
    pie: {
      donut: {
        size: "60%",
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: "14px",
            fontWeight: 600,
            color: "#374151",
          },
          value: {
            show: true,
            fontSize: "20px",
            fontWeight: 700,
            color: "#111827",
          },
          total: {
            show: true,
            showAlways: false,
            label: "Jami",
            fontSize: "14px",
            fontWeight: 600,
            color: "#6B7280",
          },
        },
      },
    },
  },
  dataLabels: {
    enabled: false,
  },
  tooltip: {
    y: {
      formatter: function (value, { seriesIndex }) {
        const total = chartSeries.value.reduce((a, b) => a + b, 0);
        const percentage = ((value / total) * 100).toFixed(1);
        return `${value} (${percentage}%)`;
      },
    },
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          width: 200,
        },
        legend: {
          position: "bottom",
        },
      },
    },
  ],
}));
</script>
