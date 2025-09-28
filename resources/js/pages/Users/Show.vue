<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
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
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { ArrowLeft, Edit, Save, X, FileText } from "lucide-vue-next";

const { t } = useTranslations();

const props = defineProps({
  user: Object,
});

const editing = ref(false);
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

const getRoleColor = (role) => {
  const colors = {
    user: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200",
    checker: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    registrator: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
    ceo: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
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
</script>

<template>
  <AppLayout>
    <Head :title="`${user.name} - ${t('messages.user_details')}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center space-x-4">
        <Button variant="ghost" size="sm" as-child>
          <Link href="/users">
            <ArrowLeft class="mr-2 h-4 w-4" />
            {{ t("messages.back_to_users") }}
          </Link>
        </Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Profile Card -->
        <div class="lg:col-span-1">
          <Card>
            <CardHeader>
              <div class="flex items-center space-x-4">
                <Avatar class="h-20 w-20">
                  <AvatarImage :src="user.avatar" :alt="user.name" />
                  <AvatarFallback class="text-2xl">{{
                    user.name.charAt(0).toUpperCase()
                  }}</AvatarFallback>
                </Avatar>
                <div class="flex-1">
                  <CardTitle class="text-2xl">{{ user.name }}</CardTitle>
                  <CardDescription>{{ user.email }}</CardDescription>
                  <Badge :class="getRoleColor(user.role)" class="mt-2">
                    {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
                  </Badge>
                </div>
              </div>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- User Details -->
              <div class="space-y-4">
                <div v-if="!editing">
                  <Label class="text-sm font-medium">{{ t("messages.name") }}</Label>
                  <p class="text-sm text-muted-foreground mt-1">{{ user.name }}</p>
                </div>
                <div v-else>
                  <Label for="name" class="text-sm font-medium">{{
                    t("messages.name")
                  }}</Label>
                  <Input id="name" v-model="form.name" class="mt-1" />
                </div>

                <div v-if="!editing">
                  <Label class="text-sm font-medium">{{ t("messages.email") }}</Label>
                  <p class="text-sm text-muted-foreground mt-1">{{ user.email }}</p>
                </div>
                <div v-else>
                  <Label for="email" class="text-sm font-medium">{{
                    t("messages.email")
                  }}</Label>
                  <Input id="email" v-model="form.email" type="email" class="mt-1" />
                </div>

                <div v-if="user.phone_number">
                  <div v-if="!editing">
                    <Label class="text-sm font-medium">{{
                      t("messages.phone_number")
                    }}</Label>
                    <p class="text-sm text-muted-foreground mt-1">
                      {{ user.phone_number }}
                    </p>
                  </div>
                  <div v-else>
                    <Label for="phone" class="text-sm font-medium">{{
                      t("messages.phone_number")
                    }}</Label>
                    <Input id="phone" v-model="form.phone_number" class="mt-1" />
                  </div>
                </div>

                <div v-if="user.region">
                  <div v-if="!editing">
                    <Label class="text-sm font-medium">{{ t("messages.region") }}</Label>
                    <p class="text-sm text-muted-foreground mt-1">{{ user.region }}</p>
                  </div>
                  <div v-else>
                    <Label for="region" class="text-sm font-medium">{{
                      t("messages.region")
                    }}</Label>
                    <Input id="region" v-model="form.region" class="mt-1" />
                  </div>
                </div>

                <div>
                  <Label class="text-sm font-medium">{{ t("messages.role") }}</Label>
                  <Select
                    :value="user.role"
                    @update:model-value="updateRole"
                    class="mt-1"
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="user">{{ t("messages.role_user") }}</SelectItem>
                      <SelectItem value="checker">{{
                        t("messages.role_checker")
                      }}</SelectItem>
                      <SelectItem value="registrator">{{
                        t("messages.role_registrator")
                      }}</SelectItem>
                      <SelectItem value="ceo">{{ t("messages.role_ceo") }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <Separator />

                <div>
                  <Label class="text-sm font-medium">{{ t("messages.joined") }}</Label>
                  <p class="text-sm text-muted-foreground mt-1">
                    {{ formatDate(user.created_at) }}
                  </p>
                </div>

                <div>
                  <Label class="text-sm font-medium">{{
                    t("messages.total_files")
                  }}</Label>
                  <p class="text-sm text-muted-foreground mt-1">
                    {{ user.uploaded_files_count }}
                  </p>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-2">
                <Button v-if="!editing" @click="startEditing" class="flex-1">
                  <Edit class="mr-2 h-4 w-4" />
                  {{ t("messages.edit_profile") }}
                </Button>
                <template v-else>
                  <Button @click="saveChanges" class="flex-1">
                    <Save class="mr-2 h-4 w-4" />
                    {{ t("messages.save_changes") }}
                  </Button>
                  <Button @click="cancelEditing" variant="outline">
                    <X class="h-4 w-4" />
                  </Button>
                </template>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Recent Files -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center">
                <FileText class="mr-2 h-5 w-5" />
                {{ t("messages.recent_files") }}
              </CardTitle>
              <CardDescription>{{ t("messages.latest_files_by_user") }}</CardDescription>
            </CardHeader>
            <CardContent>
              <div
                v-if="user.uploaded_files && user.uploaded_files.length > 0"
                class="space-y-4"
              >
                <div
                  v-for="file in user.uploaded_files"
                  :key="file.id"
                  class="flex items-center justify-between p-4 border rounded-lg"
                >
                  <div class="flex-1">
                    <h3 class="text-sm font-medium">{{ file.name }}</h3>
                    <p class="text-sm text-muted-foreground">
                      {{ file.original_filename }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ formatDate(file.created_at) }} •
                      {{ (file.file_size / 1024).toFixed(1) }} KB
                    </p>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Badge :class="getStatusColor(file.status)">
                      {{ file.status.charAt(0).toUpperCase() + file.status.slice(1) }}
                    </Badge>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8">
                <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-medium text-muted-foreground">
                  {{ t("messages.no_files_uploaded") }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  {{ t("messages.no_files_uploaded_description") }}
                </p>
              </div>

              <div v-if="user.uploaded_files_count > 10" class="mt-4 text-center">
                <Button variant="outline" as-child>
                  <Link :href="`/approval/history?user=${user.id}`">
                    {{
                      t("messages.view_all_files", { count: user.uploaded_files_count })
                    }}
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
