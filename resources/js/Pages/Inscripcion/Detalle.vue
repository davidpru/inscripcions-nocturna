<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Link, useForm } from '@inertiajs/vue3';
import { Bus, CheckCircle, Clock, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Participante {
  nombre: string;
  apellidos: string;
  dni: string;
  email: string;
  telefono: string;
  direccion: string;
  codigo_postal: string;
  poblacion: string;
  provincia: string;
  genero: string;
  fecha_nacimiento: string;
}

interface Edicion {
  anio: number;
  fecha_evento: string;
  precio_autobus: number;
}

interface Inscripcion {
  id: number;
  precio_total: number;
  estado_pago: string;
  numero_pedido: string | null;
  numero_autorizacion: string | null;
  fecha_pago: string | null;
  es_socio_uec: boolean;
  esta_federado: boolean;
  numero_licencia: string | null;
  club: string | null;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  seguro_anulacion: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  created_at: string;
  participante: Participante;
  edicion: Edicion;
}

const props = defineProps<{
  inscripcion: Inscripcion;
  precioAutobus?: number;
}>();

const mostrarFormularioAutobus = ref(false);
const autobusForm = useForm({
  parada_autobus: '',
});

const contratarAutobus = () => {
  autobusForm.post(`/inscripcion/${props.inscripcion.id}/contratar-autobus`);
};

const formatDate = (dateString: string): string => {
  if (!dateString) return '-';
  const [year, month, day] = dateString.substring(0, 10).split('-');
  return `${day}/${month}/${year}`;
};

const formatDateTime = (dateString: string): string => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getEstadoPagoInfo = (estado: string) => {
  switch (estado) {
    case 'pagado':
      return {
        icon: CheckCircle,
        text: 'Inscripción Confirmada',
        bgColor: 'bg-green-100',
        textColor: 'text-green-800',
        iconColor: 'text-green-600',
      };
    case 'pendiente':
      return {
        icon: Clock,
        text: 'Pendiente de Pago',
        bgColor: 'bg-amber-100',
        textColor: 'text-amber-800',
        iconColor: 'text-amber-600',
      };
    case 'cancelado':
      return {
        icon: XCircle,
        text: 'Inscripción Cancelada',
        bgColor: 'bg-red-100',
        textColor: 'text-red-800',
        iconColor: 'text-red-600',
      };
    default:
      return {
        icon: Clock,
        text: estado,
        bgColor: 'bg-slate-100',
        textColor: 'text-slate-800',
        iconColor: 'text-slate-600',
      };
  }
};

const estadoInfo = getEstadoPagoInfo(props.inscripcion.estado_pago);
</script>

<template>
  <Header />

  <div class="min-h-screen bg-slate-50 py-8">
    <div class="mx-auto max-w-3xl px-4">
      <!-- Estado de la inscripción -->
      <div :class="[estadoInfo.bgColor, 'mb-6 rounded-lg p-6 text-center']">
        <component
          :is="estadoInfo.icon"
          :class="['mx-auto mb-3 h-12 w-12', estadoInfo.iconColor]"
        />
        <h1 :class="['text-2xl font-bold', estadoInfo.textColor]">
          {{ estadoInfo.text }}
        </h1>
        <p class="mt-2 text-slate-600">Nocturna Fredes Paüls {{ inscripcion.edicion.anio }}</p>
      </div>

      <!-- Datos del participante -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Datos Personales</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Nombre completo</span>
            <span class="font-medium">
              {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
            </span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">DNI</span>
            <span class="font-medium">{{ inscripcion.participante.dni }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Email</span>
            <span class="font-medium">{{ inscripcion.participante.email }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Teléfono</span>
            <span class="font-medium">{{ inscripcion.participante.telefono }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Fecha de nacimiento</span>
            <span class="font-medium">{{
              formatDate(inscripcion.participante.fecha_nacimiento)
            }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Género</span>
            <span class="font-medium capitalize">{{ inscripcion.participante.genero }}</span>
          </div>
          <div class="sm:col-span-2">
            <span class="block text-sm text-slate-500">Dirección</span>
            <span class="font-medium">
              {{ inscripcion.participante.direccion }}, {{ inscripcion.participante.codigo_postal }}
              {{ inscripcion.participante.poblacion }} ({{ inscripcion.participante.provincia }})
            </span>
          </div>
        </div>
      </div>

      <!-- Detalles de la inscripción -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Detalles de la Inscripción</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Nº Inscripción</span>
            <span class="font-medium">#{{ inscripcion.id }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Fecha inscripción</span>
            <span class="font-medium">{{ formatDateTime(inscripcion.created_at) }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Socio UEC</span>
            <span class="font-medium">{{ inscripcion.es_socio_uec ? 'Sí' : 'No' }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Federado</span>
            <span class="font-medium">{{ inscripcion.esta_federado ? 'Sí' : 'No' }}</span>
          </div>
          <div v-if="inscripcion.club">
            <span class="block text-sm text-slate-500">Club</span>
            <span class="font-medium">{{ inscripcion.club }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Autobús</span>
            <span class="font-medium">
              {{ inscripcion.necesita_autobus ? `Sí (${inscripcion.parada_autobus})` : 'No' }}
            </span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Seguro de anulación</span>
            <span class="font-medium">{{ inscripcion.seguro_anulacion ? 'Sí' : 'No' }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Camiseta Caro</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_caro }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Camiseta Paüls</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_pauls }}</span>
          </div>
        </div>
      </div>

      <!-- Opción de contratar autobús si está pagado y no tiene bus -->
      <div
        v-if="inscripcion.estado_pago === 'pagado' && !inscripcion.necesita_autobus"
        class="mb-6 rounded-lg border-2 border-dashed border-blue-300 bg-blue-50 p-6"
      >
        <div class="flex items-start gap-4">
          <Bus class="h-8 w-8 shrink-0 text-blue-600" />
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-blue-900">¿Necesitas transporte?</h3>
            <p class="mt-1 text-sm text-blue-700">
              Puedes contratar el servicio de autobús por {{ precioAutobus || 12 }}€
            </p>

            <div v-if="!mostrarFormularioAutobus" class="mt-4">
              <Button @click="mostrarFormularioAutobus = true"> Contratar Autobús </Button>
            </div>

            <form v-else @submit.prevent="contratarAutobus" class="mt-4 space-y-4">
              <div>
                <Label class="mb-2 block font-medium text-blue-900">Selecciona tu parada *</Label>
                <RadioGroup
                  v-model="autobusForm.parada_autobus"
                  required
                  class="flex flex-col space-y-3"
                >
                  <div class="flex items-start space-x-2">
                    <RadioGroupItem id="parada-tortosa-det" value="tortosa" class="mt-1" />
                    <div class="flex flex-col">
                      <Label
                        for="parada-tortosa-det"
                        class="cursor-pointer font-normal text-slate-900"
                      >
                        Salida desde Tortosa
                      </Label>
                      <p class="text-sm text-slate-500">Rotonda Quatre Camins</p>
                    </div>
                  </div>
                  <div class="flex items-start space-x-2">
                    <RadioGroupItem id="parada-pauls-det" value="pauls" class="mt-1" />
                    <div class="flex flex-col">
                      <Label
                        for="parada-pauls-det"
                        class="cursor-pointer font-normal text-slate-900"
                      >
                        Salida desde Paüls
                      </Label>
                      <p class="text-sm text-slate-500">Bàscula municipal, entrada de Paüls</p>
                    </div>
                  </div>
                </RadioGroup>
                <p v-if="autobusForm.errors.parada_autobus" class="mt-1 text-sm text-red-500">
                  {{ autobusForm.errors.parada_autobus }}
                </p>
              </div>

              <div class="flex gap-3">
                <Button
                  type="submit"
                  :disabled="autobusForm.processing || !autobusForm.parada_autobus"
                >
                  {{ autobusForm.processing ? 'Procesando...' : `Pagar ${precioAutobus || 12}€` }}
                </Button>
                <Button type="button" variant="outline" @click="mostrarFormularioAutobus = false">
                  Cancelar
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Información de pago -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Información de Pago</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Importe total</span>
            <span class="text-xl font-bold text-slate-900">{{ inscripcion.precio_total }}€</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Estado</span>
            <span
              :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                estadoInfo.bgColor,
                estadoInfo.textColor,
              ]"
            >
              {{ inscripcion.estado_pago }}
            </span>
          </div>
          <div v-if="inscripcion.fecha_pago">
            <span class="block text-sm text-slate-500">Fecha de pago</span>
            <span class="font-medium">{{ formatDateTime(inscripcion.fecha_pago) }}</span>
          </div>
          <div v-if="inscripcion.numero_autorizacion">
            <span class="block text-sm text-slate-500">Nº Autorización</span>
            <span class="font-medium">{{ inscripcion.numero_autorizacion }}</span>
          </div>
        </div>
      </div>

      <!-- Botón de pago si está pendiente -->
      <div v-if="inscripcion.estado_pago === 'pendiente'" class="mb-6 rounded-lg bg-amber-50 p-6">
        <p class="mb-4 text-center text-amber-800">
          Tu inscripción está pendiente de pago. Pulsa el botón para completar el pago.
        </p>
        <div class="flex justify-center">
          <Link :href="`/pago/${inscripcion.id}`">
            <Button size="lg">Completar Pago</Button>
          </Link>
        </div>
      </div>

      <!-- Botones de navegación -->
      <div class="flex justify-center gap-4">
        <Link href="/">
          <Button variant="outline">Volver al inicio</Button>
        </Link>
      </div>
    </div>
  </div>
</template>
