<template>
  <div class="relative" data-appearance-switcher>
    <button
      @click="toggleDropdown"
      class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
    >
      <component :is="currentIcon" class="w-3.5 h-3.5" />
      <span>{{ currentAppearanceName }}</span>
      <ChevronDownIcon class="w-3.5 h-3.5" />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-show="isOpen"
      class="absolute right-0 z-50 mt-1 w-36 bg-white border border-gray-200 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-600"
    >
      <div class="py-1">
        <button
          v-for="option in appearanceOptions"
          :key="option.value"
          @click="changeAppearance(option.value)"
          :class="[
            'w-full text-left px-3 py-1.5 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2',
            appearance === option.value
              ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
              : 'text-gray-700 dark:text-gray-300',
          ]"
        >
          <component :is="option.icon" class="w-3.5 h-3.5" />
          {{ option.label }}
        </button>
      </div>
    </div>

    <!-- Overlay to close dropdown when clicking outside -->
    <div v-if="isOpen" @click="closeDropdown" class="fixed inset-0 z-40"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAppearance } from "@/composables/useAppearance";
import { SunIcon, MoonIcon, ComputerDesktopIcon } from "@heroicons/vue/24/outline";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";

type Appearance = "light" | "dark" | "system";

const { appearance, updateAppearance } = useAppearance();
const isOpen = ref(false);

const appearanceOptions = [
  { value: "light" as Appearance, icon: SunIcon, label: "Light" },
  { value: "dark" as Appearance, icon: MoonIcon, label: "Dark" },
  { value: "system" as Appearance, icon: ComputerDesktopIcon, label: "System" },
];

const currentAppearanceOption = computed(() => {
  return appearanceOptions.find((opt) => opt.value === appearance.value) || appearanceOptions[2];
});

const currentIcon = computed(() => currentAppearanceOption.value.icon);
const currentAppearanceName = computed(() => currentAppearanceOption.value.label);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
  isOpen.value = false;
};

const changeAppearance = (value: Appearance) => {
  updateAppearance(value);
  closeDropdown();
};

// Close dropdown when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  const dropdown = target.closest("[data-appearance-switcher]");
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

