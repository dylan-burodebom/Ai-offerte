<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
})
</script>

<template>
  <Head title="buro_deBom — Offerte tool" />

  <div class="min-h-screen bg-[#030810] text-white flex flex-col">

    <!-- Nav -->
    <nav class="flex items-center justify-between px-8 py-6 max-w-6xl mx-auto w-full">
      <span class="font-black text-xl tracking-tight">
        buro<span class="text-[#4076f0]">_</span>deBom
      </span>
      <Link
        v-if="canLogin"
        :href="route('login')"
        class="px-5 py-2 rounded-lg bg-[#4076f0] text-white text-sm font-medium hover:bg-blue-500 transition-colors"
      >
        Inloggen
      </Link>
    </nav>

    <!-- Hero -->
    <main class="flex-1 flex flex-col items-center justify-center px-6 text-center max-w-3xl mx-auto w-full py-24">

      <div class="w-10 h-0.5 bg-[#4076f0] mx-auto mb-10" />

      <h1 class="text-5xl sm:text-6xl font-black tracking-tight leading-tight mb-6">
        Offertes maken<br>
        <span class="text-[#4076f0]">op zijn slimst</span>
      </h1>

      <p class="text-lg text-[#aabbcc] leading-relaxed max-w-xl mb-12">
        Genereer professionele offertes met AI, beheer klanten en contactpersonen,
        en volg je omzet — allemaal op één plek.
      </p>

      <Link
        :href="route('login')"
        class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-[#4076f0] text-white font-semibold hover:bg-blue-500 transition-colors text-base"
      >
        Aan de slag
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
          <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
        </svg>
      </Link>
    </main>

    <!-- Features -->
    <section class="max-w-6xl mx-auto w-full px-6 pb-24 grid grid-cols-1 sm:grid-cols-3 gap-6">
      <div class="rounded-xl border border-white/10 p-6 bg-white/5">
        <div class="w-8 h-0.5 bg-[#4076f0] mb-4" />
        <h3 class="font-semibold text-white mb-2">AI-gegenereerde inhoud</h3>
        <p class="text-sm text-[#aabbcc] leading-relaxed">Secties automatisch invullen op basis van je projectbeschrijving.</p>
      </div>
      <div class="rounded-xl border border-white/10 p-6 bg-white/5">
        <div class="w-8 h-0.5 bg-[#4076f0] mb-4" />
        <h3 class="font-semibold text-white mb-2">Professionele PDF</h3>
        <p class="text-sm text-[#aabbcc] leading-relaxed">Exporteer elke offerte als een strak opgemaakt PDF-document.</p>
      </div>
      <div class="rounded-xl border border-white/10 p-6 bg-white/5">
        <div class="w-8 h-0.5 bg-[#4076f0] mb-4" />
        <h3 class="font-semibold text-white mb-2">CRM ingebouwd</h3>
        <p class="text-sm text-[#aabbcc] leading-relaxed">Bedrijven, contactpersonen en opmerkingen altijd bij de hand.</p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-6 text-center text-xs text-[#aabbcc]">
      &copy; {{ new Date().getFullYear() }} buro_deBom
    </footer>
  </div>
</template>
