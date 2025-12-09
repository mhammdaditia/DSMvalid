<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Filter, RotateCcw, Calendar, User, Activity as ActivityIcon } from 'lucide-vue-next'

const props = defineProps({
  logs: Object,
  users: Array,
  filters: Object,
})

const filterForm = ref({
  user_id: props.filters?.user_id || '',
  action: props.filters?.action || '',
  date: props.filters?.date || '',
})

const applyFilter = () => {
  router.get(route('activity.logs'), filterForm.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilter = () => {
  filterForm.value = { user_id: '', action: '', date: '' }
  router.get(route('activity.logs'))
}

const getActionBadgeClass = (action) => {
  const classes = {
    login: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    logout: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    page_visit: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  }
  return classes[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}
</script>

<template>
  <DSMLayout>
    <template #header>
      Activity Logs
    </template>

    <div class="space-y-4 md:space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
            Activity Logs
          </h1>
          <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            Monitor semua aktivitas user di sistem
          </p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <ActivityIcon class="w-4 h-4 md:w-5 md:h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-3">
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Total Aktivitas</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ logs.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
              <User class="w-4 h-4 md:w-5 md:h-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div class="ml-3">
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Total Users</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">{{ users.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 md:p-4 border border-gray-200 dark:border-gray-700">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <Calendar class="w-4 h-4 md:w-5 md:h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="ml-3">
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">Hari Ini</p>
              <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                {{ logs.data.filter(log => new Date(log.created_at).toDateString() === new Date().toDateString()).length }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Section -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-4 md:p-6">
        <div class="flex items-center gap-2 mb-4">
          <Filter class="w-4 h-4 md:w-5 md:h-5 text-gray-600 dark:text-gray-400" />
          <h2 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white">Filter Logs</h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
          <!-- Filter User -->
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <User class="w-3 h-3 md:w-4 md:h-4 inline mr-1" />
              User
            </label>
            <select
              v-model="filterForm.user_id"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors"
            >
              <option value="">Semua User</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} (@{{ user.username }})
              </option>
            </select>
          </div>

          <!-- Filter Action -->
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <ActivityIcon class="w-3 h-3 md:w-4 md:h-4 inline mr-1" />
              Action
            </label>
            <select
              v-model="filterForm.action"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors"
            >
              <option value="">Semua Action</option>
              <option value="login">Login</option>
              <option value="logout">Logout</option>
              <option value="page_visit">Page Visit</option>
            </select>
          </div>

          <!-- Filter Date -->
          <div>
            <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <Calendar class="w-3 h-3 md:w-4 md:h-4 inline mr-1" />
              Tanggal
            </label>
            <input
              v-model="filterForm.date"
              type="date"
              class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors"
            />
          </div>

          <!-- Buttons -->
          <div class="flex sm:items-end gap-2">
            <button
              @click="applyFilter"
              class="flex-1 flex items-center justify-center gap-2 px-3 md:px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors text-sm"
            >
              <Filter class="w-4 h-4" />
              <span class="hidden sm:inline">Filter</span>
            </button>
            <button
              @click="resetFilter"
              class="px-3 md:px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-700 text-white rounded-lg font-medium transition-colors"
              title="Reset Filter"
            >
              <RotateCcw class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Table/Cards -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Table Header -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white">
            Daftar Aktivitas
          </h3>
          <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 mt-1">
            Menampilkan {{ logs.from }} - {{ logs.to }} dari {{ logs.total }} aktivitas
          </p>
        </div>

        <!-- Mobile Cards View -->
        <div class="block lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="log in logs.data"
            :key="log.id"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-teal-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                  {{ log.user_name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium text-gray-900 dark:text-gray-200">{{ log.user_name }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">@{{ log.username }}</div>
                </div>
              </div>
              <span
                :class="getActionBadgeClass(log.action)"
                class="px-2 py-1 text-xs font-semibold rounded-full"
              >
                {{ log.action }}
              </span>
            </div>

            <div class="space-y-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400">Waktu:</span>
                <span class="text-gray-900 dark:text-gray-200 font-medium">{{ formatDateTime(log.created_at) }}</span>
              </div>
              <div v-if="log.route" class="flex items-start justify-between">
                <span class="text-gray-500 dark:text-gray-400">Route:</span>
                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-right">{{ log.route }}</span>
              </div>
              <div v-if="log.ip_address" class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400">IP:</span>
                <span class="font-mono text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">{{ log.ip_address }}</span>
              </div>
              <div v-if="log.hostname" class="flex items-start justify-between">
                <span class="text-gray-500 dark:text-gray-400">Hostname:</span>
                <span class="font-mono text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2 py-1 rounded break-all text-right">{{ log.hostname }}</span>
              </div>
              <div v-if="log.method" class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400">Method:</span>
                <span 
                  :class="{
                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': log.method === 'GET',
                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': log.method === 'POST',
                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': log.method === 'PUT',
                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': log.method === 'DELETE',
                  }"
                  class="px-2 py-1 text-xs font-semibold rounded"
                >
                  {{ log.method }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
              <tr>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Waktu
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  User
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Action
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Route
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  IP Address
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Hostname
                </th>
                <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Method
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="log in logs.data"
                :key="log.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ formatDateTime(log.created_at).split(',')[0] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateTime(log.created_at).split(',')[1] }}</span>
                  </div>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-8 w-8">
                      <div class="h-8 w-8 rounded-full bg-teal-600 flex items-center justify-center text-white font-semibold text-xs">
                        {{ log.user_name.charAt(0).toUpperCase() }}
                      </div>
                    </div>
                    <div class="ml-3">
                      <div class="font-medium text-gray-900 dark:text-gray-200">{{ log.user_name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">@{{ log.username }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getActionBadgeClass(log.action)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-4 lg:px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                  <span v-if="log.route" class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                    {{ log.route }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500 italic">-</span>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm">
                  <span v-if="log.ip_address" class="font-mono text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                    {{ log.ip_address }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500 italic">-</span>
                </td>
                <td class="px-4 lg:px-6 py-4 text-sm">
                  <span v-if="log.hostname" class="font-mono text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 px-2 py-1 rounded">
                    {{ log.hostname }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500 italic">-</span>
                </td>
                <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm">
                  <span 
                    v-if="log.method"
                    :class="{
                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': log.method === 'GET',
                      'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': log.method === 'POST',
                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': log.method === 'PUT',
                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': log.method === 'DELETE',
                    }"
                    class="px-2 py-1 text-xs font-semibold rounded"
                  >
                    {{ log.method }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500 italic">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 md:gap-4">
            <div class="text-xs md:text-sm text-gray-700 dark:text-gray-300 text-center sm:text-left">
              Menampilkan <span class="font-semibold">{{ logs.from }}</span> sampai <span class="font-semibold">{{ logs.to }}</span> dari <span class="font-semibold">{{ logs.total }}</span> hasil
            </div>
            <div class="flex flex-wrap gap-1 justify-center">
              <a
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-2 md:px-3 py-1 md:py-2 text-xs md:text-sm rounded transition-colors',
                  link.active
                    ? 'bg-teal-600 text-white font-semibold'
                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600',
                  !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
                ]"
              ></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DSMLayout>
</template>

<style scoped>
/* Custom styling for better table appearance */
tbody tr:nth-child(even) {
  @apply bg-gray-50/50 dark:bg-gray-800/50;
}
</style>