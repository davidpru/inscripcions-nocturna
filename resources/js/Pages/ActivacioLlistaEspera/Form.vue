<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, Clock, CreditCard } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Activacio {
  id: number;
  token: string;
  expires_at: string;
}

interface Inscripcion {
  id: number;
  precio_total: number;
  tarifa_aplicada: string;
  necesita_autobus: boolean;
  parada_autobus: string | null;
  esta_federado: boolean;
  es_socio_uec: boolean;
  seguro_anulacion: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  edicion: {
    anio: number;
    nombre: string;
  };
}

interface Participant {
  nom: string;
  email: string;
  dni: string;
}

const props = defineProps<{
  activacio: Activacio;
  inscripcion: Inscripcion;
  participant: Participant;
}>();

// Countdown timer
const secondsLeft = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const updateCountdown = () => {
  const diff = Math.floor((new Date(props.activacio.expires_at).getTime() - Date.now()) / 1000);
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

const countdownText = computed(() => {
  const h = Math.floor(secondsLeft.value / 3600);
  const m = Math.floor((secondsLeft.value % 3600) / 60);
  const s = secondsLeft.value % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const isExpired = computed(() => secondsLeft.value === 0);

const submitting = ref(false);

const confirmar = () => {
  if (isExpired.value || submitting.value) return;
  submitting.value = true;
  router.post(
    `/activacio-llista-espera/${props.activacio.token}`,
    {},
    {
      onError: () => {
        submitting.value = false;
      },
    }
  );
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 px-4 py-10">
    <div class="mx-auto max-w-lg">
      <!-- Capçalera -->
      <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Activació de plaça</h1>
        <p class="mt-1 text-slate-500">{{ inscripcion.edicion.nombre }}</p>
      </div>

      <!-- Compte enrere -->
      <div
        :class="isExpired ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'"
        class="mb-6 flex items-center gap-3 rounded-lg border p-4"
      >
        <AlertTriangle v-if="isExpired" class="h-5 w-5 shrink-0 text-red-500" />
        <Clock v-else class="h-5 w-5 shrink-0 text-amber-600" />
        <div>
          <p v-if="isExpired" class="font-semibold text-red-700">Enllaç caducat</p>
          <p v-else class="font-semibold text-amber-800">
            Temps restant: <span class="font-mono">{{ countdownText }}</span>
          </p>
          <p :class="isExpired ? 'text-red-600' : 'text-amber-700'" class="mt-0.5 text-xs">
            {{
              isExpired
                ? 'Aquest enllaç ja no és vàlid.'
                : 'Completa el pagament abans que caduqui.'
            }}
          </p>
        </div>
      </div>

      <!-- Dades del participant -->
      <div class="mb-4 rounded-lg border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Les teves dades</h2>
        <div class="space-y-1 text-sm text-slate-700">
          <p>
            <span class="text-slate-500">Nom:</span> <strong>{{ participant.nom }}</strong>
          </p>
          <p>
            <span class="text-slate-500">DNI:</span> <strong>{{ participant.dni }}</strong>
          </p>
          <p>
            <span class="text-slate-500">Correu:</span> <strong>{{ participant.email }}</strong>
          </p>
        </div>
      </div>

      <!-- Detalls de la inscripció -->
      <div class="mb-4 rounded-lg border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Detalls de la inscripció</h2>
        <div class="space-y-1 text-sm text-slate-700">
          <p>
            <span class="text-slate-500">Tarifa:</span>
            <strong>{{ inscripcion.tarifa_aplicada }}</strong>
          </p>
          <p>
            <span class="text-slate-500">Samarreta Caro:</span>
            <strong class="uppercase">{{ inscripcion.talla_camiseta_caro }}</strong>
          </p>
          <p>
            <span class="text-slate-500">Samarreta Paüls:</span>
            <strong class="uppercase">{{ inscripcion.talla_camiseta_pauls }}</strong>
          </p>
          <p v-if="inscripcion.esta_federado">
            <span class="text-slate-500">Federat/da:</span> <strong>Sí</strong>
          </p>
          <p v-if="inscripcion.es_socio_uec">
            <span class="text-slate-500">Soci UEC:</span> <strong>Sí</strong>
          </p>
          <p v-if="inscripcion.necesita_autobus">
            <span class="text-slate-500">Autobús:</span>
            <strong>Sí ({{ inscripcion.parada_autobus ?? 'parada no especificada' }})</strong>
          </p>
          <p v-if="inscripcion.seguro_anulacion">
            <span class="text-slate-500">Segur d'anul·lació:</span> <strong>Sí</strong>
          </p>
        </div>
      </div>

      <!-- Resum de pagament -->
      <div class="mb-6 rounded-lg border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Import a pagar</h2>
        <div class="flex items-center justify-between">
          <span class="text-sm text-slate-600">Total</span>
          <span class="text-2xl font-bold text-slate-900"
            >{{ Number(inscripcion.precio_total).toFixed(2) }}€</span
          >
        </div>
      </div>

      <!-- Botó de pagament -->
      <Button class="w-full gap-2" size="lg" :disabled="isExpired || submitting" @click="confirmar">
        <CreditCard class="h-5 w-5" />
        {{ submitting ? 'Redirigint...' : 'Pagar i confirmar plaça' }}
        <ArrowRight v-if="!submitting" class="h-4 w-4" />
      </Button>

      <p class="mt-3 text-center text-xs text-slate-400">
        Seràs redirigit/da a la passarel·la de pagament segura de Redsys.
      </p>
    </div>
  </div>
</template>
