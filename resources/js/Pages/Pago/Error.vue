<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Inscripcion {
  id: number;
  precio_total: number;
}

defineProps<{
  inscripcion?: Inscripcion | null;
  errorMessage?: string;
}>();
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow-lg">
      <div class="mb-6">
        <div
          class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100"
        >
          <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </div>
        <h1 class="mb-2 text-2xl font-bold text-slate-900">Error en el pagament</h1>
        <p class="text-slate-600">
          {{
            errorMessage || "No s'ha pogut processar el pagament. Si us plau, intenta-ho de nou."
          }}
        </p>
      </div>

      <div class="mb-6 rounded-lg bg-red-50 p-4">
        <p class="text-sm text-red-800">
          Si el problema persisteix, por favor contacta amb nosaltres per rebre assistència:
          <a href="mailto:activitats@uectortosa.cat">activitats@uectortosa.cat</a>
        </p>
      </div>

      <div class="space-y-3">
        <Link
          v-if="inscripcion"
          :href="`/pago/${inscripcion.id}`"
          class="block w-full rounded-md bg-red-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-red-800"
        >
          Intentar de nou
        </Link>
        <Link
          v-else
          href="/inscripcio"
          class="block w-full rounded-md bg-red-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-red-800"
        >
          Nova inscripció
        </Link>
        <Link
          href="/"
          class="block w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
        >
          Tornar a l'Inici
        </Link>
      </div>
    </div>
  </div>
</template>
