<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogClose,
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
  Clipboard,
} from "lucide-vue-next";
import { ref, computed, watch } from "vue";
import { useTranslations } from "@/composables/useTranslations";

const { t } = useTranslations();

const props = defineProps({
  file: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

const breadcrumbs = [
  {
    title: t("messages.files"),
    href: "/files",
  },
  {
    title: props.file.name,
    href: `/files/${props.file.id}`,
  },
];

// Check if file is too large for download
const isFileTooLarge = computed(() => {
  const maxSize = 20 * 1024 * 1024; // 20MB in bytes
  return props.file.file_size > maxSize;
});

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

const statusDialogOpen = ref(false);
const newStatus = ref(props.file.status);
const adminNotes = ref(props.file.admin_notes || "");
const feedbackFiles = ref([]);
const fileInput = ref(null);
const isUpdatingStatus = ref(false);
const successMessage = ref(props.flash?.success || "");
const isGettingFromClipboard = ref(false);
const clipboardMessage = ref('');
const imageLightboxOpen = ref(false);
const lightboxImageUrl = ref('');
const lightboxImageName = ref('');
const lightboxBlobUrl = ref(null); // Store the blob URL we create for lightbox

// Watch for flash message changes
watch(
  () => props.flash?.success,
  (newValue) => {
    if (newValue) {
      successMessage.value = newValue;
      setTimeout(() => {
        successMessage.value = "";
      }, 5000);
    }
  },
  { immediate: true }
);

// Clear messages after 5 seconds if they exist on mount
if (successMessage.value) {
  setTimeout(() => {
    successMessage.value = "";
  }, 5000);
}

// Accepted status specific fields
const registeredCount = ref(0);
const notRegisteredCount = ref(0);

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
        color: "text-green-600",
      };
    case "rejected":
      return {
        variant: "destructive",
        icon: XCircle,
        text: t("messages.status_rejected"),
        color: "text-red-600",
      };
    case "waiting":
      return {
        variant: "secondary",
        icon: Clock,
        text: t("messages.status_waiting"),
        color: "text-blue-600",
      };
    default:
      return {
        variant: "secondary",
        icon: Clock,
        text: t("messages.status_pending"),
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
  // Close lightbox if open
  if (imageLightboxOpen.value) {
    closeImageLightbox();
  }
  // Clear existing files and revoke preview URLs
  feedbackFiles.value.forEach(file => {
    if (file?.previewUrl) {
      try {
        URL.revokeObjectURL(file.previewUrl);
      } catch (e) {
        // Ignore errors if URL was already revoked
      }
    }
  });
  feedbackFiles.value = [];
  clipboardMessage.value = '';

  // Initialize accepted status fields
  registeredCount.value = props.file.registered_count || 0;
  notRegisteredCount.value = props.file.not_registered_count || 0;

  statusDialogOpen.value = true;
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  if (files.length > 0) {
    // Create preview URLs for image files
    files.forEach(file => {
      if (isImageFile(file)) {
        file.previewUrl = URL.createObjectURL(file);
      }
    });
    feedbackFiles.value = [...feedbackFiles.value, ...files];
    // Reset input to allow selecting the same files again if needed
    if (fileInput.value) {
      fileInput.value.value = "";
    }
  }
};

const handlePaste = (event) => {
  // Only handle paste when status dialog is open
  if (!statusDialogOpen.value) {
    return;
  }

  const items = event.clipboardData?.items;
  if (!items) {
    return;
  }

  const pastedFiles = [];
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (item.kind === 'file') {
      const file = item.getAsFile();
      if (file) {
        pastedFiles.push(file);
      }
    }
  }

  if (pastedFiles.length > 0) {
    // Create preview URLs for image files
    pastedFiles.forEach(file => {
      if (isImageFile(file)) {
        file.previewUrl = URL.createObjectURL(file);
      }
    });
    feedbackFiles.value = [...feedbackFiles.value, ...pastedFiles];
    event.preventDefault();
  }
};

const getFromClipboard = async () => {
  isGettingFromClipboard.value = true;
  clipboardMessage.value = '';

  try {
    // Check if Clipboard API is available
    if (!navigator.clipboard || !navigator.clipboard.read) {
      // Fallback: try to read from paste event
      clipboardMessage.value = t('messages.paste_files_hint') || 'Please press Ctrl+V (or Cmd+V on Mac) to paste files';
      setTimeout(() => {
        clipboardMessage.value = '';
      }, 3000);
      isGettingFromClipboard.value = false;
      return;
    }

    const clipboardItems = await navigator.clipboard.read();
    const pastedFiles = [];

    for (const clipboardItem of clipboardItems) {
      for (const type of clipboardItem.types) {
        if (type.startsWith('image/') || type.startsWith('application/') || type.startsWith('text/')) {
          const blob = await clipboardItem.getType(type);
          const extension = type.split('/')[1]?.split(';')[0] || 'file';
          const fileName = `pasted-${Date.now()}.${extension}`;
          const file = new File([blob], fileName, { type });
          pastedFiles.push(file);
        }
      }
    }

    if (pastedFiles.length > 0) {
      // Create preview URLs for image files
      pastedFiles.forEach(file => {
        if (isImageFile(file)) {
          file.previewUrl = URL.createObjectURL(file);
        }
      });
      feedbackFiles.value = [...feedbackFiles.value, ...pastedFiles];
      clipboardMessage.value = t('messages.files_added_from_clipboard', { count: pastedFiles.length }) || `${pastedFiles.length} file(s) added from clipboard`;
      setTimeout(() => {
        clipboardMessage.value = '';
      }, 2000);
    } else {
      clipboardMessage.value = t('messages.no_files_in_clipboard') || 'No files found in clipboard';
      setTimeout(() => {
        clipboardMessage.value = '';
      }, 3000);
    }
  } catch (error) {
    // Clipboard API might not be available or user denied permission
    clipboardMessage.value = t('messages.paste_files_hint') || 'Please press Ctrl+V (or Cmd+V on Mac) to paste files';
    setTimeout(() => {
      clipboardMessage.value = '';
    }, 3000);
  } finally {
    isGettingFromClipboard.value = false;
  }
};

// Helper function to check if file is an image
const isImageFile = (file) => {
  return file.type?.startsWith('image/') || false;
};

// Helper function to get preview URL
const getFilePreviewUrl = (file) => {
  if (isImageFile(file) && file.previewUrl) {
    return file.previewUrl;
  }
  return null;
};

// Open image lightbox
const openImageLightbox = (file) => {
  if (isImageFile(file)) {
    // Always create a fresh blob URL for the lightbox to ensure it persists
    // This prevents issues if the file's previewUrl gets revoked
    // Revoke any existing lightbox blob URL first
    if (lightboxBlobUrl.value) {
      try {
        URL.revokeObjectURL(lightboxBlobUrl.value);
      } catch (e) {
        // Ignore errors
      }
    }
    // Create a new blob URL specifically for the lightbox
    lightboxBlobUrl.value = URL.createObjectURL(file);
    lightboxImageUrl.value = lightboxBlobUrl.value;
    lightboxImageName.value = file.name;
    imageLightboxOpen.value = true;
  }
};

// Close image lightbox
const closeImageLightbox = () => {
  // Revoke the blob URL we created for the lightbox
  if (lightboxBlobUrl.value) {
    try {
      URL.revokeObjectURL(lightboxBlobUrl.value);
    } catch (e) {
      // Ignore errors if URL was already revoked
    }
    lightboxBlobUrl.value = null;
  }
  imageLightboxOpen.value = false;
  lightboxImageUrl.value = '';
  lightboxImageName.value = '';
};

// Clean up object URLs when component unmounts or files change
watch(feedbackFiles, (newFiles, oldFiles) => {
  // Revoke old preview URLs that are no longer in the new list
  if (oldFiles) {
    oldFiles.forEach(file => {
      if (isImageFile(file) && file.previewUrl) {
        // Only revoke if this URL is not in the new files and not currently in lightbox
        const stillExists = newFiles.some(f => f?.previewUrl === file.previewUrl);
        const isInLightbox = lightboxImageUrl.value === file.previewUrl;
        if (!stillExists && !isInLightbox) {
          try {
            URL.revokeObjectURL(file.previewUrl);
          } catch (e) {
            // Ignore errors if URL was already revoked
          }
        }
      }
    });
  }
  // Create preview URLs for new image files
  newFiles.forEach(file => {
    if (isImageFile(file) && !file.previewUrl) {
      file.previewUrl = URL.createObjectURL(file);
    }
  });
}, { deep: true });

// Add paste event listener when dialog opens
watch(statusDialogOpen, (isOpen) => {
  if (isOpen) {
    // Add paste listener to document
    document.addEventListener('paste', handlePaste);
  } else {
    // Remove paste listener when dialog closes
    document.removeEventListener('paste', handlePaste);
  }
});

const removeFeedbackFile = (index) => {
  const file = feedbackFiles.value[index];
  // Revoke preview URL if it exists and not currently in lightbox
  if (file?.previewUrl) {
    const isInLightbox = lightboxBlobUrl.value && lightboxImageUrl.value === file.previewUrl;
    if (!isInLightbox) {
      try {
        URL.revokeObjectURL(file.previewUrl);
      } catch (e) {
        // Ignore errors if URL was already revoked
      }
    }
  }
  feedbackFiles.value.splice(index, 1);
};

const clearAllFiles = () => {
  // Close lightbox if open
  if (imageLightboxOpen.value) {
    closeImageLightbox();
  }
  // Revoke all preview URLs
  feedbackFiles.value.forEach(file => {
    if (file.previewUrl) {
      try {
        URL.revokeObjectURL(file.previewUrl);
      } catch (e) {
        // Ignore errors if URL was already revoked
      }
    }
  });
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
  }

  // Append multiple feedback files
  feedbackFiles.value.forEach((file, index) => {
    formData.append(`feedback_files[${index}]`, file);
  });

  router.post(`/files/${props.file.id}/status`, formData, {
    forceFormData: true,
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      // Revoke all preview URLs before clearing
      feedbackFiles.value.forEach(file => {
        if (file?.previewUrl) {
          URL.revokeObjectURL(file.previewUrl);
        }
      });
      statusDialogOpen.value = false;
      feedbackFiles.value = [];
      clipboardMessage.value = '';
      isUpdatingStatus.value = false;
      // Message will come from flash after page reload
    },
    onError: () => {
      isUpdatingStatus.value = false;
    },
    onFinish: () => {
      isUpdatingStatus.value = false;
    },
  });
};

const handleDownload = () => {
  if (isFileTooLarge.value) {
    // For large files, send to Telegram - redirect will handle the response
    router.visit(`/files/${props.file.id}/download`, {
      preserveState: true,
      preserveScroll: true,
    });
  } else {
    // For small files, open download in new tab
    window.open(`/files/${props.file.id}/download`, "_blank");
  }
};

const deleteFile = () => {
  if (confirm(t("messages.delete_confirmation"))) {
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
      <!-- Success/Error Messages -->
      <Alert
        v-if="successMessage"
        class="border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
      >
        <CheckCircle class="h-4 w-4" />
        <AlertDescription>{{ successMessage }}</AlertDescription>
      </Alert>

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
            <p class="text-muted-foreground">{{ t("messages.file_details") }}</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <Button variant="outline" @click="handleDownload">
            <Download class="mr-2 h-4 w-4" />
            {{ isFileTooLarge ? "Send to Telegram" : t("messages.download") }}
          </Button>
          <Button @click="openStatusDialog">
            <Edit class="mr-2 h-4 w-4" />
            {{ t("messages.update_status") }}
          </Button>
          <Button variant="destructive" @click="deleteFile">
            {{ t("messages.delete_file") }}
          </Button>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-3">
        <!-- File Info Card -->
        <Card class="md:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2">
              <component :is="getFileIcon(file.file_type)" class="h-6 w-6" />
              <span>{{ t("messages.file_information") }}</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.name")
                }}</Label>
                <p class="text-sm">{{ file.name }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.original_filename")
                }}</Label>
                <p class="text-sm">{{ file.original_filename }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.file_type")
                }}</Label>
                <p class="text-sm capitalize">{{ file.file_type }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.mime_type")
                }}</Label>
                <p class="text-sm">{{ file.mime_type }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.file_size")
                }}</Label>
                <p class="text-sm">{{ formatFileSize(file.file_size) }}</p>
              </div>
              <div>
                <Label class="text-sm font-medium text-muted-foreground">{{
                  t("messages.status")
                }}</Label>
                <Badge :variant="getStatusBadge(file.status).variant" class="w-fit">
                  <component
                    :is="getStatusBadge(file.status).icon"
                    class="mr-1 h-3 w-3"
                  />
                  {{ getStatusBadge(file.status).text }}
                </Badge>
              </div>
            </div>

            <!-- Large file info -->
            <div
              v-if="isFileTooLarge"
              class="text-xs bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-3 py-2 rounded mt-2"
            >
              <div class="font-medium text-blue-800 dark:text-blue-200 mb-1">
                📱 Large File Notice:
              </div>
              <div class="text-blue-700 dark:text-blue-300">
                This file is larger than 20MB. Click "Send to Telegram" to receive the
                file in your Telegram chat.
              </div>
            </div>

            <div v-if="file.admin_notes">
              <Label class="text-sm font-medium text-muted-foreground">{{
                t("messages.admin_notes")
              }}</Label>
              <p class="text-sm mt-1 p-3 bg-muted rounded-md">{{ file.admin_notes }}</p>
            </div>

            <!-- Accepted status information -->
            <div
              v-if="file.status === 'accepted'"
              class="text-xs bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-3 py-2 rounded mt-2"
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
                  <strong>{{ t("messages.notes") }}:</strong> {{ file.accepted_note }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- User & Timestamps Card -->
        <Card>
          <CardHeader>
            <CardTitle>{{ t("messages.user_and_time") }}</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label class="text-sm font-medium text-muted-foreground">{{
                t("messages.uploaded_by")
              }}</Label>
              <p class="text-sm font-medium">{{ file.user.name }}</p>
              <p class="text-xs text-muted-foreground">{{ file.user.email }}</p>
              <p v-if="file.user.region" class="text-xs text-muted-foreground">
                📍 {{ file.user.region }}
              </p>
            </div>

            <div>
              <Label class="text-sm font-medium text-muted-foreground">{{
                t("messages.uploaded_at")
              }}</Label>
              <p class="text-sm">{{ formatDate(file.created_at) }}</p>
            </div>

            <div>
              <Label class="text-sm font-medium text-muted-foreground">{{
                t("messages.last_updated")
              }}</Label>
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
          <DialogTitle>{{ t("messages.update_file_status") }}</DialogTitle>
          <DialogDescription>
            "{{ file.name }}" {{ t("messages.update_status_description") }}
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div>
            <Label for="status">{{ t("messages.status") }}</Label>
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
            <Label for="notes">{{ t("messages.admin_notes") }}</Label>
            <Textarea
              id="notes"
              v-model="adminNotes"
              :placeholder="t('messages.admin_notes_placeholder')"
              class="mt-1 min-h-[80px]"
            />
          </div>

          <!-- Accepted status specific fields -->
          <div
            v-if="newStatus === 'accepted'"
            class="space-y-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
          >
            <h4 class="text-sm font-medium text-green-800 dark:text-green-200">
              {{ t("messages.approval_info") }}
            </h4>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="registered_count">{{ t("messages.registered_count") }}</Label>
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
                <Label for="not_registered_count">{{
                  t("messages.not_registered_count")
                }}</Label>
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
          </div>

          <div>
            <Label>{{ t("messages.feedback_file_optional") }}</Label>
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
                  {{ t("messages.select_file") }}
                </Button>

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  @click="getFromClipboard"
                  :disabled="isGettingFromClipboard"
                >
                  <LoaderCircle v-if="isGettingFromClipboard" class="mr-2 h-4 w-4 animate-spin" />
                  <Clipboard v-else class="mr-2 h-4 w-4" />
                  {{ isGettingFromClipboard ? (t("messages.loading") || "Loading...") : (t("messages.get_from_clipboard") || "Get from clipboard") }}
                </Button>

                <Button
                  v-if="feedbackFiles.length > 0"
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="clearAllFiles"
                >
                  <X class="mr-2 h-4 w-4" />
                  {{ t("messages.clear_all") }}
                </Button>
              </div>

              <div v-if="clipboardMessage" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                <p class="text-xs text-blue-800 dark:text-blue-200">{{ clipboardMessage }}</p>
              </div>

              <div v-if="feedbackFiles.length > 0" class="space-y-2 mt-2">
                <div
                  v-for="(file, index) in feedbackFiles"
                  :key="index"
                  class="flex items-center gap-2 p-2 bg-muted rounded-md"
                >
                  <!-- Image Preview -->
                  <div v-if="isImageFile(file)" class="flex-shrink-0 cursor-pointer" @click="openImageLightbox(file)">
                    <img
                      :src="getFilePreviewUrl(file)"
                      :alt="file.name"
                      class="h-10 w-10 object-cover rounded border border-border hover:opacity-80 transition-opacity"
                    />
                  </div>
                  <!-- File Icon for non-images -->
                  <component
                    v-else
                    :is="getFileIcon(file.type || 'document')"
                    class="h-4 w-4 text-muted-foreground flex-shrink-0"
                  />
                  <span class="text-sm flex-1 truncate">{{ file.name }}</span>
                  <span class="text-xs text-muted-foreground whitespace-nowrap">
                    {{ formatFileSize(file.size) }}
                  </span>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeFeedbackFile(index)"
                    class="flex-shrink-0"
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

        <DialogFooter>
          <Button
            variant="outline"
            @click="statusDialogOpen = false"
            :disabled="isUpdatingStatus"
          >
            {{ t("messages.cancel") }}
          </Button>
          <Button @click="updateStatus" :disabled="isUpdatingStatus">
            <LoaderCircle v-if="isUpdatingStatus" class="w-4 h-4 animate-spin mr-2" />
            {{ isUpdatingStatus ? t("messages.updating") : t("messages.update_status") }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Image Lightbox -->
    <Dialog :open="imageLightboxOpen" @update:open="(open) => { if (!open) closeImageLightbox(); }">
      <DialogContent class="max-w-7xl w-full p-0 bg-black/95 border-none [&>button]:hidden">
        <div class="relative w-full h-[90vh] flex items-center justify-center p-4">
          <img
            :src="lightboxImageUrl"
            :alt="lightboxImageName"
            class="max-w-full max-h-full object-contain rounded"
          />
          <DialogClose class="absolute top-4 right-4 z-50 rounded-md p-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 transition-colors">
            <X class="w-5 h-5" />
            <span class="sr-only">Close</span>
          </DialogClose>
          <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/70 text-white px-4 py-2 rounded text-sm">
            {{ lightboxImageName }}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
