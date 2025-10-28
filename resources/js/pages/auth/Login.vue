<script setup lang="ts">
import InputError from "@/components/InputError.vue";
import TextLink from "@/components/TextLink.vue";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AuthBase from "@/layouts/AuthLayout.vue";
import { Form, Head } from "@inertiajs/vue3";
import { LoaderCircle, FileText, Shield, Users } from "lucide-vue-next";
import { useTranslations } from "@/composables/useTranslations";

const { t } = useTranslations();

defineProps<{
  status?: string;
  canResetPassword: boolean;
}>();
</script>

<template>
  <AuthBase
    :title="t('messages.login_title')"
    :description="t('messages.login_description')"
  >
    <Head :title="t('messages.login')" />

    <!-- Fergana Kadastr Header -->
    <div class="mb-8 text-center">
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 shadow-lg"
      >
        <FileText class="h-8 w-8 text-white" />
      </div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fergana Kadastr</h1>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        Farg'ona viloyati kadastr tizimi
      </p>
    </div>

    <div v-if="status" class="mb-4 text-sm font-medium text-center text-green-600">
      {{ status }}
    </div>

    <Form
      method="post"
      :action="route('login')"
      :reset-on-success="['password']"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="email">{{ t("messages.email") }}</Label>
          <Input
            id="email"
            type="email"
            name="email"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            :placeholder="t('messages.email_placeholder')"
          />
          <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
          <div class="flex items-center justify-between">
            <Label for="password">{{ t("messages.password") }}</Label>
            <TextLink
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-sm"
              :tabindex="5"
            >
              {{ t("messages.forgot_password") }}
            </TextLink>
          </div>
          <Input
            id="password"
            type="password"
            name="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            :placeholder="t('messages.password_placeholder')"
          />
          <InputError :message="errors.password" />
        </div>

        <div class="flex items-center justify-between">
          <Label for="remember" class="flex items-center space-x-3">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <span>{{ t("messages.remember_me") }}</span>
          </Label>
        </div>

        <Button type="submit" class="w-full mt-4" :tabindex="4" :disabled="processing">
          <LoaderCircle v-if="processing" class="w-4 h-4 animate-spin" />
          {{ t("messages.login") }}
        </Button>
      </div>
    </Form>
  </AuthBase>
</template>
