<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  gebruikers: { type: Array, default: () => [] },
  clients:    { type: Array, default: () => [] },
})

const page   = usePage()
const mijnId = page.props.auth.user.id

// ── Create form ─────────────────────────────────────────────
const createForm = ref({ name: '', email: '', password: '', password_confirmation: '', role: 'medewerker', client_id: null })
const createErrors = ref({})
const creating = ref(false)
const createSuccess = ref(null)

async function aanmaken() {
  creating.value = true
  createErrors.value = {}
  createSuccess.value = null
  try {
    await window.axios.post(route('admin.gebruikers.store'), createForm.value)
    createForm.value = { name: '', email: '', password: '', password_confirmation: '', role: 'medewerker', client_id: null }
    createSuccess.value = 'Account aangemaakt.'
    router.reload({ only: ['gebruikers'] })
  } catch (e) {
    createErrors.value = e.response?.data?.errors ?? {}
  } finally {
    creating.value = false
  }
}

// ── Edit modal ──────────────────────────────────────────────
const editTarget  = ref(null)
const editForm    = ref({ role: 'medewerker', client_id: null })
const editErrors  = ref({})
const editing     = ref(false)

function openEdit(g) {
  editTarget.value = g
  editForm.value = { role: g.role, client_id: g.client_id ?? null }
  editErrors.value = {}
}

function closeEdit() {
  editTarget.value = null
}

async function opslaan() {
  editing.value = true
  editErrors.value = {}
  try {
    await window.axios.patch(route('admin.gebruikers.update', editTarget.value.id), editForm.value)
    router.reload({ only: ['gebruikers'] })
    closeEdit()
  } catch (e) {
    editErrors.value = e.response?.data?.errors ?? {}
  } finally {
    editing.value = false
  }
}

// ── Delete ───────────────────────────────────────────────────
function verwijderen(g) {
  if (!confirm(`"${g.name}" verwijderen?`)) return
  router.delete(route('admin.gebruikers.destroy', g.id), { preserveScroll: true })
}

// ── Helpers ──────────────────────────────────────────────────
function formatDatum(iso) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

function initialen(name) {
  return (name ?? '').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

const ROL_LABELS = { admin: 'Admin', medewerker: 'Medewerker', klant: 'Klant' }
const ROL_CLASSES = {
  admin:      'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400',
  medewerker: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400',
  klant:      'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-400',
}

const SELECT_CLASS = 'w-full px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500'
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

    <div class="max-w-5xl mx-auto px-3 sm:px-6 py-6 sm:py-8">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        <!-- ── Nieuw account formulier ── -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
              <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Nieuw account</h2>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Maak een nieuw gebruikersaccount aan</p>
            </div>

            <div class="p-6 space-y-4">
              <div v-if="createSuccess" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm text-green-700 dark:text-green-400">{{ createSuccess }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Naam</label>
                <input v-model="createForm.name" type="text" placeholder="Jan Janssen" :class="[SELECT_CLASS, createErrors.name ? 'border-red-300' : '']" />
                <p v-if="createErrors.name" class="mt-1 text-xs text-red-600">{{ createErrors.name[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">E-mailadres</label>
                <input v-model="createForm.email" type="email" placeholder="jan@bedrijf.nl" :class="[SELECT_CLASS, createErrors.email ? 'border-red-300' : '']" />
                <p v-if="createErrors.email" class="mt-1 text-xs text-red-600">{{ createErrors.email[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Rol</label>
                <select v-model="createForm.role" :class="SELECT_CLASS">
                  <option value="medewerker">Medewerker</option>
                  <option value="klant">Klant</option>
                </select>
              </div>

              <!-- Client koppeling — alleen voor klant -->
              <div v-if="createForm.role === 'klant'">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Bedrijf <span class="text-red-400">*</span></label>
                <select v-model="createForm.client_id" :class="[SELECT_CLASS, createErrors.client_id ? 'border-red-300' : '']">
                  <option :value="null">— Kies bedrijf —</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.naam }}</option>
                </select>
                <p v-if="createErrors.client_id" class="mt-1 text-xs text-red-600">{{ createErrors.client_id[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord</label>
                <input v-model="createForm.password" type="password" :class="[SELECT_CLASS, createErrors.password ? 'border-red-300' : '']" />
                <p v-if="createErrors.password" class="mt-1 text-xs text-red-600">{{ createErrors.password[0] }}</p>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord bevestigen</label>
                <input v-model="createForm.password_confirmation" type="password" :class="SELECT_CLASS" />
              </div>

              <button
                type="button"
                :disabled="creating"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-50 mt-2"
                @click="aanmaken"
              >
                <svg v-if="creating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ creating ? 'Aanmaken…' : 'Account aanmaken' }}
              </button>
            </div>
          </div>
        </div>

        <!-- ── Gebruikerslijst ── -->
        <div class="lg:col-span-3">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
              <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Accounts</h2>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ gebruikers.length }} {{ gebruikers.length === 1 ? 'gebruiker' : 'gebruikers' }}</p>
            </div>

            <ul class="divide-y divide-gray-100 dark:divide-gray-700/60">
              <li
                v-for="g in gebruikers"
                :key="g.id"
                class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
              >
                <div
                  class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                  :class="g.role === 'admin' ? 'bg-amber-100 dark:bg-amber-900/40' : g.role === 'klant' ? 'bg-violet-100 dark:bg-violet-900/40' : 'bg-blue-100 dark:bg-blue-900/40'"
                >
                  <span
                    class="text-xs font-bold"
                    :class="g.role === 'admin' ? 'text-amber-600 dark:text-amber-400' : g.role === 'klant' ? 'text-violet-600 dark:text-violet-400' : 'text-blue-600 dark:text-blue-400'"
                  >{{ initialen(g.name) }}</span>
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ g.name }}</p>
                    <span v-if="g.id === mijnId" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium shrink-0">jij</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0" :class="ROL_CLASSES[g.role]">{{ ROL_LABELS[g.role] }}</span>
                    <span v-if="g.role === 'klant' && g.client" class="text-xs text-gray-400 dark:text-gray-500 truncate">— {{ g.client.naam }}</span>
                  </div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ g.email }}</p>
                </div>

                <span class="text-xs text-gray-400 dark:text-gray-400 shrink-0 hidden sm:block">{{ formatDatum(g.created_at) }}</span>

                <!-- Bewerken -->
                <button
                  v-if="!g.role === 'admin' || g.id !== mijnId"
                  type="button"
                  class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 dark:text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors shrink-0"
                  title="Bewerken"
                  :disabled="g.role === 'admin'"
                  :class="g.role === 'admin' ? 'opacity-30 cursor-not-allowed' : ''"
                  @click="g.role !== 'admin' && openEdit(g)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>

                <!-- Verwijderen -->
                <button
                  v-if="g.role !== 'admin' && g.id !== mijnId"
                  type="button"
                  class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 dark:text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0"
                  title="Verwijderen"
                  @click="verwijderen(g)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <div v-else-if="g.role === 'admin' || g.id === mijnId" class="w-8 h-8 shrink-0" />
              </li>

              <li v-if="!gebruikers.length" class="px-6 py-12 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">Geen accounts gevonden.</p>
              </li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>

  <!-- ── Edit modal ── -->
  <Teleport to="body">
    <div v-if="editTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closeEdit" />
      <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-sm p-6 space-y-4">
        <div class="flex items-center justify-between mb-1">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Account bewerken</h3>
          <button type="button" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" @click="closeEdit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <p class="text-xs text-gray-400 dark:text-gray-500 -mt-2">{{ editTarget.name }} · {{ editTarget.email }}</p>

        <div>
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Rol</label>
          <select v-model="editForm.role" :class="SELECT_CLASS">
            <option value="medewerker">Medewerker</option>
            <option value="klant">Klant</option>
          </select>
        </div>

        <div v-if="editForm.role === 'klant'">
          <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Bedrijf <span class="text-red-400">*</span></label>
          <select v-model="editForm.client_id" :class="[SELECT_CLASS, editErrors.client_id ? 'border-red-300' : '']">
            <option :value="null">— Kies bedrijf —</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.naam }}</option>
          </select>
          <p v-if="editErrors.client_id" class="mt-1 text-xs text-red-600">{{ editErrors.client_id[0] }}</p>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" class="flex-1 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" @click="closeEdit">Annuleren</button>
          <button type="button" :disabled="editing" class="flex-1 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium disabled:opacity-50 transition-colors" @click="opslaan">
            {{ editing ? 'Opslaan…' : 'Opslaan' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
