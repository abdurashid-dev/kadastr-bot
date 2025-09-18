<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
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
  CheckCircle,
  XCircle,
  Clock,
  ArrowLeft,
  Edit,
  Download,
  Upload,
  X,
  LoaderCircle,
} from "lucide-vue-next";
import { ref, computed } from "vue";

const props = defineProps({
  file: {
    type: Object,
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
  {
    title: props.file.name,
    href: `/files/${props.file.id}`,
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

const statusDialogOpen = ref(false);
const newStatus = ref(props.file.status);
const adminNotes = ref(props.file.admin_notes || "");
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

const openStatusDialog = () => {
  newStatus.value = props.file.status;
  adminNotes.value = props.file.admin_notes || "";
  feedbackFiles.value = [];
  
  // Initialize accepted status fields
  registeredCount.value = props.file.registered_count || 0;
  notRegisteredCount.value = props.file.not_registered_count || 0;
  acceptedNote.value = props.file.accepted_note || "";
  
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

  router.post(`/files/${props.file.id}/status`, formData, {
    forceFormData: true,
    onSuccess: () => {
      statusDialogOpen.value = false;
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

const deleteFile = () => {
  if (confirm("Ushbu faylni o'chirishga ishonchingiz komilmi?")) {
    router.delete(`/files/${props.file.id}`, {
      onSuccess: () => {
        router.visit("/files");
      },
    });
  }
};
</script>

<template>
  <Head :title="file.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="outline" size="icon" as-child>
            <Link href="/files">
              <ArrowLeft class="h-4 w-4" />
            </Link>
          </Button>
          <div>
            <h1 class="text-2xl font-bold">{{ file.name }}</h1>
            <p class="text-muted-foreground">Fayl tafsilotlari</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <Button variant="outline" as-child>
            <a :href="`/files/${file.id}/download`" target="_blank">
              <Download class="mr-2 h-4 w-4" />
              Yuklab olish
            </a>
          </Button>
          <Button @click="openStatusDialog">
            <Edit class="mr-2 h-4 w-4" />
            Holatni yangilash
          </Button>
          <Button variant="destructive" @click="deleteFile"> Faylni o'chirish </Button>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-3">
        <!-- File Info Card -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <component :is="getFileIcon(file.file_type)" class="h-6 w-6" />
              <span>Fayl ma'lumotlari</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Nomi</Label>
                <p class="text-sm">{{ file.name }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground"
                  >Asl fayl nomi</Label
                >
                <p class="text-sm">{{ file.original_filename }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Fayl turi</Label>
                <p class="text-sm capitalize">{{ file.file_type }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">MIME turi</Label>
                <p class="text-sm">{{ file.mime_type }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground"
                  >Fayl hajmi</Label
                >
                <p class="text-sm">{{ formatFileSize(file.file_size) }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">Holat</Label>
                <Badge :variant="getStatusBadge(file.status).variant" class="w-fit">
                  <component
                    :is="getStatusBadge(file.status).icon"
                    class="mr-1 h-3 w-3"
                  />
                  {{ getStatusBadge(file.status).text }}
                </Badge>
              </div>
            </div>

            <div v-if="file.admin_notes">
              <Label class="text-sm font-medium text-muted-foreground">Admin izohi</Label>
              <p class="text-sm mt-1 p-3 bg-muted rounded-md">{{ file.admin_notes }}</p>
            </div>

            <!-- Accepted status information -->
            <div
              v-if="file.status === 'accepted'"
              class="text-xs bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-3 py-2 rounded mt-2"
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
          </CardContent>
        </Card>

        <!-- User & Timestamps Card -->
        <Card>
          <CardHeader>
            <CardTitle>Foydalanuvchi va vaqt</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-muted-foreground">Yuklagan</Label>
              <p class="text-sm font-medium">{{ file.user.name }}</p>
              <p class="text-xs text-muted-foreground">{{ file.user.email }}</p>
              <p v-if="file.user.region" class="text-xs text-muted-foreground">
                📍 {{ file.user.region }}
              </p>
            </div>

            <div>
              <Label class="text-sm font-medium text-muted-foreground">Yuklangan</Label>
              <p class="text-sm">{{ formatDate(file.created_at) }}</p>
            </div>

            <div>
              <Label class="text-sm font-medium text-muted-foreground"
                >Oxirgi yangilanish</Label
              >
              <p class="text-sm">{{ formatDate(file.updated_at) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Status Update Dialog -->
    <Dialog v-model:open="statusDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Fayl holatini yangilash</DialogTitle>
          <DialogDescription>
            "{{ file.name }}" fayli uchun holatni yangilang va izoh qo'shing
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
                <input
                  id="registered_count"
                  v-model="registeredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full mt-1 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>
              
              <div>
                <Label for="not_registered_count">Migratsiya bo'lmaganlar soni</Label>
                <input
                  id="not_registered_count"
                  v-model="notRegisteredCount"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="w-full mt-1 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
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
