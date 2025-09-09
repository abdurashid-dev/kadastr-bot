<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
  >
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Files by Status
    </h3>
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
import { computed } from "vue";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const statusLabels = {
  pending: "Jarayonda",
  waiting: "Bino inshoatga yuborildi",
  accepted: "Tasdiqlangan",
  rejected: "Rad etilgan",
};

const statusColors = ["#F59E0B", "#3B82F6", "#10B981", "#EF4444"];

const chartSeries = computed(() => {
  return Object.entries(props.data)
    .filter(([, count]) => count > 0)
    .map(([, count]) => count);
});

const chartLabels = computed(() => {
  return Object.entries(props.data)
    .filter(([, count]) => count > 0)
    .map(([status]) => statusLabels[status] || status);
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
  colors: statusColors,
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
