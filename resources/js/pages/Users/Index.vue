<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { useTranslations } from "@/composables/useTranslations";
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
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
  ChevronDown as ChevronDownIcon,
  Send,
} from "lucide-vue-next";

const { t } = useTranslations();

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
const perPage = ref(props.filters?.per_page || 15);
const successMessage = ref(props.flash?.success || "");
const errorMessage = ref("");

// Selection state
const selectedUsers = ref([]);
const selectAllMode = ref(false); // Track if "all users" across all pages are selected

// Message dialog state
const isMessageDialogOpen = ref(false);
const messageText = ref("");
const isSending = ref(false);

const allSelected = computed(() => {
  if (selectAllMode.value) return true;
  const userIds = (props.users.data || []).map((u) => u.id);
  return userIds.length > 0 && userIds.every((id) => selectedUsers.value.includes(id));
});

const someSelected = computed(() => {
  return selectedUsers.value.length > 0 && !allSelected.value && !selectAllMode.value;
});

const selectCurrentPage = () => {
  selectAllMode.value = false;
  selectedUsers.value = (props.users.data || []).map((u) => u.id);
};

const selectAllUsers = () => {
  selectAllMode.value = true;
  selectedUsers.value = (props.users.data || []).map((u) => u.id);
};

const clearSelection = () => {
  selectAllMode.value = false;
  selectedUsers.value = [];
};

const selectedCount = computed(() => {
  if (selectAllMode.value) {
    return props.users.total || 0;
  }
  return selectedUsers.value.length;
});

const openMessageDialog = () => {
  messageText.value = "";
  isMessageDialogOpen.value = true;
};

const closeMessageDialog = () => {
  isMessageDialogOpen.value = false;
  messageText.value = "";
};

const sendMessageToSelected = () => {
  if (!messageText.value || !messageText.value.trim()) {
    return;
  }

  isSending.value = true;

  const userIds = selectAllMode.value 
    ? 'all' 
    : selectedUsers.value;

  router.post('/users/bulk-send-message', {
    user_ids: userIds,
    message: messageText.value.trim(),
    select_all: selectAllMode.value,
  }, {
    onSuccess: () => {
      successMessage.value = t("messages.messages_sent_success");
      closeMessageDialog();
      setTimeout(() => {
        successMessage.value = "";
      }, 5000);
    },
    onError: (errors) => {
      errorMessage.value = errors.message || t("messages.send_message_error");
      setTimeout(() => {
        errorMessage.value = "";
      }, 5000);
    },
    onFinish: () => {
      isSending.value = false;
    },
  });
};

const handleUserSelect = (userId, event) => {
  const checked = event.target.checked;
  
  // If in "select all" mode and user unchecks, exit that mode
  if (selectAllMode.value && !checked) {
    selectAllMode.value = false;
    // Start with all current page users except the unchecked one
    selectedUsers.value = (props.users.data || [])
      .map((u) => u.id)
      .filter(id => id !== userId);
    return;
  }
  
  if (checked) {
    if (!selectedUsers.value.includes(userId)) {
      selectedUsers.value.push(userId);
    }
  } else {
    selectedUsers.value = selectedUsers.value.filter(id => id !== userId);
  }
};

const isUserSelected = (userId) => {
  if (selectAllMode.value) return true;
  return selectedUsers.value.includes(userId);
};

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

  const params = {
    per_page: perPage.value,
  };
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

const handlePerPageChange = (value) => {
  perPage.value = parseInt(value, 10);
  performSearch();
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
  // Keep per_page setting when clearing filters
  performSearch();
};

const updateUserRole = (user, newRole) => {
  if (!user || !newRole || user.role === newRole) return;

  if (
    confirm(
      t("messages.change_role_confirmation", { name: user.name || t("messages.user") })
    )
  ) {
    router.put(
      `/users/${user.id}/role`,
      { role: newRole },
      {
        onSuccess: () => {
          successMessage.value = t("messages.role_updated_success", {
            name: user.name || t("messages.user"),
          });
          setTimeout(() => {
            successMessage.value = "";
          }, 5000);
        },
        onError: (errors) => {
          errorMessage.value = errors.role || t("messages.role_update_error");
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

  if (
    confirm(
      t("messages.delete_user_confirmation", { name: user.name || t("messages.user") })
    )
  ) {
    router.delete(`/users/${user.id}`, {
      onSuccess: () => {
        successMessage.value = t("messages.user_deleted_success", {
          name: user.name || t("messages.user"),
        });
        setTimeout(() => {
          successMessage.value = "";
        }, 5000);
      },
      onError: (errors) => {
        errorMessage.value = errors.user || t("messages.user_delete_error");
        setTimeout(() => {
          errorMessage.value = "";
        }, 5000);
      },
    });
  }
};

const getRoleLabel = (role) => {
  const labels = {
    user: t("messages.role_user"),
    checker: t("messages.role_checker"),
    registrator: t("messages.role_registrator"),
    ceo: t("messages.role_ceo"),
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
    <Head :title="t('messages.user_management')" />

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
            {{ t("messages.user_management") }}
          </h1>
          <p class="text-muted-foreground text-sm mt-1">
            {{ t("messages.user_management_description") }}
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <Button variant="outline" as-child class="shadow-sm h-9">
            <Link href="/users/statistics">
              <BarChart3 class="mr-2 h-4 w-4" />
              {{ t("messages.statistics") }}
            </Link>
          </Button>
          <Button as-child class="shadow-sm h-9">
            <Link href="/users/create">
              <User class="mr-2 h-4 w-4" />
              {{ t("messages.new_user") }}
            </Link>
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <!-- Total Users -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 border border-blue-200/50 dark:border-blue-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-3">
            <div class="flex items-center justify-between mb-1.5">
              <div
                class="p-1.5 rounded-lg bg-blue-500/10 group-hover:bg-blue-500/20 transition-colors duration-300"
              >
                <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
              </div>
            </div>
            <div class="space-y-0.5">
              <p
                class="text-xs font-medium text-blue-700/70 dark:text-blue-300/70 uppercase tracking-wide"
              >
                {{ t("messages.total_users") }}
              </p>
              <p class="text-xl font-bold text-blue-900 dark:text-blue-100">
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
          <div class="relative p-3">
            <div class="flex items-center justify-between mb-1.5">
              <div
                class="p-1.5 rounded-lg bg-green-500/10 group-hover:bg-green-500/20 transition-colors duration-300"
              >
                <Shield class="h-4 w-4 text-green-600 dark:text-green-400" />
              </div>
            </div>
            <div class="space-y-0.5">
              <p
                class="text-xs font-medium text-green-700/70 dark:text-green-300/70 uppercase tracking-wide"
              >
                {{ t("messages.role_checker") }}
              </p>
              <p class="text-xl font-bold text-green-900 dark:text-green-100">
                {{ (users.data || []).filter((u) => u.role === "checker").length }}
              </p>
            </div>
          </div>
        </div>

        <!-- Bino inshoat xodimlari -->
        <div
          class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-950/50 dark:to-amber-900/30 border border-amber-200/50 dark:border-amber-800/50 hover:shadow-lg transition-all duration-300"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative p-3">
            <div class="flex items-center justify-between mb-1.5">
              <div
                class="p-1.5 rounded-lg bg-amber-500/10 group-hover:bg-amber-500/20 transition-colors duration-300"
              >
                <UserCheck class="h-4 w-4 text-amber-600 dark:text-amber-400" />
              </div>
            </div>
            <div class="space-y-0.5">
              <p
                class="text-xs font-medium text-amber-700/70 dark:text-amber-300/70 uppercase tracking-wide"
              >
                {{ t("messages.role_registrator") }}
              </p>
              <p class="text-xl font-bold text-amber-900 dark:text-amber-100">
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
          <div class="relative p-3">
            <div class="flex items-center justify-between mb-1.5">
              <div
                class="p-1.5 rounded-lg bg-rose-500/10 group-hover:bg-rose-500/20 transition-colors duration-300"
              >
                <Crown class="h-4 w-4 text-rose-600 dark:text-rose-400" />
              </div>
            </div>
            <div class="space-y-0.5">
              <p
                class="text-xs font-medium text-rose-700/70 dark:text-rose-300/70 uppercase tracking-wide"
              >
                {{ t("messages.role_ceo") }}
              </p>
              <p class="text-xl font-bold text-rose-900 dark:text-rose-100">
                {{ (users.data || []).filter((u) => u.role === "ceo").length }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Table with Integrated Filters -->
      <Card class="shadow-sm">
        <CardHeader class="pb-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <CardTitle class="flex items-center text-lg">
                <Users class="mr-2 h-5 w-5" />
                {{ t("messages.users") }}
              </CardTitle>
              <CardDescription class="text-sm">
                {{ t("messages.users_found", { count: users.total || 0 }) }}
              </CardDescription>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
              <div class="relative flex-1 sm:min-w-[250px]">
                <Search
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  v-model="searchInput"
                  :placeholder="t('messages.search_users_placeholder')"
                  class="pl-10 h-9"
                />
              </div>
              <div class="flex gap-2">
                <Select v-model="selectedRole" @update:model-value="handleRoleChange">
                  <SelectTrigger class="w-[160px] h-9">
                    <SelectValue :placeholder="t('messages.all_roles')" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">{{ t("messages.all_roles") }}</SelectItem>
                    <SelectItem v-for="role in roles" :key="role" :value="role">
                      {{ getRoleLabel(role) }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" @click="clearFilters" class="h-9 px-3">
                  <X class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent class="pt-0">
          <!-- Selection Panel -->
          <div
            v-if="selectedCount > 0 || selectAllMode"
            class="mb-4 p-3 bg-primary/5 border border-primary/20 rounded-lg"
          >
            <div class="flex items-center justify-between">
              <div class="text-sm">
                <span class="font-semibold text-foreground">{{ t("messages.selected_users") }}:</span>
                <span class="ml-2 text-muted-foreground">
                  <template v-if="selectAllMode">
                    {{ t("messages.all_users") }} {{ props.users.total || 0 }} {{ t("messages.users") }}
                  </template>
                  <template v-else>
                    {{ selectedUsers.length }} {{ t("messages.selected") }}
                  </template>
                </span>
              </div>
              <div class="flex items-center gap-2">
                <Button
                  variant="default"
                  size="sm"
                  @click="openMessageDialog"
                  class="h-8 text-xs"
                >
                  <Send class="mr-2 h-3.5 w-3.5" />
                  {{ t("messages.send_telegram_message") }}
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="clearSelection"
                  class="h-8 text-xs"
                >
                  {{ t("messages.clear_selection") }}
                </Button>
              </div>
            </div>
          </div>

          <div class="rounded-lg border overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50">
                  <TableHead class="w-[100px] py-3">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm" class="h-8 -ml-3 gap-1 px-2">
                          <input
                            type="checkbox"
                            :checked="allSelected"
                            :indeterminate.prop="someSelected"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-0 cursor-pointer pointer-events-none"
                            aria-label="Select users"
                          />
                          <ChevronDownIcon class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="start" class="w-56">
                        <DropdownMenuItem @click="selectCurrentPage" class="cursor-pointer">
                          <div class="flex items-center justify-between w-full">
                            <span>{{ t("messages.select_this_page") }}</span>
                            <Badge variant="secondary" class="ml-2">{{ (props.users.data || []).length }}</Badge>
                          </div>
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="selectAllUsers" class="cursor-pointer">
                          <div class="flex items-center justify-between w-full">
                            <span>{{ t("messages.select_all") }}</span>
                            <Badge variant="secondary" class="ml-2">{{ props.users.total || 0 }}</Badge>
                          </div>
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableHead>
                  <TableHead class="font-semibold py-3">{{
                    t("messages.user")
                  }}</TableHead>
                  <TableHead class="font-semibold py-3">{{
                    t("messages.role")
                  }}</TableHead>
                  <TableHead class="font-semibold py-3 text-center">{{
                    t("messages.files")
                  }}</TableHead>
                  <TableHead class="font-semibold py-3">{{
                    t("messages.joined_date")
                  }}</TableHead>
                  <TableHead class="w-[80px] font-semibold py-3 text-center">{{
                    t("messages.actions")
                  }}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="user in users.data || []"
                  :key="user.id"
                  class="hover:bg-muted/30 transition-colors"
                >
                  <TableCell class="py-3">
                    <input
                      type="checkbox"
                      :checked="isUserSelected(user.id)"
                      @change="(e) => handleUserSelect(user.id, e)"
                      class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-0 cursor-pointer"
                      :aria-label="`Select ${user.name}`"
                    />
                  </TableCell>
                  <TableCell class="py-3">
                    <div class="flex items-center space-x-3">
                      <Avatar class="h-8 w-8 ring-1 ring-muted">
                        <AvatarImage :src="user.avatar || ''" :alt="user.name || ''" />
                        <AvatarFallback class="text-xs font-semibold">
                          {{ (user.name || "U").charAt(0).toUpperCase() }}
                        </AvatarFallback>
                      </Avatar>
                      <div class="min-w-0 flex-1">
                        <div class="font-semibold text-foreground truncate text-sm">
                          <Link
                            :href="`/users/${user.id}`"
                            class="hover:text-primary hover:underline transition-colors"
                          >
                            {{ user.name || t("messages.unknown_user") }}
                          </Link>
                        </div>
                        <div class="text-xs text-muted-foreground truncate">
                          {{ user.email || t("messages.no_email") }}
                        </div>
                        <div
                          class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                          <span v-if="user.phone_number" class="truncate">
                            {{ user.phone_number }}
                          </span>
                          <span v-if="user.region" class="truncate">
                            {{ user.region }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell class="py-3">
                    <Select
                      :model-value="user.role"
                      @update:model-value="updateUserRole(user, $event)"
                    >
                      <SelectTrigger class="w-[130px] h-7 text-xs">
                        <SelectValue>
                          <div class="flex items-center space-x-1.5">
                            <component :is="getRoleIcon(user.role)" class="h-3.5 w-3.5" />
                            <span class="truncate">{{ getRoleLabel(user.role) }}</span>
                          </div>
                        </SelectValue>
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="user">
                          <div class="flex items-center space-x-2">
                            <User class="h-4 w-4" />
                            <span>{{ t("messages.role_user") }}</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="checker">
                          <div class="flex items-center space-x-2">
                            <Shield class="h-4 w-4" />
                            <span>{{ t("messages.role_checker") }}</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="registrator">
                          <div class="flex items-center space-x-2">
                            <UserCheck class="h-4 w-4" />
                            <span>{{ t("messages.role_registrator") }}</span>
                          </div>
                        </SelectItem>
                        <SelectItem value="ceo">
                          <div class="flex items-center space-x-2">
                            <Crown class="h-4 w-4" />
                            <span>{{ t("messages.role_ceo") }}</span>
                          </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </TableCell>
                  <TableCell class="py-3 text-center">
                    <Badge variant="secondary" class="font-medium text-xs px-2 py-1">
                      {{ user.uploaded_files_count || 0 }}
                    </Badge>
                  </TableCell>
                  <TableCell class="py-3 text-muted-foreground text-xs">
                    {{ formatDate(user.created_at) }}
                  </TableCell>
                  <TableCell class="py-3 text-center">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="h-6 w-6 p-0 hover:bg-muted">
                          <MoreHorizontal class="h-3.5 w-3.5" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" class="w-44">
                        <DropdownMenuItem as-child>
                          <Link
                            :href="`/users/${user.id}`"
                            class="flex items-center w-full text-xs"
                          >
                            <Eye class="mr-2 h-3.5 w-3.5" />
                            {{ t("messages.view_profile") }}
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                          <Link
                            :href="`/users/${user.id}/edit`"
                            class="flex items-center w-full text-xs"
                          >
                            <Edit class="mr-2 h-3.5 w-3.5" />
                            {{ t("messages.edit") }}
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          @click="deleteUser(user)"
                          class="text-destructive focus:text-destructive cursor-pointer text-xs"
                        >
                          <Trash2 class="mr-2 h-3.5 w-3.5" />
                          {{ t("messages.delete_user") }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div v-if="users && users.links" class="mt-3">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="users.prev_page_url"
                  :href="users.prev_page_url"
                  class="relative inline-flex items-center px-3 py-1.5 border border-input bg-background text-xs font-medium rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  {{ t("messages.previous") }}
                </Link>
                <Link
                  v-if="users.next_page_url"
                  :href="users.next_page_url"
                  class="ml-2 relative inline-flex items-center px-3 py-1.5 border border-input bg-background text-xs font-medium rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  {{ t("messages.next") }}
                </Link>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                  <p class="text-xs text-muted-foreground">
                    {{
                      t("messages.pagination_info", {
                        from: users.from || 0,
                        to: users.to || 0,
                        total: users.total || 0,
                      })
                    }}
                  </p>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">{{ t("messages.per_page") || "Per page" }}:</span>
                    <Select :model-value="perPage.toString()" @update:model-value="handlePerPageChange">
                      <SelectTrigger class="w-20 h-7 text-xs">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="link in users.links || []" :key="link.label">
                      <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="relative inline-flex items-center px-3 py-1.5 border text-xs font-medium transition-colors"
                        :class="[
                          link.active
                            ? 'z-10 bg-primary border-primary text-primary-foreground'
                            : 'bg-background border-input text-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                      />
                      <span
                        v-else
                        v-html="link.label"
                        class="relative inline-flex items-center px-3 py-1.5 border border-input bg-muted text-muted-foreground text-xs font-medium cursor-not-allowed"
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

    <!-- Send Message Dialog -->
    <Dialog v-model:open="isMessageDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{{ t("messages.send_telegram_message") }}</DialogTitle>
          <DialogDescription>
            {{ t("messages.enter_message") }}
            <span class="block mt-1 text-xs text-muted-foreground">
              <template v-if="selectAllMode">
                {{ t("messages.all_users") }} {{ props.users.total || 0 }} {{ t("messages.users") }}
              </template>
              <template v-else>
                {{ selectedUsers.length }} {{ t("messages.selected") }}
              </template>
            </span>
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <Textarea
            v-model="messageText"
            :placeholder="t('messages.enter_message')"
            class="min-h-[120px] resize-none"
            :maxlength="4000"
          />
          <p class="mt-2 text-xs text-muted-foreground text-right">
            {{ messageText.length }} / 4000
          </p>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="closeMessageDialog" :disabled="isSending">
            {{ t("messages.cancel") }}
          </Button>
          <Button @click="sendMessageToSelected" :disabled="!messageText.trim() || isSending">
            <Send class="mr-2 h-4 w-4" />
            {{ isSending ? t("messages.sending") : t("messages.send") }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
