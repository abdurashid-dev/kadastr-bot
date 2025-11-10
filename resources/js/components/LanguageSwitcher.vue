<template>
  <div class="relative" data-language-switcher>
    <button
      @click="toggleDropdown"
      class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
    >
      <GlobeAltIcon class="w-3.5 h-3.5" />
      <span>{{ currentLanguageName }}</span>
      <ChevronDownIcon class="w-3.5 h-3.5" />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-show="isOpen"
      class="absolute right-0 z-50 mt-1 w-44 bg-white border border-gray-200 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-600"
    >
      <div class="py-1">
        <button
          v-for="(name, code) in availableLocales"
          :key="code"
          @click="changeLanguage(code)"
          :class="[
            'w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 dark:hover:bg-gray-700',
            currentLocale === code
              ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
              : 'text-gray-700 dark:text-gray-300',
          ]"
        >
          {{ name }}
        </button>
      </div>
    </div>

    <!-- Overlay to close dropdown when clicking outside -->
    <div v-if="isOpen" @click="closeDropdown" class="fixed inset-0 z-40"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { GlobeAltIcon, ChevronDownIcon } from "@heroicons/vue/24/outline";

interface Props {
  currentLocale: string;
  availableLocales: Record<string, string>;
}

const props = defineProps<Props>();

const isOpen = ref(false);

const currentLanguageName = computed(() => {
  return props.availableLocales[props.currentLocale] || "O'zbekcha (Lotin)";
});

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

const changeLanguage = (locale: string) => {
  if (locale !== props.currentLocale) {
    router.post('/language', { locale }, {
      preserveState: false,
      preserveScroll: false,
    });
  }
  closeDropdown();
};

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  const dropdown = target.closest("[data-language-switcher]");
  if (!dropdown) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>
