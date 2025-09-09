<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  MoreHorizontal,
  Users,
  BarChart3,
  Search,
  Filter,
  X,
  CheckCircle,
  AlertCircle,
  Trash2,
  Eye,
  UserCheck,
  Crown,
  Shield,
  User,
  Edit,
} from "lucide-vue-next";

const props = defineProps({
  users: {
    type: Object,
    default: () => ({ data: [], total: 0, links: null }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  roles: {
    type: Array,
    default: () => [],
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

// Simple reactive state
const searchInput = ref(props.filters?.search || "");
const selectedRole = ref(props.filters?.role || "all");
const successMessage = ref(props.flash?.success || "");
const errorMessage = ref("");

// Clear messages after 5 seconds
if (successMessage.value) {
  setTimeout(() => {
    successMessage.value = "";
  }, 5000);
}

// Debounce helper
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

// Search function
const performSearch = () => {
  const searchValue = searchInput.value ? searchInput.value.trim() : "";
  const roleValue = selectedRole.value === "all" ? "" : selectedRole.value;

  const params = {};
  if (searchValue) {
    params.search = searchValue;
  }
  if (roleValue) {
    params.role = roleValue;
  }

  router.get("/users", params, {
    preserveState: true,
    replace: true,
  });
};

// Debounced search
const debouncedSearch = debounce(performSearch, 500);

// Watch for search input changes
watch(searchInput, () => {
  debouncedSearch();
});

const handleRoleChange = (role) => {
  selectedRole.value = role;
  performSearch();
};

const clearFilters = () => {
  searchInput.value = "";
  selectedRole.value = "all";
  performSearch();
};

const updateUserRole = (user, newRole) => {
  if (!user || !newRole || user.role === newRole) return;

  if (confirm(`${user.name || "Foydalanuvchi"}ning rolini o'zgartirishni xohlaysizmi?`)) {
    router.put(
      `/users/${user.id}/role`,
      { role: newRole },
      {
        onSuccess: () => {
          successMessage.value = `${
            user.name || "Foydalanuvchi"
          }ning roli muvaffaqiyatli yangilandi`;
          setTimeout(() => {
            successMessage.value = "";
          }, 5000);
        },
        onError: (errors) => {
          errorMessage.value = errors.role || "Rolni yangilashda xatolik yuz berdi";
          setTimeout(() => {
            errorMessage.value = "";
          }, 5000);
        },
      }
    );
  }
};

const deleteUser = (user) => {
  if (!user || !user.id) return;

  if (confirm(`${user.name || "Foydalanuvchi"}ni o'chirishni xohlaysizmi?`)) {
    router.delete(`/users/${user.id}`, {
      onSuccess: () => {
        successMessage.value = `${
          user.name || "Foydalanuvchi"
        } muvaffaqiyatli o'chirildi`;
        setTimeout(() => {
          successMessage.value = "";
        }, 5000);
      },
      onError: (errors) => {
        errorMessage.value =
          errors.user || "Foydalanuvchini o'chirishda xatolik yuz berdi";
        setTimeout(() => {
          errorMessage.value = "";
        }, 5000);
      },
    });
  }
};

const getRoleLabel = (role) => {
  const labels = {
    user: "Foydalanuvchi",
    checker: "Tekshiruvchi",
    registrator: "Ro'yxatga oluvchi",
    ceo: "Rahbar",
  };
  return labels[role] || role;
};

const getRoleIcon = (role) => {
  const icons = {
    user: User,
    checker: Shield,
    registrator: UserCheck,
    ceo: Crown,
  };
  return icons[role] || User;
};

const formatDate = (dateString) => {
  const dateObj = new Date(dateString);
  const day = dateObj.getDate().toString().padStart(2, "0");
  const month = (dateObj.getMonth() + 1).toString().padStart(2, "0");
  const year = dateObj.getFullYear();
  const hours = dateObj.getHours().toString().padStart(2, "0");
  const minutes = dateObj.getMinutes().toString().padStart(2, "0");
  
  return `${day}.${month}.${year} ${hours}:${minutes}`;
};
</script>

<template>
  <AppLayout>
    <Head title="Foydalanuvchilarni boshqarish" />

    <div class="space-y-8 p-4">
      <!-- Success/Error Messages -->
      <Alert
        v-if="successMessage"
        class="border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
      >
        <CheckCircle class="h-4 w-4" />
        <AlertDescription>{{ successMessage }}</AlertDescription>
      </Alert>

      <Alert v-if="errorMessage" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ errorMessage }}</AlertDescription>
      </Alert>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1
            class="text-2xl font-bold tracking-tight bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
          >
            Foydalanuvchilarni boshqarish
          </h1>
          <p class="text-muted-foreground text-sm mt-1">
            Foydalanuvchilar va ularning rollarini boshqaring
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <Button variant="outline" as-child class="shadow-sm h-9">
            <Link href="/users/statistics">
              <BarChart3 class="mr-2 h-4 w-4" />
              Statistika
            </Link>
          </Button>
          <Button as-child class="shadow-sm h-9">
            <Link href="/users/create">
              <User class="mr-2 h-4 w-4" />
              Yangi foydalanuvchi
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Total Users -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 border border-blue-200/50 dark:border-blue-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-4">
            <div class="flex items-center justify-between mb-2">
              <div
                class="p-2 rounded-lg bg-blue-500/10 group-hover:bg-blue-500/20 transition-colors duration-300"
              >
                <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
            </div>
            <div class="space-y-1">
              <p
                class="text-xs font-medium text-blue-700/70 dark:text-blue-300/70 uppercase tracking-wide"
              >
                Jami foydalanuvchilar
              </p>
              <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ users.total || 0 }}
              </p>
            </div>
          </div>
        </div>

        <!-- Checkers -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 border border-green-200/50 dark:border-green-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-4">
            <div class="flex items-center justify-between mb-2">
              <div
                class="p-2 rounded-lg bg-green-500/10 group-hover:bg-green-500/20 transition-colors duration-300"
              >
                <Shield class="h-5 w-5 text-green-600 dark:text-green-400" />
              </div>
            </div>
            <div class="space-y-1">
              <p
                class="text-xs font-medium text-green-700/70 dark:text-green-300/70 uppercase tracking-wide"
              >
                Tekshiruvchilar
              </p>
              <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                {{ (users.data || []).filter((u) => u.role === "checker").length }}
              </p>
            </div>
          </div>
        </div>

        <!-- Registrators -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/50 dark:to-amber-900/30 border border-amber-200/50 dark:border-amber-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-4">
            <div class="flex items-center justify-between mb-2">
              <div
                class="p-2 rounded-lg bg-amber-500/10 group-hover:bg-amber-500/20 transition-colors duration-300"
              >
                <UserCheck class="h-5 w-5 text-amber-600 dark:text-amber-400" />
              </div>
            </div>
            <div class="space-y-1">
              <p
                class="text-xs font-medium text-amber-700/70 dark:text-amber-300/70 uppercase tracking-wide"
              >
                Ro'yxatga oluvchilar
              </p>
              <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
                {{ (users.data || []).filter((u) => u.role === "registrator").length }}
              </p>
            </div>
          </div>
        </div>

        <!-- CEOs -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-rose-50 to-rose-100 dark:from-rose-950/50 dark:to-rose-900/30 border border-rose-200/50 dark:border-rose-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-4">
            <div class="flex items-center justify-between mb-2">
              <div
                class="p-2 rounded-lg bg-rose-500/10 group-hover:bg-rose-500/20 transition-colors duration-300"
              >
                <Crown class="h-5 w-5 text-rose-600 dark:text-rose-400" />
              </div>
            </div>
            <div class="space-y-1">
              <p
                class="text-xs font-medium text-rose-700/70 dark:text-rose-300/70 uppercase tracking-wide"
              >
                Rahbarlar
              </p>
              <p class="text-2xl font-bold text-rose-900 dark:text-rose-100">
                {{ (users.data || []).filter((u) => u.role === "ceo").length }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Card class="shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center text-lg">
            <Filter class="mr-2 h-5 w-5" />
            Filtrlar
          </CardTitle>
          <CardDescription class="text-sm"
            >Foydalanuvchilarni qidiring va filtrlash</CardDescription
          >
        </CardHeader>
        <CardContent class="pt-0">
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <div class="relative">
                <Search
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  v-model="searchInput"
                  placeholder="Ism, email yoki telefon bo'yicha qidiring..."
                  class="pl-10 h-10"
                />
              </div>
            </div>
            <div class="flex gap-2">
              <Select v-model="selectedRole" @update:model-value="handleRoleChange">
                <SelectTrigger class="w-[180px] h-10">
                  <SelectValue placeholder="Barcha rollar" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Barcha rollar</SelectItem>
                  <SelectItem v-for="role in roles" :key="role" :value="role">
                    {{ getRoleLabel(role) }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <Button variant="outline" @click="clearFilters" class="h-10 px-3">
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Users Table -->
      <Card class="shadow-sm">
        <CardHeader class="pb-4">
          <CardTitle class="flex items-center text-lg">
            <Users class="mr-2 h-5 w-5" />
            Foydalanuvchilar
          </CardTitle>
          <CardDescription class="text-sm">
            {{ users.total || 0 }} ta foydalanuvchi topildi
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-0">
          <div class="rounded-lg border overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50">
                  <TableHead class="font-semibold">Foydalanuvchi</TableHead>
                  <TableHead class="font-semibold">Rol</TableHead>
                  <TableHead class="font-semibold">Fayllar</TableHead>
                  <TableHead class="font-semibold">Qo'shilgan sana</TableHead>
                  <TableHead class="w-[100px] font-semibold">Amallar</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="user in users.data || []"
                  :key="user.id"
                  class="hover:bg-muted/30 transition-colors"
                >
                  <TableCell>
                    <div class="flex items-center space-x-3">
                      <Avatar class="h-10 w-10 ring-2 ring-muted">
                        <AvatarImage :src="user.avatar || ''" :alt="user.name || ''" />
                        <AvatarFallback class="text-sm font-semibold">
                          {{ (user.name || "U").charAt(0).toUpperCase() }}
                        </AvatarFallback>
                      </Avatar>
                      <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground truncate text-sm">
                          {{ user.name || "Noma'lum foydalanuvchi" }}
                        </div>
                        <div class="text-xs text-muted-foreground truncate">
                          {{ user.email || "Email yo'q" }}
                        </div>
                        <div
                          v-if="user.phone_number"
                          class="text-xs text-muted-foreground truncate"
                        >
                          {{ user.phone_number }}
                        </div>
                        <div
                          v-if="user.region"
                          class="text-xs text-muted-foreground truncate"
                        >
                          {{ user.region }}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Select
                      :model-value="user.role"
                      @update:model-value="updateUserRole(user, $event)"
                    >
                      <SelectTrigger class="w-[140px] h-8">
                        <SelectValue>
                          <div class="flex items-center space-x-2">
                            <component :is="getRoleIcon(user.role)" class="h-4 w-4" />
                            <span>{{ getRoleLabel(user.role) }}</span>
                          </div>
                        </SelectValue>
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="user">
                          <div class="flex items-center space-x-2">
                            <User class="h-4 w-4" />
                            <span>Foydalanuvchi</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="checker">
                          <div class="flex items-center space-x-2">
                            <Shield class="h-4 w-4" />
                            <span>Tekshiruvchi</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="registrator">
                          <div class="flex items-center space-x-2">
                            <UserCheck class="h-4 w-4" />
                            <span>Ro'yxatga oluvchi</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="ceo">
                          <div class="flex items-center space-x-2">
                            <Crown class="h-4 w-4" />
                            <span>Rahbar</span>
                          </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary" class="font-medium text-xs">
                      {{ user.uploaded_files_count || 0 }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-muted-foreground text-xs">
                    {{ formatDate(user.created_at) }}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="h-7 w-7 p-0 hover:bg-muted">
                          <MoreHorizontal class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem as-child>
                          <Link
                            :href="`/users/${user.id}`"
                            class="flex items-center w-full"
                          >
                            <Eye class="mr-2 h-4 w-4" />
                            Profilni ko'rish
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                          <Link
                            :href="`/users/${user.id}/edit`"
                            class="flex items-center w-full"
                          >
                            <Edit class="mr-2 h-4 w-4" />
                            Tahrirlash
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          @click="deleteUser(user)"
                          class="text-destructive focus:text-destructive cursor-pointer"
                        >
                          <Trash2 class="mr-2 h-4 w-4" />
                          Foydalanuvchini o'chirish
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div v-if="users && users.links" class="mt-4">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="users.prev_page_url"
                  :href="users.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  Oldingi
                </Link>
                <Link
                  v-if="users.next_page_url"
                  :href="users.next_page_url"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-input bg-background text-sm font-medium rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  Keyingi
                </Link>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-muted-foreground">
                    {{ users.from || 0 }} dan {{ users.to || 0 }} gacha, jami
                    {{ users.total || 0 }} ta natija
                  </p>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="link in users.links || []" :key="link.label">
                      <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors"
                        :class="[
                          link.active
                            ? 'z-10 bg-primary border-primary text-primary-foreground'
                            : 'bg-background border-input text-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                      />
                      <span
                        v-else
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border border-input bg-muted text-muted-foreground text-sm font-medium cursor-not-allowed"
                      />
                    </template>
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
