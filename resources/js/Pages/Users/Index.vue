<script setup>
import { ref, watch } from 'vue'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { UserPlus, Edit, Trash2, X, Search, Filter } from 'lucide-vue-next'

const props = defineProps({
  users: Object,
  filters: Object,
  stats: Object,
})

const page = usePage()
const currentUser = page.props.auth?.user

const search = ref(props.filters?.search || '')
const role = ref(props.filters?.role || '')

// Watch for filter changes
watch([search, role], () => {
  const params = {}
  
  if (search.value) {
    params.search = search.value
  }
  
  if (role.value !== '') {
    params.role = role.value
  }
  
  router.get(route('users.index'), params, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedUser = ref(null)

// Forms - ✅ TANPA _method
const createForm = useForm({
  name: '',
  username: '',
  password: '',
  password_confirmation: '',
  role: 'user',
})

const editForm = useForm({
  name: '',
  username: '',
  password: '',
  password_confirmation: '',
  role: '',
})

// ✅ Check if user can be edited/deleted
const canEditUser = (user) => {
  // Super admin bisa edit semua termasuk diri sendiri
  if (currentUser.role === 'super_admin') {
    return true
  }
  
  // Admin biasa tidak bisa edit super admin
  if (user.role === 'super_admin') {
    return false
  }
  
  return true
}

const canDeleteUser = (user) => {
  // Super admin bisa hapus semua termasuk diri sendiri
  if (currentUser.role === 'super_admin') {
    return true
  }
  
  // Admin biasa tidak bisa hapus super admin
  if (user.role === 'super_admin') {
    return false
  }
  
  // User tidak bisa hapus diri sendiri
  if (user.id === currentUser.id) {
    return false
  }
  
  return true
}

// Create User
const openCreateModal = () => {
  createForm.reset()
  createForm.clearErrors()
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
  createForm.reset()
}

const submitCreate = () => {
  createForm.post(route('users.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeCreateModal()
    },
  })
}

// Edit User
const openEditModal = (user) => {
  if (!canEditUser(user)) {
    alert('Anda tidak memiliki akses untuk mengedit user ini')
    return
  }
  
  selectedUser.value = user
  editForm.name = user.name
  editForm.username = user.username
  editForm.role = user.role
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.clearErrors()
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editForm.reset()
  selectedUser.value = null
}

const submitEdit = () => {
  editForm.post(route('users.update', selectedUser.value.id), {  // ✅ Pakai .post() bukan .put()
    preserveScroll: true,
    onSuccess: () => {
      closeEditModal()
    },
    onError: (errors) => {
      console.error('Update error:', errors)
    },
  })
}

// Delete User
const openDeleteModal = (user) => {
  if (!canDeleteUser(user)) {
    if (user.role === 'super_admin' && currentUser.role !== 'super_admin') {
      alert('Anda tidak memiliki akses untuk menghapus Super Admin')
    } else {
      alert('Anda tidak dapat menghapus user ini')
    }
    return
  }
  
  selectedUser.value = user
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedUser.value = null
}

const deleteUser = () => {
  router.delete(route('users.destroy', selectedUser.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteModal()
    },
  })
}

const getRoleBadge = (role) => {
  const badges = {
    'super_admin': 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    'admin': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    'user': 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
  }
  return badges[role] || badges['user']
}

const getRoleLabel = (role) => {
  const labels = {
    'super_admin': 'Super Admin',
    'admin': 'Admin',
    'user': 'User'
  }
  return labels[role] || role
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}
</script>


<template>
  <Head title="User Management" />
  
  <DSMLayout>
    <div class="space-y-4 md:space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-gray-100">User Management</h1>
          <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Kelola pengguna sistem DSMvalid</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-3 md:px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors shadow-lg text-sm md:text-base"
        >
          <UserPlus class="w-4 h-4 md:w-5 md:h-5" />
          <span class="hidden sm:inline">Tambah User</span>
          <span class="sm:hidden">Tambah</span>
        </button>
      </div>

      <!-- Statistics -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Total Users</p>
          <p class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1 md:mt-2">{{ stats?.total || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">Admin</p>
          <p class="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1 md:mt-2">{{ (stats?.admin || 0) + (stats?.super_admin || 0) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 md:p-6 border border-transparent dark:border-gray-700">
          <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400 font-medium">User</p>
          <p class="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1 md:mt-2">{{ stats?.user || 0 }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-3 md:p-4 border border-transparent dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
          <!-- Search -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-gray-400 dark:text-gray-500" />
            <input
              v-model="search"
              type="text"
              placeholder="Cari nama atau username..."
              class="w-full pl-9 md:pl-10 pr-3 md:pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent placeholder:text-gray-400 dark:placeholder:text-gray-500"
            />
          </div>

          <!-- Filter Role -->
          <div class="relative">
            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-gray-400 dark:text-gray-500" />
            <select
              v-model="role"
              class="w-full pl-9 md:pl-10 pr-3 md:pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="">Semua Role</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin</option>
              <option value="user">User</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Users Table/Cards -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 overflow-hidden border border-transparent dark:border-gray-700">
        <div v-if="users?.data && users.data.length > 0">
          <!-- Mobile Cards View -->
          <div class="block md:hidden divide-y divide-gray-200 dark:divide-gray-700">
            <div
              v-for="user in users.data"
              :key="user.id"
              class="p-4 hover:bg-emerald-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100">{{ user.name }}</h3>
                    <span 
                      v-if="user.id === currentUser.id"
                      class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-full"
                    >
                      You
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">@{{ user.username }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ formatDate(user.created_at) }}</p>
                </div>
                <span 
                  :class="getRoleBadge(user.role)" 
                  class="px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ getRoleLabel(user.role) }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  v-if="canEditUser(user)"
                  @click="openEditModal(user)"
                  class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors text-sm font-medium"
                >
                  <Edit class="w-4 h-4" />
                  Edit
                </button>
                <button
                  v-else
                  disabled
                  class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm font-medium cursor-not-allowed"
                >
                  <Edit class="w-4 h-4" />
                  Edit
                </button>
                
                <button
                  v-if="canDeleteUser(user)"
                  @click="openDeleteModal(user)"
                  class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                  title="Hapus"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
                <button
                  v-else
                  disabled
                  class="p-2 text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-not-allowed"
                  title="Tidak dapat dihapus"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Username</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Role</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Dibuat</th>
                  <th class="px-4 lg:px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(user, index) in users.data" 
                  :key="user.id" 
                  :class="[
                    'transition-colors',
                    index % 2 === 0 
                      ? 'bg-white dark:bg-gray-800 hover:bg-emerald-50 dark:hover:bg-gray-700' 
                      : 'bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-50 dark:hover:bg-gray-700'
                  ]"
                >
                  <td class="px-4 lg:px-6 py-4">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-sm text-gray-800 dark:text-gray-100">{{ user.name }}</span>
                      <span 
                        v-if="user.id === currentUser.id"
                        class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-full"
                      >
                        You
                      </span>
                    </div>
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    @{{ user.username }}
                  </td>
                  <td class="px-4 lg:px-6 py-4">
                    <span 
                      :class="getRoleBadge(user.role)" 
                      class="px-2 lg:px-3 py-1 rounded-full text-xs font-medium"
                    >
                      {{ getRoleLabel(user.role) }}
                    </span>
                  </td>
                  <td class="px-4 lg:px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ formatDate(user.created_at) }}
                  </td>
                  <td class="px-4 lg:px-6 py-4">
                    <div class="flex items-center gap-2">
                      <button
                        v-if="canEditUser(user)"
                        @click="openEditModal(user)"
                        class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                        title="Edit"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      <button
                        v-else
                        disabled
                        class="p-2 text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-not-allowed"
                        title="Tidak dapat diedit"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      
                      <button
                        v-if="canDeleteUser(user)"
                        @click="openDeleteModal(user)"
                        class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                        title="Hapus"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                      <button
                        v-else
                        disabled
                        class="p-2 text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-700 rounded-lg cursor-not-allowed"
                        :title="user.id === currentUser.id && currentUser.role !== 'super_admin' ? 'Tidak dapat menghapus diri sendiri' : 'Tidak dapat dihapus'"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            
            <div class="px-4 lg:px-6 py-3 md:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
              <p class="text-xs md:text-sm text-gray-600 dark:text-gray-400">
                Menampilkan {{ users.from || 0 }} - {{ users.to || 0 }} dari {{ users.total || 0 }} users
              </p>
            </div>
          </div>
        </div>
        
        <div v-else class="p-8 md:p-12 text-center">
          <p class="text-base md:text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
            {{ search || role ? 'Tidak Ada Data Ditemukan' : 'Belum Ada Data Users' }}
          </p>
          <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">
            {{ search || role ? 'Coba ubah filter atau kata kunci pencarian' : 'Tambahkan user baru untuk memulai' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center p-4" @click="closeCreateModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-4 md:p-6 max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg md:text-xl font-bold text-gray-800 dark:text-gray-100">Tambah User Baru</h3>
          <button @click="closeCreateModal" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="submitCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Nama Lengkap <span class="text-red-500">*</span>
            </label>
            <input
              v-model="createForm.name"
              type="text"
              required
              class="w-full px-3 md:px-4 py-2 text-sm border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="createForm.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            />
            <p v-if="createForm.errors.name" class="mt-1 text-xs md:text-sm text-red-600 dark:text-red-400">{{ createForm.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Username <span class="text-red-500">*</span>
            </label>
            <input
              v-model="createForm.username"
              type="text"
              required
              placeholder="username_tanpa_spasi"
              class="w-full px-3 md:px-4 py-2 text-sm border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="createForm.errors.username ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            />
            <p v-if="createForm.errors.username" class="mt-1 text-xs md:text-sm text-red-600 dark:text-red-400">{{ createForm.errors.username }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Password <span class="text-red-500">*</span>
            </label>
            <input
              v-model="createForm.password"
              type="password"
              required
              minlength="8"
              placeholder="Minimal 8 karakter"
              class="w-full px-3 md:px-4 py-2 text-sm border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
              :class="createForm.errors.password ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            />
            <p v-if="createForm.errors.password" class="mt-1 text-xs md:text-sm text-red-600 dark:text-red-400">{{ createForm.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Konfirmasi Password <span class="text-red-500">*</span>
            </label>
            <input
              v-model="createForm.password_confirmation"
              type="password"
              required
              placeholder="Ketik ulang password"
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Role <span class="text-red-500">*</span>
            </label>
            <select
              v-model="createForm.role"
              required
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="user">User</option>
              <option value="admin">Admin</option>
              <option v-if="currentUser.role === 'super_admin'" value="super_admin">Super Admin</option>
            </select>
          </div>

          <div class="flex gap-2 md:gap-3 pt-4">
            <button
              type="button"
              @click="closeCreateModal"
              class="flex-1 px-3 md:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="createForm.processing"
              class="flex-1 px-3 md:px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:bg-gray-400 transition-colors"
            >
              {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center p-4" @click="closeEditModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-4 md:p-6 max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg md:text-xl font-bold text-gray-800 dark:text-gray-100">Edit User</h3>
          <button @click="closeEditModal" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="submitEdit" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap</label>
            <input
              v-model="editForm.name"
              type="text"
              required
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Username</label>
            <input
              v-model="editForm.username"
              type="text"
              required
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Password Baru (opsional)
            </label>
            <input
              v-model="editForm.password"
              type="password"
              minlength="8"
              placeholder="Kosongkan jika tidak diubah"
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password</p>
          </div>

          <div v-if="editForm.password">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Konfirmasi Password Baru</label>
            <input
              v-model="editForm.password_confirmation"
              type="password"
              placeholder="Ketik ulang password baru"
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Role</label>
            <select
              v-model="editForm.role"
              required
              class="w-full px-3 md:px-4 py-2 text-sm border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            >
              <option value="user">User</option>
              <option value="admin">Admin</option>
              <option v-if="currentUser.role === 'super_admin'" value="super_admin">Super Admin</option>
            </select>
          </div>

          <div class="flex gap-2 md:gap-3 pt-4">
            <button
              type="button"
              @click="closeEditModal"
              class="flex-1 px-3 md:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="flex-1 px-3 md:px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:bg-gray-400 transition-colors"
            >
              {{ editForm.processing ? 'Menyimpan...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center p-4" @click="closeDeleteModal">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-4 md:p-6" @click.stop>
        <div class="flex items-center justify-center w-10 h-10 md:w-12 md:h-12 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full mb-4">
          <Trash2 class="w-5 h-5 md:w-6 md:h-6 text-red-600 dark:text-red-400" />
        </div>
        
        <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100 text-center mb-2">Hapus User?</h3>
        <p class="text-sm md:text-base text-gray-600 dark:text-gray-400 text-center mb-6">
          Apakah Anda yakin ingin menghapus user <strong class="text-gray-800 dark:text-gray-100">{{ selectedUser?.name }}</strong>?<br>
          <span class="text-xs md:text-sm">Tindakan ini tidak dapat dibatalkan.</span>
        </p>

        <!-- ✅ Warning untuk Super Admin hapus diri sendiri -->
        <div 
          v-if="selectedUser?.id === currentUser.id"
          class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"
        >
          <p class="text-sm text-yellow-800 dark:text-yellow-300 font-medium">
            ⚠ Anda akan menghapus akun Anda sendiri dan akan logout secara otomatis!
          </p>
        </div>

        <div class="flex gap-2 md:gap-3">
          <button
            @click="closeDeleteModal"
            class="flex-1 px-3 md:px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Batal
          </button>
          <button
            @click="deleteUser"
            class="flex-1 px-3 md:px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>
  </DSMLayout>
</template>
