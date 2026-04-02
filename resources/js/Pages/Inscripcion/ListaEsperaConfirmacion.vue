<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';

interface Edicion {
  id: number;
  anio: number;
}

defineProps<{
  edicion: Edicion;
  nombre: string;
}>();
</script>

<template>
  <Head title="Llista d'Espera - Confirmació" />
  <Header />

  <div class="min-h-screen">
    <div class="mx-auto max-w-2xl px-4 py-12">
      <Card>
        <CardContent class="p-8 text-center">
          <div
            class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-100"
          >
            <svg
              class="h-8 w-8 text-amber-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>

          <h1 class="mb-2 text-3xl font-bold text-slate-900">T'has apuntat a la llista d'espera</h1>
          <p class="mb-6 text-lg text-slate-600">
            {{ nombre }}, la teva sol·licitud s'ha registrat correctament.
          </p>

          <div class="mb-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-left">
            <h3 class="mb-2 font-semibold text-amber-900">Què passarà ara?</h3>
            <ul class="space-y-1 text-sm text-amber-800">
              <li>
                • Estàs a la llista d'espera per a la Nocturna Fredes-Paüls {{ edicion.anio }}.
              </li>
              <li>• L'organització es posarà en contacte amb tu si hi ha plaça disponible.</li>
              <li>• La teva inscripció no serà definitiva fins que es realitzi el pagament.</li>
            </ul>
          </div>

          <Link href="/">
            <Button size="lg">Tornar a l'inici</Button>
          </Link>
        </CardContent>
      </Card>
      <Footer />
    </div>
  </div>
</template>
