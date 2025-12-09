<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Bell, CheckCircle, Trash2, XCircle, Search, X, Trash } from 'lucide-vue-next'

const notifications = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const totalNotifications = ref(0)
const unreadCount = ref(0)
const errorMessage = ref('')

// Selection state
const selectedIds = ref([])
const selectAll = ref(false)

// Filters
const filterStatus = ref('all')
const searchQuery = ref('')

// Debounce search
let searchTimeout = null

// Load notifications
const loadNotifications = async (page = 1) => {
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    const response = await window.axios.get('/DSMvalid/api/notifications', {
      params: {
        page: page,
        filter: filterStatus.value,
        search: searchQuery.value || undefined
      }
    })
    
    if (response.data) {
      notifications.value = response.data.notifications || []
      currentPage.value = response.data.current_page || 1
      totalPages.value = response.data.last_page || 1
      totalNotifications.value = response.data.total || 0
      unreadCount.value = response.data.unread_count || 0
      
      // Reset selection after reload
      selectedIds.value = []
      selectAll.value = false
    }
  } catch (error) {
    console.error('Failed to load notifications:', error)
    errorMessage.value = error.response?.data?.message || 'Gagal memuat notifikasi'
  } finally {
    isLoading.value = false
  }
}

// Watch search query with debounce
watch(searchQuery, (newValue) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    loadNotifications(1)
  }, 500)
})

// Toggle select all
const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedIds.value = filteredNotifications.value.map(n => n.id)
  } else {
    selectedIds.value = []
  }
}

// Watch selected IDs to update select all checkbox
watch(selectedIds, (newValue) => {
  selectAll.value = newValue.length > 0 && newValue.length === filteredNotifications.value.length
}, { deep: true })

// Mark as read
const markAsRead = async (notificationId) => {
  try {
    const response = await window.axios.post(`/DSMvalid/api/notifications/${notificationId}/read`)
    
    if (response.data.success) {
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.read_at = new Date().toISOString()
      }
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

// Mark all as read
const markAllAsRead = async () => {
  if (!confirm('Tandai semua notifikasi sebagai dibaca?')) return
  
  try {
    const response = await window.axios.post('/DSMvalid/api/notifications/mark-all-read')
    
    if (response.data.success) {
      notifications.value.forEach(n => {
        if (!n.read_at) {
          n.read_at = new Date().toISOString()
        }
      })
      unreadCount.value = 0
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error)
    alert('❌ Gagal menandai semua notifikasi sebagai dibaca')
  }
}

// Delete notification
const deleteNotification = async (notificationId) => {
  if (!confirm('Hapus notifikasi ini?')) return
  
  try {
    const response = await window.axios.delete(`/DSMvalid/api/notifications/${notificationId}`)
    
    if (response.data.success) {
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index !== -1) {
        const notification = notifications.value[index]
        if (!notification.read_at) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
        totalNotifications.value--
      }
    }
  } catch (error) {
    console.error('Failed to delete notification:', error)
    alert('❌ Gagal menghapus notifikasi')
  }
}

// Bulk delete selected
const bulkDelete = async () => {
  if (selectedIds.value.length === 0) {
    alert('Pilih notifikasi yang ingin dihapus')
    return
  }
  
  if (!confirm(`Hapus ${selectedIds.value.length} notifikasi yang dipilih?`)) return
  
  try {
    const response = await window.axios.post('/DSMvalid/api/notifications/bulk-delete', {
      ids: selectedIds.value
    })
    
    if (response.data.success) {
      alert(`✅ ${selectedIds.value.length} notifikasi berhasil dihapus`)
      loadNotifications(currentPage.value)
    }
  } catch (error) {
    console.error('Failed to bulk delete:', error)
    alert('❌ Gagal menghapus notifikasi')
  }
}

// Delete all notifications
const deleteAll = async () => {
  if (totalNotifications.value === 0) {
    alert('Tidak ada notifikasi untuk dihapus')
    return
  }
  
  if (!confirm(`Hapus SEMUA ${totalNotifications.value} notifikasi?`)) return
  
  try {
    const response = await window.axios.delete('/DSMvalid/api/notifications/delete-all')
    
    if (response.data.success) {
      alert(`✅ Semua notifikasi berhasil dihapus`)
      loadNotifications(1)
    }
  } catch (error) {
    console.error('Failed to delete all:', error)
    alert('❌ Gagal menghapus semua notifikasi')
  }
}

// Handle notification click
const handleNotificationClick = async (notification) => {
  console.log('=== NOTIFICATION CLICK ===')
  console.log('Full notification:', notification)
  console.log('notification.data:', notification.data)
  
  // Mark as read first
  if (!notification.read_at) {
    await markAsRead(notification.id)
  }
  
  const eventId = 
    notification.data?.event_id || 
    notification.data?.ID || 
    notification.data?.Alarm_id
  
  console.log('📍 Event ID extracted:', eventId)
  
  if (eventId) {
    const targetUrl = `/DSMvalid/event-management?validate=${eventId}`
    console.log('🚀 Redirecting to:', targetUrl)
    window.location.href = targetUrl
  } else {
    console.error('❌ Event ID not found!')
    alert('Event ID tidak ditemukan dalam notifikasi ini.')
  }
}

// Change filter
const changeFilter = (status) => {
  filterStatus.value = status
  loadNotifications(1)
}

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
}

// Pagination
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    loadNotifications(page)
  }
}

// Format date
const formatDate = (dateString) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateString
  }
}

// Computed
const filteredNotifications = computed(() => {
  if (filterStatus.value === 'unread') {
    return notifications.value.filter(n => !n.read_at)
  } else if (filterStatus.value === 'read') {
    return notifications.value.filter(n => n.read_at)
  }
  return notifications.value
})

onMounted(() => {
  loadNotifications()
})
</script>

<template>
  <Head title="Notifikasi" />
  
  <DSMLayout>
    <div class="max-w-5xl mx-auto space-y-4 md:space-y-6">
      <!-- Error Message -->
      <div v-if="errorMessage" class="p-3 md:p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-700 dark:text-red-400 text-sm">
          ⚠ {{ errorMessage }}
        </p>
      </div>

      <!-- Header -->
      <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <div>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
              Notifikasi
            </h1>
            <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
              Kelola semua notifikasi event Anda
            </p>
          </div>
          
          <!-- Action buttons -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50"
              :disabled="isLoading"
            >
              <CheckCircle class="w-4 h-4 inline mr-1 md:mr-2" />
              <span class="hidden sm:inline">Baca Semua</span>
              <span class="sm:hidden">Baca</span>
            </button>
            
            <button
              v-if="selectedIds.length > 0"
              @click="bulkDelete"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
            >
              <Trash2 class="w-4 h-4 inline mr-1" />
              <span class="hidden sm:inline">Hapus Terpilih ({{ selectedIds.length }})</span>
              <span class="sm:hidden">({{ selectedIds.length }})</span>
            </button>
            
            <button
              v-if="totalNotifications > 0"
              @click="deleteAll"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium text-white bg-red-700 hover:bg-red-800 rounded-lg transition-colors"
            >
              <Trash class="w-4 h-4 inline mr-1" />
              <span class="hidden sm:inline">Hapus Semua</span>
              <span class="sm:hidden">Semua</span>
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
          <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
              <div class="p-2 bg-teal-100 dark:bg-teal-900/30 rounded-lg">
                <Bell class="w-4 h-4 md:w-5 md:h-5 text-teal-600 dark:text-teal-400" />
              </div>
              <div class="ml-3">
                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Total</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ totalNotifications }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
              <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                <CheckCircle class="w-4 h-4 md:w-5 md:h-5 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div class="ml-3">
                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Dibaca</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ totalNotifications - unreadCount }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
              <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                <XCircle class="w-4 h-4 md:w-5 md:h-5 text-orange-600 dark:text-orange-400" />
              </div>
              <div class="ml-3">
                <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Belum Dibaca</p>
                <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ unreadCount }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Search & Filters -->
        <div class="space-y-3 md:space-y-4">
          <!-- Search Bar -->
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search class="h-4 w-4 md:h-5 md:w-5 text-gray-400" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari notifikasi..."
              class="block w-full pl-9 md:pl-10 pr-10 py-2 md:py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <X class="h-4 w-4 md:h-5 md:w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
            </button>
          </div>

          <!-- Filter Buttons -->
          <div class="flex items-center gap-2 overflow-x-auto pb-2 sm:pb-0">
            <button
              @click="changeFilter('all')"
              :class="filterStatus === 'all' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 transition-colors hover:bg-emerald-50 dark:hover:bg-gray-700 whitespace-nowrap"
            >
              Semua
            </button>
            <button
              @click="changeFilter('unread')"
              :class="filterStatus === 'unread' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 transition-colors hover:bg-emerald-50 dark:hover:bg-gray-700 whitespace-nowrap"
            >
              Belum Dibaca ({{ unreadCount }})
            </button>
            <button
              @click="changeFilter('read')"
              :class="filterStatus === 'read' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
              class="px-3 md:px-4 py-2 text-xs md:text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 transition-colors hover:bg-emerald-50 dark:hover:bg-gray-700 whitespace-nowrap"
            >
              Dibaca
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Loading State -->
        <div v-if="isLoading" class="p-8 md:p-12 text-center">
          <div class="animate-spin rounded-full h-10 w-10 md:h-12 md:w-12 border-b-2 border-emerald-600 mx-auto"></div>
          <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mt-4">Memuat notifikasi...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredNotifications.length === 0" class="p-8 md:p-12 text-center">
          <Bell class="w-16 h-16 md:w-20 md:h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <p class="text-base md:text-lg font-medium text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'Tidak ada notifikasi yang cocok' : 'Tidak ada notifikasi' }}
          </p>
          <p class="text-xs md:text-sm text-gray-400 dark:text-gray-500 mt-2">
            {{ searchQuery ? 'Coba kata kunci lain' : 'Notifikasi akan muncul saat ada event baru' }}
          </p>
        </div>

        <!-- Notification Items -->
        <div v-else>
          <!-- Select All Header -->
          <div class="p-3 md:p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="selectAll"
                @change="toggleSelectAll"
                class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500"
              />
              <span class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300">
                Pilih Semua ({{ filteredNotifications.length }})
              </span>
            </label>
          </div>
          
          <!-- Notification Items List -->
          <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
              v-for="notification in filteredNotifications"
              :key="notification.id"
              class="p-3 md:p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
              :class="{ 'bg-teal-50 dark:bg-teal-900/10 border-l-4 border-l-teal-500': !notification.read_at }"
            >
              <div class="flex items-start gap-2 md:gap-4">
                <!-- Checkbox -->
                <div class="flex-shrink-0 pt-1">
                  <input
                    type="checkbox"
                    :value="notification.id"
                    v-model="selectedIds"
                    class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500"
                    @click.stop
                  />
                </div>
                
                <!-- Icon -->
                <div class="flex-shrink-0 cursor-pointer" @click="handleNotificationClick(notification)">
                  <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg text-xs md:text-sm">
                    {{ notification.data?.No_Unit?.substring(0, 2).toUpperCase() || '??' }}
                  </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 cursor-pointer" @click="handleNotificationClick(notification)">
                  <div class="flex items-start justify-between mb-1 md:mb-2">
                    <h3 class="text-xs md:text-sm font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                      Unit {{ notification.data?.No_Unit || 'Unknown' }}
                    </h3>
                    <span v-if="!notification.read_at" class="ml-2 w-2 h-2 bg-teal-600 rounded-full flex-shrink-0 animate-pulse"></span>
                  </div>
                  <p class="text-xs md:text-sm text-gray-700 dark:text-gray-300 mb-1 md:mb-2">
                    {{ notification.data?.Alarm || notification.data?.message || 'No details' }}
                  </p>
                  <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ formatDate(notification.created_at) }}</span>
                    <span v-if="notification.data?.Location" class="truncate">📍 {{ notification.data.Location }}</span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center gap-1 sm:gap-2 flex-shrink-0" @click.stop>
                  <button
                    v-if="!notification.read_at"
                    @click="markAsRead(notification.id)"
                    class="p-1.5 md:p-2 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors"
                    title="Tandai dibaca"
                  >
                    <CheckCircle class="w-4 h-4 md:w-5 md:h-5" />
                  </button>
                  <button
                    @click="deleteNotification(notification.id)"
                    class="p-1.5 md:p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="Hapus"
                  >
                    <Trash2 class="w-4 h-4 md:w-5 md:h-5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-3 md:px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs md:text-sm text-gray-600 dark:text-gray-400 text-center sm:text-left">
              Halaman {{ currentPage }} dari {{ totalPages }} ({{ totalNotifications }} total)
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="px-3 py-1 text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg transition-colors"
              >
                Previous
              </button>
              <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="px-3 py-1 text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DSMLayout>
</template>
