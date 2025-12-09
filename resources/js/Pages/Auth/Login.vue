<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Eye, EyeOff } from 'lucide-vue-next'

const showPassword = ref(false)

const form = useForm({
  username: '',
  password: '',
  remember: false,
})

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Login" />
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-lg">
      <!-- Logo di dalam card -->
      <div class="flex justify-center mb-6">
        <img 
          src="/images/kppdsm.svg" 
          alt="KPP Mining Logo" 
          class="h-16 w-auto"
        />
      </div>

      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 text-center">Login</h2>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Username -->
        <div>
          <label for="username" class="block font-medium text-sm text-gray-700">
            Username
          </label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            class="border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm mt-1 block w-full"
            required
            autofocus
            autocomplete="username"
          />
          <div v-if="form.errors.username" class="text-sm text-red-600 mt-2">
            {{ form.errors.username }}
          </div>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block font-medium text-sm text-gray-700">
            Password
          </label>
          <div class="relative mt-1">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              class="border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm block w-full pr-10"
              required
              autocomplete="current-password"
            />
            <button
              type="button"
              @click="togglePasswordVisibility"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
              tabindex="-1"
            >
              <Eye v-if="!showPassword" class="h-5 w-5" />
              <EyeOff v-else class="h-5 w-5" />
            </button>
          </div>
          <div v-if="form.errors.password" class="text-sm text-red-600 mt-2">
            {{ form.errors.password }}
          </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
          />
          <label for="remember" class="ml-2 block text-sm text-gray-900">
            Ingat saya
          </label>
        </div>

        <!-- Submit Button dengan warna hijau KPP -->
        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
          >
            {{ form.processing ? 'Loading...' : 'Log in' }}
          </button>
        </div>
      </form>
    </div>

    
  </div>
</template>
