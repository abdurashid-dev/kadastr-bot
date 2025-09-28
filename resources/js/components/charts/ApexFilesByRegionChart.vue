<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ t('messages.files_by_region') }}
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
import { useTranslations } from "@/composables/useTranslations";

const { t } = useTranslations();

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})

const selectedPeriod = ref('month');

const periods = [
  { value: "day", label: t('messages.day') },
  { value: "week", label: t('messages.week') },
  { value: "month", label: t('messages.month') },
];

// Watch for period changes and update data
watch(selectedPeriod, (newPeriod) => {
  router.get('/dashboard', {
    files_region_period: newPeriod
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['filesRegionData']
  });
});

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
