<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import VueApexCharts from 'vue3-apexcharts'
import { useDarkMode } from '@/composables/useDarkMode'

const props = defineProps({
  initialStats: { type: Object, required: true },
  jarigen:      { type: Array, default: () => [] },
})

const PERIODES = [
  { key: 'maand',    label: 'Deze maand' },
  { key: 'kwartaal', label: 'Dit kwartaal' },
  { key: 'jaar',     label: 'Dit jaar' },
  { key: 'alles',    label: 'Alles' },
]

const periode  = ref('jaar')
const stats    = ref(props.initialStats)
const loading  = ref(false)
const { dark } = useDarkMode()

const page = usePage()
const userName = computed(() => page.props.auth.user.name.split(' ')[0])

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Goedemorgen'
  if (h < 18) return 'Goedemiddag'
  return 'Goedenavond'
})

const periodeLabel = computed(() => PERIODES.find(p => p.key === periode.value)?.label ?? '')

async function laadStats(p) {
  loading.value = true
  try {
    const res = await window.axios.get(route('dashboard.stats'), { params: { periode: p } })
    if (res.data?.recente !== undefined) {
      stats.value = res.data
    }
  } catch (err) {
    console.error('[Dashboard] Stats laden mislukt:', err)
  } finally {
    loading.value = false
  }
}

watch(periode, (p) => laadStats(p))

// ── Formatters ────────────────────────────────────────────────────────────────
const fmt = (v) => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v || 0)

function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

// ── ApexCharts config ─────────────────────────────────────────────────────────
const chartOptions = computed(() => ({
  chart:   { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  colors:  ['#3b82f6', '#22c55e'],
  plotOptions: { bar: { columnWidth: '50%', borderRadius: 3 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: stats.value.perMaand.map((m) => m.maand),
    labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
    axisBorder: { show: false },
    axisTicks:  { show: false },
  },
  yaxis: {
    min: 0,
    tickAmount: 4,
    labels: { style: { fontSize: '11px', colors: '#9ca3af' } },
  },
  grid:    { borderColor: dark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
  legend:  { position: 'top', horizontalAlign: 'right', fontSize: '12px', markers: { radius: 3 }, labels: { colors: dark.value ? '#d1d5db' : '#374151' } },
  tooltip: { theme: dark.value ? 'dark' : 'light', y: { formatter: (v) => `${v} offerte${v !== 1 ? 's' : ''}` } },
}))

const chartSeries = computed(() => [
  { name: 'Aangemaakt', data: stats.value.perMaand.map((m) => m.aantal) },
  { name: 'Gewonnen',   data: stats.value.perMaand.map((m) => m.gewonnen) },
])
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-base font-semibold text-gray-800 dark:text-white">Dashboard</h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 space-y-6">

        <!-- ── Verjaardagen ───────────────────────────────────────── -->
        <div v-if="jarigen.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-5 h-5 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-1.5-.454M9 6.75V5.25A2.25 2.25 0 0111.25 3h1.5A2.25 2.25 0 0115 5.25v1.5M3 9.75h18v9a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18.75V9.75z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V4.5a.75.75 0 01.75-.75h4.5a.75.75 0 01.75.75v2.25"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <p class="text-sm font-semibold text-gray-800 dark:text-white">Vandaag jarig</p>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">{{ jarigen.length }}</span>
              </div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-3.5">
                {{ jarigen.length === 1 ? 'Een contactpersoon viert vandaag verjaardag.' : `${jarigen.length} contactpersonen vieren vandaag verjaardag.` }}
              </p>
              <div class="flex flex-wrap gap-2">
                <a
                  v-for="j in jarigen"
                  :key="j.id"
                  :href="route('clients.show', j.client_id)"
                  class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 hover:border-amber-200 dark:hover:border-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors group"
                >
                  <div class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 dark:text-amber-400 text-xs font-bold shrink-0">
                    {{ (j.naam ?? '').split(' ').filter(Boolean).map(w => w[0]).join('').substring(0, 2).toUpperCase() }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-white group-hover:text-amber-700 dark:group-hover:text-amber-400 leading-tight transition-colors">{{ j.naam }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 leading-tight">{{ j.client_naam }}</p>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Greeting + period filter ──────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ greeting }}, {{ userName }} 👋</h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Hier is wat er speelt in je offertes.</p>
          </div>
          <div class="flex items-center gap-2">
            <svg v-if="loading" class="w-4 h-4 animate-spin text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 overflow-x-auto">
              <button
                v-for="p in PERIODES"
                :key="p.key"
                type="button"
                :disabled="loading"
                class="px-2.5 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-colors disabled:opacity-50 whitespace-nowrap"
                :class="periode === p.key
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                @click="periode = p.key"
              >
                {{ p.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- ── KPI Cards ──────────────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

          <!-- Totaal offertes -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-5 flex items-start gap-2 sm:gap-4">
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Totaal offertes</p>
              <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-0.5 leading-none">{{ stats.totaal }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1.5">{{ periodeLabel }}</p>
            </div>
          </div>

          <!-- Openstaand -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-5 flex items-start gap-2 sm:gap-4">
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Openstaand</p>
              <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-0.5 leading-none">{{ stats.openstaand }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1.5">{{ stats.omzetOpenstaand ? fmt(stats.omzetOpenstaand) : `${stats.verzonden ?? 0} verzonden` }}</p>
            </div>
          </div>

          <!-- Gewonnen -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-5 flex items-start gap-2 sm:gap-4">
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Gewonnen</p>
              <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mt-0.5 leading-none">{{ stats.gewonnen }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1.5">
                {{ stats.conversie !== null ? `${stats.conversie}% conversie` : 'Geen data' }}
              </p>
            </div>
          </div>

          <!-- Omzet gewonnen -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-5 flex items-start gap-2 sm:gap-4">
            <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Omzet gewonnen</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5 leading-tight">{{ fmt(stats.omzetGewonnen) }}</p>
              <p class="text-sm mt-1.5">
                <span v-if="stats.omzetGroei !== null && stats.omzetGroei > 0" class="text-green-600 dark:text-green-400">
                  ↑ {{ stats.omzetGroei }}% t.o.v. vorig jaar
                </span>
                <span v-else-if="stats.omzetGroei !== null && stats.omzetGroei < 0" class="text-red-500 dark:text-red-400">
                  ↓ {{ Math.abs(stats.omzetGroei) }}% t.o.v. vorig jaar
                </span>
                <span v-else class="text-gray-400 dark:text-gray-500">{{ stats.verloren ?? 0 }} verloren</span>
              </p>
            </div>
          </div>
        </div>

        <!-- ── Grafiek + Conversie ────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Bar chart -->
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Offertes per maand</h3>
            </div>
            <VueApexCharts
              type="bar"
              height="220"
              :options="chartOptions"
              :series="chartSeries"
            />
          </div>

          <!-- Conversie -->
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6 flex flex-col">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Conversie</h3>

            <div class="flex-1 flex flex-col items-center justify-center gap-5">
              <div class="text-center">
                <p class="text-5xl font-bold text-green-600 dark:text-green-400">
                  {{ stats.conversie !== null ? stats.conversie + '%' : '—' }}
                </p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">gewonnen / afgesloten</p>
              </div>

              <div class="w-full space-y-2.5">
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block shrink-0" />
                    Gewonnen
                  </span>
                  <span class="font-semibold text-green-700 dark:text-green-400">{{ stats.gewonnen }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-red-400 inline-block shrink-0" />
                    Verloren
                  </span>
                  <span class="font-semibold text-red-600 dark:text-red-400">{{ stats.verloren }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-amber-400 inline-block shrink-0" />
                    Openstaand
                  </span>
                  <span class="font-semibold text-amber-700 dark:text-amber-400">{{ stats.openstaand }}</span>
                </div>
              </div>
            </div>

            <a
              :href="route('dashboard.redenen')"
              class="mt-5 w-full text-center text-sm py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-violet-400 dark:hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors block"
            >
              Bekijk redenen →
            </a>
          </div>
        </div>

        <!-- ── Recente offertes ───────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recente offertes</h3>
            <a
              :href="route('quotes.index')"
              class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
            >Bekijk alles →</a>
          </div>

          <div v-if="!stats.recente?.length" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
            Nog geen offertes. <a :href="route('quotes.create')" class="text-blue-600 dark:text-blue-400 hover:underline">Maak je eerste offerte aan.</a>
          </div>

          <div class="overflow-x-auto">
          <table v-if="stats.recente?.length" class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Nummer</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Klant</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden md:table-cell">Titel</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Bedrag</th>
                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden lg:table-cell">Geldig tot</th>
                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Acties</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="q in stats.recente"
                :key="q.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors cursor-pointer"
                @click="router.visit(route('quotes.edit', q.id))"
              >
                <td class="px-4 sm:px-6 py-3 font-mono text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">{{ q.offerte_nummer }}</td>
                <td class="px-4 sm:px-6 py-3 text-gray-700 dark:text-gray-300 hidden sm:table-cell">{{ q.client?.naam ?? '—' }}</td>
                <td class="px-4 sm:px-6 py-3 text-gray-500 dark:text-gray-400 max-w-[180px] truncate hidden md:table-cell">{{ q.titel }}</td>
                <td class="px-4 sm:px-6 py-3"><StatusBadge :status="q.status" /></td>
                <td class="px-4 sm:px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap hidden sm:table-cell">
                  {{ q.totaal ? fmt(q.totaal) : '—' }}
                </td>
                <td class="px-4 sm:px-6 py-3 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden lg:table-cell">{{ fmtDatum(q.geldig_tot) }}</td>
                <td class="px-4 sm:px-6 py-3 text-right">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                    title="Bewerken"
                    @click.stop="router.visit(route('quotes.edit', q.id))"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
