<script setup>
import { ref } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  client: { type: Object, required: true },
  quotes: { type: Array, default: () => [] },
  contactpersonen: { type: Array, default: () => [] },
  opmerkingen: { type: Array, default: () => [] },
})

// ── Formatters ──────────────────────────────────────────────
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

// ── Stats ────────────────────────────────────────────────────
const statusTelling = (status) => props.quotes.filter(q => q.status === status).length
const omzetGewonnen = props.quotes
  .filter(q => q.status === 'gewonnen')
  .reduce((s, q) => s + (parseFloat(q.totaal) || 0), 0)

// ── Contactpersonen ──────────────────────────────────────────
const showContactForm = ref(false)
const editingContact = ref(null)

const contactForm = useForm({
  naam: '',
  email: '',
  telefoon: '',
  geboortedatum: '',
})

function openContactCreate() {
  editingContact.value = null
  contactForm.reset()
  showContactForm.value = true
}

function openContactEdit(c) {
  editingContact.value = c
  contactForm.naam = c.naam ?? ''
  contactForm.email = c.email ?? ''
  contactForm.telefoon = c.telefoon ?? ''
  contactForm.geboortedatum = c.geboortedatum ? c.geboortedatum.substring(0, 10) : ''
  showContactForm.value = true
}

function submitContact() {
  if (editingContact.value) {
    contactForm.patch(route('clients.contactpersonen.update', [props.client.id, editingContact.value.id]), {
      preserveScroll: true,
      onSuccess: () => { showContactForm.value = false }
    })
  } else {
    contactForm.post(route('clients.contactpersonen.store', props.client.id), {
      preserveScroll: true,
      onSuccess: () => { showContactForm.value = false; contactForm.reset() }
    })
  }
}

function destroyContact(c) {
  if (confirm(`Contactpersoon "${c.naam}" verwijderen?`)) {
    router.delete(route('clients.contactpersonen.destroy', [props.client.id, c.id]), { preserveScroll: true })
  }
}

// ── Opmerkingen ──────────────────────────────────────────────
const currentUserId = usePage().props.auth.user.id
const opmerkingForm = useForm({ tekst: '' })

function submitOpmerking() {
  opmerkingForm.post(route('clients.opmerkingen.store', props.client.id), {
    preserveScroll: true,
    onSuccess: () => opmerkingForm.reset()
  })
}

function destroyOpmerking(o) {
  if (confirm('Opmerking verwijderen?')) {
    router.delete(route('clients.opmerkingen.destroy', [props.client.id, o.id]), { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="client.naam" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a :href="route('clients.index')" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors text-sm">← Bedrijven</a>
          <span class="text-gray-300 dark:text-gray-600">/</span>
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ client.naam }}</h2>
          <span v-if="client.sector" class="text-xs px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ client.sector }}</span>
          <span
            v-if="client.relatie_status"
            class="text-xs px-2 py-0.5 rounded"
            :class="{
              'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': client.relatie_status === 'klant',
              'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': client.relatie_status === 'prospect',
              'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': client.relatie_status === 'inactief',
            }"
          >{{ client.relatie_status }}</span>
        </div>
        <a :href="route('quotes.create')" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
          + Nieuwe offerte
        </a>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Bedrijfsgegevens -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Bedrijfsgegevens</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">E-mail</p>
              <a :href="`mailto:${client.email}`" class="text-blue-600 dark:text-blue-400 hover:underline">{{ client.email }}</a>
            </div>
            <div v-if="client.telefoon">
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Telefoon</p>
              <a :href="`tel:${client.telefoon}`" class="text-gray-800 dark:text-gray-200">{{ client.telefoon }}</a>
            </div>
            <div v-if="client.adres || client.postcode || client.stad">
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Adres</p>
              <p class="text-gray-800 dark:text-gray-200">{{ [client.adres, client.postcode, client.stad].filter(Boolean).join(', ') }}</p>
            </div>
          </div>
          <div v-if="client.beschrijving" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Beschrijving</p>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ client.beschrijving }}</p>
          </div>
        </div>

        <!-- Statistieken -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ quotes.length }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Totaal offertes</p>
          </div>
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ statusTelling('concept') + statusTelling('verzonden') }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Openstaand</p>
          </div>
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ statusTelling('gewonnen') }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Gewonnen</p>
          </div>
          <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ fmt(omzetGewonnen) }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Omzet gewonnen</p>
          </div>
        </div>

        <!-- Contactpersonen -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Contactpersonen
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ contactpersonen.length }})</span>
            </h3>
            <button
              @click="openContactCreate"
              class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors"
            >+ Toevoegen</button>
          </div>

          <!-- Formulier -->
          <div v-if="showContactForm" class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
            <form @submit.prevent="submitContact" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Naam *</label>
                  <input v-model="contactForm.naam" type="text" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                  <p v-if="contactForm.errors.naam" class="text-xs text-red-500 mt-0.5">{{ contactForm.errors.naam }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">E-mail</label>
                  <input v-model="contactForm.email" type="email" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Telefoon</label>
                  <input v-model="contactForm.telefoon" type="tel" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Geboortedatum</label>
                  <input v-model="contactForm.geboortedatum" type="date" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
              </div>
              <div class="flex gap-2 justify-end">
                <button type="button" @click="showContactForm = false" class="text-xs px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Annuleren</button>
                <button type="submit" :disabled="contactForm.processing" class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                  {{ editingContact ? 'Opslaan' : 'Toevoegen' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Lijst -->
          <div v-if="contactpersonen.length === 0 && !showContactForm" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
            Nog geen contactpersonen toegevoegd.
          </div>
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="c in contactpersonen" :key="c.id" class="px-6 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-1.5">{{ c.naam }}</p>
                <div class="flex flex-wrap gap-3">
                  <a v-if="c.email" :href="`mailto:${c.email}`" class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ c.email }}
                  </a>
                  <a v-if="c.telefoon" :href="`tel:${c.telefoon}`" class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ c.telefoon }}
                  </a>
                  <span v-if="c.geboortedatum" class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ fmtDatum(c.geboortedatum) }}
                  </span>
                </div>
              </div>
              <div class="flex gap-1.5 shrink-0">
                <button @click="openContactEdit(c)" class="text-xs px-2.5 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Bewerken</button>
                <button @click="destroyContact(c)" class="text-xs px-2.5 py-1 rounded border border-red-300 dark:border-red-700 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Verwijderen</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Opmerkingen (ClickUp-stijl) -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Opmerkingen
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ opmerkingen.length }})</span>
            </h3>
          </div>

          <!-- Thread -->
          <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
            <div v-if="opmerkingen.length === 0" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
              Nog geen opmerkingen.
            </div>

            <div
              v-for="o in opmerkingen"
              :key="o.id"
              class="group flex gap-3 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <!-- Avatar -->
              <div
                class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white select-none mt-0.5"
                :class="o.user_id === currentUserId ? 'bg-blue-500' : 'bg-violet-500'"
              >
                {{ (o.user?.name ?? '?').charAt(0).toUpperCase() }}
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2 mb-1">
                  <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {{ o.user?.name ?? 'Onbekend' }}
                  </span>
                  <span class="text-xs text-gray-400 dark:text-gray-500">{{ fmtTijd(o.created_at) }}</span>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ o.tekst }}</p>
              </div>

              <!-- Delete (hover) -->
              <button
                v-if="o.user_id === currentUserId"
                @click="destroyOpmerking(o)"
                class="shrink-0 opacity-0 group-hover:opacity-100 mt-1 p-1 rounded text-gray-300 dark:text-gray-600 hover:text-red-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                title="Verwijderen"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m2-3h6a1 1 0 011 1H8a1 1 0 011-1z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Input -->
          <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <div class="flex gap-3 items-start">
              <!-- Own avatar -->
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
                    class="px-4 py-1.5 rounded-md bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-40 transition-colors"
                  >
                    Versturen
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Offertes -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              Offertes
              <span class="ml-1 text-gray-400 dark:text-gray-500 font-normal">({{ quotes.length }})</span>
            </h3>
          </div>
          <div v-if="quotes.length === 0" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
            Nog geen offertes voor dit bedrijf.
          </div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Nummer</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Titel</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Bedrag</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Datum</th>
                <th class="px-5 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="q in quotes"
                :key="q.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer transition-colors"
                @click="router.visit(route('quotes.edit', q.id))"
              >
                <td class="px-5 py-3 font-mono text-xs text-blue-700 dark:text-blue-400 font-medium whitespace-nowrap">{{ q.offerte_nummer }}</td>
                <td class="px-5 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ q.titel }}</td>
                <td class="px-5 py-3"><StatusBadge :status="q.status" /></td>
                <td class="px-5 py-3 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ fmt(q.totaal) }}</td>
                <td class="px-5 py-3 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap">{{ fmtDatum(q.created_at) }}</td>
                <td class="px-5 py-3 text-right">
                  <button type="button" class="text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium" @click.stop="router.visit(route('quotes.edit', q.id))">
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
