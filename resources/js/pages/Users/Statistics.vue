<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Foydalanuvchi statistikasi
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Tizimdagi foydalanuvchilar haqida batafsil ma'lumot
          </p>
        </div>
        <Link
          :href="route('users.index')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Orqaga
        </Link>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UsersIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Jami foydalanuvchilar
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.total_users }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Users by Role -->
        <div
          v-for="(count, role) in stats.users_by_role"
          :key="role"
          class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"
        >
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UserIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    {{ getRoleLabel(role) }}
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ count }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            So'nggi foydalanuvchilar
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            Tizimga qo'shilgan so'nggi foydalanuvchilar ro'yxati
          </p>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li
            v-for="user in stats.recent_users"
            :key="user.id"
            class="px-4 py-4 sm:px-6"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ user.name }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ user.email }}
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getRoleBadgeClass(user.role)"
                >
                  {{ getRoleLabel(user.role) }}
                </span>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(user.created_at) }}
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import {
  ArrowLeftIcon,
  UsersIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
})

const getRoleLabel = (role) => {
  const labels = {
    user: 'Foydalanuvchi',
    checker: 'Tekshiruvchi',
    registrator: 'Ro\'yxatga oluvchi',
    ceo: 'Rahbar',
  }
  return labels[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    user: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    checker: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    registrator: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    ceo: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
  }
  return classes[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const formatDate = (date) => {
  const dateObj = new Date(date);
  const day = dateObj.getDate().toString().padStart(2, "0");
  const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
  const year = dateObj.getFullYear();
  const hours = dateObj.getHours().toString().padStart(2, "0");
  const minutes = dateObj.getMinutes().toString().padStart(2, "0");
  
  return `${day}.${month}.${year} ${hours}:${minutes}`;
}
</script>