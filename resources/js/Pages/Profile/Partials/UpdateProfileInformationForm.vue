<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})

const user = usePage().props.auth.user

// ── Avatar ────────────────────────────────────────────────────────────────────
const avatarInput   = ref(null)
const avatarPreview = ref(null)
const avatarForm    = useForm({ avatar: null })

function triggerAvatarUpload() {
    avatarInput.value?.click()
}

function onAvatarSelect(e) {
    const file = e.target.files[0]
    if (!file) return
    avatarForm.avatar = file
    const reader = new FileReader()
    reader.onload = (ev) => { avatarPreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

function submitAvatar() {
    avatarForm.post(route('profile.avatar'), {
        preserveScroll: true,
        onSuccess: () => { avatarPreview.value = null },
    })
}

function cancelAvatar() {
    avatarPreview.value = null
    avatarForm.avatar   = null
    if (avatarInput.value) avatarInput.value.value = ''
}

// ── Profiel gegevens ──────────────────────────────────────────────────────────
const infoForm = useForm({
    name:  user.name,
    email: user.email,
})
</script>

<template>
    <div class="space-y-6">

        <!-- Profielfoto -->
        <div class="flex items-center gap-5">
            <div class="relative group">
                <button type="button" class="relative block rounded-full focus:outline-none" @click="triggerAvatarUpload">
                    <img
                        v-if="avatarPreview || user.avatar_url"
                        :src="avatarPreview || user.avatar_url"
                        class="w-20 h-20 rounded-full object-cover"
                        alt="Profielfoto"
                    />
                    <div
                        v-else
                        class="w-20 h-20 rounded-full bg-yellow-400 flex items-center justify-center text-white text-2xl font-bold select-none"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </button>
                <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onAvatarSelect" />
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ user.name }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Klik op de foto om te wijzigen (max. 2 MB)</p>
                <div v-if="avatarPreview" class="flex items-center gap-2 mt-2">
                    <button
                        type="button"
                        :disabled="avatarForm.processing"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        @click="submitAvatar"
                    >
                        <svg v-if="avatarForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        {{ avatarForm.processing ? 'Uploaden…' : 'Foto opslaan' }}
                    </button>
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-lg text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        @click="cancelAvatar"
                    >Annuleren</button>
                </div>
                <p v-if="avatarForm.errors.avatar" class="text-xs text-red-500 mt-1">{{ avatarForm.errors.avatar }}</p>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700" />

        <!-- Naam + e-mail -->
        <form @submit.prevent="infoForm.patch(route('profile.update'), { preserveScroll: true })" class="space-y-4">
            <div>
                <label for="name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Naam</label>
                <input
                    id="name"
                    v-model="infoForm.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
                <p v-if="infoForm.errors.name" class="text-xs text-red-500 mt-1">{{ infoForm.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">E-mailadres</label>
                <input
                    id="email"
                    v-model="infoForm.email"
                    type="email"
                    required
                    autocomplete="username"
                    class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
                <p v-if="infoForm.errors.email" class="text-xs text-red-500 mt-1">{{ infoForm.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="text-xs text-amber-600 dark:text-amber-400">
                E-mailadres is nog niet geverifieerd.
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button
                    type="submit"
                    :disabled="infoForm.processing"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
                >
                    {{ infoForm.processing ? 'Opslaan…' : 'Opslaan' }}
                </button>
                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
                    <span v-if="infoForm.recentlySuccessful" class="text-xs text-green-600 dark:text-green-400">Opgeslagen.</span>
                </Transition>
            </div>
        </form>
    </div>
</template>
