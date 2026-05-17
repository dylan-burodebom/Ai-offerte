<script setup>
import { watch } from 'vue'
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
})

const emit = defineEmits(['close'])

const form = useForm({
  naam: '',
  contactpersoon: '',
  email: '',
  telefoon: '',
  sector: '',
  adres: '',
  postcode: '',
  stad: '',
  beschrijving: '',
})

watch(
  () => props.client,
  (client) => {
    form.reset()
    if (client) {
      form.naam = client.naam ?? ''
      form.contactpersoon = client.contactpersoon ?? ''
      form.email = client.email ?? ''
      form.telefoon = client.telefoon ?? ''
      form.sector = client.sector ?? ''
      form.adres = client.adres ?? ''
      form.postcode = client.postcode ?? ''
      form.stad = client.stad ?? ''
      form.beschrijving = client.beschrijving ?? ''
    }
  },
)

function submit() {
  if (props.client) {
    form.patch(route('clients.update', props.client.id), {
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
      <h2 class="text-lg font-semibold text-gray-900 mb-4">
        {{ client ? 'Klant bewerken' : 'Nieuwe klant' }}
      </h2>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="naam" value="Bedrijfsnaam *" />
            <TextInput id="naam" v-model="form.naam" class="mt-1 block w-full" required />
            <InputError :message="form.errors.naam" class="mt-1" />
          </div>
          <div>
            <InputLabel for="contactpersoon" value="Contactpersoon" />
            <TextInput id="contactpersoon" v-model="form.contactpersoon" class="mt-1 block w-full" />
            <InputError :message="form.errors.contactpersoon" class="mt-1" />
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

        <div>
          <InputLabel for="sector" value="Sector" />
          <select
            id="sector"
            v-model="form.sector"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">— Kies sector —</option>
            <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
          </select>
          <InputError :message="form.errors.sector" class="mt-1" />
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
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
          />
          <div class="flex justify-between mt-1">
            <InputError :message="form.errors.beschrijving" />
            <span class="text-xs text-gray-400">{{ form.beschrijving.length }}/2000</span>
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
