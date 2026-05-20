<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    gewonnen: { type: Object, required: true },
    verloren:  { type: Object, required: true },
})

const maxGewonnen = computed(() => props.gewonnen.top_redenen[0]?.aantal ?? 1)
const maxVerloren = computed(() => props.verloren.top_redenen[0]?.aantal ?? 1)

function barWidth(aantal, max) {
    return Math.max(4, Math.round((aantal / max) * 100)) + '%'
}

function formatBedrag(val) {
    if (!val) return '—'
    return '€ ' + Number(val).toLocaleString('nl-NL', { minimumFractionDigits: 2 })
}
</script>

<template>
    <Head title="Redenen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 min-w-0">
                <a :href="route('dashboard')" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0">Dashboard</a>
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-sm font-semibold text-gray-800 dark:text-white">Redenen</span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-6xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- GEWONNEN -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Gewonnen</h3>
                            <span class="ml-auto text-2xl font-bold text-green-600 dark:text-green-400">{{ gewonnen.totaal }}</span>
                        </div>

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-3">
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Top redenen</p>
                            <div v-if="gewonnen.top_redenen.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-2">
                                Nog geen redenen vastgelegd.
                            </div>
                            <div v-for="(item, i) in gewonnen.top_redenen" :key="i" class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-200 truncate max-w-[80%]" :title="item.reden">
                                        <svg v-if="i === 0" class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ item.reden }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-400 dark:text-gray-500 shrink-0 ml-2">{{ item.aantal }}×</span>
                                </div>
                                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-400 dark:bg-green-500 rounded-full transition-all" :style="{ width: barWidth(item.aantal, maxGewonnen) }"/>
                                </div>
                            </div>
                        </div>

                        <!-- Recent -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Recent</p>
                            </div>
                            <div v-if="gewonnen.recent.length === 0" class="text-sm text-gray-400 dark:text-gray-500 px-5 py-4">Geen entries.</div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="entry in gewonnen.recent" :key="entry.id" class="px-5 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <Link
                                                :href="route('quotes.edit', entry.quote.id)"
                                                class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block transition-colors"
                                            >
                                                <span class="font-mono text-xs text-blue-600 dark:text-blue-400 mr-1">{{ entry.quote.offerte_nummer }}</span>{{ entry.quote.titel }}
                                            </Link>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                        </div>
                                        <span class="text-sm font-medium text-green-600 dark:text-green-400 shrink-0 font-mono whitespace-nowrap">{{ formatBedrag(entry.quote.totaal) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 italic">{{ entry.reden }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VERLOREN -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Verloren</h3>
                            <span class="ml-auto text-2xl font-bold text-red-500 dark:text-red-400">{{ verloren.totaal }}</span>
                        </div>

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 space-y-3">
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Top redenen</p>
                            <div v-if="verloren.top_redenen.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-2">
                                Nog geen redenen vastgelegd.
                            </div>
                            <div v-for="(item, i) in verloren.top_redenen" :key="i" class="space-y-1.5">
                                <div class="flex justify-between items-center">
                                    <span class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-200 truncate max-w-[80%]" :title="item.reden">
                                        <svg v-if="i === 0" class="w-3.5 h-3.5 text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ item.reden }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-400 dark:text-gray-500 shrink-0 ml-2">{{ item.aantal }}×</span>
                                </div>
                                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-400 dark:bg-red-500 rounded-full transition-all" :style="{ width: barWidth(item.aantal, maxVerloren) }"/>
                                </div>
                            </div>
                        </div>

                        <!-- Recent -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Recent</p>
                            </div>
                            <div v-if="verloren.recent.length === 0" class="text-sm text-gray-400 dark:text-gray-500 px-5 py-4">Geen entries.</div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="entry in verloren.recent" :key="entry.id" class="px-5 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <Link
                                                :href="route('quotes.edit', entry.quote.id)"
                                                class="text-sm font-medium text-gray-800 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate block transition-colors"
                                            >
                                                <span class="font-mono text-xs text-blue-600 dark:text-blue-400 mr-1">{{ entry.quote.offerte_nummer }}</span>{{ entry.quote.titel }}
                                            </Link>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                        </div>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 shrink-0 font-mono whitespace-nowrap">{{ formatBedrag(entry.quote.totaal) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 italic">{{ entry.reden }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
