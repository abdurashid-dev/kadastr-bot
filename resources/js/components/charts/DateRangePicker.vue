<template>
  <div class="flex items-center gap-4">
    <div class="flex items-center gap-2">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ t('messages.date_from') }}:
      </label>
      <input
        type="date"
        :value="startDate"
        @input="$emit('update:startDate', $event.target.value)"
        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      />
    </div>
    
    <div class="flex items-center gap-2">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ t('messages.date_to') }}:
      </label>
      <input
        type="date"
        :value="endDate"
        @input="$emit('update:endDate', $event.target.value)"
        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      />
    </div>
    
    <button
      @click="$emit('apply')"
      class="px-4 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
    >
      {{ t('messages.filter') }}
    </button>
    
    <button
      @click="$emit('clear')"
      class="px-4 py-1 text-sm bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
    >
      {{ t('messages.clear') }}
    </button>
  </div>
</template>

<script setup>
import { useTranslations } from '@/composables/useTranslations'

const { t } = useTranslations()

defineProps({
  startDate: {
    type: String,
    default: ''
  },
  endDate: {
    type: String,
    default: ''
  }
})

defineEmits(['update:startDate', 'update:endDate', 'apply', 'clear'])
</script>
