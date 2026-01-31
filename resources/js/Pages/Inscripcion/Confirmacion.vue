<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';

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

const descargarPdf = () => {
  window.location.href = `/inscripcio/${props.inscripcion.id}/pdf`;
};
</script>

<template>
  <Head title="Confirmación de Inscripción" />

  <div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-2xl">
      <!-- Success Header -->
      <div class="mb-8 text-center">
        <div
          class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-500"
        >
          <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </div>
        <h1 class="mb-2 text-4xl font-bold text-slate-900">¡Inscripción Confirmada!</h1>
        <p class="text-lg text-slate-600">Tu inscripción se ha registrado correctamente</p>
      </div>

      <!-- Información de la Inscripción -->
      <div class="space-y-6 rounded-lg bg-white p-8 shadow-lg">
        <div>
          <h2 class="mb-4 text-2xl font-semibold text-slate-900">Detalles de la Inscripción</h2>

          <div class="space-y-3">
            <div class="flex justify-between border-b border-slate-200 py-2">
              <span class="text-slate-600">Número de Inscripción:</span>
              <span class="font-semibold text-slate-900">#{{ inscripcion.id }}</span>
            </div>

            <div class="flex justify-between border-b border-slate-200 py-2">
              <span class="text-slate-600">Evento:</span>
              <span class="font-semibold text-slate-900">
                Nocturna Fredes Paüls {{ inscripcion.edicion.anio }}
              </span>
            </div>

            <div class="flex justify-between border-b border-slate-200 py-2">
              <span class="text-slate-600">Participante:</span>
              <span class="font-semibold text-slate-900">
                {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
              </span>
            </div>

            <div class="flex justify-between border-b border-slate-200 py-2">
              <span class="text-slate-600">DNI:</span>
              <span class="font-semibold text-slate-900">
                {{ inscripcion.participante.dni }}
              </span>
            </div>

            <div class="flex justify-between border-b border-slate-200 py-2">
              <span class="text-slate-600">Email:</span>
              <span class="font-semibold text-slate-900">
                {{ inscripcion.participante.email }}
              </span>
            </div>
          </div>
        </div>

        <!-- Servicios Contratados -->
        <div>
          <h3 class="mb-3 text-xl font-semibold text-slate-900">Servicios Contratados</h3>
          <div class="space-y-2">
            <div v-if="inscripcion.necesita_autobus" class="flex items-center text-slate-700">
              <svg
                class="mr-2 h-5 w-5 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              Autobús Paüls-Fredes
            </div>
            <div v-if="inscripcion.seguro_anulacion" class="flex items-center text-slate-700">
              <svg
                class="mr-2 h-5 w-5 text-green-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              Seguro de Anulación
            </div>
          </div>
        </div>

        <!-- Tallas -->
        <div>
          <h3 class="mb-3 text-xl font-semibold text-slate-900">Tallas de Camisetas</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-slate-600">Camiseta Caro</p>
              <p class="font-semibold text-slate-900">
                {{ inscripcion.talla_camiseta_caro }}
              </p>
            </div>
            <div>
              <p class="text-sm text-slate-600">Camiseta Paüls</p>
              <p class="font-semibold text-slate-900">
                {{ inscripcion.talla_camiseta_pauls }}
              </p>
            </div>
          </div>
        </div>

        <!-- Precio Total -->
        <div class="rounded-lg bg-slate-50 p-6">
          <div class="flex items-center justify-between">
            <span class="text-xl font-semibold text-slate-900">Total a Pagar:</span>
            <span class="text-3xl font-bold text-green-600"> {{ inscripcion.precio_total }}€ </span>
          </div>
          <p class="mt-2 text-sm text-slate-600">
            Estado: <span class="font-semibold">{{ inscripcion.estado_pago.toUpperCase() }}</span>
          </p>
        </div>

        <!-- Información Adicional -->
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
          <h3 class="mb-2 font-semibold text-blue-900">Próximos Pasos</h3>
          <ul class="space-y-1 text-sm text-blue-800">
            <li>• Recibirás un email de confirmación en {{ inscripcion.participante.email }}</li>
            <li>• En breve te enviaremos los detalles para realizar el pago</li>
            <li>• Guarda tu número de inscripción: #{{ inscripcion.id }}</li>
          </ul>
        </div>

        <!-- Botones -->
        <div class="flex flex-wrap justify-center gap-4 pt-4">
          <Button variant="outline" as="a" href="/"> Tornar a l'Inici </Button>
          <Button v-if="inscripcion.estado_pago === 'pagado'" @click="descargarPdf" class="gap-2">
            <Download class="h-4 w-4" />
            Descarregar PDF
          </Button>
          <Button @click="imprimir"> Imprimir Confirmació </Button>
        </div>
      </div>
    </div>
  </div>
</template>
