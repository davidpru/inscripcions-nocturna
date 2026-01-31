<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Download } from 'lucide-vue-next';
import { onMounted } from 'vue';

interface Participante {
  nombre: string;
  apellidos: string;
  email: string;
  club?: string | null;
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
  es_socio_uec: boolean;
  esta_federado: boolean;
  participante: Participante;
  edicion: Edicion;
}

interface Transaction {
  transaction_id: string;
  value: number;
  currency: string;
  items: Array<{
    item_id: string;
    item_name: string;
    item_category: string;
    price: number;
    quantity: number;
  }>;
}

const props = defineProps<{
  inscripcion: Inscripcion;
  transaction?: Transaction;
}>();

const descargarPdf = () => {
  window.location.href = `/inscripcio/${props.inscripcion.id}/pdf`;
};

// Enviar evento de conversión a Google Analytics 4
onMounted(() => {
  if (props.transaction && typeof window.gtag !== 'undefined') {
    window.gtag('event', 'purchase', {
      transaction_id: props.transaction.transaction_id,
      value: props.transaction.value,
      currency: props.transaction.currency,
      items: props.transaction.items,
    });
  }
});
</script>

<template>
  <Head title="Inscripció Confirmada" />
  <Header />

  <div class="min-h-screen py-8">
    <div class="mx-auto max-w-2xl px-4">
      <!-- Mensaje de éxito -->
      <div class="mb-6 rounded-lg bg-green-100 p-8 text-center">
        <CheckCircle class="mx-auto mb-4 h-16 w-16 text-green-600" />
        <h1 class="font-expanded mb-2 text-2xl font-bold text-green-800">Enhorabona!</h1>
        <p class="mb-4 text-balance text-green-700">
          La teva inscripció per la Nocturna Fredes Paüls {{ inscripcion.edicion.anio }} ha estat
          confirmada i el pagament s'ha realitzat correctament.
        </p>
        <Button @click="descargarPdf" class="gap-2">
          <Download class="h-4 w-4" />
          Descargar PDF
        </Button>
      </div>

      <!-- Resumen de la inscripción -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Resum de la teua Inscripció</h2>
        <div class="space-y-3 text-xs md:text-sm">
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Nº Inscripció</span>
            <span class="font-medium">#{{ inscripcion.id }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Participant</span>
            <span class="font-medium">
              {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
            </span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Tipus d'Inscripció</span>
            <span class="font-medium">
              {{ inscripcion.es_socio_uec ? 'Soci UEC' : 'Públic' }}
              {{ inscripcion.esta_federado ? '(Federat)' : '(No federat)' }}
            </span>
          </div>
          <div v-if="inscripcion.participante.club" class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Club</span>
            <span class="font-medium">{{ inscripcion.participante.club }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Samarreta Caro</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_caro }}</span>
          </div>
          <div class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Samarreta Finisher (Jo Tota)</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_pauls }}</span>
          </div>
          <div v-if="inscripcion.necesita_autobus" class="flex justify-between border-b pb-2">
            <span class="text-slate-600">Autobús</span>
            <span class="font-medium">{{ inscripcion.parada_autobus }}</span>
          </div>
          <div class="flex justify-between pt-2">
            <span class="text-lg font-semibold text-slate-900">Total pagat</span>
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
