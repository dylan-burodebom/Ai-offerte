<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  show: Boolean,
  client: Object,
  sectoren: Array,
  relatie_statussen: Array,
  rechtsvormen: Array,
  talen: Object,
  gebruikers: Array,
})

const emit = defineEmits(['close'])

const activeTab  = ref('algemeen')
const logoPreview = ref(null)
const newLabel = ref('')

const form = useForm({
  naam: '',
  email: '',
  telefoon: '',
  website: '',
  sector: '',
  relatie_status: 'klant',
  adres: '',
  postcode: '',
  stad: '',
  beschrijving: '',
  logo: null,
  verwijder_logo: false,
  contactpersonen: [],
  // Bank
  bank: '',
  bic: '',
  iban: '',
  rekeninghouder: '',
  vestigingsplaats: '',
  // Administratie
  gebruik_afwijkende_factuurgegevens: false,
  // Extra
  kvk_nummer: '',
  rechtsvorm: '',
  btw_nummer: '',
  extern_id: '',
  // Instellingen
  relatiebeheerder_id: null,
  voertaal: '',
  taal_berichten: '',
  labels: [],
})

watch(
  () => props.show,
  (val) => {
    if (!val) return
    activeTab.value = 'algemeen'
  }
)

watch(
  () => props.client,
  (client) => {
    form.reset()
    logoPreview.value = null
    newLabel.value = ''
    form.contactpersonen = []
    if (client) {
      form.naam           = client.naam           ?? ''
      form.email          = client.email          ?? ''
      form.telefoon       = client.telefoon       ?? ''
      form.website        = client.website        ?? ''
      form.sector         = client.sector         ?? ''
      form.relatie_status = client.relatie_status ?? 'klant'
      form.adres          = client.adres          ?? ''
      form.postcode       = client.postcode       ?? ''
      form.stad           = client.stad           ?? ''
      form.beschrijving   = client.beschrijving   ?? ''
      // Bank
      form.bank             = client.bank             ?? ''
      form.bic              = client.bic              ?? ''
      form.iban             = client.iban             ?? ''
      form.rekeninghouder   = client.rekeninghouder   ?? ''
      form.vestigingsplaats = client.vestigingsplaats ?? ''
      // Administratie
      form.gebruik_afwijkende_factuurgegevens = client.gebruik_afwijkende_factuurgegevens ?? false
      // Extra
      form.kvk_nummer  = client.kvk_nummer  ?? ''
      form.rechtsvorm  = client.rechtsvorm  ?? ''
      form.btw_nummer  = client.btw_nummer  ?? ''
      form.extern_id   = client.extern_id   ?? ''
      // Instellingen
      form.relatiebeheerder_id = client.relatiebeheerder_id ?? null
      form.voertaal            = client.voertaal            ?? ''
      form.taal_berichten      = client.taal_berichten      ?? ''
      form.labels              = client.labels              ?? []
    }
  },
)

function onLogoChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.logo = file
  form.verwijder_logo = false
  logoPreview.value = URL.createObjectURL(file)
}

function verwijderLogo() {
  form.logo = null
  form.verwijder_logo = true
  logoPreview.value = null
}

function addContactpersoon() {
  form.contactpersonen.push({ naam: '', email: '', telefoon: '' })
}

function removeContactpersoon(i) {
  form.contactpersonen.splice(i, 1)
}

function addLabel() {
  const val = newLabel.value.trim()
  if (!val || form.labels.includes(val)) return
  form.labels.push(val)
  newLabel.value = ''
}

function removeLabel(i) {
  form.labels.splice(i, 1)
}

function submit() {
  if (props.client) {
    form.transform(data => ({ ...data, _method: 'PATCH' }))
      .post(route('clients.update', props.client.id), {
        onSuccess: () => emit('close'),
      })
  } else {
    form.post(route('clients.store'), {
      onSuccess: () => emit('close'),
    })
  }
}

const TABS = [
  { key: 'algemeen',      label: 'Algemeen' },
  { key: 'contact',       label: 'Contact' },
  { key: 'bank',          label: 'Bank' },
  { key: 'administratie', label: 'Administratie' },
  { key: 'extra',         label: 'Extra' },
  { key: 'instellingen',  label: 'Instellingen' },
]

const SELECT_CLASS = 'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500'
</script>

<template>
  <Modal :show="show" max-width="xl" @close="emit('close')">
    <div class="flex flex-col max-h-[90vh]">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 pt-5 pb-0 shrink-0">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
          {{ client ? 'Bedrijf bewerken' : 'Bedrijf toevoegen' }}
        </h2>
        <button
          type="button"
          class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          @click="emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex gap-0 px-6 mt-4 border-b border-gray-100 dark:border-gray-700 shrink-0 overflow-x-auto">
        <button
          v-for="tab in TABS"
          :key="tab.key"
          type="button"
          class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px whitespace-nowrap"
          :class="activeTab === tab.key
            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Scrollable body -->
      <form @submit.prevent="submit" class="flex flex-col flex-1 min-h-0">
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">

          <!-- ── Tab: Algemeen ── -->
          <template v-if="activeTab === 'algemeen'">

            <!-- Logo upload -->
            <div>
              <InputLabel value="Bedrijfslogo" />
              <div class="mt-1.5 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex items-center justify-center overflow-hidden shrink-0">
                  <img
                    v-if="logoPreview || (!form.verwijder_logo && client?.logo_url)"
                    :src="logoPreview ?? client.logo_url"
                    class="w-full h-full object-contain p-1"
                    alt="Logo"
                  />
                  <svg v-else class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                  </svg>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Afbeelding kiezen
                    <input type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                  </label>
                  <button
                    v-if="logoPreview || (!form.verwijder_logo && client?.logo_url)"
                    type="button"
                    class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 text-left transition-colors"
                    @click="verwijderLogo"
                  >Logo verwijderen</button>
                </div>
              </div>
              <InputError :message="form.errors.logo" class="mt-1" />
            </div>

            <!-- Naam + Email -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="naam" value="Bedrijfsnaam *" />
                <TextInput id="naam" v-model="form.naam" class="mt-1 block w-full" required />
                <InputError :message="form.errors.naam" class="mt-1" />
              </div>
              <div>
                <InputLabel for="email" value="E-mailadres *" />
                <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" required />
                <InputError :message="form.errors.email" class="mt-1" />
              </div>
            </div>

            <!-- Sector + Relatiestatus -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="sector" value="Sector" />
                <select id="sector" v-model="form.sector" :class="SELECT_CLASS">
                  <option value="">— Kies sector —</option>
                  <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
              <div>
                <InputLabel for="relatie_status" value="Relatiestatus" />
                <select id="relatie_status" v-model="form.relatie_status" :class="SELECT_CLASS">
                  <option v-for="s in relatie_statussen" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                </select>
              </div>
            </div>

            <!-- Adres -->
            <div>
              <InputLabel for="adres" value="Adres" />
              <TextInput id="adres" v-model="form.adres" placeholder="Straatnaam en huisnummer" class="mt-1 block w-full" />
            </div>

            <!-- Postcode + Stad -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="postcode" value="Postcode" />
                <TextInput id="postcode" v-model="form.postcode" class="mt-1 block w-full" />
              </div>
              <div>
                <InputLabel for="stad" value="Stad" />
                <TextInput id="stad" v-model="form.stad" class="mt-1 block w-full" />
              </div>
            </div>

            <!-- Beschrijving -->
            <div>
              <InputLabel for="beschrijving" value="Beschrijving" />
              <textarea
                id="beschrijving"
                v-model="form.beschrijving"
                rows="3"
                maxlength="2000"
                placeholder="Notities over de klant, het bedrijf, de samenwerking…"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
              />
              <div class="flex justify-end mt-1">
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ form.beschrijving.length }}/2000</span>
              </div>
            </div>

          </template>

          <!-- ── Tab: Contact ── -->
          <template v-else-if="activeTab === 'contact'">

            <!-- Telefoon + Website -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="telefoon" value="Telefoon" />
                <TextInput id="telefoon" v-model="form.telefoon" class="mt-1 block w-full" />
              </div>
              <div>
                <InputLabel for="website" value="Website" />
                <TextInput id="website" v-model="form.website" placeholder="https://www.bedrijf.nl" class="mt-1 block w-full" />
              </div>
            </div>

            <!-- Contactpersonen sectie -->
            <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Contactpersonen</p>
                  <p v-if="client" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Beheer contactpersonen via het bedrijfsprofiel</p>
                </div>
                <button
                  v-if="!client"
                  type="button"
                  class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors"
                  @click="addContactpersoon"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                  </svg>
                  Persoon toevoegen
                </button>
              </div>

              <!-- Existing contact persons (edit mode) -->
              <div v-if="client && client.contactpersonen?.length" class="space-y-2">
                <div
                  v-for="cp in client.contactpersonen"
                  :key="cp.id"
                  class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700"
                >
                  <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center shrink-0">
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ cp.naam.charAt(0).toUpperCase() }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ cp.naam }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ cp.email || cp.telefoon || '—' }}</p>
                  </div>
                </div>
              </div>

              <!-- New contact persons (create mode) -->
              <div v-if="!client" class="space-y-3">
                <div
                  v-for="(cp, i) in form.contactpersonen"
                  :key="i"
                  class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 p-3"
                >
                  <div class="flex items-center justify-between mb-2.5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Contactpersoon {{ i + 1 }}</p>
                    <button
                      type="button"
                      class="text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                      @click="removeContactpersoon(i)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  <div class="grid grid-cols-3 gap-2">
                    <div>
                      <InputLabel :for="`cp_naam_${i}`" value="Naam *" class="text-xs" />
                      <TextInput :id="`cp_naam_${i}`" v-model="cp.naam" class="mt-1 block w-full text-sm" />
                      <InputError :message="form.errors[`contactpersonen.${i}.naam`]" class="mt-1" />
                    </div>
                    <div>
                      <InputLabel :for="`cp_email_${i}`" value="E-mail" class="text-xs" />
                      <TextInput :id="`cp_email_${i}`" type="email" v-model="cp.email" class="mt-1 block w-full text-sm" />
                    </div>
                    <div>
                      <InputLabel :for="`cp_tel_${i}`" value="Telefoon" class="text-xs" />
                      <TextInput :id="`cp_tel_${i}`" v-model="cp.telefoon" class="mt-1 block w-full text-sm" />
                    </div>
                  </div>
                </div>

                <button
                  v-if="form.contactpersonen.length === 0"
                  type="button"
                  class="w-full py-8 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-300 hover:text-blue-500 dark:hover:border-blue-700 dark:hover:text-blue-400 transition-colors"
                  @click="addContactpersoon"
                >
                  + Contactpersoon toevoegen
                </button>

                <button
                  v-if="form.contactpersonen.length > 0"
                  type="button"
                  class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium transition-colors"
                  @click="addContactpersoon"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                  </svg>
                  Nog een persoon toevoegen
                </button>
              </div>
            </div>

          </template>

          <!-- ── Tab: Bank ── -->
          <template v-else-if="activeTab === 'bank'">

            <div>
              <InputLabel for="bank" value="Bank" />
              <TextInput id="bank" v-model="form.bank" placeholder="bijv. ING, Rabobank, ABN AMRO" class="mt-1 block w-full" />
              <InputError :message="form.errors.bank" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="iban" value="IBAN" />
                <TextInput id="iban" v-model="form.iban" placeholder="NL00 BANK 0000 0000 00" class="mt-1 block w-full" />
                <InputError :message="form.errors.iban" class="mt-1" />
              </div>
              <div>
                <InputLabel for="bic" value="BIC" />
                <TextInput id="bic" v-model="form.bic" placeholder="INGBNL2A" class="mt-1 block w-full" />
                <InputError :message="form.errors.bic" class="mt-1" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="rekeninghouder" value="Rekeninghouder" />
                <TextInput id="rekeninghouder" v-model="form.rekeninghouder" class="mt-1 block w-full" />
                <InputError :message="form.errors.rekeninghouder" class="mt-1" />
              </div>
              <div>
                <InputLabel for="vestigingsplaats" value="Vestigingsplaats" />
                <TextInput id="vestigingsplaats" v-model="form.vestigingsplaats" class="mt-1 block w-full" />
                <InputError :message="form.errors.vestigingsplaats" class="mt-1" />
              </div>
            </div>

          </template>

          <!-- ── Tab: Administratie ── -->
          <template v-else-if="activeTab === 'administratie'">

            <div class="rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 p-4">
              <label class="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.gebruik_afwijkende_factuurgegevens"
                  class="mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                />
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Gebruik afwijkende factuurgegevens</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Facturen worden gestuurd naar een ander adres of contactpersoon dan de standaard bedrijfsgegevens.</p>
                </div>
              </label>
            </div>

          </template>

          <!-- ── Tab: Extra ── -->
          <template v-else-if="activeTab === 'extra'">

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="kvk_nummer" value="KvK-nummer" />
                <TextInput id="kvk_nummer" v-model="form.kvk_nummer" class="mt-1 block w-full" />
                <InputError :message="form.errors.kvk_nummer" class="mt-1" />
              </div>
              <div>
                <InputLabel for="rechtsvorm" value="Rechtsvorm" />
                <select id="rechtsvorm" v-model="form.rechtsvorm" :class="SELECT_CLASS">
                  <option value="">— Kies rechtsvorm —</option>
                  <option v-for="r in rechtsvormen" :key="r" :value="r">{{ r }}</option>
                </select>
                <InputError :message="form.errors.rechtsvorm" class="mt-1" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="btw_nummer" value="BTW-nummer" />
                <TextInput id="btw_nummer" v-model="form.btw_nummer" placeholder="NL000000000B01" class="mt-1 block w-full" />
                <InputError :message="form.errors.btw_nummer" class="mt-1" />
              </div>
              <div>
                <InputLabel for="extern_id" value="Extern ID (leverancier)" />
                <TextInput id="extern_id" v-model="form.extern_id" class="mt-1 block w-full" />
                <InputError :message="form.errors.extern_id" class="mt-1" />
              </div>
            </div>

          </template>

          <!-- ── Tab: Instellingen ── -->
          <template v-else-if="activeTab === 'instellingen'">

            <div>
              <InputLabel for="relatiebeheerder_id" value="Relatiebeheerder" />
              <select id="relatiebeheerder_id" v-model="form.relatiebeheerder_id" :class="SELECT_CLASS">
                <option :value="null">— Geen —</option>
                <option v-for="u in gebruikers" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
              <InputError :message="form.errors.relatiebeheerder_id" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="voertaal" value="Voertaal" />
                <select id="voertaal" v-model="form.voertaal" :class="SELECT_CLASS">
                  <option value="">— Kies taal —</option>
                  <option v-for="(label, code) in talen" :key="code" :value="code">{{ label }}</option>
                </select>
                <InputError :message="form.errors.voertaal" class="mt-1" />
              </div>
              <div>
                <InputLabel for="taal_berichten" value="Taal voor berichten" />
                <select id="taal_berichten" v-model="form.taal_berichten" :class="SELECT_CLASS">
                  <option value="">— Kies taal —</option>
                  <option v-for="(label, code) in talen" :key="code" :value="code">{{ label }}</option>
                </select>
                <InputError :message="form.errors.taal_berichten" class="mt-1" />
              </div>
            </div>

            <!-- Labels -->
            <div>
              <InputLabel value="Label(s)" />
              <div class="mt-1.5 flex flex-wrap gap-1.5 min-h-[36px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2.5 py-1.5">
                <span
                  v-for="(label, i) in form.labels"
                  :key="i"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-medium"
                >
                  {{ label }}
                  <button type="button" class="hover:text-red-500 transition-colors" @click="removeLabel(i)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </span>
                <input
                  v-model="newLabel"
                  type="text"
                  placeholder="Label toevoegen…"
                  class="flex-1 min-w-[120px] border-none outline-none bg-transparent text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 py-0.5"
                  @keydown.enter.prevent="addLabel"
                  @keydown.comma.prevent="addLabel"
                />
              </div>
              <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Druk Enter of komma om een label toe te voegen.</p>
              <InputError :message="form.errors.labels" class="mt-1" />
            </div>

          </template>

        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 shrink-0">
          <button
            type="button"
            class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
            @click="emit('close')"
          >Annuleren</button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-semibold hover:bg-gray-700 dark:hover:bg-gray-100 disabled:opacity-50 transition-colors"
          >
            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ client ? 'Opslaan' : 'Toevoegen' }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>
