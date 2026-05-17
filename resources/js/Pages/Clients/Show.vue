<script setup>
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  client: { type: Object, required: true },
  quotes: { type: Array, default: () => [] },
})

const fmt = (v) => v
  ? new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v)
  : '—'

function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

const statusTelling = (status) => props.quotes.filter(q => q.status === status).length

const omzetGewonnen = props.quotes
  .filter(q => q.status === 'gewonnen')
  .reduce((s, q) => s + (parseFloat(q.totaal) || 0), 0)
</script>

<template>
  <Head :title="client.naam" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a
            :href="route('clients.index')"
            class="text-gray-400 hover:text-gray-600 transition-colors text-sm"
          >← Klanten</a>
          <span class="text-gray-300">/</span>
          <h2 class="text-xl font-semibold text-gray-800">{{ client.naam }}</h2>
          <span v-if="client.sector" class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700">
            {{ client.sector }}
          </span>
        </div>
        <a
          :href="route('quotes.create')"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
        >
          + Nieuwe offerte
        </a>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Klant info -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Klantgegevens</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div v-if="client.contactpersoon">
              <p class="text-xs text-gray-400 mb-0.5">Contactpersoon</p>
              <p class="text-gray-800 font-medium">{{ client.contactpersoon }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 mb-0.5">E-mail</p>
              <a :href="`mailto:${client.email}`" class="text-blue-600 hover:underline">{{ client.email }}</a>
            </div>
            <div v-if="client.telefoon">
              <p class="text-xs text-gray-400 mb-0.5">Telefoon</p>
              <a :href="`tel:${client.telefoon}`" class="text-gray-800">{{ client.telefoon }}</a>
            </div>
            <div v-if="client.adres || client.postcode || client.stad">
              <p class="text-xs text-gray-400 mb-0.5">Adres</p>
              <p class="text-gray-800">
                {{ [client.adres, client.postcode, client.stad].filter(Boolean).join(', ') }}
              </p>
            </div>
          </div>

          <div v-if="client.beschrijving" class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 mb-1">Beschrijving</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ client.beschrijving }}</p>
          </div>
        </div>

        <!-- Statistieken -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700">{{ quotes.length }}</p>
            <p class="text-xs text-gray-400 mt-1">Totaal offertes</p>
          </div>
          <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ statusTelling('concept') + statusTelling('verzonden') }}</p>
            <p class="text-xs text-gray-400 mt-1">Openstaand</p>
          </div>
          <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ statusTelling('gewonnen') }}</p>
            <p class="text-xs text-gray-400 mt-1">Gewonnen</p>
          </div>
          <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700">
              {{ new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(omzetGewonnen) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Omzet gewonnen</p>
          </div>
        </div>

        <!-- Offertes tabel -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
              Offertes
              <span class="ml-1 text-gray-400 font-normal">({{ quotes.length }})</span>
            </h3>
          </div>

          <div v-if="quotes.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">
            Nog geen offertes voor deze klant.
          </div>

          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nummer</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Titel</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Bedrag</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Geldig tot</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Datum</th>
                <th class="px-5 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="q in quotes"
                :key="q.id"
                class="hover:bg-gray-50 cursor-pointer transition-colors"
                @click="router.visit(route('quotes.edit', q.id))"
              >
                <td class="px-5 py-3 font-mono text-xs text-blue-700 font-medium whitespace-nowrap">
                  {{ q.offerte_nummer }}
                </td>
                <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ q.titel }}</td>
                <td class="px-5 py-3">
                  <StatusBadge :status="q.status" />
                </td>
                <td class="px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ fmt(q.totaal) }}</td>
                <td class="px-5 py-3 text-right text-gray-400 text-xs whitespace-nowrap">{{ fmtDatum(q.geldig_tot) }}</td>
                <td class="px-5 py-3 text-right text-gray-400 text-xs whitespace-nowrap">{{ fmtDatum(q.created_at) }}</td>
                <td class="px-5 py-3 text-right">
                  <button
                    type="button"
                    class="text-xs text-blue-500 hover:text-blue-700 font-medium whitespace-nowrap"
                    @click.stop="router.visit(route('quotes.edit', q.id))"
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
