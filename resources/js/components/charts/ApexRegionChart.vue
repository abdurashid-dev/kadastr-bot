<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Hududlar bo'yicha fayllar
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
        type="bar"
        :options="chartOptions"
        :series="chartSeries"
        height="256"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from "@inertiajs/vue3";

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const selectedPeriod = ref('month');

const periods = [
  { value: "day", label: "Kun" },
  { value: "week", label: "Hafta" },
  { value: "month", label: "Oy" },
];

// Watch for period changes and update data
watch(selectedPeriod, (newPeriod) => {
  router.get('/dashboard', {
    region_period: newPeriod
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['regionData']
  });
});

const chartData = computed(() => {
  return Object.entries(props.data)
    .sort(([,a], [,b]) => {
      // Sort by registered_count if available, otherwise by count
      const aRegistered = typeof a === 'object' ? a.registered_count : 0
      const bRegistered = typeof b === 'object' ? b.registered_count : 0
      const aCount = typeof a === 'object' ? a.count : a
      const bCount = typeof b === 'object' ? b.count : b
      
      // Primary sort by registered_count (descending), secondary by count (descending)
      if (aRegistered !== bRegistered) {
        return bRegistered - aRegistered
      }
      return bCount - aCount
    })
    .slice(0, 10) // Show top 10 regions
})

const chartSeries = computed(() => {
  const hasRegisteredCount = chartData.value.some(([, data]) => 
    typeof data === 'object' && data.registered_count > 0
  )
  
  if (hasRegisteredCount) {
    return [
      {
        name: 'Files',
        data: chartData.value.map(([, data]) => typeof data === 'object' ? data.count : data)
      },
      {
        name: 'Migratsiya bo\'lganlar',
        data: chartData.value.map(([, data]) => typeof data === 'object' ? data.registered_count : 0)
      }
    ]
  }
  
  return [{
    name: 'Files',
    data: chartData.value.map(([, data]) => typeof data === 'object' ? data.count : data)
  }]
})

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
  colors: ['#3B82F6', '#10B981'], // Blue for files, Green for registered count
  xaxis: {
    categories: chartData.value.map(([region]) => region),
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
      formatter: function (value, { seriesIndex, dataPointIndex }) {
        const seriesNames = ['Files', 'Migratsiya bo\'lganlar']
        const seriesName = seriesNames[seriesIndex] || 'Files'
        return `${value} ${seriesName.toLowerCase()}`
      }
    }
  }
}))
</script>
