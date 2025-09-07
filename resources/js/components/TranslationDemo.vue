<template>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">{{ t('messages.welcome') }}</h3>
        
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100">{{ t('messages.dashboard') }}</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ t('messages.statistics') }}</p>
                </div>
                
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="font-medium text-green-900 dark:text-green-100">{{ t('messages.users') }}</h4>
                    <p class="text-sm text-green-700 dark:text-green-300">{{ t('messages.user_management') }}</p>
                </div>
                
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <h4 class="font-medium text-purple-900 dark:text-purple-100">{{ t('messages.files') }}</h4>
                    <p class="text-sm text-purple-700 dark:text-purple-300">{{ t('messages.file_management') }}</p>
                </div>
                
                <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <h4 class="font-medium text-orange-900 dark:text-orange-100">{{ t('messages.settings') }}</h4>
                    <p class="text-sm text-orange-700 dark:text-orange-300">{{ t('messages.info') }}</p>
                </div>
            </div>
            
            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h5 class="font-medium mb-2">{{ t('messages.current_language') }}</h5>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('messages.selected_locale') }}: <strong>{{ currentLocale }}</strong>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ t('messages.available_languages') }}: 
                    <span v-for="(name, code) in availableLocales" :key="code" class="inline-block mr-2">
                        <span :class="{ 'font-bold text-blue-600 dark:text-blue-400': code === currentLocale }">
                            {{ name }}
                        </span>
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations'

const { t, currentLocale, availableLocales } = useTranslations()
</script>
