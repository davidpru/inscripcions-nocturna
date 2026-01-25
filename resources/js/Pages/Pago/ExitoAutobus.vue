<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { getParadaLabel } from '@/constants/paradas';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Bus, CheckCircle, MapPin } from 'lucide-vue-next';

interface Edicion {
  id: number;
  nombre: string;
  fecha_evento: string;
  precio_autobus: number;
}

interface Participante {
  id: number;
  nombre: string;
  apellidos: string;
  dni: string;
}

interface Inscripcion {
  id: number;
  participante: Participante;
  edicion: Edicion;
  parada_autobus: string;
  precio_total: number;
}

defineProps<{
  inscripcion: Inscripcion;
}>();

// Usamos getParadaLabel importado de @/constants/paradas
</script>

<template>
  <Head title="Autobús contratado" />

  <div class="min-h-screen px-4 py-12">
    <div class="mx-auto max-w-2xl">
      <Card class="shadow-lg">
        <CardHeader class="pb-2 text-center">
          <div
            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
          >
            <CheckCircle class="h-10 w-10 text-green-600" />
          </div>
          <CardTitle class="text-2xl text-green-700"> ¡Autobús Contactat! </CardTitle>
          <CardDescription class="text-base">
            El servei d'autobús s'ha afegit a la teva inscripció
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
          <!-- Información del participante -->
          <div class="rounded-lg bg-gray-50 p-4">
            <h3 class="mb-2 font-semibold text-gray-700">Participante</h3>
            <p class="text-lg">
              {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
            </p>
            <p class="text-sm text-gray-500">
              DNI: <span class="font-mono font-semibold">{{ inscripcion.participante.dni }}</span>
            </p>
          </div>

          <!-- Detalles del autobús -->
          <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="mb-3 flex items-center gap-2">
              <Bus class="h-5 w-5 text-blue-600" />
              <h3 class="font-semibold text-blue-700">Servei d'autobús</h3>
            </div>

            <div class="flex items-start gap-2 text-gray-700">
              <MapPin class="mt-0.5 h-4 w-4 text-blue-500" />
              <div>
                <p class="font-medium">Punt de recollida:</p>
                <p>{{ getParadaLabel(inscripcion.parada_autobus) }}</p>
              </div>
            </div>

            <p class="mt-3 text-sm text-blue-600">
              Horari d'autobusos i més informació
              <a href="https://nocturna.uectortosa.cat/horaris-autobusos"> al nostre web </a>.
            </p>
          </div>

          <!-- Precio actualizado -->
          <div class="rounded-lg border border-green-200 bg-green-50 p-4">
            <div class="flex items-center justify-between">
              <span class="text-gray-700">Preu total actualitzat:</span>
              <span class="text-2xl font-bold text-green-700"
                >{{ inscripcion.precio_total }} €</span
              >
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex flex-col gap-3 pt-4 sm:flex-row">
            <Button as-child variant="outline" class="flex-1">
              <Link href="/inscripcion/consulta">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Veure la meva inscripció
              </Link>
            </Button>
            <Button as-child class="flex-1">
              <Link href="/"> Tornar a l'inici </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
