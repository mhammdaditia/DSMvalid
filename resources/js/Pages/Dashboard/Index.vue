<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { TrendingUp, CheckCircle, XCircle, Clock, Calendar, AlertCircle } from 'lucide-vue-next'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Bar, Doughnut, Pie, Line } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  alarmStats: Array,
  validationStatusDistribution: Object,
  statusDistribution: Object,
  alarmByHour: Array,
  alarmByUnit: Array,
  stats: Object,
  filters: Object,
})

// ✅ Get errors and page data from Inertia
const page = usePage()
const errors = computed(() => page.props.errors || {})

// Filter states
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const shiftStartTime = ref(props.filters.shift_start_time ?? 6)
const shiftEndTime = ref(props.filters.shift_end_time ?? 18)

// Generate array jam 0-23
const hours = Array.from({ length: 24 }, (_, i) => i)

// ✅ Mobile detection
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

// ✅ Dark mode detection (reactive)
const isDark = ref(document.documentElement.classList.contains('dark'))

// Watch for dark mode changes
if (typeof window !== 'undefined') {
  const observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })
}

// ✅ Responsive font sizes
const getFontSizes = computed(() => {
  if (isMobile.value) {
    return {
      title: 8,
      label: 7,
      legend: 7,
      tick: 6,
    }
  }
  return {
    title: 12,
    label: 11,
    legend: 11,
    tick: 10,
  }
})

// ✅ Chart colors
const getChartColors = computed(() => ({
  textColor: isDark.value ? '#9CA3AF' : '#6B7280',
  gridColor: isDark.value ? '#374151' : '#E5E7EB',
}))

// Filter actions
const applyFilter = () => {
  const params = {}
  
  if (startDate.value) params.start_date = startDate.value
  if (endDate.value) params.end_date = endDate.value
  if (shiftStartTime.value !== null) params.shift_start_time = shiftStartTime.value
  if (shiftEndTime.value !== null) params.shift_end_time = shiftEndTime.value
  
  router.get(route('dashboard'), params, {
    preserveState: true,
    replace: true,
  })
}

const resetFilter = () => {
  startDate.value = ''
  endDate.value = ''
  shiftStartTime.value = 6
  shiftEndTime.value = 18
  router.get(route('dashboard'))
}

// Quick preset shift buttons
const setToday = () => {
  const today = new Date().toISOString().split('T')[0]
  startDate.value = today
  endDate.value = today
}

const setYesterday = () => {
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  const yesterdayStr = yesterday.toISOString().split('T')[0]
  startDate.value = yesterdayStr
  endDate.value = yesterdayStr
}

const setLast7Days = () => {
  const today = new Date()
  const last7Days = new Date()
  last7Days.setDate(today.getDate() - 7)
  
  endDate.value = today.toISOString().split('T')[0]
  startDate.value = last7Days.toISOString().split('T')[0]
}

// Check if filter is active
const hasShiftFilter = computed(() => {
  return shiftStartTime.value !== null && shiftEndTime.value !== null
})

const hasDateFilter = computed(() => {
  return startDate.value || endDate.value
})

// ==================== CHART DATA ====================

// Alarm Chart Data
const alarmChartData = computed(() => ({
  labels: props.alarmStats?.map(item => item.alarm) || [],
  datasets: [{
    label: 'Total Alarm',
    data: props.alarmStats?.map(item => item.total) || [],
    backgroundColor: [
      'rgba(239, 68, 68, 0.8)',
      'rgba(249, 115, 22, 0.8)',
      'rgba(234, 179, 8, 0.8)',
      'rgba(59, 130, 246, 0.8)',
      'rgba(168, 85, 247, 0.8)',
      'rgba(236, 72, 153, 0.8)',
      'rgba(20, 184, 166, 0.8)',
    ],
    borderColor: [
      'rgb(239, 68, 68)',
      'rgb(249, 115, 22)',
      'rgb(234, 179, 8)',
      'rgb(59, 130, 246)',
      'rgb(168, 85, 247)',
      'rgb(236, 72, 153)',
      'rgb(20, 184, 166)',
    ],
    borderWidth: 1,
  }],
}))

// Validation Status Chart Data
const validationStatusChartData = computed(() => ({
  labels: ['Already Validated', 'Pending Validated'],
  datasets: [{
    data: [
      props.validationStatusDistribution?.validated || 0,
      props.validationStatusDistribution?.pending || 0,
    ],
    backgroundColor: ['rgba(34, 197, 94, 0.8)', 'rgba(234, 179, 8, 0.8)'],
    borderColor: ['rgb(34, 197, 94)', 'rgb(234, 179, 8)'],
    borderWidth: 2,
  }],
}))

// Status Chart Data
const statusChartData = computed(() => ({
  labels: ['True', 'False'],
  datasets: [{
    data: [
      props.statusDistribution?.true || 0, 
      props.statusDistribution?.false || 0
    ],
    backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(239, 68, 68, 0.8)'],
    borderColor: ['rgb(59, 130, 246)', 'rgb(239, 68, 68)'],
    borderWidth: 2,
  }],
}))

// Alarm by Hour Chart Data
const alarmByHourChartData = computed(() => ({
  labels: props.alarmByHour?.map(item => `${String(item.hour).padStart(2, '0')}:00`) || [],
  datasets: [{
    label: 'Count of Alarm',
    data: props.alarmByHour?.map(item => item.count) || [],
    borderColor: 'rgb(168, 85, 247)',
    backgroundColor: 'rgba(168, 85, 247, 0.2)',
    tension: 0.4,
    fill: true,
    pointBackgroundColor: 'rgb(168, 85, 247)',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6,
  }],
}))

// Alarm by Unit Chart Data
const alarmByUnitChartData = computed(() => ({
  labels: props.alarmByUnit?.map(item => item.No_unit) || [],
  datasets: [{
    label: 'Count of Alarm',
    data: props.alarmByUnit?.map(item => item.count) || [],
    backgroundColor: 'rgba(59, 130, 246, 0.8)',
    borderColor: 'rgb(59, 130, 246)',
    borderWidth: 1,
  }],
}))

// ==================== CHART OPTIONS (RESPONSIVE) ====================

// Alarm Chart Options
const alarmChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { 
    legend: { 
      display: false 
    },
    tooltip: {
      titleFont: { size: getFontSizes.value.label },
      bodyFont: { size: getFontSizes.value.label },
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick }
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
    x: {
      ticks: {
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick }
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
  },
}))

// Donut/Pie Chart Options
const donutChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: isDark.value ? '#D1D5DB' : '#374151',
        padding: isMobile.value ? 5 : 15,
        font: { size: getFontSizes.value.legend }
      },
    },
    tooltip: {
      titleFont: { size: getFontSizes.value.label },
      bodyFont: { size: getFontSizes.value.label },
    }
  },
}))

// Alarm by Hour Chart Options
const alarmByHourChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      padding: isMobile.value ? 6 : 12,
      titleColor: '#fff',
      bodyColor: '#fff',
      titleFont: { size: getFontSizes.value.label },
      bodyFont: { size: getFontSizes.value.label },
      borderColor: 'rgb(168, 85, 247)',
      borderWidth: 1,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 10,
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick }
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
    x: {
      ticks: {
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick },
        maxRotation: isMobile.value ? 45 : 0,
        minRotation: isMobile.value ? 45 : 0,
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
  },
}))

// Alarm by Unit Chart Options
const alarmByUnitChartOptions = computed(() => ({
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      titleFont: { size: getFontSizes.value.label },
      bodyFont: { size: getFontSizes.value.label },
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: {
        stepSize: 20,
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick }
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
    y: {
      ticks: {
        color: getChartColors.value.textColor,
        font: { size: getFontSizes.value.tick }
      },
      grid: {
        color: getChartColors.value.gridColor,
      },
    },
  },
}))

// ==================== LIFECYCLE ====================

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>


<template>
  <Head title="Dashboard" />
  
  <DSMLayout>
    <div class="space-y-4 md:space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">Dashboard</h1>
        <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Overview data validasi DSM</p>
      </div>

      <!-- ✅ ERROR ALERT - Max Date Range -->
      <div v-if="errors.date_range" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 md:p-4 flex items-start gap-3 animate-shake">
        <AlertCircle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
        <div class="flex-1">
          <h4 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Rentang Tanggal Terlalu Panjang</h4>
          <p class="text-sm text-red-700 dark:text-red-400">{{ errors.date_range }}</p>
        </div>
        <button @click="router.get(route('dashboard'))" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
          <XCircle class="w-5 h-5" />
        </button>
      </div>

      <!-- ✅ Filter Section - COMPACT MENYAMPING (Mobile) -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-4 border border-transparent dark:border-gray-700">
        <!-- Quick Date Preset Buttons - COMPACT -->
        <div class="flex items-center gap-2 mb-3">
          <span class="text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Quick:</span>
          <button @click="setToday" class="flex-1 px-2 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors text-xs font-medium">
            Hari Ini
          </button>
          <button @click="setYesterday" class="flex-1 px-2 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors text-xs font-medium">
            Kemarin
          </button>
          <button @click="setLast7Days" class="flex-1 px-2 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors text-xs font-medium">
            7 Hari
          </button>
        </div>

        <!-- Date Range - COMPACT HORIZONTAL -->
        <div class="grid grid-cols-2 gap-2 mb-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              <Calendar class="w-3 h-3 inline mr-1" />
              Start
            </label>
            <input
              v-model="startDate"
              type="date"
              class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="{ 'border-red-500 dark:border-red-500': errors.start_date }"
            />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
              <Calendar class="w-3 h-3 inline mr-1" />
              End
            </label>
            <input
              v-model="endDate"
              type="date"
              class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="{ 'border-red-500 dark:border-red-500': errors.end_date }"
            />
          </div>
        </div>

        <!-- Shift Time Range - COMPACT HORIZONTAL -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
            <Clock class="w-3 h-3 inline mr-1" />
            Shift Time
          </label>
          
          <div class="grid grid-cols-3 gap-2">
            <!-- Dari Jam -->
            <select
              v-model.number="shiftStartTime"
              class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option :value="null">Dari</option>
              <option v-for="hour in hours" :key="hour" :value="hour">
                {{ String(hour).padStart(2, '0') }}:00
              </option>
            </select>
            
            <!-- Sampai Jam -->
            <select
              v-model.number="shiftEndTime"
              class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option :value="null">Sampai</option>
              <option v-for="hour in hours" :key="hour" :value="hour">
                {{ String(hour).padStart(2, '0') }}:00
              </option>
            </select>
            
            <!-- Buttons -->
            <div class="flex gap-1">
              <button
                @click="applyFilter"
                class="flex-1 px-2 py-1.5 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition-colors text-xs font-medium"
              >
                Apply
              </button>
              <button
                @click="resetFilter"
                class="px-2 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Active Filters Badge - COMPACT -->
        <div v-if="hasDateFilter || hasShiftFilter" class="mt-3 flex flex-wrap items-center gap-1">
          <span class="text-xs text-gray-600 dark:text-gray-400">Filter:</span>
          <span v-if="startDate && endDate" class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">
            <Calendar class="w-3 h-3" />
            {{ startDate.split('-')[2] }}/{{ startDate.split('-')[1] }} - {{ endDate.split('-')[2] }}/{{ endDate.split('-')[1] }}
          </span>
          <span v-if="hasShiftFilter" class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-xs font-medium">
            <Clock class="w-3 h-3" />
            {{ String(shiftStartTime).padStart(2, '0') }}:00 - {{ String(shiftEndTime).padStart(2, '0') }}:00
          </span>
        </div>
      </div>

      <!-- Stats Cards - 4 COLUMN -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Total Events</p>
              <p class="text-xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1 md:mt-2">{{ stats?.total_events || 0 }}</p>
            </div>
            <div class="hidden md:flex w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg items-center justify-center">
              <TrendingUp class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Total Validasi</p>
              <p class="text-xl md:text-3xl font-bold text-green-600 dark:text-green-400 mt-1 md:mt-2">{{ stats?.total_validations || 0 }}</p>
            </div>
            <div class="hidden md:flex w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg items-center justify-center">
              <CheckCircle class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Pending</p>
              <p class="text-xl md:text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1 md:mt-2">{{ stats?.pending_validations || 0 }}</p>
            </div>
            <div class="hidden md:flex w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg items-center justify-center">
              <Clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Validation Rate</p>
              <p class="text-xl md:text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 md:mt-2">{{ stats?.validation_rate || 0 }}%</p>
            </div>
            <div class="hidden md:flex w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg items-center justify-center">
              <CheckCircle class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- ✅ Charts Row 1 - 3 COLUMN dengan FONT KECIL -->
      <div class="grid grid-cols-3 gap-2 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-2 md:p-6 border border-transparent dark:border-gray-700">
          <h3 class="text-[10px] md:text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 md:mb-4 truncate">Validation Status</h3>
          <div class="h-32 md:h-64 flex items-center justify-center">
            <Doughnut 
              v-if="(validationStatusDistribution?.validated || 0) > 0 || (validationStatusDistribution?.pending || 0) > 0" 
              :data="validationStatusChartData" 
              :options="donutChartOptions" 
            />
            <p v-else class="text-gray-400 dark:text-gray-500 text-[8px] md:text-sm">Belum ada data</p>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-2 md:p-6 border border-transparent dark:border-gray-700">
          <h3 class="text-[10px] md:text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 md:mb-4 truncate">Alarm Statistics (Top 7)</h3>
          <div class="h-32 md:h-64">
            <Bar v-if="alarmStats?.length > 0" :data="alarmChartData" :options="alarmChartOptions" />
            <div v-else class="h-full flex items-center justify-center">
              <p class="text-gray-400 dark:text-gray-500 text-[8px] md:text-sm">Belum ada data</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-2 md:p-6 border border-transparent dark:border-gray-700">
          <h3 class="text-[10px] md:text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 md:mb-4 truncate">Status Distribution</h3>
          <div class="h-32 md:h-64 flex items-center justify-center">
            <Pie 
              v-if="(statusDistribution?.true || 0) > 0 || (statusDistribution?.false || 0) > 0" 
              :data="statusChartData" 
              :options="donutChartOptions" 
            />
            <p v-else class="text-gray-400 dark:text-gray-500 text-[8px] md:text-sm">Belum ada data</p>
          </div>
        </div>
      </div>

      <!-- ✅ Charts Row 2 - 2 COLUMN dengan FONT KECIL -->
      <div class="grid grid-cols-2 gap-2 md:gap-6">
        <!-- Count of Alarm by Jam -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-2 md:p-6 border border-transparent dark:border-gray-700">
          <h3 class="text-[10px] md:text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 md:mb-4 truncate">Alarm by Jam</h3>
          <div class="h-40 md:h-72">
            <Line v-if="alarmByHour?.length > 0" :data="alarmByHourChartData" :options="alarmByHourChartOptions" />
            <div v-else class="h-full flex items-center justify-center">
              <p class="text-gray-400 dark:text-gray-500 text-[8px] md:text-sm">Belum ada data</p>
            </div>
          </div>
        </div>

        <!-- Count of Alarm by No_Unit -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-2 md:p-6 border border-transparent dark:border-gray-700">
          <h3 class="text-[10px] md:text-lg font-bold text-gray-800 dark:text-gray-100 mb-1 md:mb-4 truncate">Alarm by Unit (Top 10)</h3>
          <div class="h-40 md:h-72">
            <Bar v-if="alarmByUnit?.length > 0" :data="alarmByUnitChartData" :options="alarmByUnitChartOptions" />
            <div v-else class="h-full flex items-center justify-center">
              <p class="text-gray-400 dark:text-gray-500 text-[8px] md:text-sm">Belum ada data</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DSMLayout>
</template>

<style scoped>
/* ✅ Animation for error shake */
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}

/* ✅ Smooth transitions */
* {
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}
</style>
