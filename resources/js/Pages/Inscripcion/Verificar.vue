<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, CreditCard, Shield, Shirt, User } from 'lucide-vue-next';

interface Participante {
  id: number;
  nombre: string;
  apellidos: string;
  dni: string;
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
  es_socio_uec: boolean;
  esta_federado: boolean;
  numero_licencia: string | null;
  club: string | null;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  seguro_anulacion: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  es_celiaco: boolean;
  tarifa_aplicada: string;
  precio_total: number;
  estado_pago: string;
  created_at: string;
}

defineProps<{
  inscripcion: Inscripcion;
  participante: Participante;
  edicion: Edicion;
}>();
</script>

<template>
  <Head :title="`Verificació Inscripció #${inscripcion.id}`" />

  <div class="min-h-screen bg-gradient-to-b from-slate-100 to-slate-200 py-12">
    <div class="mx-auto max-w-2xl px-4">
      <div class="overflow-hidden rounded-2xl bg-white shadow-xl">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-8 text-center text-white">
          <CheckCircle class="mx-auto mb-4 h-16 w-16" />
          <h1 class="text-2xl font-bold">Inscripció Verificada</h1>
          <p class="mt-2 text-red-100">Nocturna del Caro {{ edicion.anio }}</p>
        </div>

        <!-- Content -->
        <div class="space-y-6 p-6">
          <!-- Número de inscripción -->
          <div class="text-center">
            <span class="text-sm text-slate-500">Número d'inscripció</span>
            <div class="text-4xl font-bold text-slate-900">#{{ inscripcion.id }}</div>
          </div>

          <!-- Estado del pago -->
          <div class="flex justify-center">
            <span
              :class="
                inscripcion.estado_pago === 'pagado'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-amber-100 text-amber-800'
              "
              class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold"
            >
              <CreditCard class="h-4 w-4" />
              {{ inscripcion.estado_pago === 'pagado' ? 'PAGAT' : 'PENDENT' }}
            </span>
          </div>

          <!-- Datos del participante -->
          <div class="rounded-lg bg-slate-50 p-4">
            <div class="mb-3 flex items-center gap-2 text-slate-700">
              <User class="h-5 w-5" />
              <span class="font-semibold">Participant</span>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Nom:</span>
                <span class="font-medium"
                  >{{ participante.nombre }} {{ participante.apellidos }}</span
                >
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">DNI:</span>
                <span class="font-medium">{{ participante.dni }}</span>
              </div>
            </div>
          </div>

          <!-- Opciones contratadas -->
          <div class="rounded-lg bg-slate-50 p-4">
            <div class="mb-3 flex items-center gap-2 text-slate-700">
              <Shield class="h-5 w-5" />
              <span class="font-semibold">Opcions</span>
            </div>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Tarifa:</span>
                <span class="font-medium">{{ inscripcion.tarifa_aplicada }}</span>
              </div>
              <div v-if="inscripcion.necesita_autobus" class="flex justify-between">
                <span class="text-slate-500">Autobús:</span>
                <span class="font-medium">{{ inscripcion.parada_autobus }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Assegurança:</span>
                <span class="font-medium">{{ inscripcion.seguro_anulacion ? 'Sí' : 'No' }}</span>
              </div>
            </div>
          </div>

          <!-- Tallas -->
          <div class="rounded-lg bg-slate-50 p-4">
            <div class="mb-3 flex items-center gap-2 text-slate-700">
              <Shirt class="h-5 w-5" />
              <span class="font-semibold">Samarretes</span>
            </div>
            <div class="flex justify-around text-center">
              <div>
                <span class="text-xs text-slate-500">Caro</span>
                <div class="text-lg font-bold">
                  {{ inscripcion.talla_camiseta_caro.toUpperCase() }}
                </div>
              </div>
              <div>
                <span class="text-xs text-slate-500">Paüls</span>
                <div class="text-lg font-bold">
                  {{ inscripcion.talla_camiseta_pauls.toUpperCase() }}
                </div>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="rounded-lg border-2 border-red-200 bg-red-50 p-4 text-center">
            <span class="text-sm text-slate-500">Total pagat</span>
            <div class="text-3xl font-bold text-red-600">{{ inscripcion.precio_total }}€</div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t bg-slate-50 px-6 py-4 text-center">
          <Link href="/">
            <Button variant="outline">Tornar a l'inici</Button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
