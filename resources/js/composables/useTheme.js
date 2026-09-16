import { ref } from 'vue'

const isDark = ref(false)

function applyTheme(dark) {
    isDark.value = dark

    document.documentElement.classList.toggle('dark', dark)

    localStorage.setItem(
        'theme',
        dark ? 'dark' : 'light'
    )
}

function initializeTheme() {
    const savedTheme = localStorage.getItem('theme')

    if (savedTheme === 'dark') {
        applyTheme(true)
        return
    }

    if (savedTheme === 'light') {
        applyTheme(false)
        return
    }

    const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)'
    ).matches

    applyTheme(prefersDark)
}

function toggleTheme() {
    applyTheme(!isDark.value)
}

export function useTheme() {
    return {
        isDark,
        initializeTheme,
        toggleTheme,
    }
}
