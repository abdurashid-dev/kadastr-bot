<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Files by Region
    </h3>
    <div class="h-64">
      <apexchart
        type="bar"
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
    .map(([region, data]) => ({
      region,
      count: typeof data === 'object' ? data.count : data
    }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 10) // Show top 10 regions
})

const chartSeries = computed(() => [{
  name: 'Files',
  data: chartData.value.map(item => item.count)
}])

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false
    }
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '60%',
      borderRadius: 4,
      borderRadiusApplication: 'end'
    }
  },
  dataLabels: {
    enabled: false
  },
  colors: ['#3B82F6'], // Blue for files only
  xaxis: {
    categories: chartData.value.map(item => item.region),
    labels: {
      style: {
        fontSize: '11px',
        fontWeight: 500,
        colors: '#6B7280'
      },
      rotate: -45,
      rotateAlways: true,
      maxHeight: 80
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
    y: {
      formatter: function (value) {
        return `${value} files`
      }
    }
  }
}))
</script>
