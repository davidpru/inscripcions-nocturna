<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
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
  isTestMode?: boolean;
}>();

const dni = ref('');
const buscando = ref(false);
const error = ref('');

// Countdown para inscripciones
const timeRemaining = ref({ difference: 0, days: 0, hours: 0, minutes: 0, seconds: 0 });
const countdownInterval = ref<number | null>(null);

const calculateTimeRemaining = (targetDate: string) => {
  const now = new Date().getTime();
  const target = new Date(targetDate).getTime();
  const difference = target - now;

  return {
    difference,
    days: difference > 0 ? Math.floor(difference / (1000 * 60 * 60 * 24)) : 0,
    hours: difference > 0 ? Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)) : 0,
    minutes: difference > 0 ? Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)) : 0,
    seconds: difference > 0 ? Math.floor((difference % (1000 * 60)) / 1000) : 0,
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
    const response = await axios.post('/inscripcio/buscar-participante', {
      dni: dni.value,
    });

    // Navegar a inscripción con los datos del participante si existe
    const params: { dni: string; participante?: string } = { dni: dni.value };

    if (response.data.participante) {
      params.participante = JSON.stringify(response.data.participante);
    }

    router.get('/inscripcio', params);
  } catch (e) {
    // Si no encuentra participante, navegar igualmente con el DNI
    router.get('/inscripcio', { dni: dni.value });
  } finally {
    buscando.value = false;
  }
};

onMounted(() => {
  // Iniciar countdown solo si las inscripciones NO están abiertas y hay fecha futura
  if (!props.inscripcionesAbiertas && props.edicion?.fecha_inicio_inscripciones) {
    const initial = calculateTimeRemaining(props.edicion.fecha_inicio_inscripciones);
    // Solo iniciar countdown si la fecha está en el futuro
    if (initial.difference > 0) {
      timeRemaining.value = initial;
      countdownInterval.value = window.setInterval(() => {
        timeRemaining.value = calculateTimeRemaining(props.edicion!.fecha_inicio_inscripciones!);
        // Si el countdown llega a 0, recargar la página una sola vez
        if (timeRemaining.value.difference <= 0) {
          if (countdownInterval.value) {
            clearInterval(countdownInterval.value);
          }
          window.location.reload();
        }
      }, 1000);
    }
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

  <section class="min-h-screen px-4 py-12 md:px-16">
    <div class="mx-auto max-w-6xl">
      <!-- Banner de modo prueba -->
      <div
        v-if="isTestMode"
        class="mb-6 rounded-lg border-2 border-orange-500 bg-orange-50 p-4 text-center"
      >
        <p class="font-semibold text-orange-900">
          🔧 MODE PROVA ACTIVAT - Només visible per a IPs autoritzades
        </p>
        <p class="mt-1 text-sm text-orange-700">
          Les inscripcions encara no estan obertes per al públic
        </p>
      </div>

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
          <h1
            class="text-slate-90 font-expanded text-destructive mb-2 text-3xl font-bold text-balance md:text-4xl lg:text-5xl"
          >
            Nocturna <br />
            Fredes-Paüls {{ edicion?.anio }}
          </h1>
          <p v-if="inscripcionesAbiertas" class="text-foreground text-lg">Inscripcions online</p>
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
                <Link href="/inscripcions/consulta">
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
                <Link href="/inscripcions/inscrits">
                  <Button class="w-full"> Veure llistat </Button>
                </Link>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <section class="mx-auto mt-12 max-w-6xl overflow-hidden rounded-2xl shadow-lg">
      <img
        src="@/assets/site/bg-imagen-cova-vidre.jpg"
        class="aspect-square h-auto w-full object-cover md:aspect-16/7"
        alt=""
      />
    </section>
    <Footer />
  </section>
</template>
