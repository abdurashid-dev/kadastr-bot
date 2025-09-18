<template>
  <div class="flex gap-2">
    <button
      v-for="period in periods"
      :key="period.value"
      @click="$emit('update:modelValue', period.value)"
      :disabled="disabled"
      :class="[
        'px-3 py-1 text-sm font-medium rounded-md transition-colors',
        disabled 
          ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'
          : modelValue === period.value
          ? 'bg-primary text-primary-foreground'
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
      ]"
    >
      {{ period.label }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useTranslations } from '@/composables/useTranslations'

const { t } = useTranslations()

const props = defineProps({
  modelValue: {
    type: String,
    default: 'month'
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

defineEmits(['update:modelValue'])

const periods = computed(() => [
  { value: 'day', label: t('messages.day') },
  { value: 'week', label: t('messages.week') },
  { value: 'month', label: t('messages.month') },
])
</script>
