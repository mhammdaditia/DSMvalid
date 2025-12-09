import { ref, watch, onMounted } from 'vue'

export function useTheme() {
  const theme = ref(localStorage.getItem('theme') || 'system')

  const applyTheme = (newTheme) => {
    const root = document.documentElement
    
    if (newTheme === 'dark') {
      root.classList.add('dark')
    } else if (newTheme === 'light') {
      root.classList.remove('dark')
    } else {
      // System preference
      if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        root.classList.add('dark')
      } else {
        root.classList.remove('dark')
      }
    }
  }

  const setTheme = (newTheme) => {
    theme.value = newTheme
    localStorage.setItem('theme', newTheme)
    applyTheme(newTheme)
  }

  // Listen to system theme changes
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  const handleSystemThemeChange = () => {
    if (theme.value === 'system') {
      applyTheme('system')
    }
  }

  onMounted(() => {
    applyTheme(theme.value)
    mediaQuery.addEventListener('change', handleSystemThemeChange)
  })

  return {
    theme,
    setTheme,
  }
}
