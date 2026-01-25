<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
}

defineProps<{
  edicion: Edicion;
}>();

const dni = ref('');
const buscando = ref(false);
const error = ref('');

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
</script>

<template>
  <Header />

  <div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-6xl">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-slate-90 mb-2 text-4xl font-bold">
          Nocturna Fredes-Paüls {{ edicion.anio }}
        </h1>
        <p class="text-foreground text-lg">Selecciona una opció</p>
      </div>

      <!-- Selección -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                  <Input id="dni" v-model="dni" type="text" placeholder="12345678X" class="mt-1" />
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
</template>
