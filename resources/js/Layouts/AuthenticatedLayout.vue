<script setup>
import { ref } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import { Link } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50">
            <nav class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">

                    <!-- Logo + navigatie -->
                    <div class="flex items-center space-x-8">
                        <Link :href="route('dashboard')" class="font-black text-xl tracking-tight shrink-0">
                            buro<span class="text-blue-500">_</span>deBom
                            <span class="ml-1 text-sm font-normal text-gray-400">offertes</span>
                        </Link>

                        <div class="hidden sm:flex items-center space-x-1">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('clients.index')" :active="route().current('clients.*')">
                                Klanten
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

                    <!-- Rechts: gebruiker dropdown + nieuwe offerte -->
                    <div class="hidden sm:flex items-center gap-3">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-md transition">
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
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
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
                        <ResponsiveNavLink :href="route('clients.index')" :active="route().current('clients.*')">Klanten</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('quotes.index')" :active="route().current('quotes.*')">Offertes</ResponsiveNavLink>
                        <ResponsiveNavLink v-if="$page.props.auth.isAdmin" :href="route('admin.prompts')" :active="route().current('admin.*')">Prompts</ResponsiveNavLink>
                    </div>
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4 text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="px-4 text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Profiel</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Uitloggen</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Paginakop -->
            <header v-if="$slots.header" class="bg-white shadow-sm border-b border-gray-200">
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
