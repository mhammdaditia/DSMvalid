<script setup>
import { ref, watch, computed } from 'vue'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Search, Filter, CheckCircle, XCircle, Trash2, TrendingUp, AlertCircle, Edit, X, Video as VideoIcon, User as UserIcon, Eye, Upload, FileText, ExternalLink } from 'lucide-vue-next'

const props = defineProps({
  validations: Object,
  filters: Object,
  stats: Object,
  isAdmin: Boolean,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status !== undefined && props.filters?.status !== null ? String(props.filters.status) : '')

const showEditModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const selectedValidation = ref(null)

const selectedFile = ref(null)
const fileInputRef = ref(null)

const selectedIds = ref([])
const selectAll = ref(false)

const editForm = useForm({
  status: null,
  follow_up: '',
  follow_up_file: null,
  keterangan: '',
})

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
    editForm.follow_up_file = file
  }
}

const clearFile = () => {
  selectedFile.value = null
  editForm.follow_up_file = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

const getFileViewUrl = (filePath) => {
  if (!filePath) return null
  
  try {
    const encoded = btoa(filePath)
    return route('file.view', { token: encoded })
  } catch (e) {
    console.error('Error encoding file path:', e)
    return null
  }
}

const isDragging = ref(false)

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
    editForm.follow_up_file = file
  }
}

const getFileName = (filePath) => {
  if (!filePath) return null
  return filePath.split('/').pop()
}

watch([search, status], () => {
  const params = {}
  if (search.value) params.search = search.value
  if (status.value !== '') params.status = status.value
  
  router.get(route('validations.index'), params, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })

watch(() => editForm.status, (newStatus) => {
  if (newStatus === false || newStatus === 0) {
    editForm.follow_up = ''
    clearFile()
  }
})

watch(selectAll, (val) => {
  if (val) {
    selectedIds.value = props.validations.data.map(v => v.id)
  } else {
    selectedIds.value = []
  }
})

watch(() => props.validations, () => {
  selectedIds.value = []
  selectAll.value = false
}, { deep: true })

const hasSelected = computed(() => selectedIds.value.length > 0)

const toggleSelect = (id) => {
  const index = selectedIds.value.indexOf(id)
  if (index > -1) {
    selectedIds.value.splice(index, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const isSelected = (id) => {
  return selectedIds.value.includes(id)
}

const openViewModal = (validation) => {
  selectedValidation.value = validation
  showViewModal.value = true
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedValidation.value = null
}

const openEditModal = (validation) => {
  if (!props.isAdmin) {
    alert('Anda tidak memiliki akses untuk mengubah data validasi')
    return
  }
  
  selectedValidation.value = validation
  editForm.status = validation.status === true || validation.status === 1 || validation.status === '1'
  editForm.follow_up = validation.follow_up || ''
  editForm.keterangan = validation.keterangan
  editForm.clearErrors()
  clearFile()
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editForm.reset()
  selectedValidation.value = null
  clearFile()
}

const submitEdit = () => {
  editForm.transform((data) => ({
    status: data.status ? 1 : 0,
    follow_up: data.status ? data.follow_up : '',
    follow_up_file: data.follow_up_file,
    keterangan: data.keterangan
  })).post(route('validations.update', selectedValidation.value.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      closeEditModal()
    },
  })
}

const openDeleteModal = (validation) => {
  if (!props.isAdmin) {
    alert('Anda tidak memiliki akses untuk menghapus data validasi')
    return
  }
  selectedValidation.value = validation
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedValidation.value = null
}

const deleteValidation = () => {
  if (selectedValidation.value) {
    const validationId = parseInt(selectedValidation.value.id)
    
    console.log('Deleting validation ID:', validationId)
    
    router.delete(route('validations.destroy', validationId), {
      preserveScroll: true,
      onSuccess: () => {
        closeDeleteModal()
      },
      onError: (errors) => {
        console.error('Delete errors:', errors)
      }
    })
  }
}

const openBulkDeleteModal = () => {
  if (!props.isAdmin) {
    alert('Anda tidak memiliki akses untuk menghapus data validasi')
    return
  }
  showBulkDeleteModal.value = true
}

const closeBulkDeleteModal = () => {
  showBulkDeleteModal.value = false
}

const bulkDelete = () => {
  const ids = selectedIds.value.map(id => parseInt(id))
  
  console.log('Bulk deleting IDs:', ids)
  
  router.post(route('validations.bulk-delete'), {
    ids: ids
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedIds.value = []
      selectAll.value = false
      closeBulkDeleteModal()
    },
    onError: (errors) => {
      console.error('Bulk delete errors:', errors)
    }
  })
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
    return '/DSMvalid/videos/' + dateStr + '/' + event.No_Unit + '/' + event.VideoFileName
  } catch (e) {
    return null
  }
}

const getStatusBadge = (status) => {
  const isTrue = status === true || status === 1 || status === '1'
  return isTrue
    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
    : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
}

const getStatusText = (status) => {
  const isTrue = status === true || status === 1 || status === '1'
  return isTrue ? 'True' : 'False'
}

const isStatusTrue = (status) => {
  return status === true || status === 1 || status === '1'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}
</script>

<template>
  <Head title="Validations - DSMvalid" />
  
  <DSMLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Riwayat Validasi</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Lihat semua data validasi yang telah dilakukan</p>
        </div>
        
        <div class="flex items-center gap-3">
          <button
            v-if="hasSelected && isAdmin"
            @click="openBulkDeleteModal"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            <Trash2 class="w-4 h-4" />
            <span class="hidden sm:inline">Hapus</span> {{ selectedIds.length }} <span class="hidden sm:inline">Item</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Validasi</p>
              <p class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 mt-2">{{ stats?.total || 0 }}</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <TrendingUp class="w-5 h-5 md:w-6 md:h-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">True</p>
              <p class="text-2xl md:text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ stats?.valid || 0 }}</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
              <CheckCircle class="w-5 h-5 md:w-6 md:h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">False</p>
              <p class="text-2xl md:text-3xl font-bold text-red-600 dark:text-red-400 mt-2">{{ stats?.invalid || 0 }}</p>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
              <XCircle class="w-5 h-5 md:w-6 md:h-6 text-red-600 dark:text-red-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 border border-transparent dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" />
            <input
              v-model="search"
              type="text"
              placeholder="Cari nomor unit, alarm, lokasi..."
              class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder:text-gray-400 dark:placeholder:text-gray-500"
            />
          </div>

          <div class="relative">
            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" />
            <select
              v-model="status"
              class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Semua Status</option>
              <option value="1">True</option>
              <option value="0">False</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ✅ DATA TABLE - DESKTOP VIEW (Hidden on mobile) -->
      <div class="hidden md:block bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 overflow-hidden border border-transparent dark:border-gray-700">
        <div v-if="!validations || !validations.data || validations.data.length === 0" class="p-12 text-center">
          <div class="text-gray-400 dark:text-gray-500 mb-3">
            <AlertCircle class="w-16 h-16 mx-auto" />
          </div>
          <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
            {{ search || status !== '' ? 'Tidak Ada Data Ditemukan' : 'Belum Ada Data Validasi' }}
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            {{ search || status !== '' ? 'Coba ubah filter atau kata kunci pencarian' : 'Validasi event terlebih dahulu untuk melihat riwayat di sini' }}
          </p>
          <Link
            v-if="!search && status === ''"
            :href="route('events.index')"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
          >
            Ke Halaman Events
          </Link>
        </div>

        <div v-else>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                  <th v-if="isAdmin" class="px-6 py-3 text-left">
                    <input
                      type="checkbox"
                      v-model="selectAll"
                      class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500"
                    />
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">No Unit</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Alarm</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tanggal Event</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Follow Up</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Keterangan</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">File</th>
                  <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(validation, index) in validations.data" 
                  :key="validation.id" 
                  :class="[
                    'transition-colors',
                    index % 2 === 0 
                      ? 'bg-white dark:bg-gray-800 hover:bg-emerald-50 dark:hover:bg-gray-700' 
                      : 'bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-50 dark:hover:bg-gray-700'
                  ]"
                >
                  <td v-if="isAdmin" class="px-6 py-4">
                    <input
                      type="checkbox"
                      :checked="isSelected(validation.id)"
                      @change="toggleSelect(validation.id)"
                      class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ validation.no_unit }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                      {{ validation.alarm }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(validation.tanggal) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusBadge(validation.status)" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium">
                      <CheckCircle v-if="isStatusTrue(validation.status)" class="w-3 h-3" />
                      <XCircle v-else class="w-3 h-3" />
                      {{ getStatusText(validation.status) }}
                    </span>
                  </td>
                  
                  <td class="px-6 py-4 max-w-xs">
                    <p v-if="isStatusTrue(validation.status) && validation.follow_up" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" :title="validation.follow_up">
                      {{ validation.follow_up }}
                    </p>
                    <span v-else class="text-sm text-gray-400 dark:text-gray-500">-</span>
                  </td>
                  
                  <td class="px-6 py-4 max-w-xs">
                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" :title="validation.keterangan">
                      {{ validation.keterangan }}
                    </p>
                  </td>
                  
                  <td class="px-6 py-4 whitespace-nowrap">
                    <a 
                      v-if="validation.follow_up_file" 
                      :href="getFileViewUrl(validation.follow_up_file)"
                      target="_blank"
                      class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline"
                      :title="getFileName(validation.follow_up_file)"
                    >
                      <Eye class="w-3 h-3" />
                      Lihat
                    </a>
                    <span v-else class="text-sm text-gray-400 dark:text-gray-500">Belum ada</span>
                  </td>
                  
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-2">
                      <button
                        @click="openViewModal(validation)"
                        class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors"
                        title="Lihat Detail"
                      >
                        <Eye class="w-4 h-4" />
                      </button>
                      
                      <button
                        v-if="isAdmin"
                        @click="openEditModal(validation)"
                        class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                        title="Edit Validasi"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      
                      <button
                        v-if="isAdmin"
                        @click="openDeleteModal(validation)"
                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                        title="Hapus Validasi"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Desktop -->
          <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-600 dark:text-gray-400">
                Menampilkan {{ validations.from || 0 }} - {{ validations.to || 0 }} dari {{ validations.total || 0 }} validasi
              </div>
              <div v-if="validations.links && validations.links.length > 0" class="flex gap-2">
                <Link
                  v-for="(link, index) in validations.links"
                  :key="index"
                  :href="link.url || '#'"
                  v-html="link.label"
                  :class="[
                    'px-3 py-1 rounded border text-sm',
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

      <!-- ✅ MOBILE CARD VIEW (Visible only on mobile) -->
      <div class="md:hidden space-y-4">
        <div v-if="!validations || !validations.data || validations.data.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-8 text-center border border-transparent dark:border-gray-700">
          <div class="text-gray-400 dark:text-gray-500 mb-3">
            <AlertCircle class="w-12 h-12 mx-auto" />
          </div>
          <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2">
            {{ search || status !== '' ? 'Tidak Ada Data' : 'Belum Ada Data Validasi' }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            {{ search || status !== '' ? 'Coba ubah filter' : 'Validasi event terlebih dahulu' }}
          </p>
          <Link
            v-if="!search && status === ''"
            :href="route('events.index')"
            class="inline-flex items-center px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors"
          >
            Ke Halaman Events
          </Link>
        </div>

        <!-- Mobile Cards -->
        <div v-else class="space-y-3">
          <!-- Bulk Select Header (Mobile) -->
          <div v-if="isAdmin" class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 border border-transparent dark:border-gray-700">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="selectAll"
                class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Semua ({{ selectedIds.length }})</span>
            </label>
          </div>

          <!-- Validation Cards -->
          <div 
            v-for="validation in validations.data" 
            :key="validation.id" 
            class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 border border-transparent dark:border-gray-700 overflow-hidden"
          >
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 px-4 py-3 flex items-center justify-between">
              <div class="flex items-center gap-2 flex-1 min-w-0">
                <input
                  v-if="isAdmin"
                  type="checkbox"
                  :checked="isSelected(validation.id)"
                  @change="toggleSelect(validation.id)"
                  class="w-4 h-4 text-emerald-600 rounded focus:ring-emerald-500"
                  @click.stop
                />
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-white truncate">{{ validation.no_unit }}</h3>
                  <p class="text-xs text-emerald-100">{{ validation.alarm }}</p>
                </div>
              </div>
              <span :class="[
                'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium flex-shrink-0',
                isStatusTrue(validation.status) 
                  ? 'bg-green-100 text-green-700' 
                  : 'bg-red-100 text-red-700'
              ]">
                <CheckCircle v-if="isStatusTrue(validation.status)" class="w-3 h-3" />
                <XCircle v-else class="w-3 h-3" />
                {{ getStatusText(validation.status) }}
              </span>
            </div>

            <!-- Card Body -->
            <div class="p-4 space-y-3">
              <!-- Tanggal -->
              <div class="flex items-start gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Tanggal:</span>
                <span class="text-xs text-gray-700 dark:text-gray-300">{{ formatDate(validation.tanggal) }}</span>
              </div>

              <!-- Follow Up -->
              <div v-if="isStatusTrue(validation.status) && validation.follow_up" class="flex items-start gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Follow Up:</span>
                <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 line-clamp-2">{{ validation.follow_up }}</p>
              </div>

              <!-- Keterangan -->
              <div class="flex items-start gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Keterangan:</span>
                <p class="text-xs text-gray-700 dark:text-gray-300 flex-1 line-clamp-2">{{ validation.keterangan }}</p>
              </div>

              <!-- File -->
              <div v-if="validation.follow_up_file" class="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                <FileText class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                <a 
                  :href="getFileViewUrl(validation.follow_up_file)"
                  target="_blank"
                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline truncate flex-1"
                >
                  {{ getFileName(validation.follow_up_file) }}
                </a>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                <button
                  @click="openViewModal(validation)"
                  class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors"
                >
                  <Eye class="w-3 h-3" />
                  Detail
                </button>
                <button
                  v-if="isAdmin"
                  @click="openEditModal(validation)"
                  class="flex-1 flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                >
                  <Edit class="w-3 h-3" />
                  Edit
                </button>
                <button
                  v-if="isAdmin"
                  @click="openDeleteModal(validation)"
                  class="flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination Mobile -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 border border-transparent dark:border-gray-700">
            <div class="text-center text-xs text-gray-600 dark:text-gray-400 mb-3">
              Menampilkan {{ validations.from || 0 }} - {{ validations.to || 0 }} dari {{ validations.total || 0 }}
            </div>
            <div v-if="validations.links && validations.links.length > 0" class="flex flex-wrap justify-center gap-2">
              <Link
                v-for="(link, index) in validations.links"
                :key="index"
                :href="link.url || '#'"
                v-html="link.label"
                :class="[
                  'px-2 py-1 rounded border text-xs',
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

    <!-- MODALS (Keep all existing modals) -->
    <!-- View Modal -->
    <Teleport to="body">
      <div v-if="showViewModal" class="fixed inset-0 z-50 overflow-hidden" @click.self="closeViewModal">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 md:px-6 py-3 flex items-center justify-between flex-shrink-0">
              <div>
                <h2 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Detail Validasi</h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Informasi lengkap data validasi</p>
              </div>
              <button @click="closeViewModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <X class="w-5 h-5 text-gray-500 dark:text-gray-400" />
              </button>
            </div>

            <!-- Modal Content -->
            <div class="p-4 md:p-6 overflow-y-auto flex-1">
              <div class="flex flex-col gap-4 md:gap-6 w-full mx-auto">
                <!-- Informasi Event -->
                <div>
                  <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">Informasi Event</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">No Unit</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm font-semibold text-gray-800 dark:text-gray-200">{{ selectedValidation?.no_unit }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Alarm</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200">{{ selectedValidation?.alarm }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Event</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200">{{ formatDate(selectedValidation?.tanggal) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Speed</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200">{{ selectedValidation?.speed || '-' }} km/h</div>
                    </div>
                    <div class="sm:col-span-2">
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Location</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200 line-clamp-1" :title="selectedValidation?.location">{{ selectedValidation?.location }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Alarm ID</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200 font-mono">{{ selectedValidation?.alarm_id }}</div>
                    </div>
                  </div>
                </div>

                <!-- Informasi Validasi -->
                <div>
                  <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-3">Informasi Validasi</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                      <div>
                        <span :class="getStatusBadge(selectedValidation?.status)" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium">
                          <CheckCircle v-if="isStatusTrue(selectedValidation?.status)" class="w-4 h-4" />
                          <XCircle v-else class="w-4 h-4" />
                          {{ getStatusText(selectedValidation?.status) }}
                        </span>
                      </div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Validator</label>
                      <div v-if="selectedValidation?.validator" class="flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-sm font-bold shadow">
                          {{ selectedValidation.validator.name.charAt(0).toUpperCase() }}
                        </div>
                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ selectedValidation.validator.name }}</span>
                      </div>
                      <div v-else class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-400">-</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Waktu Validasi</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200">{{ formatDateTime(selectedValidation?.created_at) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Terakhir Diupdate</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200">{{ formatDateTime(selectedValidation?.updated_at) }}</div>
                    </div>
                  </div>
                </div>

                <!-- VIDEO + DETAIL TEXT -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                  <!-- Video -->
                  <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Video Rekaman</h3>
                    <div class="bg-black rounded-lg mb-2 aspect-video">
                      <div v-if="!selectedValidation?.IsVideoSent" class="flex items-center justify-center h-full">
                        <div class="text-center p-6">
                          <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <VideoIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                          </div>
                          <p class="text-xs text-gray-400">Video belum tersedia</p>
                        </div>
                      </div>
                      <video v-else :src="getVideoUrl(selectedValidation)" controls class="w-full h-full rounded-lg" style="background: #000; object-fit: contain;" preload="metadata">
                        Browser Anda tidak mendukung video player.
                      </video>
                    </div>
                  </div>

                  <!-- Follow Up & Keterangan -->
                  <div class="space-y-4">
                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Follow Up</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200 min-h-[80px]">
                        {{ selectedValidation?.follow_up || '-' }}
                      </div>
                    </div>

                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">File Bukti</label>
                      <div v-if="selectedValidation?.follow_up_file" class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 bg-blue-50 dark:bg-blue-900/10">
                        <div class="flex items-center justify-between gap-3">
                          <div class="flex items-center gap-2 flex-1 min-w-0">
                            <FileText class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                              <p class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate">{{ getFileName(selectedValidation.follow_up_file) }}</p>
                            </div>
                          </div>
                          <a 
                            :href="getFileViewUrl(selectedValidation.follow_up_file)"
                            target="_blank"
                            class="flex-shrink-0 flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition-colors"
                          >
                            <ExternalLink class="w-3 h-3" />
                            Buka
                          </a>
                        </div>
                      </div>
                      <div v-else class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-400">
                        Tidak ada file
                      </div>
                    </div>

                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Keterangan</label>
                      <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-sm text-gray-800 dark:text-gray-200 min-h-[100px]">
                        {{ selectedValidation?.keterangan }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 md:px-6 py-3 md:py-4 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
              <div class="flex justify-end gap-3">
                <button type="button" @click="closeViewModal" class="px-4 py-2 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Tutup</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Keep all other existing modals (Edit, Delete, Bulk Delete) unchanged -->
    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEditModal && isAdmin" class="fixed inset-0 z-50 overflow-hidden" @click.self="closeEditModal">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 md:px-6 py-3 flex items-center justify-between flex-shrink-0">
              <div>
                <h2 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Edit Validasi</h2>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Update data validasi event</p>
              </div>
              <button @click="closeEditModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <X class="w-5 h-5 text-gray-500 dark:text-gray-400" />
              </button>
            </div>

            <!-- Content -->
            <div class="p-4 md:p-6 overflow-y-auto flex-1">
              <div class="flex flex-col gap-4 md:gap-6 w-full mx-auto">
                <!-- Informasi Event (Read Only) -->
                <div>
                  <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Informasi Event</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">No Unit</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedValidation?.no_unit }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Alarm</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedValidation?.alarm }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ formatDate(selectedValidation?.tanggal) }}</div>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Speed</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedValidation?.speed || '-' }} km/h</div>
                    </div>
                    <div class="sm:col-span-2">
                      <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Location</label>
                      <div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-xs text-gray-800 dark:text-gray-200">{{ selectedValidation?.location }}</div>
                    </div>
                  </div>
                </div>

                <!-- VIDEO + FORM VALIDASI -->
                <div class="w-full grid grid-cols-1 lg:grid-cols-5 gap-4 md:gap-6 items-start">
                  <!-- Video Rekaman -->
                  <div class="lg:col-span-3 w-full">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Video Rekaman</h3>
                    <div class="bg-black rounded-lg mb-2 w-full aspect-video">
                      <div v-if="!selectedValidation?.IsVideoSent" class="flex items-center justify-center h-full">
                        <div class="text-center p-6">
                          <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <VideoIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                          </div>
                          <p class="text-xs text-gray-400">Video belum tersedia</p>
                        </div>
                      </div>
                      <video v-else :src="getVideoUrl(selectedValidation)" controls controlslist="nodownload" class="w-full h-full rounded-lg" style="background: #000; object-fit: contain;" preload="metadata">
                        Browser Anda tidak mendukung video player.
                      </video>
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 px-1">
                      <div class="flex items-center justify-between">
                        <span>Unit: {{ selectedValidation?.no_unit }}</span>
                        <span v-if="selectedValidation?.IsVideoSent" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                          <CheckCircle class="w-3 h-3" /> Available
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400">
                          <XCircle class="w-3 h-3" /> Not Available
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Form Validasi -->
                  <div class="lg:col-span-2 space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 mb-2">Form Validasi</h3>
                    
                    <!-- Status -->
                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Status Validasi <span class="text-red-500">*</span></label>
                      <div class="flex gap-2">
                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" :class="editForm.status === true ? 'bg-green-50 dark:bg-green-900/20 border-green-500' : ''">
                          <input type="radio" v-model="editForm.status" :value="true" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500" />
                          <span class="text-sm font-medium text-gray-900 dark:text-gray-100">True</span>
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" :class="editForm.status === false ? 'bg-red-50 dark:bg-red-900/20 border-red-500' : ''">
                          <input type="radio" v-model="editForm.status" :value="false" class="w-4 h-4 text-red-600 focus:ring-red-500" />
                          <span class="text-sm font-medium text-gray-900 dark:text-gray-100">False</span>
                        </label>
                      </div>
                      <p v-if="editForm.errors.status" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForm.errors.status }}</p>
                    </div>
                    
                    <!-- Follow Up Text -->
                    <div v-if="editForm.status === true">
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Follow Up <span class="text-red-500">*</span></label>
                      <textarea v-model="editForm.follow_up" rows="3" class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Tindakan follow up..."></textarea>
                      <p v-if="editForm.errors.follow_up" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForm.errors.follow_up }}</p>
                    </div>

                    <!-- File Upload Section -->
                    <div v-if="editForm.status === true">
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        File Bukti <span class="text-red-500">*</span>
                      </label>
                      
                      <!-- Show existing file -->
                      <div v-if="selectedValidation?.follow_up_file && !selectedFile" class="mb-2 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center gap-2">
                          <FileText class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                          <div class="flex-1 min-w-0">
                            <p class="text-xs text-blue-800 dark:text-blue-200 font-medium truncate">File saat ini:</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400 truncate">{{ getFileName(selectedValidation.follow_up_file) }}</p>
                          </div>
                          <a 
                            :href="getFileViewUrl(selectedValidation.follow_up_file)"
                            target="_blank"
                            class="flex-shrink-0 p-1 hover:bg-blue-100 dark:hover:bg-blue-800 rounded"
                            title="Lihat file"
                          >
                            <Eye class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                          </a>
                        </div>
                      </div>

                      <!-- Warning untuk data lama -->
                      <div v-if="!selectedValidation?.follow_up_file && !selectedFile" class="mb-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <p class="text-xs text-yellow-800 dark:text-yellow-200 font-medium">⚠ Data lama belum memiliki file bukti. Silakan upload file.</p>
                      </div>

                      <!-- File input -->
                      <input 
                        ref="fileInputRef"
                        type="file" 
                        @change="handleFileSelect" 
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        class="hidden"
                      />
                      
                      <!-- Drag & Drop Area -->
                      <div 
                        v-if="!selectedFile" 
                        @click="$refs.fileInputRef.click()"
                        @dragenter="handleDragEnter"
                        @dragleave="handleDragLeave"
                        @dragover="handleDragOver"
                        @drop="handleDrop"
                        :class="[
                          'border-2 border-dashed rounded-lg p-3 text-center cursor-pointer transition-all duration-200',
                          isDragging 
                            ? 'border-emerald-500 bg-emerald-100 dark:bg-emerald-900/30 scale-105' 
                            : 'border-gray-300 dark:border-gray-600 hover:border-emerald-500 dark:hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/10'
                        ]"
                      >
                        <Upload :class="[
                          'w-6 h-6 mx-auto mb-1 transition-colors',
                          isDragging ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'
                        ]" />
                        <p :class="[
                          'text-xs font-medium',
                          isDragging ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400'
                        ]">
                          {{ isDragging ? '📂 Lepaskan file di sini' : (selectedValidation?.follow_up_file ? 'Klik atau drag file untuk ganti' : 'Klik atau drag & drop file') }} <span class="text-red-500">*</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-0.5">PDF, JPG, PNG, DOC (Max 20MB)</p>
                      </div>

                      <!-- Selected file preview -->
                      <div v-else class="border border-gray-300 dark:border-gray-600 rounded-lg p-2 bg-emerald-50 dark:bg-emerald-900/10">
                        <div class="flex items-center justify-between gap-2">
                          <div class="flex items-center gap-2 flex-1 min-w-0">
                            <FileText class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                              <p class="text-xs font-medium text-gray-800 dark:text-gray-200 truncate">{{ selectedFile.name }}</p>
                              <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(selectedFile.size) }}</p>
                            </div>
                          </div>
                          <button 
                            @click.stop="clearFile" 
                            type="button"
                            class="flex-shrink-0 p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors"
                            title="Hapus file"
                          >
                            <X class="w-4 h-4 text-red-600 dark:text-red-400" />
                          </button>
                        </div>
                      </div>
                      
                      <p v-if="editForm.errors.follow_up_file" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForm.errors.follow_up_file }}</p>
                    </div>
                    
                    <!-- Keterangan -->
                    <div>
                      <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Keterangan <span class="text-red-500">*</span></label>
                      <textarea v-model="editForm.keterangan" rows="4" class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Keterangan detail..."></textarea>
                      <p v-if="editForm.errors.keterangan" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ editForm.errors.keterangan }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 md:px-6 py-3 md:py-4 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
              <div class="flex gap-3">
                <button type="button" @click="closeEditModal" class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Batal</button>
                <button type="button" @click="submitEdit" :disabled="editForm.processing" class="flex-1 px-4 py-2.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 disabled:bg-gray-400 transition-colors">
                  {{ editForm.processing ? 'Menyimpan...' : 'Update Validasi' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal && isAdmin" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center p-4" @click="closeDeleteModal">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
          <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-4">
            <AlertCircle class="w-6 h-6 text-red-600 dark:text-red-400" />
          </div>
          
          <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 text-center mb-2">Hapus Validasi?</h3>
          <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
            Apakah Anda yakin ingin menghapus data validasi untuk <strong class="text-gray-800 dark:text-gray-100">{{ selectedValidation?.no_unit }}</strong>? File bukti juga akan dihapus.
          </p>

          <div class="flex flex-col sm:flex-row gap-3">
            <button
              @click="closeDeleteModal"
              class="w-full sm:flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Batal
            </button>
            <button
              @click="deleteValidation"
              class="w-full sm:flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Bulk Delete Modal -->
    <Teleport to="body">
      <div v-if="showBulkDeleteModal && isAdmin" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center p-4" @click="closeBulkDeleteModal">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6" @click.stop>
          <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-4">
            <Trash2 class="w-6 h-6 text-red-600 dark:text-red-400" />
          </div>
          
          <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 text-center mb-2">Hapus {{ selectedIds.length }} Validasi?</h3>
          <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
            Apakah Anda yakin ingin menghapus <strong>{{ selectedIds.length }}</strong> data validasi yang dipilih? Semua file bukti juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
          </p>

          <div class="flex flex-col sm:flex-row gap-3">
            <button
              @click="closeBulkDeleteModal"
              class="w-full sm:flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Batal
            </button>
            <button
              @click="bulkDelete"
              class="w-full sm:flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Hapus Semua
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </DSMLayout>
</template>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
