<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { MoreHorizontal, Users, BarChart3, Search, Filter, X } from 'lucide-vue-next'

const props = defineProps({
  users: Object,
  filters: Object,
  roles: Array,
})

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')

// Simple debounce function
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

const debouncedSearch = debounce((value) => {
  router.get('/users', { search: value, role: roleFilter.value }, {
    preserveState: true,
    replace: true,
  })
}, 300)

watch(search, debouncedSearch)

const handleRoleFilter = (role) => {
  roleFilter.value = role
  router.get('/users', { search: search.value, role: role }, {
    preserveState: true,
    replace: true,
  })
}

const clearFilters = () => {
  search.value = ''
  roleFilter.value = ''
  router.get('/users', {}, {
    preserveState: true,
    replace: true,
  })
}

const updateUserRole = async (user, newRole) => {
  try {
    await router.put(`/users/${user.id}/role`, { role: newRole }, {
      preserveState: true,
    })
  } catch (error) {
    console.error('Error updating user role:', error)
  }
}

const deleteUser = async (user) => {
  if (confirm(`Are you sure you want to delete ${user.name}?`)) {
    try {
      await router.delete(`/users/${user.id}`, {
        preserveState: true,
      })
    } catch (error) {
      console.error('Error deleting user:', error)
    }
  }
}

const getRoleColor = (role) => {
  const colors = {
    user: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
    checker: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    registrator: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    ceo: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  }
  return colors[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
}
</script>

<template>
  <AppLayout>
    <Head title="User Management" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
          <p class="text-muted-foreground">Manage users and their roles</p>
        </div>
        <div class="flex items-center space-x-2">
          <Button variant="outline" as-child>
            <Link href="/users/statistics">
              <BarChart3 class="mr-2 h-4 w-4" />
              Statistics
            </Link>
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center">
            <Filter class="mr-2 h-5 w-5" />
            Filters
          </CardTitle>
          <CardDescription>Search and filter users</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  v-model="search"
                  placeholder="Search users by name, email, or phone..."
                  class="pl-10"
                />
              </div>
            </div>
            <div class="flex gap-2">
              <Select :value="roleFilter" @update:model-value="handleRoleFilter">
                <SelectTrigger class="w-[180px]">
                  <SelectValue placeholder="All Roles" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Roles</SelectItem>
                  <SelectItem v-for="role in roles" :key="role" :value="role">
                    {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <Button variant="outline" @click="clearFilters">
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Users Table -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center">
            <Users class="mr-2 h-5 w-5" />
            Users
          </CardTitle>
          <CardDescription>
            {{ users.total }} users found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Role</TableHead>
                  <TableHead>Files</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead class="w-[70px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="user in users.data" :key="user.id" class="hover:bg-muted/50">
                  <TableCell>
                    <div class="flex items-center space-x-3">
                      <Avatar class="h-10 w-10">
                        <AvatarImage :src="user.avatar" :alt="user.name" />
                        <AvatarFallback>{{ user.name.charAt(0).toUpperCase() }}</AvatarFallback>
                      </Avatar>
                      <div>
                        <div class="font-medium">{{ user.name }}</div>
                        <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                        <div v-if="user.phone_number" class="text-sm text-muted-foreground">
                          {{ user.phone_number }}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Select
                      :value="user.role"
                      @update:model-value="updateUserRole(user, $event)"
                    >
                      <SelectTrigger class="w-[140px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="user">User</SelectItem>
                        <SelectItem value="checker">Checker</SelectItem>
                        <SelectItem value="registrator">Registrator</SelectItem>
                        <SelectItem value="ceo">CEO</SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary">{{ user.uploaded_files_count }}</Badge>
                  </TableCell>
                  <TableCell class="text-muted-foreground">
                    {{ new Date(user.created_at).toLocaleDateString() }}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="h-8 w-8 p-0">
                          <MoreHorizontal class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                          <Link :href="`/users/${user.id}`">View Profile</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          @click="deleteUser(user)"
                          class="text-destructive focus:text-destructive"
                        >
                          Delete User
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div v-if="users.links" class="mt-6">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="users.prev_page_url"
                  :href="users.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md hover:bg-accent hover:text-accent-foreground"
                >
                  Previous
                </Link>
                <Link
                  v-if="users.next_page_url"
                  :href="users.next_page_url"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md hover:bg-accent hover:text-accent-foreground"
                >
                  Next
                </Link>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-muted-foreground">
                    Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                  </p>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <Link
                      v-for="link in users.links"
                      :key="link.label"
                      :href="link.url"
                      v-html="link.label"
                      class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                      :class="[
                        link.active
                          ? 'z-10 bg-primary border-primary text-primary-foreground'
                          : 'bg-background border-input text-foreground hover:bg-accent hover:text-accent-foreground',
                      ]"
                    />
                  </nav>
                </div>
              </div>
            </nav>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>