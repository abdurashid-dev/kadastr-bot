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
} from "lucide-vue-next";
import { ref, computed } from "vue";

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

const breadcrumbs = [
  {
    title: "Fayllar",
    href: "/files",
  },
];

// Status options based on user role
const statusOptions = computed(() => {
  const baseOptions = [
    { value: "pending", label: "Jarayonda" },
    { value: "rejected", label: "Rad etilgan" },
  ];

  // Checkers can set status to waiting
  if (props.user.role === "checker") {
    return [...baseOptions, { value: "waiting", label: "Bino inshoatga yuborish" }];
  }

  // Registrators can set status to accepted
  if (props.user.role === "registrator") {
    return [...baseOptions, { value: "accepted", label: "Tasdiqlangan" }];
  }

  // CEOs can set any status
  if (props.user.role === "ceo") {
    return [
      ...baseOptions,
      { value: "waiting", label: "Bino inshoatga yuborish" },
      { value: "accepted", label: "Tasdiqlangan" },
    ];
  }

  // Regular users can only see basic options
  return baseOptions;
});

// Get today's date in YYYY-MM-DD format
const today = new Date().toISOString().split("T")[0];

const searchQuery = ref(props.filters.search);
const selectedStatus = ref(props.filters.status);
const selectedRegion = ref(props.filters.region);
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
      return { variant: "success", icon: CheckCircle, text: "Tasdiqlangan" };
    case "rejected":
      return { variant: "destructive", icon: XCircle, text: "Rad etilgan" };
    case "waiting":
      return { variant: "secondary", icon: Clock, text: "Bino inshoatga yuborildi" };
    default:
      return { variant: "secondary", icon: Clock, text: "Jarayonda" };
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
      region: selectedRegion.value,
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
  selectedRegion.value = "";
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
  if (confirm("Ushbu faylni o'chirishga ishonchingiz komilmi?")) {
    router.delete(`/files/${file.id}`);
  }
};
</script>

<template>
  <Head title="Fayllar" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <!-- Header with Stats -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">Fayllar boshqaruvi</h1>
          <p class="text-muted-foreground">
            Telegram foydalanuvchilaridan yuklangan fayllarni boshqarish
          </p>
        </div>

        <!-- Compact Stats -->
        <div class="flex gap-2">
          <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm">
            <File class="h-4 w-4 text-muted-foreground" />
            <span class="font-medium">{{ stats.total }}</span>
            <span class="text-muted-foreground">Jami</span>
          </div>
          <div
            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer hover:bg-accent/50 transition-colors"
            @click="filterByStatus('pending')"
          >
            <Clock class="h-4 w-4 text-yellow-600" />
            <span class="font-medium text-yellow-600">{{ stats.pending }}</span>
            <span class="text-muted-foreground">Jarayonda</span>
          </div>
          <div
            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer hover:bg-accent/50 transition-colors"
            @click="filterByStatus('accepted')"
          >
            <CheckCircle class="h-4 w-4 text-green-600" />
            <span class="font-medium text-green-600">{{ stats.accepted }}</span>
            <span class="text-muted-foreground">Tasdiqlangan</span>
          </div>
          <div
            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm cursor-pointer hover:bg-accent/50 transition-colors"
            @click="filterByStatus('rejected')"
          >
            <XCircle class="h-4 w-4 text-red-600" />
            <span class="font-medium text-red-600">{{ stats.rejected }}</span>
            <span class="text-muted-foreground">Rad etilgan</span>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <Card>
        <CardContent class="p-4">
          <div class="space-y-4">
            <!-- Search Bar -->
            <div class="flex flex-1 items-center gap-2">
              <div class="relative flex-1 max-w-sm">
                <Search
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  v-model="searchQuery"
                  placeholder="Fayllar yoki foydalanuvchilarni qidirish..."
                  class="pl-9"
                  @keyup.enter="search"
                />
              </div>
              <Button @click="search" size="sm">
                <Search class="h-4 w-4" />
              </Button>
              <Button variant="outline" size="sm" @click="clearFilters">
                <RefreshCw class="h-4 w-4" />
              </Button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <!-- Status Filter -->
              <div>
                <Label class="text-sm font-medium">Holat</Label>
                <select
                  v-model="selectedStatus"
                  @change="search"
                  class="w-full mt-1 px-3 py-2 border rounded-md text-sm"
                >
                  <option value="all">Barchasi</option>
                  <option
                    v-for="option in statusOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <!-- Region Filter -->
              <div>
                <Label class="text-sm font-medium">Viloyat</Label>
                <select
                  v-model="selectedRegion"
                  @change="search"
                  class="w-full mt-1 px-3 py-2 border rounded-md text-sm"
                >
                  <option value="">Barchasi</option>
                  <option v-for="region in regions" :key="region" :value="region">
                    {{ region }}
                  </option>
                </select>
              </div>

              <!-- Date From Filter -->
              <div>
                <Label class="text-sm font-medium">Boshlanish sanasi</Label>
                <Input
                  v-model="selectedDateFrom"
                  type="date"
                  @change="search"
                  class="mt-1"
                />
              </div>

              <!-- Date To Filter -->
              <div>
                <Label class="text-sm font-medium">Tugash sanasi</Label>
                <Input
                  v-model="selectedDateTo"
                  type="date"
                  @change="search"
                  class="mt-1"
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
            class="p-8 text-center text-muted-foreground"
          >
            <File class="mx-auto h-12 w-12 mb-4 opacity-50" />
            <h3 class="text-lg font-medium mb-2">Fayllar topilmadi</h3>
            <p>Joriy qidiruv mezonlariga mos keladigan fayllar yo'q.</p>
          </div>

          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead class="w-12"></TableHead>
                <TableHead>Fayl</TableHead>
                <TableHead>Foydalanuvchi</TableHead>
                <TableHead>Holat</TableHead>
                <TableHead>Hajmi</TableHead>
                <TableHead>Sana</TableHead>
                <TableHead class="w-20">Amallar</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow
                v-for="file in files.data"
                :key="file.id"
                class="hover:bg-accent/50 transition-colors"
              >
                <TableCell>
                  <component
                    :is="getFileIcon(file.file_type)"
                    class="h-5 w-5 text-muted-foreground"
                  />
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div class="font-medium">{{ file.name }}</div>
                    <div class="text-sm text-muted-foreground">
                      {{ file.original_filename }}
                    </div>
                    <div
                      v-if="file.admin_notes"
                      class="text-xs text-muted-foreground bg-muted px-2 py-1 rounded"
                    >
                      <strong>Izoh:</strong> {{ file.admin_notes }}
                    </div>
                    
                    <!-- Accepted status information -->
                    <div
                      v-if="file.status === 'accepted'"
                      class="text-xs bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-2 py-1 rounded mt-2"
                    >
                      <div class="font-medium text-green-800 dark:text-green-200 mb-1">Tasdiqlash ma'lumotlari:</div>
                      <div class="space-y-1 text-green-700 dark:text-green-300">
                        <div v-if="file.registered_count !== null">
                          <strong>Migratsiya bo'lganlar:</strong> {{ file.registered_count }}
                        </div>
                        <div v-if="file.not_registered_count !== null">
                          <strong>Migratsiya bo'lmaganlar:</strong> {{ file.not_registered_count }}
                        </div>
                        <div v-if="file.accepted_note" class="mt-1">
                          <strong>Izoh:</strong> {{ file.accepted_note }}
                        </div>
                      </div>
                    </div>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="space-y-1">
                    <div class="font-medium">{{ file.user.name }}</div>
                    <div v-if="file.user.region" class="text-sm text-muted-foreground">
                      📍 {{ file.user.region }}
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
                          Batafsil ko'rish
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem as-child>
                        <a
                          :href="`/files/${file.id}/download`"
                          class="flex items-center"
                          target="_blank"
                        >
                          <Download class="mr-2 h-4 w-4" />
                          Yuklab olish
                        </a>
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="openStatusDialog(file)">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Holatni yangilash
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        @click="deleteFile(file)"
                        class="text-destructive focus:text-destructive"
                      >
                        <Trash2 class="mr-2 h-4 w-4" />
                        O'chirish
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="files.links.length > 3" class="flex justify-center">
        <div class="flex gap-1">
          <Link
            v-for="link in files.links"
            :key="link.label"
            :href="link.url"
            :class="[
              'px-3 py-2 text-sm rounded-md transition-colors',
              link.active
                ? 'bg-primary text-primary-foreground'
                : 'bg-background border hover:bg-accent',
              !link.url ? 'opacity-50 cursor-not-allowed' : '',
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Status Update Dialog -->
    <Dialog v-model:open="statusDialogOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Fayl holatini yangilash</DialogTitle>
          <DialogDescription>
            "{{ selectedFile?.name }}" fayli uchun holatni yangilang va izoh qo'shing
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div>
            <Label for="status">Holat</Label>
            <select
              id="status"
              v-model="newStatus"
              class="w-full mt-1 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option
                v-for="option in statusOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <div>
            <Label for="notes">Admin izohi</Label>
            <Textarea
              id="notes"
              v-model="adminNotes"
              placeholder="Ushbu fayl haqida izoh qo'shing..."
              class="mt-1 min-h-[80px]"
            />
          </div>

          <!-- Accepted status specific fields -->
          <div v-if="newStatus === 'accepted'" class="space-y-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Tasdiqlash ma'lumotlari</h4>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="registered_count">Migratsiya bo'lganlar soni</Label>
                <Input
                  id="registered_count"
                  v-model="registeredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="mt-1"
                />
              </div>
              
              <div>
                <Label for="not_registered_count">Migratsiya bo'lmaganlar soni</Label>
                <Input
                  id="not_registered_count"
                  v-model="notRegisteredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="mt-1"
                />
              </div>
            </div>
            
            <div>
              <Label for="accepted_note">Tasdiqlash izohi</Label>
              <Textarea
                id="accepted_note"
                v-model="acceptedNote"
                placeholder="Tasdiqlash haqida qo'shimcha ma'lumot..."
                class="mt-1 min-h-[60px]"
              />
            </div>
          </div>

          <div>
            <Label>Javob fayli (ixtiyoriy)</Label>
            <div class="mt-1 space-y-2">
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
                  size="sm"
                  @click="fileInput?.click()"
                >
                  <Upload class="mr-2 h-4 w-4" />
                  Fayl tanlash
                </Button>

                <Button
                  v-if="feedbackFiles.length > 0"
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="clearAllFiles"
                >
                  <X class="mr-2 h-4 w-4" />
                  Barchasini o'chirish
                </Button>
              </div>

              <div v-if="feedbackFiles.length > 0" class="space-y-2">
                <div
                  v-for="(file, index) in feedbackFiles"
                  :key="index"
                  class="flex items-center gap-2 p-2 bg-muted rounded-md"
                >
                  <component
                    :is="getFileIcon('document')"
                    class="h-4 w-4 text-muted-foreground"
                  />
                  <span class="text-sm flex-1">{{ file.name }}</span>
                  <span class="text-xs text-muted-foreground">
                    {{ (file.size / 1024 / 1024).toFixed(1) }}MB
                  </span>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeFeedbackFile(index)"
                  >
                    <X class="h-4 w-4" />
                  </Button>
                </div>
              </div>

              <p class="text-xs text-muted-foreground">
                {{
                  feedbackFiles.length > 0
                    ? `${feedbackFiles.length} fayl tanlandi. Bu fayllar foydalanuvchiga Telegram orqali yuboriladi.`
                    : "Bu fayllar foydalanuvchiga Telegram orqali yuboriladi (bir nechta fayl tanlash mumkin)"
                }}
              </p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            @click="statusDialogOpen = false"
            :disabled="isUpdatingStatus"
          >
            Bekor qilish
          </Button>
          <Button @click="updateStatus" :disabled="isUpdatingStatus">
            <LoaderCircle v-if="isUpdatingStatus" class="w-4 h-4 animate-spin mr-2" />
            {{ isUpdatingStatus ? "Yangilanmoqda..." : "Holatni yangilash" }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
