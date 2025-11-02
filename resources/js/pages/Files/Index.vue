<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  FileText,
  Image,
  Video,
  Music,
  File,
  Search,
  MoreHorizontal,
  Eye,
  CheckCircle,
  XCircle,
  Clock,
  Trash2,
  Download,
  Filter,
  RefreshCw,
  Upload,
  X,
  LoaderCircle,
  MapPin,
} from "lucide-vue-next";
import { ref, computed } from "vue";
import { useTranslations } from "@/composables/useTranslations";

const props = defineProps({
  files: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
  regions: {
    type: Array,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
});

const { t } = useTranslations();

const breadcrumbs = [
  {
    title: t("messages.breadcrumb_files"),
    href: "/files",
  },
];

// Status options based on user role
const statusOptions = computed(() => {
  const baseOptions = [
    { value: "pending", label: t("messages.status_pending") },
    { value: "rejected", label: t("messages.status_rejected") },
  ];

  // Checkers can set status to waiting
  if (props.user.role === "checker") {
    return [
      ...baseOptions,
      { value: "waiting", label: t("messages.send_to_construction") },
    ];
  }

  // Registrators can set status to accepted
  if (props.user.role === "registrator") {
    return [...baseOptions, { value: "accepted", label: t("messages.status_accepted") }];
  }

  // CEOs can set any status
  if (props.user.role === "ceo") {
    return [
      ...baseOptions,
      { value: "waiting", label: t("messages.send_to_construction") },
      { value: "accepted", label: t("messages.status_accepted") },
    ];
  }

  // Regular users can only see basic options
  return baseOptions;
});

// Get today's date in YYYY-MM-DD format
const today = new Date().toISOString().split("T")[0];

const searchQuery = ref(props.filters.search);
const selectedStatus = ref(props.filters.status);
const selectedRegion = ref(props.filters.region || "all");
const selectedDateFrom = ref(props.filters.date_from || today);
const selectedDateTo = ref(props.filters.date_to || today);
const selectedFile = ref(null);
const statusDialogOpen = ref(false);
const newStatus = ref("pending");
const adminNotes = ref("");
const feedbackFiles = ref([]);
const fileInput = ref(null);
const isUpdatingStatus = ref(false);

// Accepted status specific fields
const registeredCount = ref(0);
const notRegisteredCount = ref(0);
const acceptedNote = ref("");

const getFileIcon = (fileType) => {
  switch (fileType) {
    case "document":
      return FileText;
    case "photo":
      return Image;
    case "video":
      return Video;
    case "audio":
    case "voice":
      return Music;
    default:
      return File;
  }
};

const getStatusBadge = (status) => {
  switch (status) {
    case "accepted":
      return {
        variant: "success",
        icon: CheckCircle,
        text: t("messages.status_accepted"),
      };
    case "rejected":
      return {
        variant: "destructive",
        icon: XCircle,
        text: t("messages.status_rejected"),
      };
    case "waiting":
      return { variant: "secondary", icon: Clock, text: t("messages.status_waiting") };
    default:
      return { variant: "secondary", icon: Clock, text: t("messages.status_pending") };
  }
};

const formatFileSize = (bytes) => {
  if (bytes >= 1073741824) {
    return (bytes / 1073741824).toFixed(2) + " GB";
  } else if (bytes >= 1048576) {
    return (bytes / 1048576).toFixed(2) + " MB";
  } else if (bytes >= 1024) {
    return (bytes / 1024).toFixed(2) + " KB";
  } else {
    return bytes + " bytes";
  }
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

const search = () => {
  router.get(
    "/files",
    {
      search: searchQuery.value,
      status: selectedStatus.value,
      region: selectedRegion.value === "all" ? "" : selectedRegion.value,
      date_from: selectedDateFrom.value,
      date_to: selectedDateTo.value,
    },
    {
      preserveState: true,
      replace: true,
    }
  );
};

const filterByStatus = (status) => {
  selectedStatus.value = status;
  search();
};

const clearFilters = () => {
  searchQuery.value = "";
  selectedStatus.value = "all";
  selectedRegion.value = "all";
  selectedDateFrom.value = "";
  selectedDateTo.value = "";
  search();
};

const openStatusDialog = (file) => {
  selectedFile.value = file;
  newStatus.value = file.status;
  adminNotes.value = file.admin_notes || "";
  feedbackFiles.value = [];

  // Initialize accepted status fields
  registeredCount.value = file.registered_count || 0;
  notRegisteredCount.value = file.not_registered_count || 0;
  acceptedNote.value = file.accepted_note || "";

  statusDialogOpen.value = true;
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  if (files.length > 0) {
    feedbackFiles.value = [...feedbackFiles.value, ...files];
    // Reset input to allow selecting the same files again if needed
    if (fileInput.value) {
      fileInput.value.value = "";
    }
  }
};

const removeFeedbackFile = (index) => {
  feedbackFiles.value.splice(index, 1);
};

const clearAllFiles = () => {
  feedbackFiles.value = [];
  if (fileInput.value) {
    fileInput.value.value = "";
  }
};

const updateStatus = () => {
  if (!selectedFile.value) return;

  isUpdatingStatus.value = true;

  const formData = new FormData();
  formData.append("status", newStatus.value);
  formData.append("admin_notes", adminNotes.value);

  // Add accepted status fields if status is accepted
  if (newStatus.value === "accepted") {
    formData.append("registered_count", registeredCount.value);
    formData.append("not_registered_count", notRegisteredCount.value);
    formData.append("accepted_note", acceptedNote.value);
  }

  // Append multiple feedback files
  feedbackFiles.value.forEach((file, index) => {
    formData.append(`feedback_files[${index}]`, file);
  });

  router.post(`/files/${selectedFile.value.id}/status`, formData, {
    forceFormData: true,
    onSuccess: () => {
      statusDialogOpen.value = false;
      selectedFile.value = null;
      feedbackFiles.value = [];
      isUpdatingStatus.value = false;
    },
    onError: () => {
      isUpdatingStatus.value = false;
    },
    onFinish: () => {
      isUpdatingStatus.value = false;
    },
  });
};

const deleteFile = (file) => {
  if (confirm(t("messages.delete_confirmation"))) {
    router.delete(`/files/${file.id}`);
  }
};
</script>

<template>
  <Head :title="t('messages.files')" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <!-- Header with Stats -->
      <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
          <h1 class="text-3xl font-bold tracking-tight">
            {{ t("messages.file_management") }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ t("messages.file_management_description") }}
          </p>
        </div>

        <!-- Stats Cards -->
        <div class="flex flex-wrap gap-3">
          <div
            class="flex items-center gap-2 rounded-lg border bg-card px-4 py-2.5 text-sm shadow-sm transition-shadow hover:shadow-md"
          >
            <File class="h-4 w-4 text-muted-foreground" />
            <span class="font-semibold">{{ stats.total }}</span>
            <span class="text-muted-foreground">{{ t("messages.total") }}</span>
          </div>
          <button
            type="button"
            class="flex items-center gap-2 rounded-lg border bg-card px-4 py-2.5 text-sm shadow-sm transition-all hover:bg-accent hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            @click="filterByStatus('pending')"
          >
            <Clock class="h-4 w-4 text-yellow-600 dark:text-yellow-500" />
            <span class="font-semibold text-yellow-600 dark:text-yellow-500">{{
              stats.pending
            }}</span>
            <span class="text-muted-foreground">{{ t("messages.status_pending") }}</span>
          </button>
          <button
            type="button"
            class="flex items-center gap-2 rounded-lg border bg-card px-4 py-2.5 text-sm shadow-sm transition-all hover:bg-accent hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            @click="filterByStatus('accepted')"
          >
            <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-500" />
            <span class="font-semibold text-green-600 dark:text-green-500">{{
              stats.accepted
            }}</span>
            <span class="text-muted-foreground">{{ t("messages.status_accepted") }}</span>
          </button>
          <button
            type="button"
            class="flex items-center gap-2 rounded-lg border bg-card px-4 py-2.5 text-sm shadow-sm transition-all hover:bg-accent hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
            @click="filterByStatus('rejected')"
          >
            <XCircle class="h-4 w-4 text-red-600 dark:text-red-500" />
            <span class="font-semibold text-red-600 dark:text-red-500">{{
              stats.rejected
            }}</span>
            <span class="text-muted-foreground">{{ t("messages.status_rejected") }}</span>
          </button>
        </div>
      </div>

      <!-- Search and Filters -->
      <Card>
        <CardContent class="p-6">
          <div class="space-y-6">
            <!-- Search Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
              <div class="relative flex-1">
                <Search
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none"
                />
                <Input
                  v-model="searchQuery"
                  :placeholder="t('messages.search_placeholder')"
                  class="pl-10 h-10"
                  @keyup.enter="search"
                />
              </div>
              <div class="flex gap-2">
                <Button @click="search" size="default" class="h-10 px-4">
                  <Search class="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  size="default"
                  class="h-10 px-4"
                  @click="clearFilters"
                >
                  <RefreshCw class="h-4 w-4" />
                </Button>
              </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Status Filter -->
              <div class="space-y-2">
                <Label class="text-sm font-medium">{{ t("messages.status") }}</Label>
                <Select v-model="selectedStatus" @update:model-value="search">
                  <SelectTrigger class="w-full h-10">
                    <SelectValue :placeholder="t('messages.all')" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">{{ t("messages.all") }}</SelectItem>
                    <SelectItem
                      v-for="option in statusOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Region Filter -->
              <div class="space-y-2">
                <Label class="text-sm font-medium">{{ t("messages.region") }}</Label>
                <Select v-model="selectedRegion" @update:model-value="search">
                  <SelectTrigger class="w-full h-10">
                    <SelectValue :placeholder="t('messages.all')" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">{{ t("messages.all") }}</SelectItem>
                    <SelectItem v-for="region in regions" :key="region" :value="region">
                      {{ region }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Date From Filter -->
              <div class="space-y-2">
                <Label class="text-sm font-medium">{{ t("messages.date_from") }}</Label>
                <Input
                  v-model="selectedDateFrom"
                  type="date"
                  @change="search"
                  class="h-10"
                />
              </div>

              <!-- Date To Filter -->
              <div class="space-y-2">
                <Label class="text-sm font-medium">{{ t("messages.date_to") }}</Label>
                <Input
                  v-model="selectedDateTo"
                  type="date"
                  @change="search"
                  class="h-10"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Files Table -->
      <Card>
        <CardContent class="p-0">
          <div
            v-if="files.data.length === 0"
            class="flex flex-col items-center justify-center p-12 text-center"
          >
            <div class="rounded-full bg-muted p-4 mb-4">
              <File class="h-8 w-8 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-semibold mb-1">{{ t("messages.no_files_found") }}</h3>
            <p class="text-sm text-muted-foreground max-w-md">
              {{ t("messages.no_files_description") }}
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-12"></TableHead>
                  <TableHead class="font-semibold">{{ t("messages.file") }}</TableHead>
                  <TableHead class="font-semibold">{{ t("messages.user") }}</TableHead>
                  <TableHead class="font-semibold">{{ t("messages.status") }}</TableHead>
                  <TableHead class="font-semibold">{{
                    t("messages.file_size")
                  }}</TableHead>
                  <TableHead class="font-semibold">{{ t("messages.date") }}</TableHead>
                  <TableHead class="w-24 font-semibold">{{
                    t("messages.actions")
                  }}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="file in files.data"
                  :key="file.id"
                  class="hover:bg-accent/50 transition-colors cursor-pointer"
                >
                  <TableCell>
                    <component
                      :is="getFileIcon(file.file_type)"
                      class="h-5 w-5 text-muted-foreground"
                    />
                  </TableCell>
                  <TableCell>
                    <div class="space-y-1">
                      <Link
                        :href="`/files/${file.id}`"
                        class="font-medium text-primary hover:underline transition-colors"
                      >
                        {{ file.name }}
                      </Link>
                      <div class="text-sm text-muted-foreground">
                        {{ file.original_filename }}
                      </div>
                      <div
                        v-if="file.admin_notes"
                        class="text-xs text-muted-foreground bg-muted px-2 py-1 rounded"
                      >
                        <strong>{{ t("messages.admin_notes") }}:</strong>
                        {{ file.admin_notes }}
                      </div>

                      <!-- Accepted status information -->
                      <div
                        v-if="file.status === 'accepted'"
                        class="text-xs bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-2 py-1 rounded mt-2"
                      >
                        <div class="font-medium text-green-800 dark:text-green-200 mb-1">
                          {{ t("messages.approval_info") }}:
                        </div>
                        <div class="space-y-1 text-green-700 dark:text-green-300">
                          <div v-if="file.registered_count !== null">
                            <strong>{{ t("messages.registered_count") }}:</strong>
                            {{ file.registered_count }}
                          </div>
                          <div v-if="file.not_registered_count !== null">
                            <strong>{{ t("messages.not_registered_count") }}:</strong>
                            {{ file.not_registered_count }}
                          </div>
                          <div v-if="file.accepted_note" class="mt-1">
                            <strong>{{ t("messages.notes") }}:</strong>
                            {{ file.accepted_note }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div class="space-y-1">
                      <div class="font-medium">{{ file.user.name }}</div>
                      <div
                        v-if="file.user.region"
                        class="flex items-center gap-1 text-sm text-muted-foreground"
                      >
                        <MapPin class="h-3 w-3" />
                        {{ file.user.region }}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(file.status).variant" class="text-xs">
                      <component
                        :is="getStatusBadge(file.status).icon"
                        class="mr-1 h-3 w-3"
                      />
                      {{ getStatusBadge(file.status).text }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ formatFileSize(file.file_size) }}
                  </TableCell>
                  <TableCell class="text-sm text-muted-foreground">
                    {{ formatDate(file.created_at) }}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                          <Link :href="`/files/${file.id}`" class="flex items-center">
                            <Eye class="mr-2 h-4 w-4" />
                            {{ t("messages.view_details") }}
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                          <a
                            :href="`/files/${file.id}/download`"
                            class="flex items-center"
                            target="_blank"
                          >
                            <Download class="mr-2 h-4 w-4" />
                            {{ t("messages.download") }}
                          </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="openStatusDialog(file)">
                          <CheckCircle class="mr-2 h-4 w-4" />
                          {{ t("messages.update_status") }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          @click="deleteFile(file)"
                          class="text-destructive focus:text-destructive"
                        >
                          <Trash2 class="mr-2 h-4 w-4" />
                          {{ t("messages.delete") }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="files.links.length > 3" class="flex justify-center pt-2">
        <nav class="flex gap-1" aria-label="Pagination">
          <Link
            v-for="link in files.links"
            :key="link.label"
            :href="link.url"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-md transition-all',
              link.active
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-background border hover:bg-accent hover:border-accent-foreground/20',
              !link.url
                ? 'opacity-50 cursor-not-allowed pointer-events-none'
                : 'cursor-pointer',
            ]"
            v-html="link.label"
          />
        </nav>
      </div>
    </div>

    <!-- Status Update Dialog -->
    <Dialog v-model:open="statusDialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle class="text-xl">{{
            t("messages.update_file_status")
          }}</DialogTitle>
          <DialogDescription class="text-sm">
            {{ t("messages.update_status_description") }}
            <span class="font-medium text-foreground">{{ selectedFile?.name }}</span>
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-5 py-4">
          <div class="space-y-2">
            <Label for="status" class="text-sm font-medium">{{
              t("messages.status")
            }}</Label>
            <Select id="status" v-model="newStatus">
              <SelectTrigger class="w-full h-10">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in statusOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label for="notes" class="text-sm font-medium">{{
              t("messages.admin_notes")
            }}</Label>
            <Textarea
              id="notes"
              v-model="adminNotes"
              :placeholder="t('messages.admin_notes_placeholder')"
              class="min-h-[100px] resize-none"
            />
          </div>

          <!-- Accepted status specific fields -->
          <div
            v-if="newStatus === 'accepted'"
            class="space-y-4 p-5 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
          >
            <h4 class="text-sm font-semibold text-green-800 dark:text-green-200 mb-1">
              {{ t("messages.approval_info") }}
            </h4>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="registered_count" class="text-sm font-medium">
                  {{ t("messages.registered_count") }}
                </Label>
                <Input
                  id="registered_count"
                  v-model="registeredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="h-10"
                />
              </div>

              <div class="space-y-2">
                <Label for="not_registered_count" class="text-sm font-medium">
                  {{ t("messages.not_registered_count") }}
                </Label>
                <Input
                  id="not_registered_count"
                  v-model="notRegisteredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="h-10"
                />
              </div>
            </div>

            <div class="space-y-2">
              <Label for="accepted_note" class="text-sm font-medium">
                {{ t("messages.approval_notes") }}
              </Label>
              <Textarea
                id="accepted_note"
                v-model="acceptedNote"
                :placeholder="t('messages.approval_notes_placeholder')"
                class="min-h-[80px] resize-none"
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label class="text-sm font-medium">{{
              t("messages.feedback_file_optional")
            }}</Label>
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <input
                  ref="fileInput"
                  type="file"
                  @change="handleFileSelect"
                  accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt"
                  multiple
                  class="hidden"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="default"
                  class="h-10"
                  @click="fileInput?.click()"
                >
                  <Upload class="mr-2 h-4 w-4" />
                  {{ t("messages.select_file") }}
                </Button>

                <Button
                  v-if="feedbackFiles.length > 0"
                  type="button"
                  variant="ghost"
                  size="default"
                  class="h-10"
                  @click="clearAllFiles"
                >
                  <X class="mr-2 h-4 w-4" />
                  {{ t("messages.clear_all") }}
                </Button>
              </div>

              <div
                v-if="feedbackFiles.length > 0"
                class="space-y-2 max-h-48 overflow-y-auto"
              >
                <div
                  v-for="(file, index) in feedbackFiles"
                  :key="index"
                  class="flex items-center gap-3 p-3 bg-muted rounded-md border"
                >
                  <component
                    :is="getFileIcon('document')"
                    class="h-4 w-4 text-muted-foreground shrink-0"
                  />
                  <span class="text-sm flex-1 truncate">{{ file.name }}</span>
                  <span class="text-xs text-muted-foreground shrink-0">
                    {{ formatFileSize(file.size) }}
                  </span>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0 shrink-0"
                    @click="removeFeedbackFile(index)"
                  >
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>

              <p class="text-xs text-muted-foreground">
                {{
                  feedbackFiles.length > 0
                    ? t("messages.files_selected", { count: feedbackFiles.length })
                    : t("messages.feedback_files_description")
                }}
              </p>
            </div>
          </div>
        </div>

        <DialogFooter class="gap-2 sm:gap-0">
          <Button
            variant="outline"
            @click="statusDialogOpen = false"
            :disabled="isUpdatingStatus"
            class="w-full sm:w-auto"
          >
            {{ t("messages.cancel") }}
          </Button>
          <Button
            @click="updateStatus"
            :disabled="isUpdatingStatus"
            class="w-full sm:w-auto"
          >
            <LoaderCircle v-if="isUpdatingStatus" class="w-4 h-4 animate-spin mr-2" />
            {{ isUpdatingStatus ? t("messages.updating") : t("messages.update_status") }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
