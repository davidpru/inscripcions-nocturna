<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { PARADAS, getParadaLabel } from '@/constants/paradas';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Bus, CheckCircle, Clock, Mail, XCircle } from 'lucide-vue-next';
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
const mostrarFormularioCambiarParada = ref(false);
const autobusForm = useForm({
  parada_autobus: '',
});
const cambiarParadaForm = useForm({
  parada_autobus: props.inscripcion.parada_autobus || '',
});

const contratarAutobus = () => {
  autobusForm.post(`/inscripcio/${props.inscripcion.id}/contratar-autobus`);
};

const cambiarParada = () => {
  cambiarParadaForm.post(`/inscripcio/${props.inscripcion.id}/cambiar-parada`, {
    preserveScroll: true,
    onSuccess: () => {
      mostrarFormularioCambiarParada.value = false;
    },
  });
};

const reenviarCorreo = () => {
  router.post(
    `/admin/inscripciones/${props.inscripcion.id}/reenviar-correo`,
    {},
    {
      preserveScroll: true,
    }
  );
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
        text: 'Inscripció Confirmada',
        bgColor: 'bg-green-100',
        textColor: 'text-green-800',
        iconColor: 'text-green-600',
      };
    case 'pendiente':
      return {
        icon: Clock,
        text: 'Pendent de Pagament',
        bgColor: 'bg-amber-100',
        textColor: 'text-amber-800',
        iconColor: 'text-amber-600',
      };
    case 'cancelado':
      return {
        icon: XCircle,
        text: 'Inscripció Cancel·lada',
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
  <Head title="Detall de la Inscripció" />
  <Header />

  <div class="min-h-screen py-8">
    <div class="mx-auto max-w-3xl px-4">
      <!-- Estado de la inscripción -->
      <div :class="[estadoInfo.bgColor, 'mb-6 rounded-lg p-6 text-center']">
        <component
          :is="estadoInfo.icon"
          :class="['mx-auto mb-3 h-12 w-12', estadoInfo.iconColor]"
        />
        <h1 :class="['text-xl font-bold md:text-2xl', estadoInfo.textColor]">
          {{ estadoInfo.text }}
        </h1>
        <p class="mt-2 text-slate-600">Nocturna Fredes Paüls {{ inscripcion.edicion.anio }}</p>
      </div>

      <!-- Dades del participant -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Dades Personals</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Nom complet</span>
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
            <span class="block text-sm text-slate-500">Telèfon</span>
            <span class="font-medium">{{ inscripcion.participante.telefono }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Data de naixement</span>
            <span class="font-medium">{{
              formatDate(inscripcion.participante.fecha_nacimiento)
            }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Gènere</span>
            <span class="font-medium capitalize">{{ inscripcion.participante.genero }}</span>
          </div>
          <div class="sm:col-span-2">
            <span class="block text-sm text-slate-500">Adreça</span>
            <span class="font-medium">
              {{ inscripcion.participante.direccion }}, {{ inscripcion.participante.codigo_postal }}
              {{ inscripcion.participante.poblacion }} ({{ inscripcion.participante.provincia }})
            </span>
          </div>
        </div>
      </div>

      <!-- Detalls de la inscripció -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Detalls de la Inscripció</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Nº Inscripció</span>
            <span class="font-medium">#{{ inscripcion.id }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Data inscripció</span>
            <span class="font-medium">{{ formatDateTime(inscripcion.created_at) }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Socio UEC</span>
            <span class="font-medium">{{ inscripcion.es_socio_uec ? 'Sí' : 'No' }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Federat</span>
            <span class="font-medium">{{ inscripcion.esta_federado ? 'Sí' : 'No' }}</span>
          </div>
          <div v-if="inscripcion.club">
            <span class="block text-sm text-slate-500">Club</span>
            <span class="font-medium">{{ inscripcion.club }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Autobús</span>
            <span class="block font-medium">
              {{
                inscripcion.necesita_autobus
                  ? `Sí (${getParadaLabel(inscripcion.parada_autobus)})`
                  : 'No'
              }}
            </span>
            <button
              v-if="inscripcion.necesita_autobus && inscripcion.estado_pago === 'pagado'"
              type="button"
              class="text-xs text-blue-600 hover:text-blue-800 hover:underline"
              @click="mostrarFormularioCambiarParada = true"
            >
              Canviar parada
            </button>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Assegurança d'anul·lació</span>
            <span class="font-medium">{{ inscripcion.seguro_anulacion ? 'Sí' : 'No' }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Samarreta Caro</span>
            <span class="font-medium">Talla {{ inscripcion.talla_camiseta_caro }}</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Samarreta Paüls</span>
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
            <h3 class="text-lg font-semibold text-blue-900">Necessites transport?</h3>
            <p class="mt-1 text-sm text-blue-700">
              Pots contractar el servei d'autobús per {{ precioAutobus || 12 }}€
            </p>

            <div v-if="!mostrarFormularioAutobus" class="mt-4">
              <Button @click="mostrarFormularioAutobus = true"> Contractar Autobús </Button>
            </div>

            <form v-else @submit.prevent="contratarAutobus" class="mt-4 space-y-4">
              <div>
                <Label class="mb-2 block font-medium text-blue-900"
                  >Selecciona la teva parada *</Label
                >
                <RadioGroup
                  v-model="autobusForm.parada_autobus"
                  required
                  class="flex flex-col space-y-3"
                >
                  <div
                    v-for="parada in PARADAS"
                    :key="parada.value"
                    class="flex items-start space-x-2"
                  >
                    <RadioGroupItem
                      :id="`parada-${parada.value}-det`"
                      :value="parada.value"
                      class="mt-1"
                    />
                    <div class="flex flex-col">
                      <Label
                        :for="`parada-${parada.value}-det`"
                        class="cursor-pointer font-normal text-slate-900"
                      >
                        {{ parada.label }}
                      </Label>
                      <p class="text-sm text-slate-500">{{ parada.descripcion }}</p>
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
                  {{ autobusForm.processing ? 'Processant...' : `Pagar ${precioAutobus || 12}€` }}
                </Button>
                <Button type="button" variant="outline" @click="mostrarFormularioAutobus = false">
                  Cancel·lar
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Informació de pagament -->
      <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Informació de Pagament</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <span class="block text-sm text-slate-500">Import total</span>
            <span class="text-xl font-bold text-slate-900">{{ inscripcion.precio_total }}€</span>
          </div>
          <div>
            <span class="block text-sm text-slate-500">Estat</span>
            <span
              :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                estadoInfo.bgColor,
                estadoInfo.textColor,
              ]"
            >
              {{ estadoInfo.text }}
            </span>
          </div>
          <div v-if="inscripcion.fecha_pago">
            <span class="block text-sm text-slate-500">Data de pagament</span>
            <span class="font-medium">{{ formatDateTime(inscripcion.fecha_pago) }}</span>
          </div>
          <div v-if="inscripcion.numero_autorizacion">
            <span class="block text-sm text-slate-500">Nº Autorització</span>
            <span class="font-medium">{{ inscripcion.numero_autorizacion }}</span>
          </div>
        </div>
      </div>

      <!-- Dialog para cambiar parada de autobús -->
      <Dialog v-model:open="mostrarFormularioCambiarParada">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <Bus class="h-5 w-5 text-blue-600" />
              Canviar parada d'autobús
            </DialogTitle>
            <DialogDescription>
              Selecciona la nova parada on vols pujar a l'autobús
            </DialogDescription>
          </DialogHeader>

          <form @submit.prevent="cambiarParada" class="space-y-4">
            <div>
              <RadioGroup
                v-model="cambiarParadaForm.parada_autobus"
                required
                class="flex flex-col space-y-3"
              >
                <div
                  v-for="parada in PARADAS"
                  :key="parada.value"
                  class="flex items-start space-x-2"
                >
                  <RadioGroupItem
                    :id="`cambiar-parada-${parada.value}`"
                    :value="parada.value"
                    class="mt-1"
                  />
                  <div class="flex flex-col">
                    <Label
                      :for="`cambiar-parada-${parada.value}`"
                      class="cursor-pointer font-normal text-slate-900"
                    >
                      {{ parada.label }}
                    </Label>
                    <p class="text-sm text-slate-500">{{ parada.descripcion }}</p>
                  </div>
                </div>
              </RadioGroup>
              <p v-if="cambiarParadaForm.errors.parada_autobus" class="mt-1 text-sm text-red-500">
                {{ cambiarParadaForm.errors.parada_autobus }}
              </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
              <Button
                type="button"
                variant="outline"
                @click="mostrarFormularioCambiarParada = false"
              >
                Cancel·lar
              </Button>
              <Button
                type="submit"
                :disabled="cambiarParadaForm.processing || !cambiarParadaForm.parada_autobus"
              >
                {{ cambiarParadaForm.processing ? 'Guardant...' : 'Guardar canvis' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Botón de pago si está pendiente -->
      <div v-if="inscripcion.estado_pago === 'pendiente'" class="mb-6 rounded-lg bg-amber-50 p-6">
        <p class="mb-4 text-center text-amber-800">
          La teva inscripció està pendent de pagament. Prem el botó per completar el pagament.
        </p>
        <div class="flex justify-center">
          <Link :href="`/pago/${inscripcion.id}`">
            <Button size="lg">Completar Pagament</Button>
          </Link>
        </div>
      </div>

      <!-- Botones de navegación -->
      <div class="flex flex-wrap justify-center gap-4">
        <Link href="/">
          <Button variant="outline">Tornar a l'inici</Button>
        </Link>
        <Button v-if="inscripcion.estado_pago === 'pagado'" @click="reenviarCorreo" class="gap-2">
          <Mail class="h-4 w-4" />
          Reenviar correu de confirmació
        </Button>
      </div>
    </div>
  </div>
</template>
