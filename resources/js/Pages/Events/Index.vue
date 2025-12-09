<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Search, Filter, Clock, X, Save, CheckCircle, XCircle, Video as VideoIcon, Calendar, Upload, FileText } from 'lucide-vue-next'

const props = defineProps({
  events: Object,
  alarmTypes: Array,
  filters: Object,
  validatedIds: Array,
  validateEvent: Object,
})

const search = ref(props.filters?.search || '')
const alarmType = ref(props.filters?.alarm_type || '')
const dateRange = ref(props.filters?.date_range || 'last_2_days')
const showModal = ref(false)
const selectedEvent = ref(null)
const showFollowUp = ref(false)

// Video polling state
const videoPollingInterval = ref(null)
const videoLoadAttempt = ref(0)
const isVideoLoading = ref(false)
const videoKey = ref(0)

// File upload state
const selectedFile = ref(null)
const fileInputRef = ref(null)
const isDragging = ref(false)

const form = useForm({
  status: '',
  follow_up: '',
  follow_up_file: null,
  keterangan: '',
})

// File upload handlers
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 20 * 1024 * 1024) {
      alert('Ukuran file maksimal 20MB')
      return
    }
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    if (!allowedTypes.includes(file.type)) {
      alert('Format file harus: PDF, JPG, JPEG, PNG, DOC, atau DOCX')
      return
    }
    selectedFile.value = file
    form.follow_up_file = file
  }
}

const clearFile = () => {
  selectedFile.value = null
  form.follow_up_file = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

// Drag & Drop handlers
const handleDragEnter = (e) => {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = true
}

const handleDragLeave = (e) => {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
}

const handleDragOver = (e) => {
  e.preventDefault()
  e.stopPropagation()
}

const handleDrop = (e) => {
  e.preventDefault()
  e.stopPropagation()
  isDragging.value = false
  
  const files = e.dataTransfer.files
  if (files && files.length > 0) {
    const file = files[0]
    
    if (file.size > 20 * 1024 * 1024) {
      alert('Ukuran file maksimal 20MB')
      return
    }
    
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    if (!allowedTypes.includes(file.type)) {
      alert('Format file harus: PDF, JPG, JPEG, PNG, DOC, atau DOCX')
      return
    }
    
    selectedFile.value = file
    form.follow_up_file = file
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  try {
    const dateObj = new Date(date)
    if (isNaN(dateObj.getTime())) return '-'
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    const day = String(dateObj.getDate()).padStart(2, '0')
    const month = months[dateObj.getMonth()]
    const year = dateObj.getFullYear()
    const hour = String(dateObj.getHours()).padStart(2, '0')
    const minute = String(dateObj.getMinutes()).padStart(2, '0')
    return `${day} ${month} ${year}, ${hour}:${minute}`
  } catch (e) {
    return '-'
  }
}

const getVideoUrl = (event) => {
  if (!event || !event.IsVideoSent || !event.VideoFileName) return null
  try {
    const date = new Date(event.Tanggal)
    if (isNaN(date.getTime())) return null
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    const dateStr = year.toString() + month + day
    const cacheBuster = '?v=' + videoKey.value
    return `/DSMvalid/videos/${dateStr}/${event.No_Unit}/${event.VideoFileName}${cacheBuster}`
  } catch (e) {
    return null
  }
}

const startVideoPolling = async () => {
  if (!selectedEvent.value || selectedEvent.value.IsVideoSent) return
  
  console.log('🔄 Starting video polling...')
  isVideoLoading.value = true
  videoLoadAttempt.value = 0
  
  videoPollingInterval.value = setInterval(async () => {
    videoLoadAttempt.value++
    console.log(`🔍 Checking video (attempt ${videoLoadAttempt.value}/20)...`)
    
    try {
      const response = await window.axios.get(route('events.check-video', selectedEvent.value.ID))
      
      if (response.data.IsVideoSent) {
        console.log('✅ Video ready!')
        selectedEvent.value.IsVideoSent = true
        selectedEvent.value.VideoFileName = response.data.VideoFileName
        videoKey.value++
        stopVideoPolling()
      }
    } catch (e) {
      console.error('❌ Error checking video:', e)
    }
    
    if (videoLoadAttempt.value >= 20) {
      console.log('⏰ Max polling attempts reached')
      stopVideoPolling()
    }
  }, 3000)
}

const stopVideoPolling = () => {
  if (videoPollingInterval.value) {
    clearInterval(videoPollingInterval.value)
    videoPollingInterval.value = null
    isVideoLoading.value = false
    console.log('🛑 Video polling stopped')
  }
}

const refreshEvents = () => {
  router.get(route('events.index'), {
    search: search.value,
    alarm_type: alarmType.value,
    date_range: dateRange.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['events', 'validatedIds'],
  })
}

const checkAndOpenModalFromUrl = () => {
  const urlParams = new URLSearchParams(window.location.search)
  const validateId = urlParams.get('validate')
  
  if (props.validateEvent) {
    nextTick(() => {
      openValidationModal(props.validateEvent)
      window.history.replaceState({}, '', window.location.pathname)
    })
    return
  }
  
  if (validateId && props.events?.data) {
    const event = props.events.data.find(e => e.ID == validateId)
    if (event) {
      nextTick(() => {
        openValidationModal(event)
        window.history.replaceState({}, '', window.location.pathname)
      })
    }
  }
}

const isValidated = (alarmId) => props.validatedIds?.includes(alarmId)

const openValidationModal = (event) => {
  console.log('📝 Opening modal for:', event)
  selectedEvent.value = event
  showModal.value = true
  form.reset()
  form.clearErrors()
  clearFile()
  videoKey.value++
  
  if (!event.IsVideoSent) {
    nextTick(() => {
      startVideoPolling()
    })
  }
}

const closeModal = () => {
  showModal.value = false
  selectedEvent.value = null
  form.reset()
  form.clearErrors()
  clearFile()
  stopVideoPolling()
}

const submit = () => {
  console.log('=== SUBMIT VALIDATION ===')
  console.log('Status:', form.status)
  console.log('Has File?', !!form.follow_up_file)
  
  if (form.status === 'false') {
    console.log('✅ Status FALSE - Clearing errors')
    form.clearErrors('follow_up_file')
    form.clearErrors('follow_up')
  }
  
  if (form.status === 'true' && !form.follow_up_file) {
    console.log('❌ Status TRUE but no file')
    form.setError('follow_up_file', 'File bukti wajib diupload jika status True')
    return
  }
  
  console.log('✅ Submitting form...')
  
  const submitData = {
    status: form.status,
    keterangan: form.keterangan,
  }
  
  if (form.status === 'true') {
    submitData.follow_up = form.follow_up
    submitData.follow_up_file = form.follow_up_file
  }
  
  form.transform(() => submitData)
    .post(route('events.store-validation', selectedEvent.value.ID), {
      forceFormData: true,
      onSuccess: () => {
        console.log('✅ Submit SUCCESS')
        closeModal()
        refreshEvents()
      },
      onError: (errors) => {
        console.error('❌ Submit FAILED:', errors)
        console.error('Error Details:', JSON.stringify(errors, null, 2))
      }
    })
}

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('validate')) {
    checkAndOpenModalFromUrl()
  }
  
  window.Echo.channel('dsm-events')
    .subscribed(() => console.log('✅ Subscribed to dsm-events'))
    .listen('.new-event', (data) => {
      console.log('🔔 New event:', data)
      refreshEvents()
    })
})

onBeforeUnmount(() => {
  window.Echo.leave('dsm-events')
  stopVideoPolling()
})

watch(() => props.validateEvent, (newVal) => {
  if (newVal) nextTick(() => openValidationModal(newVal))
}, { deep: true })

watch([search, alarmType, dateRange], () => {
  router.get(route('events.index'), {
    search: search.value,
    alarm_type: alarmType.value,
    date_range: dateRange.value,
  }, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })

watch(() => form.status, (newStatus) => {
  showFollowUp.value = newStatus === 'true'
  if (newStatus === 'false') {
    form.follow_up = ''
    clearFile()
    form.clearErrors('follow_up_file')
    form.clearErrors('follow_up')
  }
})
</script>

<template>
  <Head title="Events Management" />
  
  <DSMLayout>
    <div class="space-y-4 md:space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">Events Management</h1>
          <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Kelola dan validasi data deviasi DSM</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-4 border border-transparent dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-gray-400 dark:text-gray-500" />
            <input
              v-model="search"
              type="text"
              placeholder="Cari unit, alarm, lokasi..."
              class="w-full pl-9 md:pl-10 pr-3 md:pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder:text-gray-400 dark:placeholder:text-gray-500"
            />
          </div>

          <div class="relative">
            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-gray-400 dark:text-gray-500" />
            <select
              v-model="alarmType"
              class="w-full pl-9 md:pl-10 pr-3 md:pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Semua Jenis Alarm</option>
              <option v-for="type in alarmTypes" :key="type" :value="type">
                {{ type }}
              </option>
            </select>
          </div>

          <div class="relative">
            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-gray-400 dark:text-gray-500" />
            <select
              v-model="dateRange"
              class="w-full pl-9 md:pl-10 pr-3 md:pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="last_2_days">2 Hari Terakhir</option>
              <option value="last_7_days">7 Hari Terakhir</option>
              <option value="last_30_days">30 Hari Terakhir</option>
              <option value="all">Semua Data</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Events Table/Cards -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 overflow-hidden border border-transparent dark:border-gray-700">
        <div v-if="!events || !events.data || events.data.length === 0" class="p-8 md:p-12 text-center">
          <div class="text-gray-400 dark:text-gray-500 mb-3">
            <Clock class="w-12 h-12 md:w-16 md:h-16 mx-auto" />
          </div>
          <h3 class="text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Belum Ada Data Events</h3>
          <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">Data event akan muncul di sini setelah ada alarm dari sistem DSM</p>
        </div>

        <div v-else>
          <!-- Mobile Cards View -->
          <div class="block md:hidden divide-y divide-gray-200 dark:divide-gray-700">
            <div 
              v-for="event in events.data" 
              :key="event.ID"
              class="p-4 hover:bg-emerald-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-start justify-between mb-2">
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span class="font-bold text-gray-800 dark:text-gray-100">{{ event.No_Unit || '-' }}</span>
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                      {{ event.Alarm || '-' }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-600 dark:text-gray-400">{{ formatDate(event.Tanggal) }}</p>
                </div>
              </div>
              
              <div class="space-y-1 mb-3">
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Lokasi:</span>
                  <span class="text-gray-700 dark:text-gray-300 truncate">{{ event.Location || '-' }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Speed:</span>
                  <span class="text-gray-700 dark:text-gray-300">{{ event.Speed ? parseFloat(event.Speed).toFixed(2) : '-' }} km/h</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Alarm ID:</span>
                  <span class="text-gray-700 dark:text-gray-300 font-mono">{{ event.Alarm_id || '-' }}</span>
                </div>
              </div>
              
              <button
                v-if="!isValidated(event.Alarm_id)"
                @click="openValidationModal(event)"
                class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
              >
                Validasi
              </button>
              
              <div
                v-else
                class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg text-sm font-medium text-center"
              >
                Tervalidasi
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">No Unit</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Alarm</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Location</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Speed (km/h)</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Alarm ID</th>
                  <th class="px-4 lg:px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(event, index) in events.data" 
                  :key="event.ID" 
                  :class="[
                    'transition-colors',
                    index % 2 === 0 
                      ? 'bg-white dark:bg-gray-800 hover:bg-emerald-50 dark:hover:bg-gray-700' 
                      : 'bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-50 dark:hover:bg-gray-700'
                  ]"
                >
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                    <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ event.No_Unit || '-' }}</span>
                  </td>
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                    <span class="px-2 lg:px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                      {{ event.Alarm || '-' }}
                    </span>
                  </td>
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(event.Tanggal) }}
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                    {{ event.Location || '-' }}
                  </td>
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    {{ event.Speed ? parseFloat(event.Speed).toFixed(2) : '-' }}
                  </td>
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 font-mono">
                    {{ event.Alarm_id || '-' }}
                  </td>
                  <td class="px-4 lg:px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        v-if="!isValidated(event.Alarm_id)"
                        @click="openValidationModal(event)"
                        class="px-3 lg:px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium"
                      >
                        Validasi
                      </button>
                      
                      <span
                        v-else
                        class="px-3 lg:px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg text-sm font-medium cursor-not-allowed"
                      >
                        Tervalidasi
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
              <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                Menampilkan {{ events.from || 0 }} - {{ events.to || 0 }} dari {{ events.total || 0 }} events
              </div>
              <div v-if="events.links && events.links.length > 0" class="flex flex-wrap gap-1 sm:gap-2 justify-center">
                <Link
                  v-for="(link, index) in events.links"
                  :key="index"
                  :href="link.url || '#'"
                  v-html="link.label"
                  :class="[
                    'px-2 sm:px-3 py-1 rounded border text-xs sm:text-sm',
                    link.active
                      ? 'bg-emerald-600 text-white border-emerald-600'
                      : link.url
                      ? 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border-gray-200 dark:border-gray-700 cursor-not-allowed'
                  ]"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ✅ IMPROVED: Validation Modal with Better Desktop Video Layout -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-hidden" @click.self="closeModal">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="flex min-h-full items-center justify-center p-2 sm:p-4">
          <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-6xl max-h-[95vh] sm:max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 flex items-center justify-between flex-shrink-0">
              <div>
                <h2 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-100">Validasi Event</h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 hidden sm:block">Lakukan validasi terhadap data event</p>
              </div>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <X class="w-5 h-5 text-gray-500 dark:text-gray-400" />
              </button>
            </div>

            <!-- Modal Content -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1">
              <div class="flex flex-col gap-4 sm:gap-6 w-full mx-auto">
                <!-- Informasi Event -->
                <div>
                  <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Informasi Event</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">No Unit</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedEvent?.No_Unit }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Alarm</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedEvent?.Alarm }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ formatDate(selectedEvent?.Tanggal) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Speed</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedEvent?.Speed ? parseFloat(selectedEvent.Speed).toFixed(2) : '-' }} km/h</div>
                    </div>
                    <div class="sm:col-span-2">
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Location</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedEvent?.Location }}</div>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-1">
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Alarm ID</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200 font-mono">{{ selectedEvent?.Alarm_id }}</div>
                    </div>
                  </div>
                </div>

                <!-- ✅ IMPROVED: Video + Form Section with Better Desktop Layout -->
                <div class="w-full grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                  <!-- Video Player (Desktop: Proper Aspect Ratio) -->
                  <div class="w-full">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Video Rekaman</h3>
                    
                    <!-- Video Loading Indicator -->
                    <div v-if="isVideoLoading" class="mb-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                      <div class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent"></div>
                        <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">
                          Menunggu video... ({{ videoLoadAttempt }}/20)
                        </p>
                      </div>
                    </div>
                    
                    <!-- ✅ FIX: Video Container with Proper 16:9 Aspect Ratio for Desktop -->
                    <div class="relative w-full bg-black rounded-lg overflow-hidden" style="aspect-ratio: 16/9;">
                      <!-- No Video State -->
                      <div v-if="!selectedEvent?.IsVideoSent" class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center p-4 sm:p-6">
                          <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <VideoIcon class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-400" />
                          </div>
                          <p class="text-xs sm:text-sm text-gray-400">Video belum tersedia</p>
                          <p v-if="isVideoLoading" class="text-xs text-blue-400 mt-2">Sedang memeriksa...</p>
                        </div>
                      </div>
                      
                      <!-- Video Player -->
                      <video 
                        v-else 
                        :key="videoKey"
                        :src="getVideoUrl(selectedEvent)" 
                        controls 
                        controlslist="nodownload" 
                        class="absolute inset-0 w-full h-full object-contain" 
                        preload="metadata"
                      >
                        Browser Anda tidak mendukung video player.
                      </video>
                    </div>
                    
                    <!-- Video Info -->
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 px-1">
                      <div class="flex items-center justify-between">
                        <span>Unit: {{ selectedEvent?.No_Unit }}</span>
                        <span v-if="selectedEvent?.IsVideoSent" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                          <CheckCircle class="w-3 h-3" /> Available
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                          <XCircle class="w-3 h-3" /> Not Available
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Form Validasi -->
                  <div class="space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Form Validasi</h3>
                    
                    <!-- Status -->
                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Status Validasi <span class="text-red-500">*</span></label>
                      <div class="flex gap-2">
                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" :class="form.status === 'true' ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : ''">
                          <input type="radio" v-model="form.status" value="true" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500" />
                          <span class="text-sm font-medium text-gray-900 dark:text-gray-100">True</span>
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" :class="form.status === 'false' ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : ''">
                          <input type="radio" v-model="form.status" value="false" class="w-4 h-4 text-red-600 focus:ring-red-500" />
                          <span class="text-sm font-medium text-gray-900 dark:text-gray-100">False</span>
                        </label>
                      </div>
                      <p v-if="form.errors.status" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.status }}</p>
                    </div>
                    
                    <!-- Follow Up Text -->
                    <div v-if="showFollowUp">
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Follow Up <span class="text-red-500">*</span></label>
                      <textarea v-model="form.follow_up" rows="3" class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Tindakan follow up yang akan dilakukan..."></textarea>
                      <p v-if="form.errors.follow_up" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.follow_up }}</p>
                    </div>

                    <!-- File Upload -->
                    <div v-if="showFollowUp">
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Upload File Bukti <span class="text-red-500">*</span>
                      </label>
                      
                      <input 
                        ref="fileInputRef"
                        type="file" 
                        @change="handleFileSelect" 
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        class="hidden"
                      />
                      
                      <div 
                        v-if="!selectedFile" 
                        @click="$refs.fileInputRef.click()"
                        @dragenter="handleDragEnter"
                        @dragleave="handleDragLeave"
                        @dragover="handleDragOver"
                        @drop="handleDrop"
                        :class="[
                          'border-2 border-dashed rounded-lg p-3 sm:p-4 text-center cursor-pointer transition-all duration-200',
                          isDragging 
                            ? 'border-emerald-500 bg-emerald-100 dark:bg-emerald-900/30 scale-105' 
                            : 'border-gray-300 dark:border-gray-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/10'
                        ]"
                      >
                        <Upload :class="[
                          'w-6 h-6 sm:w-8 sm:h-8 mx-auto mb-2 transition-colors',
                          isDragging ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'
                        ]" />
                        <p :class="[
                          'text-xs font-medium',
                          isDragging ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400'
                        ]">
                          {{ isDragging ? '📂 Lepaskan file di sini' : 'Klik atau drag & drop file di sini' }} <span class="text-red-500">*</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, JPG, PNG, DOC, DOCX (Max 20MB)</p>
                      </div>

                      <div v-else class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-emerald-50 dark:bg-emerald-900/10">
                        <div class="flex items-center justify-between gap-3">
                          <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="flex-shrink-0">
                              <FileText class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                              <p class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate">{{ selectedFile.name }}</p>
                              <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(selectedFile.size) }}</p>
                            </div>
                          </div>
                          <button 
                            @click.stop="clearFile" 
                            type="button"
                            class="flex-shrink-0 p-1.5 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors"
                            title="Hapus file"
                          >
                            <X class="w-4 h-4 text-red-600 dark:text-red-400" />
                          </button>
                        </div>
                      </div>
                      
                      <p v-if="form.errors.follow_up_file" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.follow_up_file }}</p>
                    </div>
                    
                    <!-- Keterangan -->
                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Keterangan <span class="text-red-500">*</span></label>
                      <textarea v-model="form.keterangan" rows="4" class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Tambahkan keterangan detail mengenai validasi ini..."></textarea>
                      <p v-if="form.errors.keterangan" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.keterangan }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
              <div class="flex gap-2 sm:gap-3">
                <button type="button" @click="closeModal" class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Batal</button>
                <button type="button" @click="submit" :disabled="form.processing" class="flex-1 px-3 sm:px-4 py-2 sm:py-2.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 disabled:bg-gray-400 transition-colors inline-flex items-center justify-center gap-2">
                  <Save class="w-4 h-4" />
                  <span class="hidden sm:inline">{{ form.processing ? 'Menyimpan...' : 'Simpan Validasi' }}</span>
                  <span class="sm:hidden">{{ form.processing ? 'Saving...' : 'Simpan' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </DSMLayout>
</template>
