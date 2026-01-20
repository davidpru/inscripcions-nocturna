<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
  anio: new Date().getFullYear(),
  fecha_evento: '',
  limite_inscritos: 650,
  fecha_limite_tarifa_normal: '',
  estado: 'abierta' as 'abierta' | 'cerrada',
});

const enviarFormulario = () => {
  form.post('/admin/ediciones');
};
</script>

<template>
  <Head title="Nueva Edición" />

  <div class="min-h-screen bg-slate-50 px-4 py-8 dark:bg-slate-900">
    <div class="mx-auto max-w-2xl">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Nueva Edición</h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">
          Crea una nueva edición de la Nocturna Fredes Paüls
        </p>
      </div>

      <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
        <form @submit.prevent="enviarFormulario" class="space-y-6">
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Año *
            </label>
            <input
              v-model.number="form.anio"
              type="number"
              required
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
            />
            <p v-if="form.errors.anio" class="mt-1 text-sm text-red-600">{{ form.errors.anio }}</p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Fecha del Evento *
            </label>
            <input
              v-model="form.fecha_evento"
              type="date"
              required
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
            />
            <p v-if="form.errors.fecha_evento" class="mt-1 text-sm text-red-600">
              {{ form.errors.fecha_evento }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Límite de Inscritos *
            </label>
            <input
              v-model.number="form.limite_inscritos"
              type="number"
              required
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
            />
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Por defecto: 650 inscritos
            </p>
            <p v-if="form.errors.limite_inscritos" class="mt-1 text-sm text-red-600">
              {{ form.errors.limite_inscritos }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Fecha Límite Tarifa Normal *
            </label>
            <input
              v-model="form.fecha_limite_tarifa_normal"
              type="date"
              required
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
            />
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Después de esta fecha o al alcanzar el límite de inscritos, se aplicará la tarifa
              tardía
            </p>
            <p v-if="form.errors.fecha_limite_tarifa_normal" class="mt-1 text-sm text-red-600">
              {{ form.errors.fecha_limite_tarifa_normal }}
            </p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Estado *
            </label>
            <select
              v-model="form.estado"
              required
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
            >
              <option value="abierta">Abierta</option>
              <option value="cerrada">Cerrada</option>
            </select>
            <p v-if="form.errors.estado" class="mt-1 text-sm text-red-600">
              {{ form.errors.estado }}
            </p>
          </div>

          <div class="flex justify-end gap-4 pt-4">
            <Button variant="outline" as="a" href="/admin/ediciones"> Cancelar </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Guardando...' : 'Crear Edición' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
