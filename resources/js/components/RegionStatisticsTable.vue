<script setup>
import { computed, ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useTranslations } from "@/composables/useTranslations";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Download, FileSpreadsheet, FileText } from "lucide-vue-next";

const { t } = useTranslations();

const props = defineProps({
  regionStatistics: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

// Get current week start and end dates
const getCurrentWeekDates = () => {
  const now = new Date();
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
  const monday = new Date(now.setDate(diff));
  const sunday = new Date(monday);
  sunday.setDate(monday.getDate() + 6);
  
  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };
  
  return {
    start: formatDate(monday),
    end: formatDate(sunday),
  };
};

// Filter state
const startDate = ref(props.filters?.start_date || "");
const endDate = ref(props.filters?.end_date || "");
const sortBy = ref("accepted_objects");
const sortOrder = ref("desc");

// Set default to current week if no filters provided
onMounted(() => {
  if (!props.filters?.start_date && !props.filters?.end_date) {
    const weekDates = getCurrentWeekDates();
    startDate.value = weekDates.start;
    endDate.value = weekDates.end;
    applyFilters();
  }
});

// Client-side sorted region statistics
const sortedRegionStatistics = computed(() => {
  const stats = [...props.regionStatistics];

  return stats.sort((a, b) => {
    const aValue = a[sortBy.value] || 0;
    const bValue = b[sortBy.value] || 0;

    if (sortOrder.value === "asc") {
      return aValue - bValue;
    } else {
      return bValue - aValue;
    }
  });
});

// Calculate totals
const totals = computed(() => {
  return sortedRegionStatistics.value.reduce(
    (acc, stat) => {
      acc.accepted_objects += stat.accepted_objects || 0;
      acc.accepted_files += stat.accepted_files || 0;
      acc.rejected_files += stat.rejected_files || 0;
      return acc;
    },
    {
      accepted_objects: 0,
      accepted_files: 0,
      rejected_files: 0,
    }
  );
});

// Handle header click for sorting
const handleSort = (field) => {
  if (sortBy.value === field) {
    // Toggle order if same field
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    // New field, default to desc
    sortBy.value = field;
    sortOrder.value = "desc";
  }
};

// Get sort icon for header
const getSortIcon = (field) => {
  if (sortBy.value !== field) return "";
  return sortOrder.value === "asc" ? "↑" : "↓";
};

// Apply filters (only date filters now)
const applyFilters = () => {
  const params = {};
  if (startDate.value) params.start_date = startDate.value;
  if (endDate.value) params.end_date = endDate.value;

  router.get("/dashboard", params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

// Clear filters - reset to current week
const clearFilters = () => {
  const weekDates = getCurrentWeekDates();
  startDate.value = weekDates.start;
  endDate.value = weekDates.end;
  sortBy.value = "accepted_objects";
  sortOrder.value = "desc";
  applyFilters();
};

// Format date for display (dd/mm/yyyy)
const formatDateDisplay = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
};

// Export function
const exportData = (format) => {
  const params = new URLSearchParams();
  if (startDate.value) params.append('start_date', startDate.value);
  if (endDate.value) params.append('end_date', endDate.value);
  params.append('format', format);

  const url = `/dashboard/export-region-statistics?${params.toString()}`;
  window.open(url, '_blank');
};
</script>

<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-lg border border-sidebar-border/70 dark:border-sidebar-border p-4"
  >
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ t("messages.region_statistics") || "Tumanlar bo'yicha statistika" }}
      </h2>
      <div class="flex flex-col sm:flex-row gap-2">
        <Input
          v-model="startDate"
          type="date"
          :placeholder="t('messages.date_from') || 'Dan'"
          class="w-full sm:w-40 h-10 text-sm"
          @change="applyFilters"
        />
        <Input
          v-model="endDate"
          type="date"
          :placeholder="t('messages.date_to') || 'Gacha'"
          class="w-full sm:w-40 h-10 text-sm"
          @change="applyFilters"
        />
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="default"
              size="sm"
              class="h-10 px-4 text-sm"
            >
              <Download class="mr-2 h-4 w-4" />
              {{ t("messages.export") || "Export" }}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="exportData('csv')">
              <FileText class="mr-2 h-4 w-4" />
              {{ t("messages.export_csv") || "Export as CSV" }}
            </DropdownMenuItem>
            <DropdownMenuItem @click="exportData('excel')">
              <FileSpreadsheet class="mr-2 h-4 w-4" />
              {{ t("messages.export_excel") || "Export as Excel" }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
        <Button
          @click="clearFilters"
          variant="outline"
          size="sm"
          class="h-10 px-4 text-sm"
        >
          {{ t("messages.clear") || "Tozalash" }}
        </Button>
      </div>
    </div>
    <div class="overflow-x-auto rounded-md border border-border">
      <Table>
        <TableHeader>
          <TableRow class="bg-muted/50 hover:bg-muted/50">
            <TableHead class="w-12 text-center font-semibold py-2 text-xs">Tr</TableHead>
            <TableHead class="font-semibold py-2 text-xs">{{ t("messages.regions") || "Tumanlar" }}</TableHead>
            <TableHead
              class="text-center font-semibold py-2 text-xs cursor-pointer hover:bg-muted/70 transition-colors select-none"
              @click="handleSort('accepted_objects')"
            >
              <div class="flex items-center justify-center gap-1">
                <span>{{ t("messages.accepted_objects") || "Qabul qilingan objectlar" }}</span>
                <span v-if="sortBy === 'accepted_objects'" class="text-primary font-bold">
                  {{ getSortIcon('accepted_objects') }}
                </span>
              </div>
            </TableHead>
            <TableHead
              class="text-center font-semibold py-2 text-xs cursor-pointer hover:bg-muted/70 transition-colors select-none"
              @click="handleSort('accepted_files')"
            >
              <div class="flex items-center justify-center gap-1">
                <span>{{ t("messages.accepted_files") || "Qabul qilingan fayllar" }}</span>
                <span v-if="sortBy === 'accepted_files'" class="text-primary font-bold">
                  {{ getSortIcon('accepted_files') }}
                </span>
              </div>
            </TableHead>
            <TableHead
              class="text-center font-semibold py-2 text-xs cursor-pointer hover:bg-muted/70 transition-colors select-none"
              @click="handleSort('rejected_files')"
            >
              <div class="flex items-center justify-center gap-1">
                <span>{{ t("messages.rejected_files") || "Rad etilgan fayllar" }}</span>
                <span v-if="sortBy === 'rejected_files'" class="text-primary font-bold">
                  {{ getSortIcon('rejected_files') }}
                </span>
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="(stat, index) in sortedRegionStatistics"
            :key="stat.region"
            class="hover:bg-muted/50 transition-colors border-b border-border"
          >
            <TableCell class="text-center font-medium text-muted-foreground py-2 text-xs">
              {{ index + 1 }}
            </TableCell>
            <TableCell class="font-medium text-foreground py-2 text-sm">{{ stat.region }}</TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-semibold text-xs bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                {{ stat.accepted_objects || 0 }}
              </span>
            </TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-medium text-xs bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800">
                {{ stat.accepted_files || 0 }}
              </span>
            </TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-medium text-xs bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">
                {{ stat.rejected_files || 0 }}
              </span>
            </TableCell>
          </TableRow>
          <TableRow v-if="sortedRegionStatistics.length === 0">
            <TableCell colspan="5" class="text-center py-8 text-muted-foreground text-sm">
              {{ t("messages.no_data") || "Ma'lumot mavjud emas" }}
            </TableCell>
          </TableRow>
          <TableRow
            v-if="sortedRegionStatistics.length > 0"
            class="bg-muted/30 hover:bg-muted/30 border-t-2 border-primary/30 font-bold"
          >
            <TableCell class="text-center font-bold text-foreground py-2 text-xs"></TableCell>
            <TableCell class="font-bold text-foreground py-2 text-sm">
              {{ t("messages.total") || "Jami" }}
            </TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-bold text-xs bg-green-100 dark:bg-green-950/50 text-green-800 dark:text-green-300 border-2 border-green-300 dark:border-green-700">
                {{ totals.accepted_objects }}
              </span>
            </TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-bold text-xs bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300 border-2 border-green-300 dark:border-green-700">
                {{ totals.accepted_files }}
              </span>
            </TableCell>
            <TableCell class="text-center py-2">
              <span class="inline-flex items-center justify-center min-w-[50px] px-2 py-0.5 rounded font-bold text-xs bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300 border-2 border-red-300 dark:border-red-700">
                {{ totals.rejected_files }}
              </span>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>

