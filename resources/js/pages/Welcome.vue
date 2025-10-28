<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import LanguageSwitcher from "@/components/LanguageSwitcher.vue";
import { useTranslations } from "@/composables/useTranslations";
import { computed } from "vue";

const page = usePage();
const { t } = useTranslations();
const locale = computed(() => page.props.locale as string);
const availableLocales = computed(
  () => page.props.available_locales as Record<string, string>
);
</script>

<template>
  <Head title="Fergana Kadastr">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>
  <div
    class="flex min-h-screen flex-col items-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:from-[#0a0a0a] dark:via-[#0f0f0f] dark:to-[#0a0a0a] dark:bg-[#0a0a0a]"
  >
    <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
      <nav class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
            {{ t("messages.welcome_title") }}
          </h2>
        </div>
        <div class="flex items-center gap-4">
          <!-- Language Switcher -->
          <LanguageSwitcher
            :current-locale="locale"
            :available-locales="availableLocales"
          />

          <Link
            v-if="$page.props.auth.user"
            :href="route('dashboard')"
            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
          >
            {{ t("messages.dashboard_button") }}
          </Link>
          <template v-else>
            <Link
              :href="route('login')"
              class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
            >
              {{ t("messages.login_button") }}
            </Link>
          </template>
        </div>
      </nav>
    </header>
    <div
      class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
    >
      <main
        class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
      >
        <div
          class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
        >
          <h1 class="mb-1 font-medium text-2xl">Fergana Kadastr</h1>
          <p class="mb-6 text-[#706f6c] dark:text-[#A1A09A]">
            Farg'ona viloyati kadastr tizimi. Hujjatlarni yuklash va boshqarish uchun
            tizimga kiring.
          </p>

          <div class="flex flex-col gap-4 sm:flex-row">
            <Link
              :href="route('login')"
              class="inline-block rounded-sm border border-[#19140035] bg-[#1b1b18] px-6 py-3 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
            >
              Tizimga kirish
            </Link>
          </div>
        </div>
        <div
          class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-blue-100 via-indigo-50 to-purple-100 lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:from-[#1e1b4b] dark:via-[#312e81] dark:to-[#1e1b4b]"
        >
          <!-- Fergana Kadastr Logo -->
          <div class="flex h-full items-center justify-center p-8">
            <div class="text-center">
              <!-- Main Logo -->
              <div class="mb-6">
                <div
                  class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 shadow-lg"
                >
                  <svg
                    class="h-10 w-10 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                  Fergana Kadastr
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  Farg'ona viloyati kadastr tizimi
                </p>
              </div>

              <!-- Features -->
              <div class="space-y-3 text-left">
                <div class="flex items-center space-x-3">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900"
                  >
                    <svg
                      class="h-4 w-4 text-green-600 dark:text-green-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                  </div>
                  <span class="text-sm text-gray-700 dark:text-gray-300"
                    >Hujjatlarni yuklash</span
                  >
                </div>
                <div class="flex items-center space-x-3">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900"
                  >
                    <svg
                      class="h-4 w-4 text-blue-600 dark:text-blue-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                      />
                    </svg>
                  </div>
                  <span class="text-sm text-gray-700 dark:text-gray-300"
                    >Boshqarish tizimi</span
                  >
                </div>
                <div class="flex items-center space-x-3">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900"
                  >
                    <svg
                      class="h-4 w-4 text-purple-600 dark:text-purple-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                  </div>
                  <span class="text-sm text-gray-700 dark:text-gray-300">Xavfsizlik</span>
                </div>
              </div>
            </div>
          </div>
          <div
            class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:overflow-hidden lg:rounded-t-none lg:rounded-r-lg dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
          />
        </div>
      </main>
    </div>
    <div class="hidden h-14.5 lg:block"></div>
  </div>
</template>
