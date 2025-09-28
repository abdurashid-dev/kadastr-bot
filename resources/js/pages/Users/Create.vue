<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
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
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  ArrowLeft,
  CheckCircle,
  AlertCircle,
  User,
  Mail,
  Phone,
  MapPin,
  MessageSquare,
  Shield,
  UserCheck,
  Crown,
  Save,
} from "lucide-vue-next";

const { t } = useTranslations();

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
  regions: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  name: "",
  email: "",
  phone_number: "",
  region: "",
  role: "user",
  password: "",
  password_confirmation: "",
});

const isSubmitting = ref(false);

// Phone mask implementation
const phoneNumber = ref("");

const formatPhoneNumber = (value) => {
  // Remove all non-digit characters
  const digits = value.replace(/\D/g, "");

  // If it starts with 998, format with +998 prefix
  if (digits.startsWith("998")) {
    const phoneDigits = digits.substring(3); // Remove 998
    return formatPhoneWithMask(phoneDigits);
  }

  // If it starts with 9, add +998 prefix and format
  if (digits.startsWith("9") && digits.length <= 9) {
    return formatPhoneWithMask(digits);
  }

  // If it's just digits, add +998 prefix and format
  if (digits.length <= 9) {
    return formatPhoneWithMask(digits);
  }

  // If it already has +998, keep it
  if (value.startsWith("+998")) {
    return value;
  }

  return value;
};

const formatPhoneWithMask = (digits) => {
  if (digits.length === 0) return "+998";
  if (digits.length <= 2) return `+998(${digits}`;
  if (digits.length <= 5) return `+998(${digits.substring(0, 2)})${digits.substring(2)}`;
  if (digits.length <= 7)
    return `+998(${digits.substring(0, 2)})${digits.substring(2, 5)}-${digits.substring(
      5
    )}`;
  if (digits.length <= 9)
    return `+998(${digits.substring(0, 2)})${digits.substring(2, 5)}-${digits.substring(
      5,
      7
    )}-${digits.substring(7)}`;

  // Limit to 9 digits
  const limitedDigits = digits.substring(0, 9);
  return `+998(${limitedDigits.substring(0, 2)})${limitedDigits.substring(
    2,
    5
  )}-${limitedDigits.substring(5, 7)}-${limitedDigits.substring(7)}`;
};

const handlePhoneInput = (event) => {
  const value = event.target.value;
  const formatted = formatPhoneNumber(value);
  phoneNumber.value = formatted;
  form.phone_number = formatted;
};

// Computed property for region to handle "none" value
const regionValue = computed({
  get: () => form.region || "none",
  set: (value) => {
    form.region = value === "none" ? "" : value;
  },
});

const submit = () => {
  isSubmitting.value = true;

  // Clean phone number before submitting (remove formatting)
  const cleanPhoneNumber = form.phone_number.replace(/\D/g, "");
  form.phone_number = cleanPhoneNumber;

  form.post(route("users.store"), {
    onSuccess: () => {
      isSubmitting.value = false;
    },
    onError: () => {
      isSubmitting.value = false;
    },
  });
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
</script>

<template>
  <AppLayout>
    <Head :title="t('messages.create_user')" />

    <div class="space-y-6 px-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Button variant="outline" size="sm" as-child>
            <Link :href="route('users.index')">
              <ArrowLeft class="h-4 w-4 mr-2" />
              {{ t("messages.back") }}
            </Link>
          </Button>
          <div>
            <h1
              class="text-2xl font-bold tracking-tight bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
            >
              {{ t("messages.create_user") }}
            </h1>
            <p class="text-muted-foreground text-sm mt-1">
              {{ t("messages.create_user_description") }}
            </p>
          </div>
        </div>
      </div>

      <!-- Success/Error Messages -->
      <Alert
        v-if="form.wasSuccessful"
        class="border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200"
      >
        <CheckCircle class="h-4 w-4" />
        <AlertDescription> {{ t("messages.user_created_success") }} </AlertDescription>
      </Alert>

      <Alert v-if="form.hasErrors" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          <ul class="list-disc list-inside space-y-1">
            <li v-for="(error, field) in form.errors" :key="field">
              {{ error }}
            </li>
          </ul>
        </AlertDescription>
      </Alert>

      <!-- Form -->
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle class="flex items-center text-lg">
            <User class="mr-2 h-5 w-5" />
            {{ t("messages.user_information") }}
          </CardTitle>
          <CardDescription>
            {{ t("messages.enter_user_details") }}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Name Field -->
            <div class="space-y-2">
              <Label for="name" class="text-sm font-medium">
                {{ t("messages.name") }} <span class="text-destructive">*</span>
              </Label>
              <div class="relative">
                <User
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  :placeholder="t('messages.enter_full_name')"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.name }"
                />
              </div>
              <p v-if="form.errors.name" class="text-sm text-destructive">
                {{ form.errors.name }}
              </p>
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
              <Label for="email" class="text-sm font-medium">
                {{ t("messages.email") }} <span class="text-destructive">*</span>
              </Label>
              <div class="relative">
                <Mail
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  :placeholder="t('messages.email_placeholder')"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.email }"
                />
              </div>
              <p v-if="form.errors.email" class="text-sm text-destructive">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Phone Number Field -->
            <div class="space-y-2">
              <Label for="phone_number" class="text-sm font-medium">
                {{ t("messages.phone_number") }}
              </Label>
              <div class="relative">
                <Phone
                  class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                  id="phone_number"
                  v-model="phoneNumber"
                  type="tel"
                  :placeholder="t('messages.phone_placeholder')"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.phone_number }"
                  @input="handlePhoneInput"
                />
              </div>
              <p v-if="form.errors.phone_number" class="text-sm text-destructive">
                {{ form.errors.phone_number }}
              </p>
            </div>

            <!-- Region Field -->
            <div class="space-y-2">
              <Label for="region" class="text-sm font-medium">
                {{ t("messages.region") }}
              </Label>
              <div class="relative">
                <Select v-model="regionValue">
                  <SelectTrigger
                    class="w-full"
                    :class="{ 'border-destructive': form.errors.region }"
                  >
                    <SelectValue :placeholder="t('messages.select_region')" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">{{ t("messages.no_region") }}</SelectItem>
                    <SelectItem v-for="region in regions" :key="region" :value="region">
                      {{ region }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <p v-if="form.errors.region" class="text-sm text-destructive">
                {{ form.errors.region }}
              </p>
            </div>

            <!-- Role Field -->
            <div class="space-y-2">
              <Label for="role" class="text-sm font-medium">
                {{ t("messages.role") }} <span class="text-destructive">*</span>
              </Label>
              <Select v-model="form.role">
                <SelectTrigger
                  class="w-full"
                  :class="{ 'border-destructive': form.errors.role }"
                >
                  <SelectValue :placeholder="t('messages.select_role')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="role in roles" :key="role" :value="role">
                    <div class="flex items-center space-x-2">
                      <component :is="getRoleIcon(role)" class="h-4 w-4" />
                      <span>{{ getRoleLabel(role) }}</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.role" class="text-sm text-destructive">
                {{ form.errors.role }}
              </p>
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
              <Label for="password" class="text-sm font-medium">
                {{ t("messages.password") }} <span class="text-destructive">*</span>
              </Label>
              <Input
                id="password"
                v-model="form.password"
                type="password"
                :placeholder="t('messages.enter_password')"
                :class="{ 'border-destructive': form.errors.password }"
              />
              <p v-if="form.errors.password" class="text-sm text-destructive">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Password Confirmation Field -->
            <div class="space-y-2">
              <Label for="password_confirmation" class="text-sm font-medium">
                {{ t("messages.confirm_password") }}
                <span class="text-destructive">*</span>
              </Label>
              <Input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                :placeholder="t('messages.re_enter_password')"
                :class="{ 'border-destructive': form.errors.password_confirmation }"
              />
              <p
                v-if="form.errors.password_confirmation"
                class="text-sm text-destructive"
              >
                {{ form.errors.password_confirmation }}
              </p>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4">
              <Button type="button" variant="outline" as-child :disabled="isSubmitting">
                <Link :href="route('users.index')"> {{ t("messages.cancel") }} </Link>
              </Button>
              <Button
                type="submit"
                :disabled="isSubmitting || form.processing"
                class="min-w-[120px]"
              >
                <Save v-if="!isSubmitting && !form.processing" class="mr-2 h-4 w-4" />
                <div
                  v-else
                  class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
                />
                {{
                  isSubmitting || form.processing
                    ? t("messages.creating")
                    : t("messages.create")
                }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
