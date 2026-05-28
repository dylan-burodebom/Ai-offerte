<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ClientForm from '@/Components/ClientForm.vue'

const props = defineProps({
  client:           { type: Object, required: true },
  quotes:           { type: Array,  default: () => [] },
  contactpersonen:  { type: Array,  default: () => [] },
  opmerkingen:      { type: Array,  default: () => [] },
  sectoren:         { type: Array,  default: () => [] },
  relatie_statussen:{ type: Array,  default: () => [] },
  rechtsvormen:     { type: Array,  default: () => [] },
  talen:            { type: Object, default: () => ({}) },
  gebruikers:       { type: Array,  default: () => [] },
})

// ── Formatters ───────────────────────────────────────────────
const fmt = (v) => v
  ? new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v)
  : '—'

function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function fmtTijd(iso) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}

function getInitials(naam) {
  return (naam ?? '').split(' ').filter(Boolean).map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

// ── Stats ────────────────────────────────────────────────────
const statusTelling = (status) => props.quotes.filter(q => q.status === status).length
const omzetGewonnen = props.quotes
  .filter(q => q.status === 'gewonnen')
  .reduce((s, q) => s + (parseFloat(q.totaal) || 0), 0)

const thisYear = new Date().getFullYear()
const omzetHuidigJaar = props.quotes
  .filter(q => q.status === 'gewonnen' && new Date(q.created_at).getFullYear() === thisYear)
  .reduce((s, q) => s + (parseFloat(q.totaal) || 0), 0)
const omzetVorigjaar = props.quotes
  .filter(q => q.status === 'gewonnen' && new Date(q.created_at).getFullYear() === thisYear - 1)
  .reduce((s, q) => s + (parseFloat(q.totaal) || 0), 0)
const omzetGroei = omzetVorigjaar > 0
  ? Math.round((omzetHuidigJaar - omzetVorigjaar) / omzetVorigjaar * 100)
  : null

// ── Edit modal ───────────────────────────────────────────────
const showEditForm = ref(false)

// ── Detail tabs ──────────────────────────────────────────────
const detailTab = ref('algemeen')
const DETAIL_TABS = [
  { key: 'algemeen',      label: 'Algemeen' },
  { key: 'bank',          label: 'Bank' },
  { key: 'administratie', label: 'Administratie' },
  { key: 'extra',         label: 'Extra' },
  { key: 'instellingen',  label: 'Instellingen' },
]

// ── Contactpersonen ──────────────────────────────────────────
const showContactForm = ref(false)
const editingContact  = ref(null)
const contactForm     = useForm({ naam: '', email: '', telefoon: '', geboortedatum: '' })

function openContactCreate() {
  editingContact.value = null
  contactForm.reset()
  showContactForm.value = true
}

function openContactEdit(c) {
  editingContact.value = c
  contactForm.naam          = c.naam          ?? ''
  contactForm.email         = c.email         ?? ''
  contactForm.telefoon      = c.telefoon      ?? ''
  contactForm.geboortedatum = c.geboortedatum ? c.geboortedatum.substring(0, 10) : ''
  showContactForm.value = true
}

function submitContact() {
  if (editingContact.value) {
    contactForm.patch(route('clients.contactpersonen.update', [props.client.id, editingContact.value.id]), {
      preserveScroll: true,
      onSuccess: () => { showContactForm.value = false },
    })
  } else {
    contactForm.post(route('clients.contactpersonen.store', props.client.id), {
      preserveScroll: true,
      onSuccess: () => { showContactForm.value = false; contactForm.reset() },
    })
  }
}

function destroyContact(c) {
  if (confirm(`Contactpersoon "${c.naam}" verwijderen?`)) {
    router.delete(route('clients.contactpersonen.destroy', [props.client.id, c.id]), { preserveScroll: true })
  }
}

// ── Opmerkingen ──────────────────────────────────────────────
const currentUserId  = usePage().props.auth.user.id
const opmerkingForm  = useForm({ tekst: '' })
const sortOrder      = ref('nieuwste')

const sortedOpmerkingen = computed(() => {
  const sorted = [...props.opmerkingen]
  return sortOrder.value === 'nieuwste'
    ? sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    : sorted.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
})

function submitOpmerking() {
  opmerkingForm.post(route('clients.opmerkingen.store', props.client.id), {
    preserveScroll: true,
    onSuccess: () => opmerkingForm.reset(),
  })
}

function destroyOpmerking(o) {
  if (confirm('Opmerking verwijderen?')) {
    router.delete(route('clients.opmerkingen.destroy', [props.client.id, o.id]), { preserveScroll: true })
  }
}

// ── Helpers ──────────────────────────────────────────────────
function taalLabel(code) {
  return props.talen[code] ?? code ?? '—'
}
</script>

<template>
  <Head :title="client.naam" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-4 w-full">
        <div class="flex items-center gap-2 min-w-0">
          <a :href="route('clients.index')" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0">Bedrijven</a>
          <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
          <h2 class="text-base font-semibold text-gray-800 dark:text-white truncate">{{ client.naam }}</h2>
          <span v-if="client.sector" class="hidden sm:inline shrink-0 text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ client.sector }}</span>
          <span
            v-if="client.relatie_status"
            class="hidden sm:inline shrink-0 text-xs px-2 py-0.5 rounded-full capitalize"
            :class="{
              'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': client.relatie_status === 'klant',
              'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': client.relatie_status === 'prospect',
              'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': client.relatie_status === 'inactief',
            }"
          >{{ client.relatie_status }}</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <a
            :href="route('quotes.create', { client_id: client.id })"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nieuwe offerte
          </a>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-5xl mx-auto px-3 sm:px-6 space-y-6">

        <!-- ── Hero card ─────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">

          <!-- Top row: avatar + naam + badges + Bewerken -->
          <div class="flex items-start gap-4 px-6 pt-6 pb-5">
            <div class="shrink-0 w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xl font-bold select-none overflow-hidden">
              <img v-if="client.logo_url" :src="client.logo_url" class="w-full h-full object-contain" alt="" />
              <span v-else>{{ getInitials(client.naam) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ client.naam }}</h1>
              <div class="flex flex-wrap gap-1.5 mt-1.5">
                <span v-if="client.sector" class="text-xs px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ client.sector }}</span>
                <span
                  v-if="client.relatie_status"
                  class="text-xs px-2.5 py-0.5 rounded-full capitalize"
                  :class="{
                    'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': client.relatie_status === 'klant',
                    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': client.relatie_status === 'prospect',
                    'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': client.relatie_status === 'inactief',
                  }"
                >{{ client.relatie_status }}</span>
                <span
                  v-for="(label, i) in (client.labels ?? [])"
                  :key="i"
                  class="text-xs px-2.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-400"
                >{{ label }}</span>
              </div>
            </div>
            <button
              type="button"
              class="shrink-0 px-3.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              @click="showEditForm = true"
            >Bewerken</button>
          </div>

          <!-- Detail tabs -->
          <div class="flex flex-wrap border-b border-gray-100 dark:border-gray-700 px-6">
            <button
              v-for="tab in DETAIL_TABS"
              :key="tab.key"
              type="button"
              class="w-1/2 sm:w-auto px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium border-b-2 transition-colors -mb-px text-center"
              :class="detailTab === tab.key
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
              @click="detailTab = tab.key"
            >{{ tab.label }}</button>
          </div>

          <!-- Tab content -->
          <div class="px-6 py-5">

            <!-- Algemeen -->
            <template v-if="detailTab === 'algemeen'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">E-mail</p>
                  <a v-if="client.email" :href="`mailto:${client.email}`" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all">{{ client.email }}</a>
                  <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Telefoon</p>
                  <a v-if="client.telefoon" :href="`tel:${client.telefoon}`" class="text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900">{{ client.telefoon }}</a>
                  <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Website</p>
                  <a v-if="client.website" :href="client.website" target="_blank" rel="noopener" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all">{{ client.website }}</a>
                  <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Adres</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ [client.adres, client.postcode, client.stad].filter(Boolean).join(', ') || '—' }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Sector</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.sector || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Relatiestatus</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ client.relatie_status || '—' }}</p>
                </div>
              </div>
              <template v-if="client.beschrijving">
                <hr class="my-5 border-gray-100 dark:border-gray-700">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Beschrijving</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ client.beschrijving }}</p>
                </div>
              </template>
            </template>

            <!-- Bank -->
            <template v-else-if="detailTab === 'bank'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Bank</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.bank || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">IBAN</p>
                  <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ client.iban || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">BIC</p>
                  <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ client.bic || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Rekeninghouder</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.rekeninghouder || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Vestigingsplaats</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.vestigingsplaats || '—' }}</p>
                </div>
              </div>
            </template>

            <!-- Administratie -->
            <template v-else-if="detailTab === 'administratie'">
              <div class="flex items-center gap-3">
                <div
                  class="w-5 h-5 rounded flex items-center justify-center shrink-0"
                  :class="client.gebruik_afwijkende_factuurgegevens ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600'"
                >
                  <svg v-if="client.gebruik_afwijkende_factuurgegevens" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Gebruik afwijkende factuurgegevens</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ client.gebruik_afwijkende_factuurgegevens ? 'Ingeschakeld' : 'Uitgeschakeld' }}</p>
                </div>
              </div>
            </template>

            <!-- Extra -->
            <template v-else-if="detailTab === 'extra'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">KvK-nummer</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.kvk_nummer || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Rechtsvorm</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.rechtsvorm || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">BTW-nummer</p>
                  <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ client.btw_nummer || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Extern ID (leverancier)</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.extern_id || '—' }}</p>
                </div>
              </div>
            </template>

            <!-- Instellingen -->
            <template v-else-if="detailTab === 'instellingen'">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Relatiebeheerder</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ client.relatiebeheerder?.name || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Voertaal</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ taalLabel(client.voertaal) || '—' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Taal voor berichten</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">{{ taalLabel(client.taal_berichten) || '—' }}</p>
                </div>
              </div>
              <template v-if="client.labels?.length">
                <hr class="my-5 border-gray-100 dark:border-gray-700">
                <div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Labels</p>
                  <div class="flex flex-wrap gap-1.5">
                    <span
                      v-for="(label, i) in client.labels"
                      :key="i"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-xs font-medium"
                    >{{ label }}</span>
                  </div>
                </div>
              </template>
            </template>

          </div>
        </div>

        <!-- ── Stat cards ─────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <p class="text-3xl font-bold text-gray-900 dark:text-white leading-none">{{ quotes.length }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Totaal offertes</p>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-3xl font-bold text-gray-900 dark:text-white leading-none">{{ statusTelling('concept') + statusTelling('verzonden') }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Openstaand</p>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-3xl font-bold text-gray-900 dark:text-white leading-none">{{ statusTelling('gewonnen') }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Gewonnen</p>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-xl font-bold text-gray-900 dark:text-white leading-none">{{ fmt(omzetGewonnen) }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Omzet gewonnen</p>
              <p v-if="omzetGroei !== null && omzetGroei > 0" class="text-xs text-green-600 dark:text-green-400 mt-0.5">↑ {{ omzetGroei }}% t.o.v. vorig jaar</p>
              <p v-else-if="omzetGroei !== null && omzetGroei < 0" class="text-xs text-red-500 dark:text-red-400 mt-0.5">↓ {{ Math.abs(omzetGroei) }}% t.o.v. vorig jaar</p>
            </div>
          </div>
        </div>

        <!-- ── Contactpersonen ────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Contactpersonen
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ contactpersonen.length }})</span>
            </h3>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 transition-colors"
              @click="openContactCreate"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
              </svg>
              Toevoegen
            </button>
          </div>

          <div v-if="showContactForm" class="px-6 py-4 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
            <form @submit.prevent="submitContact" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Naam *</label>
                  <input v-model="contactForm.naam" type="text" required class="w-full rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                  <p v-if="contactForm.errors.naam" class="text-xs text-red-500 mt-0.5">{{ contactForm.errors.naam }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">E-mail</label>
                  <input v-model="contactForm.email" type="email" class="w-full rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Telefoon</label>
                  <input v-model="contactForm.telefoon" type="tel" class="w-full rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Geboortedatum</label>
                  <input v-model="contactForm.geboortedatum" type="date" class="w-full rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
              </div>
              <div class="flex gap-2 justify-end">
                <button type="button" @click="showContactForm = false" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Annuleren</button>
                <button type="submit" :disabled="contactForm.processing" class="text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                  {{ editingContact ? 'Opslaan' : 'Toevoegen' }}
                </button>
              </div>
            </form>
          </div>

          <div v-if="contactpersonen.length === 0 && !showContactForm" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
            Nog geen contactpersonen toegevoegd.
          </div>
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="c in contactpersonen" :key="c.id" class="px-6 py-4 flex items-start gap-4">
              <div class="shrink-0 w-9 h-9 rounded-full bg-violet-500 flex items-center justify-center text-white text-xs font-bold select-none mt-0.5">
                {{ getInitials(c.naam) }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2 mb-1">
                  <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ c.naam }}</p>
                  <div class="flex gap-2 shrink-0">
                    <button type="button" @click="openContactEdit(c)" class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Bewerken</button>
                    <button type="button" @click="destroyContact(c)" class="text-xs px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-700 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">Verwijderen</button>
                  </div>
                </div>
                <div class="flex flex-wrap gap-4">
                  <a v-if="c.email" :href="`mailto:${c.email}`" class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ c.email }}
                  </a>
                  <a v-if="c.telefoon" :href="`tel:${c.telefoon}`" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ c.telefoon }}
                  </a>
                  <span v-if="c.geboortedatum" class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ fmtDatum(c.geboortedatum) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Opmerkingen ────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Opmerkingen
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ opmerkingen.length }})</span>
            </h3>
            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
              <span>Sorteren op:</span>
              <select v-model="sortOrder" class="border-0 bg-transparent text-xs font-medium text-gray-600 dark:text-gray-300 focus:ring-0 pr-7 py-0 cursor-pointer">
                <option value="nieuwste">Nieuwste eerst</option>
                <option value="oudste">Oudste eerst</option>
              </select>
            </div>
          </div>

          <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
            <div v-if="opmerkingen.length === 0" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
              Nog geen opmerkingen.
            </div>
            <div
              v-for="o in sortedOpmerkingen"
              :key="o.id"
              class="group flex gap-3 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <div
                class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white select-none mt-0.5"
                :class="o.user_id === currentUserId ? 'bg-blue-500' : 'bg-violet-500'"
              >
                {{ (o.user?.name ?? '?').charAt(0).toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2 mb-1">
                  <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ o.user?.name ?? 'Onbekend' }}</span>
                  <span class="text-xs text-gray-400 dark:text-gray-500">{{ fmtTijd(o.created_at) }}</span>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ o.tekst }}</p>
              </div>
              <button
                v-if="o.user_id === currentUserId"
                type="button"
                class="shrink-0 opacity-0 group-hover:opacity-100 mt-1 p-1 rounded text-gray-300 dark:text-gray-600 hover:text-red-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                title="Verwijderen"
                @click="destroyOpmerking(o)"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m2-3h6a1 1 0 011 1H8a1 1 0 011-1z" />
                </svg>
              </button>
            </div>
          </div>

          <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            <div class="flex gap-3 items-start">
              <div class="shrink-0 w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold text-white mt-1 select-none">
                {{ ($page.props.auth.user.name ?? '?').charAt(0).toUpperCase() }}
              </div>
              <form @submit.prevent="submitOpmerking" class="flex-1 flex flex-col gap-2">
                <textarea
                  v-model="opmerkingForm.tekst"
                  rows="2"
                  placeholder="Schrijf een opmerking… (Enter om te versturen)"
                  class="w-full rounded-lg border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:ring-blue-500 focus:border-blue-500 resize-none px-3 py-2"
                  required
                  @keydown.enter.exact.prevent="submitOpmerking"
                />
                <div class="flex justify-end">
                  <button
                    type="submit"
                    :disabled="opmerkingForm.processing || !opmerkingForm.tekst.trim()"
                    class="px-4 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-40 transition-colors"
                  >Versturen</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- ── Offertes ───────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Offertes
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ quotes.length }})</span>
            </h3>
          </div>

          <div v-if="quotes.length === 0" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
            Nog geen offertes voor dit bedrijf.
            <a :href="route('quotes.create', { client_id: client.id })" class="text-blue-600 dark:text-blue-400 hover:underline ml-1">Maak de eerste aan.</a>
          </div>

          <div class="overflow-x-auto">
          <table v-if="quotes.length" class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Nummer</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Titel</th>
                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Bedrag</th>
                <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden md:table-cell">Datum</th>
                <th class="px-4 sm:px-6 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="q in quotes"
                :key="q.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer transition-colors"
                @click="router.visit(route('quotes.edit', q.id))"
              >
                <td class="px-4 sm:px-6 py-3 font-mono text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">{{ q.offerte_nummer }}</td>
                <td class="px-4 sm:px-6 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate hidden sm:table-cell">{{ q.titel }}</td>
                <td class="px-4 sm:px-6 py-3"><StatusBadge :status="q.status" /></td>
                <td class="px-4 sm:px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap hidden sm:table-cell">{{ fmt(q.totaal) }}</td>
                <td class="px-4 sm:px-6 py-3 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden md:table-cell">{{ fmtDatum(q.created_at) }}</td>
                <td class="px-4 sm:px-6 py-3 text-right">
                  <button
                    type="button"
                    class="text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium whitespace-nowrap"
                    @click.stop="router.visit(route('quotes.edit', q.id))"
                  >Bewerken →</button>
                </td>
              </tr>
            </tbody>
          </table>
          </div>

          <div v-if="quotes.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 text-center">
            <a
              :href="route('quotes.index', { search: client.naam })"
              class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
            >Bekijk alle offertes →</a>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>

  <!-- Edit modal (reuses the same ClientForm as the create flow) -->
  <ClientForm
    :show="showEditForm"
    :client="{ ...client, contactpersonen }"
    :sectoren="sectoren"
    :relatie_statussen="relatie_statussen"
    :rechtsvormen="rechtsvormen"
    :talen="talen"
    :gebruikers="gebruikers"
    @close="showEditForm = false"
  />
</template>
