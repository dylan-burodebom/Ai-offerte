<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    gewonnen: { type: Object, required: true },
    verloren:  { type: Object, required: true },
})

const totaalAfgesloten = computed(() => props.gewonnen.totaal + props.verloren.totaal)
const winRate = computed(() =>
    totaalAfgesloten.value > 0
        ? Math.round(props.gewonnen.totaal / totaalAfgesloten.value * 100)
        : null
)

const circumference = 2 * Math.PI * 20   // r=20
const strokeOffset  = computed(() =>
    winRate.value !== null
        ? circumference * (1 - winRate.value / 100)
        : circumference
)

const maxGewonnen = computed(() => props.gewonnen.top_redenen[0]?.aantal ?? 1)
const maxVerloren = computed(() => props.verloren.top_redenen[0]?.aantal ?? 1)

function barPct(aantal, max) {
    return Math.max(4, Math.round((aantal / max) * 100))
}

function pct(aantal, max) {
    return Math.round((aantal / max) * 100)
}

function fmt(val) {
    if (!val) return '—'
    return '€ ' + Number(val).toLocaleString('nl-NL', { minimumFractionDigits: 2 })
}

function rank(i) {
    return String(i + 1).padStart(2, '0')
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
                <span class="text-sm font-semibold text-gray-800 dark:text-white">Redenen analyse</span>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto px-6 space-y-7">

                <!-- ── KPI-balk ─────────────────────────────────────────────── -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                    <!-- Conversieratio -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 flex items-center gap-5">
                        <div class="relative w-16 h-16 shrink-0">
                            <svg viewBox="0 0 48 48" class="w-16 h-16 -rotate-90">
                                <circle cx="24" cy="24" r="20" fill="none" stroke="currentColor" class="text-gray-100 dark:text-gray-700" stroke-width="4.5"/>
                                <circle
                                    cx="24" cy="24" r="20" fill="none"
                                    class="text-green-500"
                                    stroke="currentColor"
                                    stroke-width="4.5"
                                    stroke-linecap="round"
                                    :stroke-dasharray="circumference"
                                    :stroke-dashoffset="strokeOffset"
                                    style="transition: stroke-dashoffset .6s ease"
                                />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-sm font-bold text-gray-900 dark:text-white rotate-0">
                                {{ winRate !== null ? winRate + '%' : '—' }}
            </span>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Conversieratio</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">
                                {{ winRate !== null ? winRate + '%' : 'Geen data' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                van {{ totaalAfgesloten }} afgesloten
                            </p>
                        </div>
                    </div>

                    <!-- Gewonnen -->
                    <div class="relative bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/10 rounded-2xl border border-green-200 dark:border-green-800/60 p-6 overflow-hidden">
                        <div class="absolute right-4 top-4 w-16 h-16 rounded-full bg-green-100 dark:bg-green-800/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-[11px] font-bold text-green-600 dark:text-green-400 uppercase tracking-widest">Gewonnen</p>
                        <p class="text-4xl font-black text-green-700 dark:text-green-300 mt-2 leading-none">{{ gewonnen.totaal }}</p>
                        <p class="text-xs text-green-500 dark:text-green-500 mt-2">offertes gewonnen</p>
                    </div>

                    <!-- Verloren -->
                    <div class="relative bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/10 rounded-2xl border border-red-200 dark:border-red-800/60 p-6 overflow-hidden">
                        <div class="absolute right-4 top-4 w-16 h-16 rounded-full bg-red-100 dark:bg-red-800/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-400 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-[11px] font-bold text-red-500 dark:text-red-400 uppercase tracking-widest">Verloren</p>
                        <p class="text-4xl font-black text-red-600 dark:text-red-300 mt-2 leading-none">{{ verloren.totaal }}</p>
                        <p class="text-xs text-red-400 dark:text-red-500 mt-2">offertes verloren</p>
                    </div>

                </div>

                <!-- ── Twee kolommen ────────────────────────────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- GEWONNEN kolom -->
                    <div class="space-y-5">

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-1.5 h-5 rounded-full bg-green-500 shrink-0"></div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top redenen gewonnen</h3>
                            </div>
                            <div v-if="gewonnen.top_redenen.length === 0" class="flex flex-col items-center py-8 gap-2">
                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                                </div>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Nog geen redenen vastgelegd</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="(item, i) in gewonnen.top_redenen" :key="i">
                                    <div class="flex items-center gap-3 mb-1.5">
                                        <span class="text-[11px] font-black tabular-nums text-gray-300 dark:text-gray-600 w-5 shrink-0">{{ rank(i) }}</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-200 flex-1 min-w-0 truncate" :title="item.reden">{{ item.reden }}</span>
                                        <span class="text-xs font-semibold text-green-600 dark:text-green-400 shrink-0 tabular-nums">{{ item.aantal }}×</span>
                                        <span class="text-[11px] text-gray-400 dark:text-gray-500 shrink-0 w-9 text-right tabular-nums">{{ pct(item.aantal, maxGewonnen) }}%</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden ml-8">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="i === 0 ? 'bg-green-500' : 'bg-green-300 dark:bg-green-600'"
                                            :style="{ width: barPct(item.aantal, maxGewonnen) + '%' }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recente gewonnen -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent gewonnen</h3>
                                <span v-if="gewonnen.recent.length" class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 font-medium">{{ gewonnen.recent.length }}</span>
                            </div>
                            <div v-if="gewonnen.recent.length === 0" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Geen entries.
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="entry in gewonnen.recent" :key="entry.id" class="group px-6 py-4 hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <Link
                                                :href="route('quotes.edit', entry.quote.id)"
                                                class="flex items-baseline gap-1.5 group/link"
                                            >
                                                <span class="font-mono text-[11px] font-semibold text-green-600 dark:text-green-400 shrink-0">{{ entry.quote.offerte_nummer }}</span>
                                                <span class="text-sm font-medium text-gray-800 dark:text-white group-hover/link:text-green-600 dark:group-hover/link:text-green-400 truncate transition-colors">{{ entry.quote.titel }}</span>
                                            </Link>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                        </div>
                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400 shrink-0 tabular-nums">{{ fmt(entry.quote.totaal) }}</span>
                                    </div>
                                    <p v-if="entry.reden" class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 italic leading-relaxed line-clamp-2">"{{ entry.reden }}"</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- VERLOREN kolom -->
                    <div class="space-y-5">

                        <!-- Top redenen -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-1.5 h-5 rounded-full bg-red-400 shrink-0"></div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top redenen verloren</h3>
                            </div>
                            <div v-if="verloren.top_redenen.length === 0" class="flex flex-col items-center py-8 gap-2">
                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
                                </div>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Nog geen redenen vastgelegd</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="(item, i) in verloren.top_redenen" :key="i">
                                    <div class="flex items-center gap-3 mb-1.5">
                                        <span class="text-[11px] font-black tabular-nums text-gray-300 dark:text-gray-600 w-5 shrink-0">{{ rank(i) }}</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-200 flex-1 min-w-0 truncate" :title="item.reden">{{ item.reden }}</span>
                                        <span class="text-xs font-semibold text-red-500 dark:text-red-400 shrink-0 tabular-nums">{{ item.aantal }}×</span>
                                        <span class="text-[11px] text-gray-400 dark:text-gray-500 shrink-0 w-9 text-right tabular-nums">{{ pct(item.aantal, maxVerloren) }}%</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden ml-8">
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="i === 0 ? 'bg-red-400' : 'bg-red-200 dark:bg-red-700'"
                                            :style="{ width: barPct(item.aantal, maxVerloren) + '%' }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recente verloren -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent verloren</h3>
                                <span v-if="verloren.recent.length" class="text-xs px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-500 dark:text-red-400 font-medium">{{ verloren.recent.length }}</span>
                            </div>
                            <div v-if="verloren.recent.length === 0" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Geen entries.
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div v-for="entry in verloren.recent" :key="entry.id" class="group px-6 py-4 hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <Link
                                                :href="route('quotes.edit', entry.quote.id)"
                                                class="flex items-baseline gap-1.5 group/link"
                                            >
                                                <span class="font-mono text-[11px] font-semibold text-blue-500 dark:text-blue-400 shrink-0">{{ entry.quote.offerte_nummer }}</span>
                                                <span class="text-sm font-medium text-gray-800 dark:text-white group-hover/link:text-blue-600 dark:group-hover/link:text-blue-400 truncate transition-colors">{{ entry.quote.titel }}</span>
                                            </Link>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ entry.quote.client?.naam }}</p>
                                        </div>
                                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500 shrink-0 tabular-nums">{{ fmt(entry.quote.totaal) }}</span>
                                    </div>
                                    <p v-if="entry.reden" class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 italic leading-relaxed line-clamp-2">"{{ entry.reden }}"</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
