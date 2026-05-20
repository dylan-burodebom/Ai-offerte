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
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
                    @click="router.visit(route('dashboard'))"
                >
                    ← Dashboard
                </button>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Gewonnen &amp; Verloren — Redenen</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- GEWONNEN -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gewonnen</h3>
                            <span class="ml-auto text-2xl font-bold text-green-600 dark:text-green-400">{{ gewonnen.totaal }}</span>
                        </div>

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Top redenen</p>

                            <div v-if="gewonnen.top_redenen.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-2">
                                Nog geen redenen vastgelegd.
                            </div>

                            <div v-for="(item, i) in gewonnen.top_redenen" :key="i" class="space-y-1">
                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300">
                                    <span class="truncate max-w-[80%]" :title="item.reden">
                                        <span v-if="i === 0" class="mr-1 text-green-500 font-bold">★</span>
                                        {{ item.reden }}
                                    </span>
                                    <span class="font-semibold shrink-0 ml-2">{{ item.aantal }}×</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-green-400 rounded-full transition-all"
                                        :style="{ width: barWidth(item.aantal, maxGewonnen) }"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Recent -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide px-5 pt-4 pb-2">Recent</p>
                            <div v-if="gewonnen.recent.length === 0" class="text-sm text-gray-400 dark:text-gray-500 px-5 pb-4">Geen entries.</div>
                            <div
                                v-for="entry in gewonnen.recent"
                                :key="entry.id"
                                class="px-5 py-3 border-t border-gray-50 dark:border-gray-700 first:border-t-0"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <Link
                                            :href="route('quotes.edit', entry.quote.id)"
                                            class="text-sm font-medium text-gray-800 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate block"
                                        >
                                            {{ entry.quote.offerte_nummer }} — {{ entry.quote.titel }}
                                        </Link>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                    </div>
                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium shrink-0">{{ formatBedrag(entry.quote.totaal) }}</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ entry.reden }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- VERLOREN -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-red-400 shrink-0"></span>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Verloren</h3>
                            <span class="ml-auto text-2xl font-bold text-red-500 dark:text-red-400">{{ verloren.totaal }}</span>
                        </div>

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-3">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Top redenen</p>

                            <div v-if="verloren.top_redenen.length === 0" class="text-sm text-gray-400 dark:text-gray-500 py-2">
                                Nog geen redenen vastgelegd.
                            </div>

                            <div v-for="(item, i) in verloren.top_redenen" :key="i" class="space-y-1">
                                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300">
                                    <span class="truncate max-w-[80%]" :title="item.reden">
                                        <span v-if="i === 0" class="mr-1 text-red-400 font-bold">★</span>
                                        {{ item.reden }}
                                    </span>
                                    <span class="font-semibold shrink-0 ml-2">{{ item.aantal }}×</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-red-400 rounded-full transition-all"
                                        :style="{ width: barWidth(item.aantal, maxVerloren) }"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Recent -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide px-5 pt-4 pb-2">Recent</p>
                            <div v-if="verloren.recent.length === 0" class="text-sm text-gray-400 dark:text-gray-500 px-5 pb-4">Geen entries.</div>
                            <div
                                v-for="entry in verloren.recent"
                                :key="entry.id"
                                class="px-5 py-3 border-t border-gray-50 dark:border-gray-700 first:border-t-0"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <Link
                                            :href="route('quotes.edit', entry.quote.id)"
                                            class="text-sm font-medium text-gray-800 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 truncate block"
                                        >
                                            {{ entry.quote.offerte_nummer }} — {{ entry.quote.titel }}
                                        </Link>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 font-medium shrink-0">{{ formatBedrag(entry.quote.totaal) }}</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ entry.reden }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
