<script setup lang="ts">
import { XCircle } from 'lucide-vue-next';
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <div class="w-full max-w-md rounded-lg border bg-white p-8 text-center">
      <XCircle class="mx-auto mb-4 h-14 w-14 text-slate-400" />
      <h1 class="text-xl font-bold text-slate-900">Enllaç caducat</h1>
      <p class="mt-2 text-slate-500">
        Aquest enllaç d'activació ha caducat o ja no és vàlid. Contacta amb l'organització per
        obtenir un nou enllaç.
      </p>
    </div>
  </div>
</template>
