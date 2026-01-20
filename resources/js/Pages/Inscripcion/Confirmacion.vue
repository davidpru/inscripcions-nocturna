<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface Participante {
  dni: string;
  nombre: string;
  apellidos: string;
  email: string;
  telefono: string;
}

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
}

interface Inscripcion {
  id: number;
  precio_total: number;
  estado_pago: string;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  necesita_autobus: boolean;
  seguro_anulacion: boolean;
  participante: Participante;
  edicion: Edicion;
}

const props = defineProps<{
  inscripcion: Inscripcion;
}>();

const imprimir = () => {
  window.print();
};
</script>

<template>
  <Head title="Confirmación de Inscripción" />

  <div class="min-h-screen bg-gradient-to-b from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 py-12 px-4">
    <div class="max-w-2xl mx-auto">
      <!-- Success Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-500 rounded-full mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-100 mb-2">
          ¡Inscripción Confirmada!
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400">
          Tu inscripción se ha registrado correctamente
        </p>
      </div>

      <!-- Información de la Inscripción -->
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-8 space-y-6">
        <div>
          <h2 class="text-2xl font-semibold mb-4 text-slate-900 dark:text-slate-100">
            Detalles de la Inscripción
          </h2>
          
          <div class="space-y-3">
            <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
              <span class="text-slate-600 dark:text-slate-400">Número de Inscripción:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">#{{ inscripcion.id }}</span>
            </div>

            <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
              <span class="text-slate-600 dark:text-slate-400">Evento:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">
                Nocturna Fredes Paüls {{ inscripcion.edicion.anio }}
              </span>
            </div>

            <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
              <span class="text-slate-600 dark:text-slate-400">Participante:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">
                {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
              </span>
            </div>

            <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
              <span class="text-slate-600 dark:text-slate-400">DNI:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">
                {{ inscripcion.participante.dni }}
              </span>
            </div>

            <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
              <span class="text-slate-600 dark:text-slate-400">Email:</span>
              <span class="font-semibold text-slate-900 dark:text-slate-100">
                {{ inscripcion.participante.email }}
              </span>
            </div>
          </div>
        </div>

        <!-- Servicios Contratados -->
        <div>
          <h3 class="text-xl font-semibold mb-3 text-slate-900 dark:text-slate-100">
            Servicios Contratados
          </h3>
          <div class="space-y-2">
            <div v-if="inscripcion.necesita_autobus" class="flex items-center text-slate-700 dark:text-slate-300">
              <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Autobús Paüls-Fredes
            </div>
            <div v-if="inscripcion.seguro_anulacion" class="flex items-center text-slate-700 dark:text-slate-300">
              <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Seguro de Anulación
            </div>
          </div>
        </div>

        <!-- Tallas -->
        <div>
          <h3 class="text-xl font-semibold mb-3 text-slate-900 dark:text-slate-100">
            Tallas de Camisetas
          </h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-slate-600 dark:text-slate-400">Camiseta Caro</p>
              <p class="font-semibold text-slate-900 dark:text-slate-100">{{ inscripcion.talla_camiseta_caro }}</p>
            </div>
            <div>
              <p class="text-sm text-slate-600 dark:text-slate-400">Camiseta Paüls</p>
              <p class="font-semibold text-slate-900 dark:text-slate-100">{{ inscripcion.talla_camiseta_pauls }}</p>
            </div>
          </div>
        </div>

        <!-- Precio Total -->
        <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-6">
          <div class="flex justify-between items-center">
            <span class="text-xl font-semibold text-slate-900 dark:text-slate-100">Total a Pagar:</span>
            <span class="text-3xl font-bold text-green-600 dark:text-green-400">
              {{ inscripcion.precio_total }}€
            </span>
          </div>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
            Estado: <span class="font-semibold">{{ inscripcion.estado_pago.toUpperCase() }}</span>
          </p>
        </div>

        <!-- Información Adicional -->
        <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
          <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
            Próximos Pasos
          </h3>
          <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
            <li>• Recibirás un email de confirmación en {{ inscripcion.participante.email }}</li>
            <li>• En breve te enviaremos los detalles para realizar el pago</li>
            <li>• Guarda tu número de inscripción: #{{ inscripcion.id }}</li>
          </ul>
        </div>

        <!-- Botones -->
        <div class="flex gap-4 justify-center pt-4">
          <Button variant="outline" as="a" href="/">
            Volver al Inicio
          </Button>
          <Button @click="imprimir">
            Imprimir Confirmación
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
