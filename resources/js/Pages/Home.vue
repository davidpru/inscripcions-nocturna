<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
  fecha_inicio_inscripciones: string | null;
}

const props = defineProps<{
  edicion: Edicion | null;
  hayEdicion: boolean;
  inscripcionesAbiertas: boolean;
}>();

const dni = ref('');
const buscando = ref(false);
const error = ref('');

// Countdown para inscripciones
const timeRemaining = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
const countdownInterval = ref<number | null>(null);

const calculateTimeRemaining = (targetDate: string) => {
  const now = new Date().getTime();
  const target = new Date(targetDate).getTime();
  const difference = target - now;

  if (difference <= 0) {
    return { days: 0, hours: 0, minutes: 0, seconds: 0 };
  }

  return {
    days: Math.floor(difference / (1000 * 60 * 60 * 24)),
    hours: Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
    minutes: Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)),
    seconds: Math.floor((difference % (1000 * 60)) / 1000),
  };
};

const iniciarInscripcion = async () => {
  if (!dni.value.trim()) {
    error.value = 'Introdueix el teu DNI/NIE per continuar';
    return;
  }

  error.value = '';
  buscando.value = true;

  try {
    const response = await axios.post('/inscripcion/buscar-participante', {
      dni: dni.value,
    });

    // Navegar a inscripción con los datos del participante si existe
    router.get('/inscripcion', {
      dni: dni.value,
      participante: response.data.participante ? JSON.stringify(response.data.participante) : null,
    });
  } catch (e) {
    // Si no encuentra participante, navegar igualmente con el DNI
    router.get('/inscripcion', { dni: dni.value });
  } finally {
    buscando.value = false;
  }
};

onMounted(() => {
  // Iniciar countdown si hay fecha de inicio de inscripciones
  if (props.edicion?.fecha_inicio_inscripciones) {
    timeRemaining.value = calculateTimeRemaining(props.edicion.fecha_inicio_inscripciones);
    countdownInterval.value = window.setInterval(() => {
      timeRemaining.value = calculateTimeRemaining(props.edicion!.fecha_inicio_inscripciones!);
      // Si el countdown llega a 0, recargar la página
      if (
        timeRemaining.value.days === 0 &&
        timeRemaining.value.hours === 0 &&
        timeRemaining.value.minutes === 0 &&
        timeRemaining.value.seconds === 0
      ) {
        window.location.reload();
      }
    }, 1000);
  }
});

onUnmounted(() => {
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value);
  }
});
</script>

<template>
  <Header />

  <div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-6xl">
      <!-- Sin edición activa -->
      <div v-if="!hayEdicion" class="py-12 text-center">
        <Card class="mx-auto max-w-md">
          <CardHeader>
            <CardTitle class="text-2xl">No hi ha inscripcions obertes</CardTitle>
            <CardDescription>
              Actualment no hi ha cap edició activa. Torna més endavant.
            </CardDescription>
          </CardHeader>
        </Card>
      </div>

      <!-- Con edición activa -->
      <div v-else>
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-slate-90 mb-2 text-4xl font-bold">
            Nocturna Fredes-Paüls {{ edicion?.anio }}
          </h1>
          <p v-if="inscripcionesAbiertas" class="text-foreground text-lg">Selecciona una opció</p>
          <p v-else class="text-foreground text-lg">Pròximament</p>
        </div>

        <!-- Countdown si las inscripciones no están abiertas -->
        <div
          v-if="!inscripcionesAbiertas && edicion?.fecha_inicio_inscripciones"
          class="to-gray-1000 mb-8 rounded-lg bg-linear-to-r from-gray-800 to-gray-900 p-8 text-white shadow-xl"
        >
          <h2 class="mb-4 text-center text-3xl font-bold">Les inscripcions s'obren en:</h2>
          <div class="mx-auto grid max-w-2xl grid-cols-2 gap-4 text-center md:grid-cols-4">
            <div class="rounded-lg bg-white/20 p-4 backdrop-blur-sm">
              <div class="text-5xl font-bold">{{ timeRemaining.days }}</div>
              <div class="mt-2 text-sm tracking-wider uppercase opacity-90">Dies</div>
            </div>
            <div class="rounded-lg bg-white/20 p-4 backdrop-blur-sm">
              <div class="text-5xl font-bold">{{ timeRemaining.hours }}</div>
              <div class="mt-2 text-sm tracking-wider uppercase opacity-90">Hores</div>
            </div>
            <div class="rounded-lg bg-white/20 p-4 backdrop-blur-sm">
              <div class="text-5xl font-bold">{{ timeRemaining.minutes }}</div>
              <div class="mt-2 text-sm tracking-wider uppercase opacity-90">Minuts</div>
            </div>
            <div class="rounded-lg bg-white/20 p-4 backdrop-blur-sm">
              <div class="text-5xl font-bold">{{ timeRemaining.seconds }}</div>
              <div class="mt-2 text-sm tracking-wider uppercase opacity-90">Segons</div>
            </div>
          </div>
        </div>

        <!-- Mensaje cuando inscripciones cerradas sin fecha -->
        <Card
          v-if="!inscripcionesAbiertas && !edicion?.fecha_inicio_inscripciones"
          class="mx-auto max-w-md"
        >
          <CardHeader>
            <CardTitle class="text-2xl">Inscripcions tancades</CardTitle>
            <CardDescription>
              Les inscripcions encara no estan obertes. Torna més endavant.
            </CardDescription>
          </CardHeader>
        </Card>

        <!-- Selección -->
        <div v-if="inscripcionesAbiertas" class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="">
            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-2xl">Nova Inscripció</CardTitle>
                <CardDescription>Inscriu-te a la Fredes-Paüls</CardDescription>
              </CardHeader>
              <CardContent>
                <p class="mb-4 text-balance text-slate-600">
                  Introdueix el teu DNI/NIE per començar el procés d'inscripció.
                </p>
                <form @submit.prevent="iniciarInscripcion" class="space-y-4">
                  <div>
                    <Label for="dni">DNI/NIE</Label>
                    <Input
                      id="dni"
                      v-model="dni"
                      type="text"
                      placeholder="12345678X"
                      class="mt-1"
                    />
                    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
                  </div>
                  <Button type="submit" size="xl" :disabled="buscando" class="w-full">
                    {{ buscando ? 'Cercant...' : 'Continuar' }}
                  </Button>
                </form>
              </CardContent>
            </Card>
          </div>
          <div class="flex flex-col gap-4">
            <Card class="cursor-pointer transition-all hover:shadow-xl">
              <CardHeader>
                <CardTitle class="text-2xl">Consultar inscripció</CardTitle>
                <CardDescription
                  >Verifica la teva inscripció amb les teves dades personals</CardDescription
                >
              </CardHeader>
              <CardContent class="">
                <Link href="/inscripcion/consulta">
                  <Button class="w-full"> Consultar Inscripció </Button>
                </Link>
              </CardContent>
            </Card>
            <Card class="cursor-pointer transition-all hover:shadow-xl">
              <CardHeader>
                <CardTitle class="text-2xl">Llistat d'inscrits</CardTitle>
                <CardDescription>Comprova el llistat general d'inscrits</CardDescription>
              </CardHeader>
              <CardContent class="">
                <Link href="/inscripcion/listado">
                  <Button class="w-full"> Veure llistat </Button>
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
