<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Monthly Upload Trend
    </h3>
    <div class="h-64">
      <apexchart
        type="area"
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

const chartData = computed(() => {
  return Object.entries(props.data)
    .sort(([a], [b]) => a.localeCompare(b))
})

const chartSeries = computed(() => [{
  name: 'Files Uploaded',
  data: chartData.value.map(([, count]) => count)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false
    },
    zoom: {
      enabled: false
    }
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 3
  },
  colors: ['#3B82F6'],
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.1,
      stops: [0, 90, 100]
    }
  },
  xaxis: {
    categories: chartData.value.map(([month]) => {
      const [year, monthNum] = month.split('-')
      const date = new Date(year, monthNum - 1)
      return date.toLocaleDateString('uz-UZ', { month: 'short', year: 'numeric' })
    }),
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 500,
        colors: '#6B7280'
      }
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 500,
        colors: '#6B7280'
      }
    }
  },
  grid: {
    borderColor: '#E5E7EB',
    strokeDashArray: 3,
    xaxis: {
      lines: {
        show: false
      }
    }
  },
  tooltip: {
    x: {
      format: 'MMM yyyy'
    },
    y: {
      formatter: function (value) {
        return `${value} files`
      }
    }
  },
  markers: {
    size: 4,
    colors: ['#3B82F6'],
    strokeColors: '#FFFFFF',
    strokeWidth: 2,
    hover: {
      size: 6
    }
  }
}))
</script>
