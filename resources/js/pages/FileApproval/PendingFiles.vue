<template>
  <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Pending Files for Review</h1>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
          {{ files.total }} files pending
        </span>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="file in files.data" :key="file.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center flex-1">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4 flex-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ file.original_filename }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ file.file_type }} • {{ formatFileSize(file.file_size) }}
                      </div>
                      <div class="text-xs text-gray-400 dark:text-gray-500">
                        Uploaded by {{ file.user.name }} on {{ formatDate(file.created_at) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-3 ml-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                  Pending Review
                </span>
                <div class="flex space-x-2">
                  <button
                    @click="approveFile(file.id)"
                    :disabled="processing === file.id"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg v-if="processing === file.id" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ processing === file.id ? 'Approving...' : 'Approve' }}
                  </button>
                  <button
                    @click="openRejectModal(file)"
                    :disabled="processing === file.id"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <!-- Empty state -->
        <div v-if="files.data.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No pending files</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All files have been reviewed.</p>
        </div>

        <!-- Pagination -->
        <div v-if="files.links && files.links.length > 3" class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <Link
              v-if="files.prev_page_url"
              :href="files.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              Previous
            </Link>
            <Link
              v-if="files.next_page_url"
              :href="files.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              Next
            </Link>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ files.from }}</span>
                to
                <span class="font-medium">{{ files.to }}</span>
                of
                <span class="font-medium">{{ files.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <Link
                  v-for="(link, index) in files.links"
                  :key="index"
                  :href="link.url"
                  :class="[
                    link.url === null ? 'cursor-not-allowed' : '',
                    index === 0 ? 'rounded-l-md' : '',
                    index === files.links.length - 1 ? 'rounded-r-md' : '',
                    link.active
                      ? 'z-10 bg-indigo-50 dark:bg-indigo-900 border-indigo-500 text-indigo-600 dark:text-indigo-300'
                      : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600'
                  ]"
                  class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                  v-html="link.label"
                />
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Reject File</h3>
          <div class="mb-4">
            <label for="reject-notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Reason for rejection (optional)
            </label>
            <textarea
              id="reject-notes"
              v-model="rejectNotes"
              rows="3"
              class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md"
              placeholder="Provide a reason for rejection..."
            ></textarea>
          </div>
          <div class="flex justify-end space-x-3">
            <button
              @click="closeRejectModal"
              class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500"
            >
              Cancel
            </button>
            <button
              @click="rejectFile"
              :disabled="processing === selectedFileId"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
            >
              {{ processing === selectedFileId ? 'Rejecting...' : 'Reject' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
  files: {
    type: Object,
    required: true
  }
})

const processing = ref(null)
const showRejectModal = ref(false)
const selectedFileId = ref(null)
const rejectNotes = ref('')

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const approveFile = async (fileId) => {
  processing.value = fileId
  
  try {
    await router.post(`/approval/files/${fileId}/approve-checker`)
    // Refresh the page to show updated data
    router.reload()
  } catch (error) {
    console.error('Error approving file:', error)
  } finally {
    processing.value = null
  }
}

const openRejectModal = (file) => {
  selectedFileId.value = file.id
  rejectNotes.value = ''
  showRejectModal.value = true
}

const closeRejectModal = () => {
  showRejectModal.value = false
  selectedFileId.value = null
  rejectNotes.value = ''
}

const rejectFile = async () => {
  if (!selectedFileId.value) return
  
  processing.value = selectedFileId.value
  
  try {
    await router.post(`/approval/files/${selectedFileId.value}/reject`, {
      notes: rejectNotes.value
    })
    closeRejectModal()
    // Refresh the page to show updated data
    router.reload()
  } catch (error) {
    console.error('Error rejecting file:', error)
  } finally {
    processing.value = null
  }
}
</script>
