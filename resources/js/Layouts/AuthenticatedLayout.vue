<script setup>
import { onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useDarkMode } from '@/composables/useDarkMode'

const { dark, toggle, init } = useDarkMode()
onMounted(init)
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">

        <!-- ── Sidebar ────────────────────────────────────────── -->
        <aside class="fixed inset-y-0 left-0 w-52 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 flex flex-col z-30">

            <!-- Logo -->
            <div class="px-5 pt-5 pb-5">
                <Link :href="route('dashboard')" class="font-black text-lg tracking-tight text-gray-900 dark:text-white">
                    buro<span class="text-blue-500">.</span>deBom
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto pb-2">

                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 py-2.5 pr-3 text-sm font-medium transition-colors"
                    :class="route().current('dashboard')
                        ? 'pl-[17px] border-l-[3px] border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        : 'mx-2 pl-3 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </Link>

                <Link
                    :href="route('clients.index')"
                    class="flex items-center gap-3 py-2.5 pr-3 text-sm font-medium transition-colors"
                    :class="route().current('clients.*')
                        ? 'pl-[17px] border-l-[3px] border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        : 'mx-2 pl-3 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Bedrijven
                </Link>

                <Link
                    :href="route('quotes.index')"
                    class="flex items-center gap-3 py-2.5 pr-3 text-sm font-medium transition-colors"
                    :class="route().current('quotes.*')
                        ? 'pl-[17px] border-l-[3px] border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        : 'mx-2 pl-3 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Offertes
                </Link>

                <Link
                    v-if="$page.props.auth.isAdmin"
                    :href="route('admin.prompts')"
                    class="flex items-center gap-3 py-2.5 pr-3 text-sm font-medium transition-colors"
                    :class="route().current('admin.*')
                        ? 'pl-[17px] border-l-[3px] border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        : 'mx-2 pl-3 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'"
                >
                    <svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.636-6.364l.707.707M6.343 17.657l-.707.707M17.657 17.657l.707.707M12 21v-1M15.536 8.464a5 5 0 11-7.072 0"/>
                    </svg>
                    Prompts
                </Link>

            </nav>

            <!-- Bottom: user section -->
            <div class="border-t border-gray-100 dark:border-gray-700 p-3 space-y-0.5">

                <!-- Mijn profiel -->
                <Link
                    :href="route('profile.edit')"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="route().current('profile.*')
                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white'"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mijn profiel
                </Link>

                <!-- Dark mode toggle -->
                <button
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white transition-colors"
                    @click="toggle"
                >
                    <svg v-if="dark" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z"/>
                    </svg>
                    <svg v-else class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    {{ dark ? 'Lichte modus' : 'Donkere modus' }}
                </button>

                <!-- Uitloggen -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Uitloggen
                </Link>

            </div>
        </aside>

        <!-- ── Main ──────────────────────────────────────────── -->
        <div class="ml-52 flex-1 min-w-0 flex flex-col">

            <!-- Top header bar -->
            <header class="sticky top-0 z-20 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 h-14 flex items-center gap-4 px-6">
                <div class="flex-1 min-w-0">
                    <slot name="header" />
                </div>
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-2.5 shrink-0 group"
                >
                    <div class="w-8 h-8 rounded-full bg-yellow-400 flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 leading-tight">{{ $page.props.auth.isAdmin ? 'Beheerder' : 'Gebruiker' }}</p>
                    </div>
                </Link>
            </header>

            <!-- Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
