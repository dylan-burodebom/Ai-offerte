<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Inloggen" />

    <h2 class="text-lg font-semibold text-white mb-6">Inloggen</h2>

    <div v-if="status" class="mb-4 text-sm font-medium text-green-400">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label for="email" class="block text-sm font-medium text-[#aabbcc] mb-1.5">E-mailadres</label>
        <input
          id="email"
          type="email"
          v-model="form.email"
          required
          autofocus
          autocomplete="username"
          class="w-full rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/30 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4076f0] focus:border-transparent transition"
        />
        <InputError class="mt-1.5" :message="form.errors.email" />
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="password" class="text-sm font-medium text-[#aabbcc]">Wachtwoord</label>
          <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="text-xs text-[#4076f0] hover:text-blue-400 transition-colors"
          >
            Wachtwoord vergeten?
          </Link>
        </div>
        <input
          id="password"
          type="password"
          v-model="form.password"
          required
          autocomplete="current-password"
          class="w-full rounded-lg bg-white/10 border border-white/20 text-white placeholder-white/30 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4076f0] focus:border-transparent transition"
        />
        <InputError class="mt-1.5" :message="form.errors.password" />
      </div>

      <div class="flex items-center gap-2">
        <input
          id="remember"
          type="checkbox"
          v-model="form.remember"
          class="rounded border-white/20 bg-white/10 text-[#4076f0] focus:ring-[#4076f0]"
        />
        <label for="remember" class="text-sm text-[#aabbcc]">Onthoud mij</label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full py-2.5 rounded-lg bg-[#4076f0] text-white font-semibold text-sm hover:bg-blue-500 transition-colors disabled:opacity-50"
      >
        Inloggen
      </button>
    </form>
  </GuestLayout>
</template>
