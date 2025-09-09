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
import { ref } from "vue";

const props = defineProps({
  file: {
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

const statusDialogOpen = ref(false);
const newStatus = ref(props.file.status);
const adminNotes = ref(props.file.admin_notes || "");
const feedbackFiles = ref([]);
const fileInput = ref(null);
const isUpdatingStatus = ref(false);

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
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const openStatusDialog = () => {
  newStatus.value = props.file.status;
  adminNotes.value = props.file.admin_notes || "";
  feedbackFiles.value = [];
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
              class="w-full mt-1 px-3 py-2 border rounded-md"
            >
              <option value="pending">Jarayonda</option>
              <option value="accepted">Tasdiqlangan</option>
              <option value="rejected">Rad etilgan</option>
            </select>
          </div>

          <div>
            <Label for="notes">Admin izohi</Label>
            <Textarea
              id="notes"
              v-model="adminNotes"
              placeholder="Ushbu fayl haqida izoh qo'shing..."
              class="mt-1"
            />
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
