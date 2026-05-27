<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useDarkMode } from '@/composables/useDarkMode'

const { dark, toggle, init } = useDarkMode()
onMounted(init)

const dropdownOpen = ref(false)
const avatarError  = ref(false)
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">

        <!-- Top bar -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 h-14 flex items-center px-6 shrink-0">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <span class="font-black text-[18px] tracking-tight text-gray-900 dark:text-white leading-none">
                    AI<span class="text-blue-500">_</span>offerte
                </span>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <slot name="title">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Mijn offertes</span>
                </slot>
            </div>

            <!-- Right: dark mode + avatar -->
            <div class="flex items-center gap-3 shrink-0 relative">
                <button
                    type="button"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    @click="toggle"
                    :title="dark ? 'Lichte modus' : 'Donkere modus'"
                >
                    <svg v-if="dark" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </button>

                <!-- Avatar dropdown -->
                <div v-if="dropdownOpen" class="fixed inset-0 z-40" @click="dropdownOpen = false" />
                <div
                    v-if="dropdownOpen"
                    class="absolute right-0 top-full mt-2 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-xl overflow-hidden z-50"
                >
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white leading-tight">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 leading-tight mt-0.5">Klant</p>
                    </div>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        @click="dropdownOpen = false"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Uitloggen
                    </Link>
                </div>

                <button
                    type="button"
                    class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    @click="dropdownOpen = !dropdownOpen"
                >
                    <img
                        v-if="$page.props.auth.user.avatar_url && !avatarError"
                        :src="$page.props.auth.user.avatar_url"
                        class="w-7 h-7 rounded-full object-cover shrink-0"
                        alt=""
                        @error="avatarError = true"
                    />
                    <div
                        v-else
                        class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-violet-500 flex items-center justify-center text-white text-xs font-bold shrink-0"
                    >
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 hidden sm:block">{{ $page.props.auth.user.name }}</span>
                </button>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1">
            <slot />
        </main>
    </div>
</template>
