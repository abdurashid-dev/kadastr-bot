<template>
  <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Analytics Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Overview of file approval workflow statistics
        </p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Files -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-gray-500 dark:bg-gray-600 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Total Files
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.total }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Accepted Files -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Accepted Files
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.accepted }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Rejected Files -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Rejected Files
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.rejected }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Files -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Pending Files
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.pending }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Status Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">File Status Distribution</h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Accepted</span>
              <div class="flex items-center space-x-2">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="bg-green-500 h-2 rounded-full" 
                    :style="{ width: getPercentage(stats.accepted, stats.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                  {{ getPercentage(stats.accepted, stats.total) }}%
                </span>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejected</span>
              <div class="flex items-center space-x-2">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="bg-red-500 h-2 rounded-full" 
                    :style="{ width: getPercentage(stats.rejected, stats.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                  {{ getPercentage(stats.rejected, stats.total) }}%
                </span>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</span>
              <div class="flex items-center space-x-2">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="bg-yellow-500 h-2 rounded-full" 
                    :style="{ width: getPercentage(stats.pending, stats.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                  {{ getPercentage(stats.pending, stats.total) }}%
                </span>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Waiting</span>
              <div class="flex items-center space-x-2">
                <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <div 
                    class="bg-blue-500 h-2 rounded-full" 
                    :style="{ width: getPercentage(stats.waiting, stats.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-white w-12 text-right">
                  {{ getPercentage(stats.waiting, stats.total) }}%
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Workflow Summary</h3>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <span class="text-sm font-medium text-green-800 dark:text-green-200">Approval Rate</span>
              <span class="text-lg font-semibold text-green-800 dark:text-green-200">
                {{ getApprovalRate() }}%
              </span>
            </div>
            
            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <span class="text-sm font-medium text-red-800 dark:text-red-200">Rejection Rate</span>
              <span class="text-lg font-semibold text-red-800 dark:text-red-200">
                {{ getRejectionRate() }}%
              </span>
            </div>
            
            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <span class="text-sm font-medium text-blue-800 dark:text-blue-200">In Progress</span>
              <span class="text-lg font-semibold text-blue-800 dark:text-blue-200">
                {{ stats.pending + stats.waiting }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="mt-8 bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Activity</h3>
        </div>
        <div class="p-6">
          <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="mt-2 text-sm">Activity tracking will be available in future updates</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: {
    type: Object,
    required: true
  }
})

const getPercentage = (value, total) => {
  if (total === 0) return 0
  return Math.round((value / total) * 100)
}

const getApprovalRate = () => {
  const { accepted, total } = stats
  if (total === 0) return 0
  return Math.round((accepted / total) * 100)
}

const getRejectionRate = () => {
  const { rejected, total } = stats
  if (total === 0) return 0
  return Math.round((rejected / total) * 100)
}
</script>
