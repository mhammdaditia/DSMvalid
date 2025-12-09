<script setup>
import DSMLayout from '@/Layouts/DSMLayout.vue'

const props = defineProps({
  htmlUrl: String,
  pdfUrl: String,
})
</script>

<template>
  <div>
    <h1 class="text-xl font-semibold mb-4">Report DSM</h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
      <p class="text-sm text-gray-700 dark:text-gray-300">
        Klik tombol di bawah untuk membuka report di SSRS atau download PDF.
      </p>

      <div class="space-x-3">
        <!-- Buka halaman report (HTML) di tab baru -->
        <a
          :href="htmlUrl"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-md"
        >
          Buka Report (HTML)
        </a>

        <!-- Download / buka PDF di tab baru -->
        <a
          :href="pdfUrl"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-md"
        >
          Download PDF
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  layout: DSMLayout,
}
</script>
