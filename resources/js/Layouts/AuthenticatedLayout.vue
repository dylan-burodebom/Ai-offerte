<script setup>
import { ref, onMounted } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link } from '@inertiajs/vue3'
import { useDarkMode } from '@/composables/useDarkMode'

const showingNavigationDropdown = ref(false)
const { dark, toggle, init } = useDarkMode()
onMounted(init)
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">

                    <!-- Logo + navigatie -->
                    <div class="flex items-center space-x-8">
                        <Link :href="route('dashboard')" class="font-black text-xl tracking-tight shrink-0 text-gray-900 dark:text-white">
                            buro<span class="text-blue-500">_</span>deBom
                            <span class="ml-1 text-sm font-normal text-gray-400 dark:text-gray-500">offertes</span>
                        </Link>

                        <div class="hidden sm:flex items-center space-x-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('clients.index')" :active="route().current('clients.*')">
                                Bedrijven
                            </NavLink>
                            <NavLink :href="route('quotes.index')" :active="route().current('quotes.*')">
                                Offertes
                            </NavLink>
                            <NavLink
                                v-if="$page.props.auth.isAdmin"
                                :href="route('admin.prompts')"
                                :active="route().current('admin.*')"
                            >
                                Prompts
                            </NavLink>
                        </div>
                    </div>

                    <!-- Rechts: dark toggle + gebruiker dropdown + nieuwe offerte -->
                    <div class="hidden sm:flex items-center gap-3">

                        <!-- Dark mode toggle -->
                        <button
                            type="button"
                            @click="toggle"
                            class="p-2 rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :title="dark ? 'Lichte modus' : 'Donkere modus'"
                        >
                            <!-- Sun -->
                            <svg v-if="dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z" />
                            </svg>
                            <!-- Moon -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                            </svg>
                        </button>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition">
                                    {{ $page.props.auth.user.name }}
                                    <svg class="inline-block ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profiel</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Uitloggen</DropdownLink>
                            </template>
                        </Dropdown>

                        <Link
                            :href="route('quotes.create')"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"
                        >
                            + Nieuwe offerte
                        </Link>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center gap-2 sm:hidden">
                        <button
                            type="button"
                            @click="toggle"
                            class="p-2 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        >
                            <svg v-if="dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                            </svg>
                        </button>
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 focus:outline-none"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Responsive menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('clients.index')" :active="route().current('clients.*')">Bedrijven</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('quotes.index')" :active="route().current('quotes.*')">Offertes</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.isAdmin" :href="route('admin.prompts')" :active="route().current('admin.*')">Prompts</ResponsiveNavLink>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pb-1 pt-4">
                        <div class="px-4 text-base font-medium text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                        <div class="px-4 text-sm text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profiel</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Uitloggen</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Paginakop -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-6 py-5">
                    <slot name="header" />
                </div>
            </header>

            <!-- Inhoud -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
