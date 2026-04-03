<script setup lang="ts">
import { CheckCircle } from 'lucide-vue-next';
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-md rounded-lg border bg-white p-8 text-center">
      <CheckCircle class="mx-auto mb-4 h-14 w-14 text-green-500" />
      <h1 class="text-xl font-bold text-slate-900">Plaça ja activada</h1>
      <p class="mt-2 text-slate-500">Aquesta activació ja ha estat processada correctament.</p>
    </div>
  </div>
</template>
