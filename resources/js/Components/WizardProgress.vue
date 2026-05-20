<script setup>
defineProps({
  currentStep: { type: Number, required: true },
  steps: {
    type: Array,
    default: () => ['Klant', 'Offerte', 'Secties', 'Investering'],
  },
})
</script>

<template>
  <div class="flex items-center justify-center mb-8">
    <template v-for="(step, i) in steps" :key="i">
      <div class="flex flex-col items-center">
        <div
          class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold transition-colors"
          :class="
            i + 1 < currentStep
              ? 'bg-blue-600 text-white'
              : i + 1 === currentStep
                ? 'bg-blue-600 text-white ring-4 ring-blue-200 dark:ring-blue-900'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500'
          "
        >
          <svg
            v-if="i + 1 < currentStep"
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span
          class="mt-1.5 text-xs font-medium"
          :class="i + 1 === currentStep ? 'text-blue-600 dark:text-blue-400' : i + 1 < currentStep ? 'text-blue-400 dark:text-blue-500' : 'text-gray-400 dark:text-gray-500'"
        >
          {{ step }}
        </span>
      </div>

      <div
        v-if="i < steps.length - 1"
        class="w-16 h-0.5 mb-5 transition-colors"
        :class="i + 1 < currentStep ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
      />
    </template>
  </div>
</template>
