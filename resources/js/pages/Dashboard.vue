<script setup>
import { computed, ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import ApexStatusChart from "@/components/charts/ApexStatusChart.vue";
import ApexRegionChart from "@/components/charts/ApexRegionChart.vue";
import ApexFilesByRegionChart from "@/components/charts/ApexFilesByRegionChart.vue";
import ApexMonthlyTrendChart from "@/components/charts/ApexMonthlyTrendChart.vue";
import RegionStatisticsTable from "@/components/RegionStatisticsTable.vue";
import { useTranslations } from "@/composables/useTranslations";

const { t } = useTranslations();

const breadcrumbs = [
  {
    title: t("messages.dashboard"),
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
  statusData: {
    type: Object,
    required: true,
  },
  regionData: {
    type: Object,
    required: true,
  },
  filesRegionData: {
    type: Object,
    required: true,
  },
  trendData: {
    type: Object,
    required: true,
  },
  regionStatistics: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  botUsername: {
    type: String,
    default: null,
  },
  hasTelegramId: {
    type: Boolean,
    default: false,
  },
});

// Computed properties for statistics
const totalFiles = computed(() => props.fileStats.total_files || 0);
const filesByStatus = computed(() => props.fileStats.files_by_status || {});
const filesByRegion = computed(() => props.fileStats.files_by_region || {});
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
    pending: t("messages.status_pending"),
    waiting: t("messages.status_waiting"),
    accepted: t("messages.status_accepted"),
    rejected: t("messages.status_rejected"),
  };
  return labels[status] || status;
};

// Get status color
const getStatusColor = (status) => {
  const colors = {
    pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
    waiting: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    accepted: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return (
    colors[status] || "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
  );
};

// Format date
const formatDate = (date) => {
  const dateObj = new Date(date);
  const day = dateObj.getDate().toString().padStart(2, "0");
  const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
  const year = dateObj.getFullYear();
  const hours = dateObj.getHours().toString().padStart(2, "0");
  const minutes = dateObj.getMinutes().toString().padStart(2, "0");

  return `${day}.${month}.${year} ${hours}:${minutes}`;
};

const getRoleLabel = (role) => {
  const labels = {
    user: t("messages.role_user"),
    checker: t("messages.role_checker"),
    registrator: t("messages.role_registrator"),
    ceo: t("messages.role_ceo"),
    branch_agency_head: t("messages.role_branch_agency_head"),
    branch_chamber_head: t("messages.role_branch_chamber_head"),
    branch_deputy: t("messages.role_branch_deputy"),
    onec_developer: t("messages.role_onec_developer"),
  };
  return labels[role] || role;
};

// Telegram connection state
const isConnecting = ref(false);
const connectionError = ref(null);


// Handle Telegram connection
const connectToTelegram = async () => {
  isConnecting.value = true;
  connectionError.value = null;
  
  try {
    const response = await fetch('/telegram/connect', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Open Telegram in a new tab/window
      window.open(data.deepLink, '_blank');
      
      // Show success message
      setTimeout(() => {
        // Refresh the page to update the connection status
        router.reload();
      }, 2000);
    } else {
      connectionError.value = data.message || 'Failed to connect to Telegram';
    }
  } catch (error) {
    connectionError.value = 'Network error. Please try again.';
  } finally {
    isConnecting.value = false;
  }
};
</script>

<template>
  <Head :title="t('messages.dashboard')" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <!-- Welcome Section -->
      <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
      >
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          {{ t("messages.dashboard_welcome", { name: user.name }) }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          <span class="font-semibold capitalize">{{ getRoleLabel(user.role) }}</span>
        </p>
      </div>

      <!-- Telegram Connection Card -->
      <div
        v-if="!hasTelegramId && botUsername"
        class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 text-white"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mr-4">
              <svg
                class="w-6 h-6 text-white"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-white">
                Telegram bilan bog'lanish
              </h3>
              <p class="text-blue-100 text-sm">
                Fayllaringizni Telegram orqali yuklash va xabarlar olish uchun bog'laning
              </p>
            </div>
          </div>
          <button
            @click="connectToTelegram"
            :disabled="isConnecting"
            class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
          >
            <svg
              v-if="isConnecting"
              class="w-5 h-5 mr-2 animate-spin"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            <svg
              v-else
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
            {{ isConnecting ? 'Bog\'lanmoqda...' : 'Bog\'lanish' }}
          </button>
        </div>
        
        <!-- Error Message -->
        <div
          v-if="connectionError"
          class="mt-4 p-4 bg-red-500/20 border border-red-500/30 rounded-lg"
        >
          <div class="flex items-center">
            <svg
              class="w-5 h-5 text-red-400 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span class="text-red-200 text-sm">{{ connectionError }}</span>
          </div>
        </div>
      </div>

      <!-- Telegram Connected Status -->
      <div
        v-if="hasTelegramId"
        class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4"
      >
        <div class="flex items-center">
          <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
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
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
              Telegram bilan bog'langan
            </h4>
            <p class="text-xs text-green-600 dark:text-green-400">
              Sizning hisobingiz Telegram bot bilan bog'langan
            </p>
          </div>
        </div>
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fayllar</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Fayl boshqaruvi</p>
          <Link
            href="/approval/history"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            So'nggi faoliyat
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fayllar</h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Kutilayotgan fayllar</p>
          <Link
            href="/approval/pending"
            class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
          >
            Kutilayotgan fayllar
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

        <!-- Bino inshoat xodimi Role Card -->
        <div
          v-if="user.role === 'registrator'"
          class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center mb-4">
            <div
              class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3"
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
              Kutilayotgan fayllar
            </h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Kutilayotgan fayllar</p>
          <Link
            href="/approval/waiting"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            Kutilayotgan fayllar
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

        <!-- Admin Role Card -->
        <div
          v-if="user.role === 'admin'"
          class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center mb-4">
            <div
              class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3"
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
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Sozlamalar
            </h3>
          </div>
          <p class="text-gray-600 dark:text-gray-400 mb-4">Foydalanuvchi boshqaruvi</p>
          <Link
            href="/users"
            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
          >
            Foydalanuvchilar
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
          {{ t("messages.overview") }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ totalFiles }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
              {{ t("messages.total_files") }}
            </div>
          </div>
          <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ statusCounts.accepted }}
            </div>
            <div class="text-sm text-green-600 dark:text-green-400">
              {{ t("messages.status_accepted") }} {{ t("messages.files") }}
            </div>
          </div>
          <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
              {{ statusCounts.rejected }}
            </div>
            <div class="text-sm text-red-600 dark:text-red-400">
              {{ t("messages.status_rejected") }} {{ t("messages.files") }}
            </div>
          </div>
          <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
              {{ statusCounts.pending }}
            </div>
            <div class="text-sm text-yellow-600 dark:text-yellow-400">
              {{ t("messages.status_pending") }} {{ t("messages.files") }}
            </div>
          </div>
          <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {{ statusCounts.waiting }}
            </div>
            <div class="text-sm text-blue-600 dark:text-blue-400">
              {{ t("messages.status_waiting") }} {{ t("messages.files") }}
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
        <!-- Status Chart -->
        <ApexStatusChart :data="statusData" />
      </div>

      <!-- Additional Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Region Chart (with registered count) -->
        <ApexRegionChart :data="regionData" />

        <!-- Files by Region Chart (files only) -->
        <ApexFilesByRegionChart :data="filesRegionData" />
      </div>

      <!-- Monthly Trend Chart -->
      <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
        <ApexMonthlyTrendChart :data="trendData" />
      </div>

      <!-- Region Statistics Table -->
      <RegionStatisticsTable
        :region-statistics="regionStatistics"
        :filters="filters"
      />

      <!-- Recent Files -->
      <div
        class="bg-white dark:bg-gray-800 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6"
      >
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
          {{ t("messages.recent_files") }}
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
                :class="getStatusColor(file.status)"
                class="px-2 py-1 rounded-full text-xs font-medium"
              >
                {{ getStatusLabel(file.status) }}
              </span>
              <Link
                :href="`/files/${file.id}`"
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
          {{ t("messages.no_recent_files") }}
        </div>
      </div>
    </div>
  </AppLayout>
</template>
