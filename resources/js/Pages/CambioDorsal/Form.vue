<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, Clock, Info } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface CambioDorsal {
  id: number;
  token: string;
  precio_base: number;
  expires_at: string;
}

interface Inscripcion {
  id: number;
  esta_federado: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  edicion: {
    anio: number;
    nombre: string;
    precio_licencia_federativa_socio: number;
    precio_licencia_federativa_publico: number;
  };
}

const props = defineProps<{
  cambioDorsal: CambioDorsal;
  inscripcion: Inscripcion;
}>();

// Countdown timer
const secondsLeft = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const updateCountdown = () => {
  const diff = Math.floor((new Date(props.cambioDorsal.expires_at).getTime() - Date.now()) / 1000);
  secondsLeft.value = Math.max(0, diff);
  if (secondsLeft.value === 0 && timer) {
    clearInterval(timer);
  }
};

onMounted(() => {
  updateCountdown();
  timer = setInterval(updateCountdown, 1000);
});

onBeforeUnmount(() => {
  if (timer) clearInterval(timer);
});

const countdownDisplay = computed(() => {
  const h = Math.floor(secondsLeft.value / 3600);
  const m = Math.floor((secondsLeft.value % 3600) / 60);
  const s = secondsLeft.value % 60;
  return `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
});

const isExpired = computed(() => secondsLeft.value === 0);

// Form
const form = ref({
  dni: '',
  nombre: '',
  apellidos: '',
  genero: '',
  fecha_nacimiento: '',
  telefono: '',
  email: '',
  email_confirm: '',
  direccion: '',
  codigo_postal: '',
  poblacion: '',
  provincia: '',
  es_socio_uec: false,
  club: '',
  esta_federado: false,
  numero_licencia: '',
  es_celiaco: false,
});

const errors = ref<Record<string, string>>({});
const submitting = ref(false);

// Precio: base + federativa si el origen no la tenía y el destino sí
const precioFederativa = computed(() => {
  if (form.value.esta_federado && !props.inscripcion.esta_federado) {
    return form.value.es_socio_uec
      ? props.inscripcion.edicion.precio_licencia_federativa_socio
      : props.inscripcion.edicion.precio_licencia_federativa_publico;
  }
  return 0;
});

const precioTotal = computed(() => props.cambioDorsal.precio_base + precioFederativa.value);

const submit = () => {
  if (isExpired.value) return;
  errors.value = {};
  submitting.value = true;
  router.post(
    `/canvi-dorsal/${props.cambioDorsal.token}`,
    { ...form.value },
    {
      onError: (e) => {
        errors.value = e as Record<string, string>;
        submitting.value = false;
      },
    }
  );
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-10">
    <div class="mx-auto max-w-xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Canvi de Dorsal</h1>
        <p class="mt-1 text-slate-500">{{ inscripcion.edicion.nombre }}</p>
      </div>

      <!-- Expirado -->
      <div v-if="isExpired" class="rounded-lg border border-red-200 bg-red-50 p-6 text-center">
        <AlertTriangle class="mx-auto mb-3 h-10 w-10 text-red-500" />
        <p class="font-semibold text-red-700">L'enllaç ha caducat.</p>
        <p class="mt-1 text-sm text-red-600">
          Si necessites realitzar el canvi, contacta amb l'organització.
        </p>
      </div>

      <template v-else>
        <!-- Countdown -->
        <div
          class="mb-6 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3"
        >
          <Clock class="h-5 w-5 shrink-0 text-amber-600" />
          <div>
            <p class="text-sm font-medium text-amber-800">
              Temps restant per completar el pagament
            </p>
            <p class="font-mono text-lg font-bold text-amber-700">{{ countdownDisplay }}</p>
          </div>
        </div>

        <!-- Info herència tallas + bus -->
        <div class="mb-6 rounded-lg border bg-white p-4">
          <div class="flex items-start gap-2">
            <Info class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" />
            <p class="text-sm text-slate-600">
              Les talles de samarreta i el servei d'autobús s'hereten de la inscripció original i no
              es poden modificar.
            </p>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded bg-slate-50 px-3 py-2">
              <p class="text-xs text-slate-500">Samarreta Caro</p>
              <p class="font-semibold uppercase">{{ inscripcion.talla_camiseta_caro }}</p>
            </div>
            <div class="rounded bg-slate-50 px-3 py-2">
              <p class="text-xs text-slate-500">Samarreta Paüls</p>
              <p class="font-semibold uppercase">{{ inscripcion.talla_camiseta_pauls }}</p>
            </div>
            <div class="rounded bg-slate-50 px-3 py-2">
              <p class="text-xs text-slate-500">Autobús</p>
              <p class="font-semibold">
                {{
                  inscripcion.necesita_autobus
                    ? `Sí (${inscripcion.parada_autobus ?? 'parada no especificada'})`
                    : 'No'
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form class="space-y-5 rounded-lg border bg-white p-6" @submit.prevent="submit">
          <h2 class="font-semibold text-slate-900">Dades del nou participant</h2>

          <!-- DNI -->
          <div class="space-y-1">
            <Label for="dni">DNI / NIE *</Label>
            <Input id="dni" v-model="form.dni" :class="errors.dni ? 'border-red-500' : ''" />
            <p v-if="errors.dni" class="text-xs text-red-600">{{ errors.dni }}</p>
          </div>

          <!-- Nom + Cognoms -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1">
              <Label for="nombre">Nom *</Label>
              <Input
                id="nombre"
                v-model="form.nombre"
                :class="errors.nombre ? 'border-red-500' : ''"
              />
              <p v-if="errors.nombre" class="text-xs text-red-600">{{ errors.nombre }}</p>
            </div>
            <div class="space-y-1">
              <Label for="apellidos">Cognoms *</Label>
              <Input
                id="apellidos"
                v-model="form.apellidos"
                :class="errors.apellidos ? 'border-red-500' : ''"
              />
              <p v-if="errors.apellidos" class="text-xs text-red-600">{{ errors.apellidos }}</p>
            </div>
          </div>

          <!-- Gènere + Data naixement -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1">
              <Label for="genero">Gènere *</Label>
              <select
                id="genero"
                v-model="form.genero"
                class="border-input bg-background ring-offset-background focus:ring-ring w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:outline-none"
                :class="errors.genero ? 'border-red-500' : ''"
              >
                <option value="">Selecciona...</option>
                <option value="masculino">Masculí</option>
                <option value="femenino">Femení</option>
              </select>
              <p v-if="errors.genero" class="text-xs text-red-600">{{ errors.genero }}</p>
            </div>
            <div class="space-y-1">
              <Label for="fecha_nacimiento">Data de naixement *</Label>
              <Input
                id="fecha_nacimiento"
                v-model="form.fecha_nacimiento"
                type="date"
                :class="errors.fecha_nacimiento ? 'border-red-500' : ''"
              />
              <p v-if="errors.fecha_nacimiento" class="text-xs text-red-600">
                {{ errors.fecha_nacimiento }}
              </p>
            </div>
          </div>

          <!-- Telèfon -->
          <div class="space-y-1">
            <Label for="telefono">Telèfon *</Label>
            <Input
              id="telefono"
              v-model="form.telefono"
              type="tel"
              :class="errors.telefono ? 'border-red-500' : ''"
            />
            <p v-if="errors.telefono" class="text-xs text-red-600">{{ errors.telefono }}</p>
          </div>

          <!-- Email + confirmació -->
          <div class="space-y-1">
            <Label for="email">Correu electrònic *</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              :class="errors.email ? 'border-red-500' : ''"
            />
            <p v-if="errors.email" class="text-xs text-red-600">{{ errors.email }}</p>
          </div>
          <div class="space-y-1">
            <Label for="email_confirm">Confirma el correu *</Label>
            <Input
              id="email_confirm"
              v-model="form.email_confirm"
              type="email"
              :class="errors.email_confirm ? 'border-red-500' : ''"
            />
            <p v-if="errors.email_confirm" class="text-xs text-red-600">
              {{ errors.email_confirm }}
            </p>
          </div>

          <!-- Adreça -->
          <div class="space-y-1">
            <Label for="direccion">Adreça *</Label>
            <Input
              id="direccion"
              v-model="form.direccion"
              :class="errors.direccion ? 'border-red-500' : ''"
            />
            <p v-if="errors.direccion" class="text-xs text-red-600">{{ errors.direccion }}</p>
          </div>

          <!-- CP + Població + Província -->
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="space-y-1">
              <Label for="codigo_postal">C.P. *</Label>
              <Input
                id="codigo_postal"
                v-model="form.codigo_postal"
                :class="errors.codigo_postal ? 'border-red-500' : ''"
              />
              <p v-if="errors.codigo_postal" class="text-xs text-red-600">
                {{ errors.codigo_postal }}
              </p>
            </div>
            <div class="col-span-1 space-y-1 sm:col-span-2">
              <Label for="poblacion">Població *</Label>
              <Input
                id="poblacion"
                v-model="form.poblacion"
                :class="errors.poblacion ? 'border-red-500' : ''"
              />
              <p v-if="errors.poblacion" class="text-xs text-red-600">{{ errors.poblacion }}</p>
            </div>
          </div>
          <div class="space-y-1">
            <Label for="provincia">Província *</Label>
            <Input
              id="provincia"
              v-model="form.provincia"
              :class="errors.provincia ? 'border-red-500' : ''"
            />
            <p v-if="errors.provincia" class="text-xs text-red-600">{{ errors.provincia }}</p>
          </div>

          <!-- Dades esportives -->
          <div class="space-y-3 rounded-lg border bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-800">Dades esportives</h3>

            <!-- Soci UEC -->
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model="form.es_socio_uec"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300"
              />
              <span class="text-sm text-slate-700">Sóc soci/a de la UEC Tortosa</span>
            </label>

            <!-- Club -->
            <div class="space-y-1">
              <Label for="club">Club (opcional)</Label>
              <Input id="club" v-model="form.club" placeholder="Nom del club o entitat" />
            </div>

            <!-- Federativa -->
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model="form.esta_federado"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300"
              />
              <span class="text-sm text-slate-700">Tinc llicència federativa</span>
            </label>

            <!-- Número llicència (condicional) -->
            <div v-if="form.esta_federado" class="space-y-1">
              <Label for="numero_licencia">Número de llicència *</Label>
              <Input
                id="numero_licencia"
                v-model="form.numero_licencia"
                :class="errors.numero_licencia ? 'border-red-500' : ''"
              />
              <p v-if="errors.numero_licencia" class="text-xs text-red-600">
                {{ errors.numero_licencia }}
              </p>
            </div>
            <!-- Celíac -->
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model="form.es_celiaco"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300"
              />
              <span class="text-sm text-slate-700">Tinc intolerància al gluten (menú celíac)</span>
            </label>
          </div>

          <!-- Preu + submit -->
          <div class="space-y-1 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <div class="flex justify-between">
              <span>Canvi de dorsal</span>
              <span>{{ cambioDorsal.precio_base.toFixed(2) }}€</span>
            </div>
            <div v-if="precioFederativa > 0" class="flex justify-between text-slate-600">
              <span>Llicència federativa</span>
              <span>{{ precioFederativa.toFixed(2) }}€</span>
            </div>
            <div
              class="flex justify-between border-t border-slate-200 pt-1 font-bold text-slate-900"
            >
              <span>Total</span>
              <span>{{ precioTotal.toFixed(2) }}€</span>
            </div>
          </div>

          <Button type="submit" class="w-full gap-2" :disabled="submitting || isExpired">
            <ArrowRight class="h-4 w-4" />
            {{ submitting ? 'Redirigint...' : 'Pagar i confirmar el canvi' }}
          </Button>
        </form>
      </template>
    </div>
  </div>
</template>
