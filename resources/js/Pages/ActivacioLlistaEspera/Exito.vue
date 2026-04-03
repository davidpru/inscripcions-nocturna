<script setup lang="ts">
import { CheckCircle, Download } from 'lucide-vue-next';

interface Inscripcion {
  id: number;
  hash_token: string;
  precio_total: number;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  participante: {
    nombre: string;
    apellidos: string;
    email: string;
  };
  edicion: {
    anio: number;
    nombre: string;
  };
}

defineProps<{
  inscripcion: Inscripcion;
}>();
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-10">
    <div class="mx-auto max-w-xl text-center">
      <CheckCircle class="mx-auto mb-4 h-16 w-16 text-green-500" />
      <h1 class="text-2xl font-bold text-slate-900">Plaça confirmada!</h1>
      <p class="mt-1 text-slate-500">{{ inscripcion.edicion.nombre }}</p>

      <div class="mt-6 rounded-lg border bg-white p-6 text-left text-sm">
        <h2 class="mb-3 font-semibold text-slate-900">Detalls de la teva inscripció</h2>
        <div class="space-y-2 text-slate-700">
          <p>
            <span class="text-slate-500">Número:</span> <strong>#{{ inscripcion.id }}</strong>
          </p>
          <p>
            <span class="text-slate-500">Participant:</span>
            <strong
              >{{ inscripcion.participante.nombre }}
              {{ inscripcion.participante.apellidos }}</strong
            >
          </p>
          <p>
            <span class="text-slate-500">Import pagat:</span>
            <strong>{{ Number(inscripcion.precio_total).toFixed(2) }}€</strong>
          </p>
          <p>
            <span class="text-slate-500">Samarreta Caro:</span>
            <strong class="uppercase">{{ inscripcion.talla_camiseta_caro }}</strong>
          </p>
          <p>
            <span class="text-slate-500">Samarreta Paüls:</span>
            <strong class="uppercase">{{ inscripcion.talla_camiseta_pauls }}</strong>
          </p>
          <p v-if="inscripcion.necesita_autobus">
            <span class="text-slate-500">Autobús:</span>
            <strong>Sí ({{ inscripcion.parada_autobus ?? 'parada no especificada' }})</strong>
          </p>
        </div>
      </div>

      <p class="mt-4 text-sm text-slate-500">
        En breu rebràs un correu electrònic de confirmació a
        <strong>{{ inscripcion.participante.email }}</strong
        >.
      </p>

      <a
        :href="`/inscripcio/d/${inscripcion.hash_token}/pdf`"
        class="mt-4 inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
        target="_blank"
      >
        <Download class="h-4 w-4" />
        Descarregar PDF amb codi QR
      </a>
    </div>
  </div>
</template>
