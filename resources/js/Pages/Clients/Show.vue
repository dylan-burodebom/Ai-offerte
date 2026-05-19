<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
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
      onSuccess: () => { showContactForm.value = false }
    })
  } else {
    contactForm.post(route('clients.contactpersonen.store', props.client.id), {
      onSuccess: () => { showContactForm.value = false; contactForm.reset() }
    })
  }
}

function destroyContact(c) {
  if (confirm(`Contactpersoon "${c.naam}" verwijderen?`)) {
    router.delete(route('clients.contactpersonen.destroy', [props.client.id, c.id]))
  }
}

// ── Opmerkingen ──────────────────────────────────────────────
const opmerkingForm = useForm({ tekst: '' })

function submitOpmerking() {
  opmerkingForm.post(route('clients.opmerkingen.store', props.client.id), {
    onSuccess: () => opmerkingForm.reset()
  })
}

function destroyOpmerking(o) {
  if (confirm('Opmerking verwijderen?')) {
    router.delete(route('clients.opmerkingen.destroy', [props.client.id, o.id]))
  }
}
</script>

<template>
  <Head :title="client.naam" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a :href="route('clients.index')" class="text-gray-400 hover:text-gray-600 transition-colors text-sm">← Bedrijven</a>
          <span class="text-gray-300">/</span>
          <h2 class="text-xl font-semibold text-gray-800">{{ client.naam }}</h2>
          <span v-if="client.sector" class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700">{{ client.sector }}</span>
        </div>
        <a :href="route('quotes.create')" class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
          + Nieuwe offerte
        </a>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Bedrijfsgegevens -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl p-6">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Bedrijfsgegevens</h3>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
            <div v-if="client.contactpersoon">
              <p class="text-xs text-gray-400 mb-0.5">Contactpersoon (oud)</p>
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
              <p class="text-gray-800">{{ [client.adres, client.postcode, client.stad].filter(Boolean).join(', ') }}</p>
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
            <p class="text-2xl font-bold text-green-700">{{ fmt(omzetGewonnen) }}</p>
            <p class="text-xs text-gray-400 mt-1">Omzet gewonnen</p>
          </div>
        </div>

        <!-- Contactpersonen -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
              Contactpersonen
              <span class="ml-1 text-gray-400 font-normal">({{ contactpersonen.length }})</span>
            </h3>
            <button
              @click="openContactCreate"
              class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors"
            >+ Toevoegen</button>
          </div>

          <!-- Formulier -->
          <div v-if="showContactForm" class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <form @submit.prevent="submitContact" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Naam *</label>
                  <input v-model="contactForm.naam" type="text" required class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" />
                  <p v-if="contactForm.errors.naam" class="text-xs text-red-500 mt-0.5">{{ contactForm.errors.naam }}</p>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">E-mail</label>
                  <input v-model="contactForm.email" type="email" class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Telefoon</label>
                  <input v-model="contactForm.telefoon" type="tel" class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Geboortedatum</label>
                  <input v-model="contactForm.geboortedatum" type="date" class="w-full rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>
              </div>
              <div class="flex gap-2 justify-end">
                <button type="button" @click="showContactForm = false" class="text-xs px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-100">Annuleren</button>
                <button type="submit" :disabled="contactForm.processing" class="text-xs px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                  {{ editingContact ? 'Opslaan' : 'Toevoegen' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Lijst -->
          <div v-if="contactpersonen.length === 0 && !showContactForm" class="px-6 py-8 text-center text-sm text-gray-400">
            Nog geen contactpersonen toegevoegd.
          </div>
          <div v-else class="divide-y divide-gray-100">
            <div v-for="c in contactpersonen" :key="c.id" class="px-6 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ c.naam }}</p>
                <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-0.5">
                  <a v-if="c.email" :href="`mailto:${c.email}`" class="text-xs text-blue-600 hover:underline">{{ c.email }}</a>
                  <a v-if="c.telefoon" :href="`tel:${c.telefoon}`" class="text-xs text-gray-500">{{ c.telefoon }}</a>
                  <span v-if="c.geboortedatum" class="text-xs text-gray-400">{{ fmtDatum(c.geboortedatum) }}</span>
                </div>
              </div>
              <div class="flex gap-1.5 shrink-0">
                <button @click="openContactEdit(c)" class="text-xs px-2.5 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">Bewerken</button>
                <button @click="destroyContact(c)" class="text-xs px-2.5 py-1 rounded border border-red-300 text-red-500 hover:bg-red-50">Verwijderen</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Opmerkingen -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">
              Opmerkingen
              <span class="ml-1 text-gray-400 font-normal">({{ opmerkingen.length }})</span>
            </h3>
          </div>

          <!-- Nieuwe opmerking -->
          <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <form @submit.prevent="submitOpmerking" class="flex gap-3">
              <textarea
                v-model="opmerkingForm.tekst"
                rows="2"
                placeholder="Opmerking toevoegen…"
                class="flex-1 rounded border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
                required
              />
              <button type="submit" :disabled="opmerkingForm.processing" class="self-end px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700 disabled:opacity-50 whitespace-nowrap">
                Toevoegen
              </button>
            </form>
          </div>

          <!-- Lijst -->
          <div v-if="opmerkingen.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">
            Nog geen opmerkingen.
          </div>
          <div v-else class="divide-y divide-gray-100">
            <div v-for="o in opmerkingen" :key="o.id" class="px-6 py-4 flex gap-4">
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ o.tekst }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ fmtTijd(o.created_at) }}</p>
              </div>
              <button @click="destroyOpmerking(o)" class="shrink-0 text-xs text-gray-400 hover:text-red-500 transition-colors">Verwijderen</button>
            </div>
          </div>
        </div>

        <!-- Offertes -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">
              Offertes
              <span class="ml-1 text-gray-400 font-normal">({{ quotes.length }})</span>
            </h3>
          </div>
          <div v-if="quotes.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">
            Nog geen offertes voor dit bedrijf.
          </div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Nummer</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Titel</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Bedrag</th>
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
                <td class="px-5 py-3 font-mono text-xs text-blue-700 font-medium whitespace-nowrap">{{ q.offerte_nummer }}</td>
                <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ q.titel }}</td>
                <td class="px-5 py-3"><StatusBadge :status="q.status" /></td>
                <td class="px-5 py-3 text-right font-mono text-gray-700 whitespace-nowrap">{{ fmt(q.totaal) }}</td>
                <td class="px-5 py-3 text-right text-gray-400 text-xs whitespace-nowrap">{{ fmtDatum(q.created_at) }}</td>
                <td class="px-5 py-3 text-right">
                  <button type="button" class="text-xs text-blue-500 hover:text-blue-700 font-medium" @click.stop="router.visit(route('quotes.edit', q.id))">
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
