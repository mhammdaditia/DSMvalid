<script setup>
import { ref } from 'vue'
import DSMLayout from '@/Layouts/DSMLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { User, Key, Shield, Save } from 'lucide-vue-next'

const page = usePage()
const user = page.props.auth.user

// Check if user is admin
const isAdmin = user.role === 'admin'

// Form update profile information (HANYA ADMIN)
const profileForm = useForm({
  name: user.name,
  username: user.username,
})

// Form update password (HANYA ADMIN)
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updateProfileInformation = () => {
  profileForm.put(route('user-profile-information.update'), {
    preserveScroll: true,
  })
}

const updatePassword = () => {
  passwordForm.put(route('user-password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
    },
  })
}
</script>

<template>
  <Head title="Profile" />
  
  <DSMLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Profile Settings</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ isAdmin ? 'Kelola informasi profil Anda' : 'Informasi profil Anda' }}</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Profile Card -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 border border-transparent dark:border-gray-700">
            <!-- Avatar (tanpa upload) -->
            <div class="text-center mb-6">
              <div class="w-32 h-32 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-4xl font-bold text-gray-700 dark:text-gray-200 mx-auto">
                <span>{{ user.name.charAt(0) }}</span>
              </div>
              
              <h3 class="mt-4 text-xl font-bold text-gray-800 dark:text-gray-100">{{ user.name }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">@{{ user.username }}</p>
              <div class="mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium capitalize"
                  :class="user.role === 'admin' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'"
                >
                  <Shield class="w-3 h-3 mr-1" />
                  {{ user.role }}
                </span>
              </div>
            </div>

            <!-- Quick Info -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Status</span>
                <span class="font-semibold text-green-600 dark:text-green-400">Active</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Joined</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200">
                  {{ new Date(user.created_at).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Update Profile Information (HANYA ADMIN) -->
          <div v-if="isAdmin" class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 border border-transparent dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <User class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Informasi Profil</h2>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Perbarui informasi profil Anda</p>
            </div>

            <form @submit.prevent="updateProfileInformation" class="p-6 space-y-4">
              <!-- Name -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Nama Lengkap
                </label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  class="w-full px-4 py-2 border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  :class="profileForm.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                />
                <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ profileForm.errors.name }}
                </p>
              </div>

              <!-- Username -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Username
                </label>
                <div class="relative">
                  <User class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 dark:text-gray-500" />
                  <input
                    v-model="profileForm.username"
                    type="text"
                    class="w-full pl-10 pr-4 py-2 border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    :class="profileForm.errors.username ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                  />
                </div>
                <p v-if="profileForm.errors.username" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ profileForm.errors.username }}
                </p>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  @click="profileForm.reset()"
                  class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                  Reset
                </button>
                <button
                  type="submit"
                  :disabled="profileForm.processing"
                  class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed inline-flex items-center gap-2"
                >
                  <Save class="w-4 h-4" />
                  {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </div>

              <!-- Success Message -->
              <div v-if="profileForm.recentlySuccessful" class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Profil berhasil diperbarui!
              </div>
            </form>
          </div>

          <!-- Info untuk User Biasa (Read-only) -->
          <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 border border-transparent dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <User class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Informasi Profil</h2>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Informasi akun Anda</p>
            </div>

            <div class="p-6 space-y-4">
              <!-- Name (Read-only) -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Nama Lengkap
                </label>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200">
                  {{ user.name }}
                </div>
              </div>

              <!-- Username (Read-only) -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Username
                </label>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200">
                  @{{ user.username }}
                </div>
              </div>

              <!-- Info Box -->
              <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                  <p class="font-semibold mb-1">Informasi</p>
                  <p>Hanya admin yang dapat mengubah informasi profil. Hubungi admin jika perlu melakukan perubahan.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Update Password (HANYA ADMIN) -->
          <div v-if="isAdmin" class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 border border-transparent dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <Key class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Ubah Password</h2>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Pastikan akun Anda menggunakan password yang kuat</p>
            </div>

            <form @submit.prevent="updatePassword" class="p-6 space-y-4">
              <!-- Current Password -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Password Saat Ini
                </label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  autocomplete="current-password"
                  class="w-full px-4 py-2 border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  :class="passwordForm.errors.current_password ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                />
                <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ passwordForm.errors.current_password }}
                </p>
              </div>

              <!-- New Password -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Password Baru
                </label>
                <input
                  v-model="passwordForm.password"
                  type="password"
                  autocomplete="new-password"
                  class="w-full px-4 py-2 border dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                  :class="passwordForm.errors.password ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
                />
                <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
                  {{ passwordForm.errors.password }}
                </p>
              </div>

              <!-- Confirm Password -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Konfirmasi Password Baru
                </label>
                <input
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  autocomplete="new-password"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                />
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  @click="passwordForm.reset()"
                  class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                  Reset
                </button>
                <button
                  type="submit"
                  :disabled="passwordForm.processing"
                  class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed inline-flex items-center gap-2"
                >
                  <Key class="w-4 h-4" />
                  {{ passwordForm.processing ? 'Mengubah...' : 'Ubah Password' }}
                </button>
              </div>

              <!-- Success Message -->
              <div v-if="passwordForm.recentlySuccessful" class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Password berhasil diubah!
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </DSMLayout>
</template>
