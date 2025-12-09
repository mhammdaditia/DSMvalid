<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const notifications = ref([]);
const showDropdown = ref(false);
const unreadCount = ref(0);
const isLoading = ref(false);

// Load notifications from API
const loadNotifications = async () => {
    try {
        isLoading.value = true;
        const response = await window.axios.get('/api/notifications');
        
        if (response.data) {
            notifications.value = response.data.notifications;
            unreadCount.value = response.data.unread_count;
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

// Handle notification click
const handleNotificationClick = async (notification) => {
    try {
        // Mark as read
        await window.axios.post(`/api/notifications/${notification.id}/read`);

        // Update local state
        const index = notifications.value.findIndex(n => n.id === notification.id);
        if (index !== -1) {
            notifications.value[index].read_at = new Date().toISOString();
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);

        // Redirect ke halaman validasi
        router.get(`/events/${notification.data.event_id}/validate`);
        showDropdown.value = false;

    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

// Mark all as read
const markAllAsRead = async () => {
    try {
        await window.axios.post('/api/notifications/read-all');
        
        // Update local state
        notifications.value.forEach(n => {
            if (!n.read_at) {
                n.read_at = new Date().toISOString();
            }
        });
        unreadCount.value = 0;
        
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
};

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    
    return date.toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Show browser notification with sound
const showBrowserNotification = (data) => {
    if (Notification.permission === 'granted') {
        const notification = new Notification('🚨 Event Baru - DSMvalid', {
            body: `Unit ${data.no_unit}: ${data.alarm}`,
            icon: '/favicon.ico',
            tag: `event-${data.id}`,
            requireInteraction: true,
        });

        notification.onclick = () => {
            window.focus();
            router.get(`/events/${data.id}/validate`);
            notification.close();
        };
    }

    // Play notification sound (optional)
    try {
        const audio = new Audio('/sounds/notification.mp3');
        audio.play().catch(e => console.log('Audio play failed:', e));
    } catch (e) {
        console.log('Audio not available');
    }
};

let echoChannel;

onMounted(() => {
    // Load initial notifications
    loadNotifications();

    // Request browser notification permission
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Listen untuk real-time notifications via Reverb
    if (window.Echo) {
        console.log('Setting up Echo listener...');
        
        echoChannel = window.Echo.channel('dsm-events')
            .listen('.new-event', (data) => {
                console.log('✓ New event received via Reverb:', data);

                // Show browser notification
                showBrowserNotification(data);

                // Reload notifications list
                loadNotifications();

                // Show toast (optional - bisa pakai library toast)
                alert(`🚨 Event Baru!\n\nUnit: ${data.no_unit}\nAlarm: ${data.alarm}\n\nKlik OK untuk validasi.`);
            });
        
        console.log('✓ Echo listener registered on channel: dsm-events');
    } else {
        console.error('❌ Echo not initialized! Check bootstrap.js');
    }
});

onUnmounted(() => {
    if (echoChannel) {
        window.Echo.leave('dsm-events');
        console.log('Echo listener removed');
    }
});

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (showDropdown.value && !event.target.closest('.notification-container')) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative notification-container">
        <!-- Bell Button -->
        <button 
            @click="showDropdown = !showDropdown"
            class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            aria-label="Notifications"
            type="button"
        >
            <!-- Bell Icon -->
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            
            <!-- Unread Badge with Animation -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform scale-0"
                enter-to-class="transform scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform scale-100"
                leave-to-class="transform scale-0"
            >
                <span 
                    v-if="unreadCount > 0" 
                    class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-600 rounded-full shadow-lg animate-pulse"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Transition>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95 -translate-y-2"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div 
                v-show="showDropdown" 
                class="absolute right-0 z-50 mt-2 w-96 max-w-[calc(100vw-2rem)] bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Notifikasi
                        </h3>
                        <p v-if="unreadCount > 0" class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                            {{ unreadCount }} notifikasi baru
                        </p>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        @click="markAllAsRead"
                        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                    >
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="max-h-[480px] overflow-y-auto">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="p-8 text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Memuat...</p>
                    </div>

                    <!-- Empty State -->
                    <div 
                        v-else-if="notifications.length === 0" 
                        class="p-12 text-center text-gray-500 dark:text-gray-400"
                    >
                        <svg class="w-20 h-20 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="font-medium">Tidak ada notifikasi</p>
                        <p class="text-xs mt-1">Notifikasi akan muncul saat ada event baru</p>
                    </div>

                    <!-- Notification Items -->
                    <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        class="w-full p-4 text-left border-b border-gray-100 dark:border-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200"
                        :class="{ 
                            'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500': !notification.read_at,
                            'opacity-60': notification.read_at 
                        }"
                    >
                        <div class="flex items-start space-x-3">
                            <!-- Icon/Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg text-sm">
                                    {{ notification.data.no_unit?.substring(0, 2).toUpperCase() || '??' }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        Unit {{ notification.data.no_unit }}
                                    </p>
                                    <span v-if="!notification.read_at" class="ml-2 w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"></span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 mb-1">
                                    {{ notification.data.alarm }}
                                </p>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(notification.created_at) }}
                                    </p>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                        Klik untuk validasi →
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Footer -->
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <button 
                        @click="router.get('/notifications'); showDropdown = false"
                        class="w-full px-4 py-2.5 text-sm text-center text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-800 rounded-lg transition-colors font-semibold"
                    >
                        Lihat Semua Notifikasi
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
