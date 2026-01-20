<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Participante {
  nombre: string;
  apellidos: string;
  email: string;
}

interface Edicion {
  anio: number;
}

interface Inscripcion {
  id: number;
  precio_total: number;
  participante: Participante;
  edicion: Edicion;
}

defineProps<{
  inscripcion: Inscripcion;
}>();
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow-lg">
      <div class="mb-6">
        <div
          class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
        >
          <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </div>
        <h1 class="mb-2 text-2xl font-bold text-slate-900">¡Pago realizado con éxito!</h1>
        <p class="text-slate-600">
          Tu inscripción ha sido confirmada. Recibirás un email con los detalles.
        </p>
      </div>

      <div class="mb-6 rounded-lg bg-green-50 p-4">
        <p class="text-sm text-green-800">
          Tu número de inscripción y los detalles del evento han sido enviados a tu correo
          electrónico.
        </p>
      </div>

      <div class="space-y-3">
        <Link
          href="/"
          class="block w-full rounded-md bg-red-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-red-800"
        >
          Volver al inicio
        </Link>
        <Link
          href="/inscripcion/consulta"
          class="block w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
        >
          Consultar mi inscripción
        </Link>
      </div>
    </div>
  </div>
</template>
