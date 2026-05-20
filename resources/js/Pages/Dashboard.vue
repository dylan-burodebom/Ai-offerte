<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
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

async function laadStats(p) {
  loading.value = true
  try {
    const res = await window.axios.get(route('dashboard.stats'), { params: { periode: p } })
    if (res.data && typeof res.data === 'object') {
      stats.value = res.data
    }
  } catch (err) {
    console.error('[Dashboard] Stats laden mislukt:', err)
  } finally {
    loading.value = false
  }
}

watch(periode, (p) => laadStats(p))

// ── KPI helpers ───────────────────────────────────────────────────────────────
const fmt = (v) => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v || 0)

const kpiCards = computed(() => [
  {
    label: 'Totaal offertes',
    value: stats.value.totaal,
    sub:   null,
    color: 'blue',
    icon:  '📄',
  },
  {
    label: 'Openstaand',
    value: stats.value.openstaand,
    sub:   `${stats.value.verzonden ?? 0} verzonden`,
    color: 'amber',
    icon:  '⏳',
  },
  {
    label: 'Gewonnen',
    value: stats.value.gewonnen,
    sub:   stats.value.conversie !== null ? `${stats.value.conversie}% conversie` : 'Geen data',
    color: 'green',
    icon:  '✓',
  },
  {
    label: 'Omzet gewonnen',
    value: fmt(stats.value.omzetGewonnen),
    sub:   `${stats.value.verloren ?? 0} verloren`,
    color: 'blue',
    icon:  '€',
    isText: true,
  },
])

const colorMap = {
  blue:  { card: 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20',   icon: 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400',   value: 'text-blue-700 dark:text-blue-400' },
  amber: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20', icon: 'bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400', value: 'text-amber-700 dark:text-amber-400' },
  green: { card: 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20', icon: 'bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400', value: 'text-green-700 dark:text-green-400' },
}

// ── ApexCharts config ─────────────────────────────────────────────────────────
const chartOptions = computed(() => ({
  chart:   { type: 'bar', toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  colors:  ['#6366f1', '#22c55e'],
  plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
  dataLabels: { enabled: false },
  xaxis: {
    categories: stats.value.perMaand.map((m) => m.maand),
    labels: { style: { fontSize: '11px', colors: dark.value ? '#9ca3af' : '#9ca3af' } },
    axisBorder: { show: false },
    axisTicks:  { show: false },
  },
  yaxis: { labels: { style: { fontSize: '11px', colors: dark.value ? '#9ca3af' : '#9ca3af' } } },
  grid:   { borderColor: dark.value ? '#374151' : '#f3f4f6', strokeDashArray: 4 },
  legend: { position: 'top', horizontalAlign: 'right', fontSize: '12px', markers: { radius: 3 }, labels: { colors: dark.value ? '#d1d5db' : '#374151' } },
  tooltip: { theme: dark.value ? 'dark' : 'light', y: { formatter: (v) => `${v} offerte${v !== 1 ? 's' : ''}` } },
}))

const chartSeries = computed(() => [
  { name: 'Aangemaakt', data: stats.value.perMaand.map((m) => m.aantal) },
  { name: 'Gewonnen',   data: stats.value.perMaand.map((m) => m.gewonnen) },
])

// ── Tabel helpers ─────────────────────────────────────────────────────────────
function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function fmtBedrag(v) {
  return v ? fmt(v) : '—'
}

function openEdit(id) {
  router.visit(route('quotes.edit', id))
}
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Dashboard</h2>

        <!-- Periode filter -->
        <div class="flex items-center gap-2">
          <svg v-if="loading" class="w-4 h-4 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button
              v-for="p in PERIODES"
              :key="p.key"
              type="button"
              :disabled="loading"
              class="px-3 py-1 rounded-md text-sm font-medium transition-colors disabled:opacity-50"
              :class="periode === p.key
                ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
              @click="periode = p.key"
            >
              {{ p.label }}
            </button>
          </div>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- ── Verjaardagen ───────────────────────────────────────── -->
        <div v-if="jarigen.length" class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 px-5 py-4">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">🎂</span>
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Vandaag jarig</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <a
              v-for="j in jarigen"
              :key="j.id"
              :href="route('clients.show', j.client_id)"
              class="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-400 text-white font-medium text-sm hover:bg-amber-500 transition-colors"
            >
              🎉 {{ j.naam }}
              <span class="opacity-80 text-xs font-normal">· {{ j.client_naam }}</span>
            </a>
          </div>
        </div>

        <!-- ── KPI Cards ──────────────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="card in kpiCards"
            :key="card.label"
            class="rounded-xl border p-5 flex items-start gap-4"
            :class="colorMap[card.color].card"
          >
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg flex-shrink-0" :class="colorMap[card.color].icon">
              {{ card.icon }}
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-0.5">{{ card.label }}</p>
              <p class="text-2xl font-bold leading-none" :class="colorMap[card.color].value">
                {{ card.value }}
              </p>
              <p v-if="card.sub" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ card.sub }}</p>
            </div>
          </div>
        </div>

        <!-- ── Grafiek + Conversie ────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Bar chart -->
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Offertes per maand (laatste 12 maanden)</h3>
            <VueApexCharts
              type="bar"
              height="220"
              :options="chartOptions"
              :series="chartSeries"
            />
          </div>

          <!-- Conversie + verloren stat -->
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6 flex flex-col justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Conversie</h3>

            <div class="flex-1 flex flex-col items-center justify-center gap-6">
              <div class="text-center">
                <p class="text-5xl font-bold text-green-600 dark:text-green-400">
                  {{ stats.conversie !== null ? stats.conversie + '%' : '—' }}
                </p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">gewonnen / afgesloten</p>
              </div>

              <div class="w-full space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block" />
                    Gewonnen
                  </span>
                  <span class="font-semibold text-green-700 dark:text-green-400">{{ stats.gewonnen }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-red-400 inline-block" />
                    Verloren
                  </span>
                  <span class="font-semibold text-red-600 dark:text-red-400">{{ stats.verloren }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                    <span class="w-2 h-2 rounded-full bg-amber-400 inline-block" />
                    Openstaand
                  </span>
                  <span class="font-semibold text-amber-700 dark:text-amber-400">{{ stats.openstaand }}</span>
                </div>
              </div>
            </div>

            <a
              :href="route('dashboard.redenen')"
              class="mt-4 w-full text-center text-xs py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-violet-400 dark:hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors block"
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
              :href="route('quotes.create')"
              class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
            >+ Nieuwe offerte</a>
          </div>

          <div v-if="stats.recente.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">
            Nog geen offertes. <a :href="route('quotes.create')" class="text-blue-600 dark:text-blue-400 hover:underline">Maak je eerste offerte aan.</a>
          </div>

          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Nummer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Klant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Titel</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Bedrag</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Geldig tot</th>
                <th class="px-6 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="q in stats.recente"
                :key="q.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors cursor-pointer"
                @click="openEdit(q.id)"
              >
                <td class="px-6 py-3 font-mono text-xs text-blue-700 dark:text-blue-400 font-medium">{{ q.offerte_nummer }}</td>
                <td class="px-6 py-3 text-gray-700 dark:text-gray-300">{{ q.client?.naam ?? '—' }}</td>
                <td class="px-6 py-3 text-gray-600 dark:text-gray-400 max-w-[200px] truncate">{{ q.titel }}</td>
                <td class="px-6 py-3">
                  <StatusBadge :status="q.status" />
                </td>
                <td class="px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300">{{ fmtBedrag(q.totaal) }}</td>
                <td class="px-6 py-3 text-right text-gray-400 dark:text-gray-500 text-xs">{{ fmtDatum(q.geldig_tot) }}</td>
                <td class="px-6 py-3 text-right">
                  <button
                    type="button"
                    class="text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium"
                    @click.stop="openEdit(q.id)"
                  >
                    Bewerken →
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
