<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <div class="min-h-screen bg-[#030810] flex flex-col items-center justify-center px-4">
    <Link href="/" class="mb-8 font-black text-xl tracking-tight text-white">
      buro<span class="text-[#4076f0]">_</span>deBom
    </Link>

    <div class="w-full max-w-md bg-white/5 border border-white/10 rounded-2xl px-8 py-8">
      <slot />
    </div>
  </div>
</template>
