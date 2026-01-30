<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/vue3';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
}

defineProps<{
  edicion: Edicion;
}>();

const consultaForm = useForm<{
  dni: string;
  fecha_nacimiento: string;
  general?: string;
}>({
  dni: '',
  fecha_nacimiento: '',
});

const consultarInscripcion = () => {
  consultaForm.post('/inscripcion/buscar-inscripcion');
};
</script>

<template>
  <Header />

  <div class="min-h-screen px-4 py-10">
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="mb-2 text-4xl font-bold text-slate-900">
          Nocturna Fredes Paüls {{ edicion.anio }}
        </h1>
        <p class="text-lg text-slate-600">Consultar Inscripción</p>
      </div>

      <!-- Formulario de consulta -->
      <div class="rounded-lg bg-white p-8 shadow-lg">
        <Link href="/">
          <Button variant="ghost" class="mb-4">← Volver</Button>
        </Link>

        <form @submit.prevent="consultarInscripcion" class="space-y-6">
          <!-- Error general -->
          <div
            v-if="consultaForm.errors.general"
            class="rounded-md bg-red-50 p-4 text-sm text-red-500"
          >
            {{ consultaForm.errors.general }}
          </div>

          <Field>
            <Label for="consulta_dni">DNI/NIE *</Label>
            <Input
              id="consulta_dni"
              v-model="consultaForm.dni"
              type="text"
              required
              placeholder="12345678X"
              :class="{ 'border-red-500': consultaForm.errors.dni }"
            />
            <p v-if="consultaForm.errors.dni" class="mt-1 text-sm text-red-500">
              {{ consultaForm.errors.dni }}
            </p>
          </Field>

          <Field>
            <Label for="consulta_fecha">Fecha de Nacimiento *</Label>
            <Input
              id="consulta_fecha"
              v-model="consultaForm.fecha_nacimiento"
              type="date"
              required
              :class="{ 'border-red-500': consultaForm.errors.fecha_nacimiento }"
            />
            <p v-if="consultaForm.errors.fecha_nacimiento" class="mt-1 text-sm text-red-500">
              {{ consultaForm.errors.fecha_nacimiento }}
            </p>
          </Field>

          <div class="flex justify-center pt-4">
            <Button type="submit" size="lg" :disabled="consultaForm.processing">
              {{ consultaForm.processing ? 'Consultando...' : 'Consultar Inscripción' }}
            </Button>
          </div>
        </form>
      </div>
      <Footer />
    </div>
  </div>
</template>
