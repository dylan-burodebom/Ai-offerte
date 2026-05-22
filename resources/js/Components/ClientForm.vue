<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  show: Boolean,
  client: Object,
  sectoren: Array,
  relatie_statussen: Array,
})

const emit = defineEmits(['close'])

const logoPreview = ref(null)

const form = useForm({
  naam: '',
  email: '',
  telefoon: '',
  sector: '',
  relatie_status: 'klant',
  adres: '',
  postcode: '',
  stad: '',
  beschrijving: '',
  logo: null,
  verwijder_logo: false,
})

watch(
  () => props.client,
  (client) => {
    form.reset()
    logoPreview.value = null
    if (client) {
      form.naam = client.naam ?? ''
      form.email = client.email ?? ''
      form.telefoon = client.telefoon ?? ''
      form.sector = client.sector ?? ''
      form.relatie_status = client.relatie_status ?? 'klant'
      form.adres = client.adres ?? ''
      form.postcode = client.postcode ?? ''
      form.stad = client.stad ?? ''
      form.beschrijving = client.beschrijving ?? ''
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
</script>

<template>
  <Modal :show="show" max-width="lg" @close="emit('close')">
    <div class="p-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        {{ client ? 'Bedrijf bewerken' : 'Nieuwe klant' }}
      </h2>

      <form @submit.prevent="submit" class="space-y-4">

        <!-- Logo upload -->
        <div>
          <InputLabel value="Bedrijfslogo" />
          <div class="mt-1 flex items-center gap-4">
            <!-- Huidige preview -->
            <div class="w-16 h-16 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex items-center justify-center overflow-hidden shrink-0">
              <img
                v-if="logoPreview || (!form.verwijder_logo && client?.logo_url)"
                :src="logoPreview ?? client.logo_url"
                class="w-full h-full object-contain p-1"
                alt="Logo"
              />
              <svg v-else class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">JPG, PNG of WebP · max 2 MB</p>
          <InputError :message="form.errors.logo" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="naam" value="Bedrijfsnaam *" />
            <TextInput id="naam" v-model="form.naam" class="mt-1 block w-full" required />
            <InputError :message="form.errors.naam" class="mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="email" value="E-mailadres *" />
            <TextInput id="email" type="email" v-model="form.email" class="mt-1 block w-full" required />
            <InputError :message="form.errors.email" class="mt-1" />
          </div>
          <div>
            <InputLabel for="telefoon" value="Telefoon" />
            <TextInput id="telefoon" v-model="form.telefoon" class="mt-1 block w-full" />
            <InputError :message="form.errors.telefoon" class="mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="sector" value="Sector" />
            <select
              id="sector"
              v-model="form.sector"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">— Kies sector —</option>
              <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
            </select>
            <InputError :message="form.errors.sector" class="mt-1" />
          </div>
          <div>
            <InputLabel for="relatie_status" value="Relatiestatus" />
            <select
              id="relatie_status"
              v-model="form.relatie_status"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
            >
              <option v-for="s in relatie_statussen" :key="s" :value="s" class="capitalize">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
            </select>
            <InputError :message="form.errors.relatie_status" class="mt-1" />
          </div>
        </div>

        <div>
          <InputLabel for="adres" value="Adres" />
          <TextInput id="adres" v-model="form.adres" class="mt-1 block w-full" />
          <InputError :message="form.errors.adres" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="postcode" value="Postcode" />
            <TextInput id="postcode" v-model="form.postcode" class="mt-1 block w-full" />
            <InputError :message="form.errors.postcode" class="mt-1" />
          </div>
          <div>
            <InputLabel for="stad" value="Stad" />
            <TextInput id="stad" v-model="form.stad" class="mt-1 block w-full" />
            <InputError :message="form.errors.stad" class="mt-1" />
          </div>
        </div>

        <div>
          <InputLabel for="beschrijving" value="Beschrijving" />
          <textarea
            id="beschrijving"
            v-model="form.beschrijving"
            rows="4"
            maxlength="2000"
            placeholder="Notities over de klant, het bedrijf, de samenwerking…"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
          />
          <div class="flex justify-between mt-1">
            <InputError :message="form.errors.beschrijving" />
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ form.beschrijving.length }}/2000</span>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <SecondaryButton type="button" @click="emit('close')">Annuleren</SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            {{ client ? 'Opslaan' : 'Aanmaken' }}
          </PrimaryButton>
        </div>
      </form>
    </div>
  </Modal>
</template>
