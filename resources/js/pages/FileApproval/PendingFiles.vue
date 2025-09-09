<template>
  <AppLayout>
    <Head title="Jarayondagi fayllar" />

    <div class="space-y-6 p-4">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Jarayondagi fayllar
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Ko'rib chiqish uchun kutayotgan fayllar
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge
            variant="secondary"
            class="text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20"
          >
            <Clock class="w-3 h-3 mr-1" />
            {{ files.total }} ta fayl
          </Badge>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Jami fayllar
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ files.total }}
              </p>
            </div>
            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <FileText class="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Bugun yuklangan
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ todayCount }}
              </p>
            </div>
            <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <Upload class="h-6 w-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Haftalik</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ weeklyCount }}
              </p>
            </div>
            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <RefreshCw class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg border p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Oylik</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ monthlyCount }}
              </p>
            </div>
            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
              <Clock class="h-6 w-6 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Files Table -->
      <Card>
        <CardHeader>
          <CardTitle>Fayllar ro'yxati</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="files.data.length === 0" class="text-center py-12">
            <FileText class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              Jarayondagi fayllar yo'q
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Hozircha ko'rib chiqish uchun kutayotgan fayllar mavjud emas.
            </p>
          </div>

          <div v-else class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Fayl</TableHead>
                  <TableHead>Foydalanuvchi</TableHead>
                  <TableHead>Holat</TableHead>
                  <TableHead>Yuklangan</TableHead>
                  <TableHead class="text-right">Amallar</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="file in files.data" :key="file.id">
                  <TableCell>
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <component
                          :is="getFileIcon(file.file_type)"
                          class="h-5 w-5 text-gray-600 dark:text-gray-400"
                        />
                      </div>
                      <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                          {{ file.name }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ file.original_filename }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                          {{ formatFileSize(file.file_size) }} • {{ file.file_type }}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ file.user.name }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ file.user.region }}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge :variant="getStatusBadge(file.status).variant">
                      <component
                        :is="getStatusBadge(file.status).icon"
                        class="w-3 h-3 mr-1"
                      />
                      {{ getStatusBadge(file.status).text }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div class="text-sm text-gray-900 dark:text-white">
                      {{ formatDate(file.created_at) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatTime(file.created_at) }}
                    </div>
                  </TableCell>
                  <TableCell class="text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal class="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="viewFile(file)">
                          <Eye class="mr-2 h-4 w-4" />
                          Ko'rish
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="openStatusDialog(file)">
                          <CheckCircle class="mr-2 h-4 w-4" />
                          Holatni yangilash
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="downloadFile(file)">
                          <Download class="mr-2 h-4 w-4" />
                          Yuklab olish
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          @click="deleteFile(file)"
                          class="text-red-600 dark:text-red-400"
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
          </div>

          <!-- Pagination -->
          <div v-if="files.links && files.links.length > 3" class="mt-6">
            <nav class="flex items-center justify-between">
              <div class="text-sm text-gray-700 dark:text-gray-300">
                {{ files.from }}-{{ files.to }} dan {{ files.total }} ta natija
              </div>
              <div class="flex space-x-1">
                <Link
                  v-for="(link, index) in files.links"
                  :key="index"
                  :href="link.url"
                  :class="[
                    'px-3 py-2 text-sm font-medium rounded-md',
                    link.active
                      ? 'bg-primary text-primary-foreground'
                      : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                    !link.url
                      ? 'cursor-not-allowed opacity-50'
                      : 'hover:bg-gray-100 dark:hover:bg-gray-800',
                  ]"
                  v-html="link.label"
                />
              </div>
            </nav>
          </div>
        </CardContent>
      </Card>
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

<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
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
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  FileText,
  Image,
  Video,
  Music,
  File,
  MoreHorizontal,
  Eye,
  CheckCircle,
  XCircle,
  Clock,
  Trash2,
  Download,
  Upload,
  X,
  LoaderCircle,
  RefreshCw,
} from "lucide-vue-next";
import { ref, computed } from "vue";

const props = defineProps({
  files: {
    type: Object,
    required: true,
  },
});

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
        text: "Tasdiqlangan",
        color: "text-green-600",
      };
    case "rejected":
      return {
        variant: "destructive",
        icon: XCircle,
        text: "Rad etilgan",
        color: "text-red-600",
      };
    case "waiting":
      return {
        variant: "secondary",
        icon: Clock,
        text: "Bino inshoatga yuborildi",
        color: "text-blue-600",
      };
    default:
      return {
        variant: "secondary",
        icon: Clock,
        text: "Jarayonda",
        color: "text-yellow-600",
      };
  }
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString("uz-UZ", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString("uz-UZ", {
    hour: "2-digit",
    minute: "2-digit",
  });
};

// Status options based on user role
const statusOptions = computed(() => {
  const baseOptions = [
    { value: "waiting", label: "Bino inshoatga yuborildi" },
    { value: "rejected", label: "Rad etilgan" },
  ];

  // Checkers can set status to waiting
  if (props.user?.role === "checker") {
    baseOptions.push({ value: "waiting", label: "Bino inshoatga yuborish" });
  }

  // Registrators can set status to accepted
  if (props.user?.role === "registrator") {
    baseOptions.push({ value: "accepted", label: "Tasdiqlash" });
  }

  return baseOptions;
});

// Calculate stats
const todayCount = computed(() => {
  const today = new Date().toISOString().split("T")[0];
  return props.files.data.filter((file) => file.created_at.startsWith(today)).length;
});

const weeklyCount = computed(() => {
  const weekAgo = new Date();
  weekAgo.setDate(weekAgo.getDate() - 7);
  return props.files.data.filter((file) => new Date(file.created_at) >= weekAgo).length;
});

const monthlyCount = computed(() => {
  const monthAgo = new Date();
  monthAgo.setMonth(monthAgo.getMonth() - 1);
  return props.files.data.filter((file) => new Date(file.created_at) >= monthAgo).length;
});

const viewFile = (file) => {
  router.visit(`/files/${file.id}`);
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

const downloadFile = (file) => {
  window.open(`/files/${file.id}/download`, "_blank");
};

const deleteFile = (file) => {
  if (confirm("Ushbu faylni o'chirishga ishonchingiz komilmi?")) {
    router.delete(`/files/${file.id}`);
  }
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  feedbackFiles.value = [...feedbackFiles.value, ...files];
  if (fileInput.value) {
    fileInput.value.value = "";
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
</script>
