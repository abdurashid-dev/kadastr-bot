<script setup>
import { computed } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import ApexStatusChart from "@/components/charts/ApexStatusChart.vue";
import ApexRegionChart from "@/components/charts/ApexRegionChart.vue";
import ApexFileTypeChart from "@/components/charts/ApexFileTypeChart.vue";
import ApexMonthlyTrendChart from "@/components/charts/ApexMonthlyTrendChart.vue";

const breadcrumbs = [
  {
    title: "Dashboard",
    href: "/dashboard",
  },
];

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  fileStats: {
    type: Object,
    required: true,
  },
});

// Computed properties for statistics
const totalFiles = computed(() => props.fileStats.total_files || 0);
const filesByStatus = computed(() => props.fileStats.files_by_status || {});
const filesByRegion = computed(() => props.fileStats.files_by_region || {});
const filesByType = computed(() => props.fileStats.files_by_type || {});
const recentFiles = computed(() => props.fileStats.recent_files || []);
const monthlyStats = computed(() => props.fileStats.monthly_stats || {});

// Status counts
const statusCounts = computed(() => ({
  pending: filesByStatus.value.pending || 0,
  waiting: filesByStatus.value.waiting || 0,
  accepted: filesByStatus.value.accepted || 0,
  rejected: filesByStatus.value.rejected || 0,
}));

// Get status label
const getStatusLabel = (status) => {
  const labels = {
    pending: "Kutilmoqda",
    waiting: "Bino inshoatga yuborildi",
    accepted: "Tasdiqlangan",
    rejected: "Rad etilgan",
  };
  return labels[status] || status;
};

// Get status badge class
const getStatusBadgeClass = (status) => {
  const classes = {
    pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    waiting: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    accepted: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
  };
  return (
    classes[status] || "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
  );
};

// Format date
const formatDate = (date) => {
  return new Date(date).toLocaleDateString("uz-UZ", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <!-- Welcome Section -->
      <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
      >
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Welcome back, {{ user.name }}!
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          You are logged in as a
          <span class="font-semibold capitalize">{{ user.role }}</span>
        </p>
      </div>

      <!-- Role-Based Navigation -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-3">
        <!-- User Role Card -->
        <div
          v-if="user.role === 'user'"
          class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center mb-4">
            <div
              class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3"
            >
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Files</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            View your uploaded files and their approval status
          </p>
          <Link
            href="/approval/history"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            View History
            <svg
              class="w-4 h-4 ml-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
        </div>

        <!-- Checker Role Card -->
        <div
          v-if="user.role === 'checker'"
          class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center mb-4">
            <div
              class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center mr-3"
            >
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Review Files
            </h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            Review and approve/reject pending file uploads
          </p>
          <Link
            href="/approval/pending"
            class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
          >
            Review Pending
            <svg
              class="w-4 h-4 ml-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
        </div>

        <!-- Registrator Role Card -->
        <div
          v-if="user.role === 'registrator'"
          class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center mb-4">
            <div
              class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3"
            >
              <svg
                class="w-6 h-6 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Final Approval
            </h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            Final review and approval of checker-approved files
          </p>
          <Link
            href="/approval/waiting"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Review Waiting
            <svg
              class="w-4 h-4 ml-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </Link>
        </div>
      </div>

      <!-- Quick Stats -->
      <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
      >
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          Quick Overview
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ totalFiles }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Jami fayllar</div>
          </div>
          <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ statusCounts.accepted }}
            </div>
            <div class="text-sm text-green-600 dark:text-green-400">Tasdiqlangan</div>
          </div>
          <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
              {{ statusCounts.rejected }}
            </div>
            <div class="text-sm text-red-600 dark:text-red-400">Rad etilgan</div>
          </div>
          <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
              {{ statusCounts.pending }}
            </div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">Kutilmoqda</div>
          </div>
          <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ statusCounts.waiting }}
            </div>
            <div class="text-sm text-blue-600 dark:text-blue-400">
              Bino inshoatga yuborildi
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Chart -->
        <ApexStatusChart :data="filesByStatus" />

        <!-- Region Chart -->
        <ApexRegionChart :data="filesByRegion" />
      </div>

      <!-- Additional Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- File Type Chart -->
        <ApexFileTypeChart :data="filesByType" />

        <!-- Monthly Trend Chart -->
        <ApexMonthlyTrendChart :data="monthlyStats" />
      </div>

      <!-- Recent Files -->
      <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
      >
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          So'nggi yuklangan fayllar
        </h2>
        <div v-if="recentFiles.length > 0" class="space-y-3">
          <div
            v-for="file in recentFiles"
            :key="file.id"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div class="flex items-center space-x-3">
              <div
                class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-4 h-4 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
              </div>
              <div>
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ file.name }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ file.user?.name }} • {{ formatDate(file.created_at) }}
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusBadgeClass(file.status)"
              >
                {{ getStatusLabel(file.status) }}
              </span>
              <Link
                :href="route('files.show', file.id)"
                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </Link>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
          <svg
            class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <p>Hali hech qanday fayl yuklanmagan</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
