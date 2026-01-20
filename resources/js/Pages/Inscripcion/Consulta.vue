<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
}

defineProps<{
  edicion: Edicion;
}>();

const consultaForm = useForm({
  dni: '',
  fecha_nacimiento: '',
});

const consultarInscripcion = () => {
  consultaForm.post('/inscripcion/buscar-inscripcion');
};
</script>

<template>
  <Header />
  <Head title="Consultar Inscripción - Nocturna Fredes Paüls" />

  <div
    class="min-h-screen bg-linear-to-b from-slate-50 to-slate-100 px-4 py-12 dark:from-slate-900 dark:to-slate-800"
  >
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="mb-2 text-4xl font-bold text-slate-900 dark:text-slate-100">
          Nocturna Fredes Paüls {{ edicion.anio }}
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400">Consultar Inscripción</p>
      </div>

      <!-- Formulario de consulta -->
      <div class="rounded-lg bg-white p-8 shadow-lg dark:bg-slate-800">
        <Link href="/">
          <Button variant="ghost" class="mb-4">← Volver</Button>
        </Link>

        <form @submit.prevent="consultarInscripcion" class="space-y-6">
          <Field>
            <Label for="consulta_dni">DNI/NIE *</Label>
            <Input
              id="consulta_dni"
              v-model="consultaForm.dni"
              type="text"
              required
              placeholder="12345678X"
            />
          </Field>

          <Field>
            <Label for="consulta_fecha">Fecha de Nacimiento *</Label>
            <Input
              id="consulta_fecha"
              v-model="consultaForm.fecha_nacimiento"
              type="date"
              required
            />
          </Field>

          <div class="flex justify-center pt-4">
            <Button type="submit" size="lg" :disabled="consultaForm.processing">
              {{ consultaForm.processing ? 'Consultando...' : 'Consultar Inscripción' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
