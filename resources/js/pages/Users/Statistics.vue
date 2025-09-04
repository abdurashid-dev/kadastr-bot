<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Users, BarChart3, RefreshCw, UserCheck, UserX, UserPlus, Crown } from 'lucide-vue-next'

const props = defineProps({
  statistics: Object,
})

const stats = ref(props.statistics)

const fetchStatistics = async () => {
  try {
    const response = await fetch('/users/statistics')
    const data = await response.json()
    stats.value = data
  } catch (error) {
    console.error('Error fetching statistics:', error)
  }
}

onMounted(() => {
  fetchStatistics()
})

const getRoleColor = (role) => {
  const colors = {
    user: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    checker: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    registrator: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    ceo: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  }
  return colors[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
}

const getRoleIcon = (role) => {
  const icons = {
    user: UserPlus,
    checker: UserCheck,
    registrator: UserX,
    ceo: Crown,
  }
  return icons[role] || UserPlus
}
</script>

<template>
  <AppLayout>
    <Head title="User Statistics" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="ghost" size="sm" as-child>
            <Link href="/users">
              <ArrowLeft class="mr-2 h-4 w-4" />
              Back to Users
            </Link>
          </Button>
          <div>
            <h1 class="text-3xl font-bold tracking-tight">User Statistics</h1>
            <p class="text-muted-foreground">Overview of user activity and distribution</p>
          </div>
        </div>
        <Button variant="outline" @click="fetchStatistics">
          <RefreshCw class="mr-2 h-4 w-4" />
          Refresh
        </Button>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Users -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats?.total_users || 0 }}</div>
            <p class="text-xs text-muted-foreground">Registered users</p>
          </CardContent>
        </Card>

        <!-- Users by Role -->
        <Card
          v-for="(count, role) in stats?.users_by_role"
          :key="role"
        >
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              {{ role.charAt(0).toUpperCase() + role.slice(1) }}s
            </CardTitle>
            <component :is="getRoleIcon(role)" class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ count }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats?.total_users ? Math.round((count / stats.total_users) * 100) : 0 }}% of total
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Role Distribution and Recent Users -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Role Distribution -->
        <Card>
          <CardHeader>
            <CardTitle>Role Distribution</CardTitle>
            <CardDescription>Breakdown of users by role</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div
                v-for="(count, role) in stats?.users_by_role"
                :key="role"
                class="flex items-center justify-between"
              >
                <div class="flex items-center space-x-3">
                  <Badge :class="getRoleColor(role)">
                    {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                  </Badge>
                  <span class="text-sm font-medium">{{ count }} users</span>
                </div>
                <div class="text-sm text-muted-foreground">
                  {{ stats?.total_users ? Math.round((count / stats.total_users) * 100) : 0 }}%
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Users -->
        <Card>
          <CardHeader>
            <CardTitle>Recent Users</CardTitle>
            <CardDescription>Latest registered users</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div
                v-for="user in stats?.recent_users"
                :key="user.id"
                class="flex items-center justify-between"
              >
                <div class="flex items-center space-x-3">
                  <Avatar class="h-8 w-8">
                    <AvatarImage :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="text-xs">{{ user.name.charAt(0).toUpperCase() }}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p class="text-sm font-medium">{{ user.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <Badge :class="getRoleColor(user.role)" class="text-xs">
                    {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
                  </Badge>
                  <span class="text-xs text-muted-foreground">
                    {{ new Date(user.created_at).toLocaleDateString() }}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Quick Actions -->
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>Common administrative tasks</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <Button as-child>
              <Link href="/users">
                <Users class="mr-2 h-4 w-4" />
                Manage Users
              </Link>
            </Button>
            <Button variant="outline" as-child>
              <Link href="/approval/analytics">
                <BarChart3 class="mr-2 h-4 w-4" />
                File Analytics
              </Link>
            </Button>
            <Button variant="outline" @click="fetchStatistics">
              <RefreshCw class="mr-2 h-4 w-4" />
              Refresh Data
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>