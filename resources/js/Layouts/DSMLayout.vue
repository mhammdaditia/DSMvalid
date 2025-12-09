<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { 
  Home, 
  Bell, 
  CheckSquare, 
  Users, 
  ChevronLeft, 
  User, 
  LogOut, 
  Sun, 
  Moon, 
  Monitor, 
  X, 
  Trash2, 
  CheckCircle as CheckCircleIcon,
  Activity,
  FileText
} from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const csrfToken = computed(() => page.props.csrf_token)

// Theme Management
const { theme, setTheme } = useTheme()

// State untuk desktop sidebar collapse - LOAD FROM LOCALSTORAGE
const sidebarCollapsed = ref(false)

// State untuk mobile menu
const mobileMenuOpen = ref(false)

// State untuk user dropdown
const userDropdownOpen = ref(false)

// State untuk notification dropdown
const notificationDropdownOpen = ref(false)

// Notifications state
const notifications = ref([])
const unreadCount = ref(0)
const isLoadingNotifications = ref(false)

// Toast notifications state
const toastNotifications = ref([])
let toastId = 0

// Echo channel reference
let echoChannel = null

// ✅ Mobile detection
const isMobile = ref(false)

const checkMobile = () => {
  if (typeof window !== 'undefined') {
    isMobile.value = window.innerWidth < 640
  }
}

// Toggle sidebar collapse
const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value)
  }
}

// Toggle mobile menu
const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

// Toggle user dropdown
const toggleUserDropdown = () => {
  userDropdownOpen.value = !userDropdownOpen.value
  if (userDropdownOpen.value) {
    notificationDropdownOpen.value = false
  }
}

// Toggle notification dropdown
const toggleNotificationDropdown = async () => {
  notificationDropdownOpen.value = !notificationDropdownOpen.value
  if (notificationDropdownOpen.value) {
    userDropdownOpen.value = false
    await fetchNotifications()
  }
}

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
  const userDropdown = document.querySelector('.user-dropdown-container')
  const notificationDropdown = document.querySelector('.notification-dropdown-container')
  
  if (userDropdown && !userDropdown.contains(event.target)) {
    userDropdownOpen.value = false
  }
  
  if (notificationDropdown && !notificationDropdown.contains(event.target)) {
    notificationDropdownOpen.value = false
  }
}

// Fetch notifications from backend
const fetchNotifications = async () => {
  if (isLoadingNotifications.value) return
  
  isLoadingNotifications.value = true
  try {
    const response = await fetch('/DSMvalid/api/notifications', {
      headers: {
        'Accept': 'application/json'
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    notifications.value = data.notifications || []
    unreadCount.value = data.unread_count || 0
  } catch (error) {
    console.error('Error fetching notifications:', error)
    notifications.value = []
    unreadCount.value = 0
  } finally {
    isLoadingNotifications.value = false
  }
}

// Mark notification as read
const markAsRead = async (notificationId) => {
  try {
    const response = await fetch(`/DSMvalid/api/notifications/${notificationId}/read`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken.value
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification && !notification.read_at) {
      notification.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Error marking notification as read:', error)
  }
}

// Mark all as read
const markAllAsRead = async () => {
  try {
    const response = await fetch('/DSMvalid/api/notifications/mark-all-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken.value
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    notifications.value.forEach(n => {
      n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
  } catch (error) {
    console.error('❌ Error marking all as read:', error)
  }
}

// Delete notification
const deleteNotification = async (notificationId) => {
  try {
    const response = await fetch(`/DSMvalid/api/notifications/${notificationId}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken.value
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const index = notifications.value.findIndex(n => n.id === notificationId)
    if (index !== -1) {
      if (!notifications.value[index].read_at) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
      notifications.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Error deleting notification:', error)
  }
}

// Handle notification click
const handleNotificationClick = async (notification) => {
  if (!notification.read_at) {
    await markAsRead(notification.id)
  }
  
  const eventId = 
    notification.data?.ID ||
    notification.data?.event_id ||
    notification.data?.Alarm_id
  
  if (eventId) {
    notificationDropdownOpen.value = false
    if (typeof window !== 'undefined') {
      window.location.href = `/DSMvalid/event-management?validate=${eventId}`
    }
  } else {
    alert('Event ID tidak ditemukan dalam notifikasi ini.')
  }
}

// Format time ago
const formatTimeAgo = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  
  if (seconds < 60) return 'Baru saja'
  if (seconds < 3600) return `${Math.floor(seconds / 60)} menit yang lalu`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)} jam yang lalu`
  if (seconds < 604800) return `${Math.floor(seconds / 86400)} hari yang lalu`
  
  return date.toLocaleDateString('id-ID', { 
    day: 'numeric', 
    month: 'short', 
    year: 'numeric' 
  })
}

// Toast notification functions
const showToast = (notification) => {
  const id = ++toastId
  const toast = {
    id,
    title: notification.title || 'Notifikasi Baru',
    message: notification.message || notification.data?.message || notification.data?.Alarm,
    data: notification.data
  }
  
  toastNotifications.value.push(toast)
  
  setTimeout(() => {
    removeToast(id)
  }, 5000)
}

const removeToast = (id) => {
  const index = toastNotifications.value.findIndex(t => t.id === id)
  if (index !== -1) {
    toastNotifications.value.splice(index, 1)
  }
}

const handleToastClick = (toast) => {
  const eventId = 
    toast.data?.ID || 
    toast.data?.event_id || 
    toast.data?.Alarm_id
  
  if (eventId) {
    removeToast(toast.id)
    if (typeof window !== 'undefined') {
      window.location.href = `/DSMvalid/event-management?validate=${eventId}`
    }
  } else {
    router.visit(route('events.index'))
    removeToast(toast.id)
  }
}

// Setup Echo listener for real-time notifications
const setupEchoListener = () => {
  if (typeof window === 'undefined' || !window.Echo) {
    console.warn('Echo not initialized')
    return
  }
  
  echoChannel = window.Echo.channel('dsm-events')
    .subscribed(() => {
      console.log('✅ Successfully subscribed to dsm-events channel')
    })
    .listen('.new-event', (data) => {
      unreadCount.value++
      
      const newNotification = {
        id: Date.now(),
        type: 'App\\Notifications\\NewDSMEvent',
        data: data,
        read_at: null,
        created_at: new Date().toISOString()
      }
      
      notifications.value.unshift(newNotification)
      showToast(newNotification)
    })
    .error((error) => {
      console.error('❌ Error on dsm-events channel:', error)
    })
}

// Cleanup Echo listener
const cleanupEchoListener = () => {
  if (typeof window !== 'undefined' && echoChannel) {
    window.Echo.leave('dsm-events')
    echoChannel = null
  }
}

// Lifecycle hooks
onMounted(() => {
  // ✅ Load sidebar state from localStorage
  if (typeof localStorage !== 'undefined') {
    const savedState = localStorage.getItem('sidebarCollapsed')
    sidebarCollapsed.value = savedState === 'true'
  }
  
  // ✅ Safe window access
  if (typeof window !== 'undefined') {
    checkMobile()
    window.addEventListener('resize', checkMobile)
    document.addEventListener('click', handleClickOutside)
    fetchNotifications()
    setupEchoListener()
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', checkMobile)
    document.removeEventListener('click', handleClickOutside)
    cleanupEchoListener()
  }
})

// Watch for route changes to close mobile menu
watch(() => page.url, () => {
  closeMobileMenu()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
    <!-- Desktop Sidebar -->
    <aside
      :class="[
        'hidden lg:flex flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300',
        sidebarCollapsed ? 'w-16' : 'w-64'
      ]"
      class="fixed inset-y-0 left-0 z-30"
    >
      <!-- Logo Header -->
      <div class="h-14 flex items-center justify-center px-4 border-b border-gray-200 dark:border-gray-700">
        <button
          @click="toggleSidebar"
          class="hover:opacity-80 transition-opacity"
          :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
        >
          <img 
            src="/images/kppdsm.svg" 
            alt="Logo" 
            :class="sidebarCollapsed ? 'h-8' : 'h-10'"
            class="w-auto transition-all duration-300"
          />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <!-- Dashboard -->
        <Link 
          :href="route('dashboard')" 
          class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative group"
          :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('dashboard'), 'justify-center': sidebarCollapsed }"
          :title="sidebarCollapsed ? 'Dashboard' : ''"
        >
          <Home class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('dashboard') }" />
          <span 
            v-show="!sidebarCollapsed"
            class="font-medium whitespace-nowrap"
          >
            Dashboard
          </span>
          <div 
            v-if="sidebarCollapsed"
            class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
          >
            Dashboard
          </div>
        </Link>

        <!-- Events -->
        <Link 
          :href="route('events.index')" 
          class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1 relative group"
          :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('events.*'), 'justify-center': sidebarCollapsed }"
          :title="sidebarCollapsed ? 'Events' : ''"
        >
          <Bell class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('events.*') }" />
          <span 
            v-show="!sidebarCollapsed"
            class="font-medium whitespace-nowrap"
          >
            Events
          </span>
          <div 
            v-if="sidebarCollapsed"
            class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
          >
            Events
          </div>
        </Link>

        <!-- Validations -->
        <Link 
          :href="route('validations.index')" 
          class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1 relative group"
          :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('validations.*'), 'justify-center': sidebarCollapsed }"
          :title="sidebarCollapsed ? 'Validations' : ''"
        >
          <CheckSquare class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('validations.*') }" />
          <span 
            v-show="!sidebarCollapsed"
            class="font-medium whitespace-nowrap"
          >
            Validations
          </span>
          <div 
            v-if="sidebarCollapsed"
            class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
          >
            Validations
          </div>
        </Link>

        <!-- Report DSM -->
        <Link 
          :href="route('reports.dsm')" 
          class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1 relative group"
          :class="{
            'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('reports.dsm'),
            'justify-center': sidebarCollapsed
          }"
          :title="sidebarCollapsed ? 'Report DSM' : ''"
        >
          <FileText class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('reports.dsm') }" />
          <span 
            v-show="!sidebarCollapsed"
            class="font-medium whitespace-nowrap"
          >
            Report DSM
          </span>
          <div 
            v-if="sidebarCollapsed"
            class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
          >
            Report DSM
          </div>
        </Link>

        <!-- Admin/Super Admin Only -->
        <template v-if="user?.role === 'admin' || user?.role === 'super_admin'">
          <div 
            v-show="!sidebarCollapsed"
            class="mt-6 mb-2 px-3"
          >
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Management</p>
          </div>
          
          <div 
            v-if="sidebarCollapsed"
            class="my-4 border-t border-gray-200 dark:border-gray-700 mx-2"
          ></div>

          <Link 
            :href="route('users.index')"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative group"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('users.*'), 'justify-center': sidebarCollapsed }"
            :title="sidebarCollapsed ? 'Users' : ''"
          >
            <Users class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('users.*') }" />
            <span 
              v-show="!sidebarCollapsed"
              class="font-medium whitespace-nowrap"
            >
              Users
            </span>
            <div 
              v-if="sidebarCollapsed"
              class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
            >
              Users
            </div>
          </Link>

          <!-- Activity Logs (Super Admin Only) -->
          <Link 
            v-if="user?.role === 'super_admin'"
            :href="route('activity.logs')"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1 relative group"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('activity.logs'), 'justify-center': sidebarCollapsed }"
            :title="sidebarCollapsed ? 'Activity Logs' : ''"
          >
            <Activity class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('activity.logs') }" />
            <span 
              v-show="!sidebarCollapsed"
              class="font-medium whitespace-nowrap"
            >
              Activity Logs
            </span>
            <div 
              v-if="sidebarCollapsed"
              class="absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50"
            >
              Activity Logs
            </div>
          </Link>
        </template>
      </nav>
    </aside>

    <!-- Mobile Sidebar Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileMenuOpen"
        @click="closeMobileMenu"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
      ></div>
    </Transition>

    <!-- Mobile Sidebar -->
    <Transition
      enter-active-class="transition-transform duration-300"
      enter-from-class="-translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-300"
      leave-from-class="translate-x-0"
      leave-to-class="-translate-x-full"
    >
      <aside
        v-if="mobileMenuOpen"
        class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 z-50 lg:hidden flex flex-col"
      >
        <!-- Mobile Logo Header -->
        <div class="h-14 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700">
          <img 
            src="/images/kppdsm.svg" 
            alt="Logo" 
            class="h-10 w-auto"
          />
          <button
            @click="closeMobileMenu"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 transition-colors"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Mobile Navigation -->
        <nav class="flex-1 px-3 py-4 overflow-y-auto">
          <Link 
            :href="route('dashboard')"
            @click="closeMobileMenu"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('dashboard') }"
          >
            <Home class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('dashboard') }" />
            <span class="font-medium">Dashboard</span>
          </Link>

          <Link 
            :href="route('events.index')"
            @click="closeMobileMenu"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('events.*') }"
          >
            <Bell class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('events.*') }" />
            <span class="font-medium">Events</span>
          </Link>

          <Link 
            :href="route('validations.index')"
            @click="closeMobileMenu"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('validations.*') }"
          >
            <CheckSquare class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('validations.*') }" />
            <span class="font-medium">Validations</span>
          </Link>

          <Link 
            :href="route('reports.dsm')"
            @click="closeMobileMenu"
            class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1"
            :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('reports.dsm') }"
          >
            <FileText class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('reports.dsm') }" />
            <span class="font-medium">Report DSM</span>
          </Link>

          <template v-if="user?.role === 'admin' || user?.role === 'super_admin'">
            <div class="mt-6 mb-2 px-3">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Management</p>
            </div>

            <Link 
              :href="route('users.index')"
              @click="closeMobileMenu"
              class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('users.*') }"
            >
              <Users class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('users.*') }" />
              <span class="font-medium">Users</span>
            </Link>

            <Link 
              v-if="user?.role === 'super_admin'"
              :href="route('activity.logs')"
              @click="closeMobileMenu"
              class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mt-1"
              :class="{ 'bg-gray-50 dark:bg-gray-700 text-teal-600 dark:text-teal-400': route().current('activity.logs') }"
            >
              <Activity class="w-5 h-5 flex-shrink-0" :class="{ 'text-teal-600 dark:text-teal-400': route().current('activity.logs') }" />
              <span class="font-medium">Activity Logs</span>
            </Link>
          </template>
        </nav>
      </aside>
    </Transition>

    <!-- Main Content -->
    <div 
      :class="[
        'flex-1 flex flex-col transition-all duration-300',
        sidebarCollapsed ? 'lg:ml-16' : 'lg:ml-64'
      ]"
    >
      <!-- Top Header -->
      <header class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 sticky top-0 z-20">
        <!-- Mobile Logo -->
        <div class="lg:hidden">
          <button
            @click="toggleMobileMenu"
            class="hover:opacity-80 transition-opacity"
          >
            <img 
              src="/images/kppdsm.svg" 
              alt="Logo" 
              class="h-8 w-auto"
            />
          </button>
        </div>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-2 ml-auto">
          <!-- Theme Switcher -->
          <div class="relative">
            <button
              @click="setTheme(theme === 'dark' ? 'light' : 'dark')"
              class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              :title="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
              <Sun v-if="theme === 'dark'" class="w-5 h-5" />
              <Moon v-else class="w-5 h-5" />
            </button>
          </div>

          <!-- Notifications Dropdown -->
          <div class="relative notification-dropdown-container">
            <button
              @click="toggleNotificationDropdown"
              class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <Bell class="w-5 h-5" />
              <span 
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <!-- Notification Dropdown Panel -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div
                v-if="notificationDropdownOpen"
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-[9999]"
              >
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                  <h3 class="font-semibold text-gray-900 dark:text-white">Notifikasi</h3>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-xs text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 font-medium"
                  >
                    Tandai Semua Dibaca
                  </button>
                </div>

                <div class="max-h-96 overflow-y-auto">
                  <div v-if="isLoadingNotifications" class="p-4 text-center text-gray-500 dark:text-gray-400">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto"></div>
                  </div>

                  <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <Bell class="w-12 h-12 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Tidak ada notifikasi</p>
                  </div>

                  <div v-else>
                    <div
                      v-for="notification in notifications"
                      :key="notification.id"
                      class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 group relative"
                      :class="{ 'bg-teal-50/50 dark:bg-teal-900/10': !notification.read_at }"
                      @click="handleNotificationClick(notification)"
                    >
                      <div class="flex gap-3">
                        <div class="flex-shrink-0 mt-1">
                          <CheckCircleIcon class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ notification.data?.No_Unit ? `Unit ${notification.data.No_Unit}` : 'Event Baru' }}
                          </p>
                          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ notification.data?.Alarm || notification.data?.message || 'No details' }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ formatTimeAgo(notification.created_at) }}
                          </p>
                        </div>
                        <button
                          @click.stop="deleteNotification(notification.id)"
                          class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                        >
                          <Trash2 class="w-4 h-4 text-gray-400 hover:text-red-600 dark:hover:text-red-400" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="p-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                  <button 
                    @click="router.get(route('notifications.index')); notificationDropdownOpen = false"
                    class="w-full px-3 py-1.5 text-xs text-center text-teal-600 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-gray-800 rounded transition-colors font-semibold"
                  >
                    Lihat Semua Notifikasi
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- User Dropdown -->
          <div class="relative user-dropdown-container">
            <button
              @click="toggleUserDropdown"
              class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <div class="w-8 h-8 rounded-full bg-teal-600 flex items-center justify-center text-white font-semibold text-sm">
                {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
              <div class="hidden md:block text-left">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ user?.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ user?.role }}</p>
              </div>
            </button>

            <!-- User Dropdown Panel -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div
                v-if="userDropdownOpen"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden z-[9999]"
              >
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user?.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
                </div>

                <div class="py-1">
                  <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                  >
                    <LogOut class="w-4 h-4" />
                    Logout
                  </Link>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed bottom-4 right-4 z-[9999] space-y-2">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-x-full opacity-0"
        enter-to-class="transform translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-x-0 opacity-100"
        leave-to-class="transform translate-x-full opacity-0"
      >
        <div
          v-for="toast in toastNotifications"
          :key="toast.id"
          @click="handleToastClick(toast)"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 w-80 cursor-pointer hover:shadow-xl transition-shadow"
        >
          <div class="flex gap-3">
            <div class="flex-shrink-0">
              <CheckCircleIcon class="w-6 h-6 text-teal-600 dark:text-teal-400" />
            </div>
            <div class="flex-1">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ toast.title }}
              </h4>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ toast.message }}
              </p>
            </div>
            <button
              @click.stop="removeToast(toast.id)"
              class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar - Light Mode */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Custom scrollbar - Dark Mode */
:global(.dark) ::-webkit-scrollbar-track {
  background: #1f2937;
}

:global(.dark) ::-webkit-scrollbar-thumb {
  background: #4b5563;
  border-radius: 10px;
}

:global(.dark) ::-webkit-scrollbar-thumb:hover {
  background: #6b7280;
}

/* Firefox scrollbar */
* {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Firefox scrollbar - Dark Mode */
:global(.dark) * {
  scrollbar-color: #4b5563 #1f2937;
}
</style>
