<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';

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
  numero_autorizacion: string | null;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  participante: Participante;
  edicion: Edicion;
}

defineProps<{
  inscripcion: Inscripcion;
}>();
</script>

<template>
  <Header />

  <div class="min-h-screen bg-slate-50 py-8">
    <div class="mx-auto max-w-2xl px-4">
      <!-- Mensaje de éxito -->
      <div class="mb-6 rounded-lg bg-green-100 p-8 text-center">
        <CheckCircle class="mx-auto mb-4 h-16 w-16 text-green-600" />
        <h1 class="mb-2 text-2xl font-bold text-green-800">¡Pago realizado con éxito!</h1>
        <p class="text-green-700">
          Tu inscripción para la Nocturna Fredes Paüls {{ inscripcion.edicion.anio }} ha sido
          confirmada.
        </p>
      </div>

      <!-- Resumen de la inscripción -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Resumen de tu Inscripción</h2>
        <div class="space-y-3">
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Nº Inscripción</span>
            <span class="font-medium">#{{ inscripcion.id }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Participant</span>
            <span class="font-medium">
              {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
            </span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Camiseta Caro</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_caro }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Camiseta Paüls</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_pauls }}</span>
          </div>
          <div v-if="inscripcion.necesita_autobus" class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Autobús</span>
            <span class="font-medium">{{ inscripcion.parada_autobus }}</span>
          </div>
          <div class="flex justify-between pt-2">
            <span class="text-lg font-semibold text-slate-900">Total pagado</span>
            <span class="text-lg font-bold text-green-600">{{ inscripcion.precio_total }}€</span>
          </div>
        </div>
      </div>

      <!-- Información adicional -->
      <div class="mb-6 rounded-lg bg-blue-50 p-4">
        <p class="text-sm text-blue-800">
          <strong>Importante:</strong> Recibirás un email de confirmación en
          <strong>{{ inscripcion.participante.email }}</strong> con todos los detalles de tu
          inscripción y la información necesaria para el día del evento.
        </p>
      </div>

      <!-- Botones -->
      <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
        <Link href="/">
          <Button class="w-full sm:w-auto">Volver al inicio</Button>
        </Link>
        <Link href="/inscripcions/consulta">
          <Button variant="outline" class="w-full sm:w-auto">Consultar mi inscripción</Button>
        </Link>
      </div>
    </div>
  </div>
</template>
