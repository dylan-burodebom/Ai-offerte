import { ref } from 'vue'

const dark = ref(false)

function init() {
    const stored = localStorage.getItem('theme')
    dark.value = stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)
    document.documentElement.classList.toggle('dark', dark.value)
}

function toggle() {
    dark.value = !dark.value
    document.documentElement.classList.toggle('dark', dark.value)
    localStorage.setItem('theme', dark.value ? 'dark' : 'light')
}

export function useDarkMode() {
    return { dark, toggle, init }
}
