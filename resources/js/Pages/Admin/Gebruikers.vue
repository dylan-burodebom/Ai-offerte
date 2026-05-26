<script setup>
import { ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  gebruikers: { type: Array, default: () => [] },
})

const page    = usePage()
const mijnId  = page.props.auth.user.id
const adminEmail = page.props.auth.user.email

const form   = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const saving = ref(false)
const success = ref(null)

function kanVerwijderen(g) {
  return g.id !== mijnId && g.email !== adminEmail
}

async function aanmaken() {
  saving.value = true
  errors.value = {}
  success.value = null
  try {
    await window.axios.post(route('admin.gebruikers.store'), form.value)
    form.value = { name: '', email: '', password: '', password_confirmation: '' }
    success.value = 'Account aangemaakt.'
    router.reload({ only: ['gebruikers'] })
  } catch (e) {
    errors.value = e.response?.data?.errors ?? {}
  } finally {
    saving.value = false
  }
}

function verwijderen(gebruiker) {
  if (!confirm(`"${gebruiker.name}" verwijderen?`)) return
  router.delete(route('admin.gebruikers.destroy', gebruiker.id), { preserveScroll: true })
}

function formatDatum(iso) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function initialen(name) {
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<template>
  <Head title="Gebruikers" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <h1 class="text-sm font-semibold text-gray-900 dark:text-white">Gebruikersbeheer</h1>
      </div>
    </template>

    <div class="max-w-5xl mx-auto px-6 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        <!-- Nieuw account formulier -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
              <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Nieuw account</h2>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Maak een nieuw gebruikersaccount aan</p>
            </div>

            <div class="p-6 space-y-4">
              <div v-if="success" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm text-green-700 dark:text-green-400">{{ success }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Naam</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Jan Janssen"
                  class="w-full px-3 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  :class="errors.name ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white'"
                />
                <p v-if="errors.name" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ errors.name[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">E-mailadres</label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="jan@burodebom.nl"
                  class="w-full px-3 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  :class="errors.email ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white'"
                />
                <p v-if="errors.email" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ errors.email[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord</label>
                <input
                  v-model="form.password"
                  type="password"
                  class="w-full px-3 py-2.5 rounded-xl border text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  :class="errors.password ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10' : 'border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white'"
                />
                <p v-if="errors.password" class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ errors.password[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord bevestigen</label>
                <input
                  v-model="form.password_confirmation"
                  type="password"
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                />
              </div>

              <button
                type="button"
                :disabled="saving"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-2"
                @click="aanmaken"
              >
                <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ saving ? 'Aanmaken…' : 'Account aanmaken' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Gebruikerslijst -->
        <div class="lg:col-span-3">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex items-center justify-between">
              <div>
                <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Accounts</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ gebruikers.length }} {{ gebruikers.length === 1 ? 'gebruiker' : 'gebruikers' }}</p>
              </div>
            </div>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700/60">
              <li
                v-for="g in gebruikers"
                :key="g.id"
                class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
              >
                <!-- Avatar -->
                <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center shrink-0">
                  <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ initialen(g.name) }}</span>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ g.name }}</p>
                    <span v-if="g.id === mijnId" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 font-medium shrink-0">jij</span>
                    <span v-else-if="g.email === adminEmail" class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 font-medium shrink-0">admin</span>
                  </div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ g.email }}</p>
                </div>

                <!-- Datum -->
                <span class="text-xs text-gray-300 dark:text-gray-600 shrink-0 hidden sm:block">{{ formatDatum(g.created_at) }}</span>

                <!-- Verwijder knop -->
                <button
                  v-if="kanVerwijderen(g)"
                  type="button"
                  class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0"
                  title="Verwijderen"
                  @click="verwijderen(g)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <div v-else class="w-8 h-8 shrink-0" />
              </li>

              <li v-if="!gebruikers.length" class="px-6 py-12 text-center">
                <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                </div>
                <p class="text-sm text-gray-400 dark:text-gray-500">Geen accounts gevonden.</p>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
