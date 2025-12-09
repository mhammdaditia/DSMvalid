import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

const appName = import.meta.env.VITE_APP_NAME || 'Valid DSM'

createInertiaApp({
  title: title => title ? `${title} – ${appName}` : appName,
  resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
    
    // Global error handler
    app.config.errorHandler = (err, instance, info) => {
      console.error('Vue Error:', err)
      console.error('Error Info:', info)
    }

    // Make axios available globally
    app.config.globalProperties.$axios = window.axios
    
    // Make Echo available globally (untuk debugging)
    app.config.globalProperties.$echo = window.Echo

    app.mount(el)
    return app
  },
  progress: { color: '#10b981' },
})

// Log Echo status (Echo sudah di-setup di bootstrap.js)
if (window.Echo) {
  console.log('✅ Laravel Echo available globally')
  console.log('📡 Echo connector:', window.Echo.connector.name)
} else {
  console.error('❌ Laravel Echo not available! Check bootstrap.js')
}

// Request notification permission on page load
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission().then(permission => {
    console.log('🔔 Notification permission:', permission)
  })
}
