<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Files by Type
    </h3>
    <div class="h-64">
      <apexchart
        type="pie"
        :options="chartOptions"
        :series="chartSeries"
        height="256"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const typeLabels = {
  document: 'Hujjat',
  image: 'Rasm',
  video: 'Video',
  audio: 'Audio',
  archive: 'Arxiv',
  other: 'Boshqa'
}

const typeColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#6B7280']

const chartSeries = computed(() => {
  return Object.entries(props.data)
    .filter(([, count]) => count > 0)
    .map(([, count]) => count)
})

const chartLabels = computed(() => {
  return Object.entries(props.data)
    .filter(([, count]) => count > 0)
    .map(([type]) => typeLabels[type] || type)
})

const chartOptions = computed(() => ({
  chart: {
    type: 'pie',
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false
    }
  },
  labels: chartLabels.value,
  colors: typeColors,
  legend: {
    position: 'bottom',
    fontSize: '12px',
    fontWeight: 500,
    markers: {
      width: 8,
      height: 8,
      radius: 4
    },
    itemMargin: {
      horizontal: 12,
      vertical: 8
    }
  },
  plotOptions: {
    pie: {
      expandOnClick: true
    }
  },
  dataLabels: {
    enabled: true,
    formatter: function (val) {
      return val.toFixed(1) + '%'
    },
    style: {
      fontSize: '12px',
      fontWeight: 600,
      colors: ['#FFFFFF']
    },
    dropShadow: {
      enabled: true,
      top: 1,
      left: 1,
      blur: 1,
      color: '#000',
      opacity: 0.45
    }
  },
  tooltip: {
    y: {
      formatter: function (value, { seriesIndex }) {
        const total = chartSeries.value.reduce((a, b) => a + b, 0)
        const percentage = ((value / total) * 100).toFixed(1)
        return `${value} (${percentage}%)`
      }
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
}))
</script>
