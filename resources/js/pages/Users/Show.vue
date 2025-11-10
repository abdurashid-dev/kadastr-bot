<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useTranslations } from "@/composables/useTranslations";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  ArrowLeft,
  Edit,
  Save,
  X,
  FileText,
  MessageSquare,
  Send,
  CheckCircle,
  XCircle,
  Clock,
  User as UserIcon,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Shield,
  UserCheck,
  Crown,
  User,
} from "lucide-vue-next";

const { t } = useTranslations();

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  telegramMessages: {
    type: Object,
    default: () => ({ data: [], links: [], meta: {} }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const editing = ref(false);
const messageDialogOpen = ref(false);
const messageText = ref("");
const sendingMessage = ref(false);
const activeTab = ref("files");
const messagesPerPage = ref(props.filters.messages_per_page || 15);
const form = ref({
  name: props.user.name,
  email: props.user.email,
  phone_number: props.user.phone_number || "",
  region: props.user.region || "",
});

const startEditing = () => {
  editing.value = true;
  form.value = {
    name: props.user.name,
    email: props.user.email,
    phone_number: props.user.phone_number || "",
    region: props.user.region || "",
  };
};

const cancelEditing = () => {
  editing.value = false;
  form.value = {
    name: props.user.name,
    email: props.user.email,
    phone_number: props.user.phone_number || "",
    region: props.user.region || "",
  };
};

const saveChanges = async () => {
  try {
    await router.put(`/users/${props.user.id}`, form.value, {
      preserveState: true,
    });
    editing.value = false;
  } catch (error) {
    console.error("Error updating user:", error);
  }
};

const updateRole = async (newRole) => {
  try {
    await router.put(
      `/users/${props.user.id}/role`,
      { role: newRole },
      {
        preserveState: true,
      }
    );
  } catch (error) {
    console.error("Error updating user role:", error);
  }
};

const openMessageDialog = () => {
  messageDialogOpen.value = true;
  messageText.value = "";
};

const closeMessageDialog = () => {
  messageDialogOpen.value = false;
  messageText.value = "";
  sendingMessage.value = false;
};

const sendMessage = async () => {
  if (!messageText.value.trim()) return;
  
  sendingMessage.value = true;
  
  try {
    await router.post(`/users/${props.user.id}/send-message`, {
      message: messageText.value.trim(),
    }, {
      onSuccess: () => {
        closeMessageDialog();
        router.reload({ 
          only: ['telegramMessages', 'filters'],
          preserveState: true,
          preserveScroll: true,
        });
      },
    });
  } catch (error) {
    console.error("Error sending message:", error);
    sendingMessage.value = false;
  }
};

const getRoleIcon = (role) => {
  const icons = {
    user: UserIcon,
    checker: Shield,
    registrator: UserCheck,
    ceo: Crown,
  };
  return icons[role] || UserIcon;
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

const getRoleColor = (role) => {
  const colors = {
    user: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200",
    checker: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    registrator: "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200",
    ceo: "bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200",
  };
  return colors[role] || "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200";
};

const getStatusColor = (status) => {
  const colors = {
    pending: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
    waiting: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    accepted: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return (
    colors[status] || "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200"
  );
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

const formatRelativeTime = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);
  
  if (diffInSeconds < 60) return t("messages.just_now") || "Just now";
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} ${t("messages.minutes_ago") || "minutes ago"}`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ${t("messages.hours_ago") || "hours ago"}`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} ${t("messages.days_ago") || "days ago"}`;
  
  return formatDate(dateString);
};

const handleMessagesPerPageChange = (value) => {
  messagesPerPage.value = parseInt(value);
  router.get(
    window.location.pathname,
    { messages_per_page: messagesPerPage.value },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["telegramMessages", "filters"],
    }
  );
};
</script>

<template>
  <AppLayout>
    <Head :title="`${user.name} - ${t('messages.user_details')}`" />

    <div class="space-y-6 p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="sm" as-child>
            <Link href="/users">
              <ArrowLeft class="mr-2 h-4 w-4" />
              {{ t("messages.back_to_users") }}
            </Link>
          </Button>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="!editing" @click="startEditing" variant="outline">
            <Edit class="mr-2 h-4 w-4" />
            {{ t("messages.edit_profile") }}
          </Button>
          <template v-else>
            <Button @click="saveChanges">
              <Save class="mr-2 h-4 w-4" />
              {{ t("messages.save_changes") }}
            </Button>
            <Button @click="cancelEditing" variant="outline">
              <X class="h-4 w-4" />
            </Button>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: User Profile -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Profile Card -->
          <Card>
            <CardHeader class="pb-4">
              <div class="flex flex-col items-center text-center space-y-4">
                <Avatar class="h-24 w-24 ring-2 ring-primary/20">
                  <AvatarImage :src="user.avatar" :alt="user.name" />
                  <AvatarFallback class="text-3xl font-semibold">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </AvatarFallback>
                </Avatar>
                <div class="space-y-1">
                  <CardTitle class="text-xl">
                    {{ editing ? form.name : user.name }}
                  </CardTitle>
                  <CardDescription>{{ editing ? form.email : user.email }}</CardDescription>
                  <Badge :class="getRoleColor(user.role)" class="mt-2">
                    <component :is="getRoleIcon(user.role)" class="mr-1.5 h-3.5 w-3.5" />
                    {{ getRoleLabel(user.role) }}
                  </Badge>
                </div>
              </div>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Contact Info -->
              <div class="space-y-3">
                <div class="flex items-center gap-3 text-sm">
                  <UserIcon class="h-4 w-4 text-muted-foreground" />
                  <div class="flex-1">
                    <div v-if="!editing" class="text-foreground">{{ user.name }}</div>
                    <Input v-else id="name" v-model="form.name" class="h-8" />
                  </div>
                </div>

                <div class="flex items-center gap-3 text-sm">
                  <Mail class="h-4 w-4 text-muted-foreground" />
                  <div class="flex-1">
                    <div v-if="!editing" class="text-foreground">{{ user.email }}</div>
                    <Input v-else id="email" v-model="form.email" type="email" class="h-8" />
                  </div>
                </div>

                <div v-if="user.phone_number" class="flex items-center gap-3 text-sm">
                  <Phone class="h-4 w-4 text-muted-foreground" />
                  <div class="flex-1">
                    <div v-if="!editing" class="text-foreground">{{ user.phone_number }}</div>
                    <Input v-else id="phone" v-model="form.phone_number" class="h-8" />
                  </div>
                </div>

                <div v-if="user.region" class="flex items-center gap-3 text-sm">
                  <MapPin class="h-4 w-4 text-muted-foreground" />
                  <div class="flex-1">
                    <div v-if="!editing" class="text-foreground">{{ user.region }}</div>
                    <Input v-else id="region" v-model="form.region" class="h-8" />
                  </div>
                </div>

                <div class="flex items-center gap-3 text-sm">
                  <Calendar class="h-4 w-4 text-muted-foreground" />
                  <div class="text-foreground">{{ formatDate(user.created_at) }}</div>
                </div>
              </div>

              <Separator />

              <!-- Stats -->
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-3 bg-muted/50 rounded-lg">
                  <div class="text-2xl font-bold text-foreground">{{ user.uploaded_files_count || 0 }}</div>
                  <div class="text-xs text-muted-foreground mt-1">{{ t("messages.files") }}</div>
                </div>
                <div class="text-center p-3 bg-muted/50 rounded-lg">
                  <div class="text-2xl font-bold text-foreground">{{ telegramMessages?.total || 0 }}</div>
                  <div class="text-xs text-muted-foreground mt-1">{{ t("messages.messages") || "Messages" }}</div>
                </div>
              </div>

              <!-- Role Selector -->
              <div>
                <Label class="text-xs font-medium text-muted-foreground mb-2 block">
                  {{ t("messages.role") }}
                </Label>
                <Select :model-value="user.role" @update:model-value="updateRole">
                  <SelectTrigger class="h-9">
                    <SelectValue>
                      <div class="flex items-center gap-2">
                        <component :is="getRoleIcon(user.role)" class="h-4 w-4" />
                        <span>{{ getRoleLabel(user.role) }}</span>
                      </div>
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="user">
                      <div class="flex items-center gap-2">
                        <UserIcon class="h-4 w-4" />
                        <span>{{ t("messages.role_user") }}</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="checker">
                      <div class="flex items-center gap-2">
                        <Shield class="h-4 w-4" />
                        <span>{{ t("messages.role_checker") }}</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="registrator">
                      <div class="flex items-center gap-2">
                        <UserCheck class="h-4 w-4" />
                        <span>{{ t("messages.role_registrator") }}</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="ceo">
                      <div class="flex items-center gap-2">
                        <Crown class="h-4 w-4" />
                        <span>{{ t("messages.role_ceo") }}</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Send Message Button -->
              <Button
                v-if="user.telegram_id"
                @click="openMessageDialog"
                class="w-full"
              >
                <Send class="mr-2 h-4 w-4" />
                {{ t("messages.send_telegram_message") }}
              </Button>
              <div v-else class="text-xs text-muted-foreground text-center p-2 bg-muted/50 rounded">
                {{ t("messages.no_telegram_id") || "No Telegram ID" }}
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column: Files and Messages with Tabs -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader class="pb-3">
              <!-- Tabs -->
              <div class="inline-flex gap-1 rounded-lg bg-muted p-1">
                <button
                  @click="activeTab = 'files'"
                  :class="[
                    'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'files'
                      ? 'bg-background shadow-sm text-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                  ]"
                >
                  <FileText class="h-4 w-4" />
                  {{ t("messages.files") || "Files" }}
                  <Badge v-if="user.uploaded_files_count > 0" variant="secondary" class="ml-1 text-xs">
                    {{ user.uploaded_files_count }}
                  </Badge>
                </button>
                <button
                  @click="activeTab = 'messages'"
                  :class="[
                    'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'messages'
                      ? 'bg-background shadow-sm text-foreground'
                      : 'text-muted-foreground hover:text-foreground',
                  ]"
                >
                  <MessageSquare class="h-4 w-4" />
                  {{ t("messages.telegram_messages") || "Messages" }}
                  <Badge v-if="telegramMessages?.total > 0" variant="secondary" class="ml-1 text-xs">
                    {{ telegramMessages?.total || 0 }}
                  </Badge>
                </button>
              </div>
            </CardHeader>
            <CardContent>
              <!-- Files Tab -->
              <div v-if="activeTab === 'files'">
                <div
                  v-if="user.uploaded_files && user.uploaded_files.length > 0"
                  class="space-y-3"
                >
                  <Link
                    v-for="file in user.uploaded_files"
                    :key="file.id"
                    :href="`/files/${file.id}`"
                    class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/30 transition-colors group"
                  >
                    <div class="flex-1 min-w-0">
                      <h3 class="text-sm font-medium group-hover:text-primary transition-colors truncate">
                        {{ file.name }}
                      </h3>
                      <p class="text-sm text-muted-foreground truncate">
                        {{ file.original_filename }}
                      </p>
                      <p class="text-xs text-muted-foreground mt-1">
                        {{ formatDate(file.created_at) }} •
                        {{ (file.file_size / 1024).toFixed(1) }} KB
                      </p>
                    </div>
                    <Badge :class="getStatusColor(file.status)" class="ml-4">
                      {{ file.status.charAt(0).toUpperCase() + file.status.slice(1) }}
                    </Badge>
                  </Link>
                </div>

                <div v-else class="text-center py-12">
                  <FileText class="mx-auto h-12 w-12 text-muted-foreground opacity-50" />
                  <h3 class="mt-4 text-sm font-medium text-muted-foreground">
                    {{ t("messages.no_files_uploaded") }}
                  </h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    {{ t("messages.no_files_uploaded_description") }}
                  </p>
                </div>

                <div v-if="user.uploaded_files_count > 10" class="mt-4 text-center">
                  <Button variant="outline" as-child>
                    <Link :href="`/approval/history?user=${user.id}`">
                      {{ t("messages.view_all_files", { count: user.uploaded_files_count }) }}
                    </Link>
                  </Button>
                </div>
              </div>

              <!-- Messages Tab -->
              <div v-if="activeTab === 'messages'">
                <!-- Per Page Selector -->
                <div v-if="telegramMessages?.data && telegramMessages.data.length > 0" class="mb-4 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">{{ t("messages.per_page") || "Per page" }}:</span>
                    <Select :model-value="messagesPerPage.toString()" @update:model-value="handleMessagesPerPageChange">
                      <SelectTrigger class="w-20 h-7 text-xs">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="15">15</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <div v-if="telegramMessages?.data && telegramMessages.data.length > 0" class="space-y-3">
                  <div
                    v-for="msg in telegramMessages.data"
                    :key="msg.id"
                    class="p-4 border rounded-lg hover:bg-muted/30 transition-colors"
                  >
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                          <Avatar class="h-6 w-6">
                            <AvatarFallback class="text-xs">
                              {{ msg.sender?.name?.charAt(0).toUpperCase() || "A" }}
                            </AvatarFallback>
                          </Avatar>
                          <span class="text-sm font-medium">{{ msg.sender?.name || t("messages.unknown_user") }}</span>
                          <Badge
                            :variant="msg.sent_successfully ? 'default' : 'destructive'"
                            class="text-xs"
                          >
                            <CheckCircle v-if="msg.sent_successfully" class="mr-1 h-3 w-3" />
                            <XCircle v-else class="mr-1 h-3 w-3" />
                            {{ msg.sent_successfully ? t("messages.sent") || "Sent" : t("messages.failed") || "Failed" }}
                          </Badge>
                        </div>
                        <p class="text-sm text-foreground whitespace-pre-wrap break-words">{{ msg.message }}</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                          <span class="flex items-center gap-1">
                            <Clock class="h-3 w-3" />
                            {{ formatRelativeTime(msg.created_at) }}
                          </span>
                          <span v-if="msg.is_bulk" class="text-xs">
                            {{ t("messages.bulk_message") || "Bulk" }}
                          </span>
                        </div>
                        <div v-if="msg.error_message" class="mt-2 text-xs text-destructive bg-destructive/10 p-2 rounded">
                          {{ msg.error_message }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-12">
                  <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground opacity-50" />
                  <h3 class="mt-4 text-sm font-medium text-muted-foreground">
                    {{ t("messages.no_messages") || "No messages yet" }}
                  </h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    {{ t("messages.no_messages_description") || "No Telegram messages have been sent to this user." }}
                  </p>
                </div>

                <!-- Pagination -->
                <div v-if="telegramMessages?.links && telegramMessages.links.length > 3" class="mt-6">
                  <nav class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                      <Link
                        v-if="telegramMessages?.prev_page_url"
                        :href="telegramMessages.prev_page_url"
                        class="relative inline-flex items-center px-3 py-1.5 border border-input bg-background text-xs font-medium rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                      >
                        {{ t("messages.previous") }}
                      </Link>
                      <Link
                        v-if="telegramMessages?.next_page_url"
                        :href="telegramMessages.next_page_url"
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
                              from: telegramMessages?.from || 0,
                              to: telegramMessages?.to || 0,
                              total: telegramMessages?.total || 0,
                            })
                          }}
                        </p>
                      </div>
                      <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                          <template v-for="link in telegramMessages?.links || []" :key="link.label">
                            <Link
                              v-if="link.url"
                              :href="link.url"
                              :class="[
                                'relative inline-flex items-center px-3 py-1.5 border border-input text-xs font-medium transition-colors',
                                link.active
                                  ? 'z-10 bg-primary text-primary-foreground border-primary'
                                  : 'bg-background text-foreground hover:bg-accent hover:text-accent-foreground',
                                link === (telegramMessages?.links || [])[0] ? 'rounded-l-md' : '',
                                link === (telegramMessages?.links || [])[(telegramMessages?.links || []).length - 1] ? 'rounded-r-md' : '',
                              ]"
                              v-html="link.label"
                            />
                            <span
                              v-else
                              :class="[
                                'relative inline-flex items-center px-3 py-1.5 border border-input bg-background text-xs font-medium text-muted-foreground',
                                link === (telegramMessages?.links || [])[0] ? 'rounded-l-md' : '',
                                link === (telegramMessages?.links || [])[(telegramMessages?.links || []).length - 1] ? 'rounded-r-md' : '',
                              ]"
                              v-html="link.label"
                            />
                          </template>
                        </nav>
                      </div>
                    </div>
                  </nav>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Send Message Dialog -->
    <Dialog v-model:open="messageDialogOpen">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{{ t("messages.send_telegram_message") }}</DialogTitle>
          <DialogDescription>
            {{ t("messages.send_message_to_user") || `Send a message to ${user.name} via Telegram` }}
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <Textarea
            v-model="messageText"
            :placeholder="t('messages.enter_message')"
            class="min-h-[120px] resize-none"
            :maxlength="4000"
            :disabled="sendingMessage"
          />
          <p class="mt-2 text-xs text-muted-foreground text-right">
            {{ messageText.length }} / 4000
          </p>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            @click="closeMessageDialog"
            :disabled="sendingMessage"
          >
            {{ t("messages.cancel") }}
          </Button>
          <Button
            @click="sendMessage"
            :disabled="!messageText.trim() || sendingMessage"
          >
            <Send v-if="!sendingMessage" class="mr-2 h-4 w-4" />
            {{ sendingMessage ? t("messages.sending") : t("messages.send") }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
