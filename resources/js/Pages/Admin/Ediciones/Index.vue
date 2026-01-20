<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/vue3';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
  limite_inscritos: number;
  fecha_limite_tarifa_normal: string;
  estado: string;
  inscripciones_count: number;
}

const props = defineProps<{
  ediciones: Edicion[];
}>();

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getEstadoBadgeClass = (estado: string) => {
  return estado === 'abierta'
    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
};
</script>

<template>
  <Head title="Gestión de Ediciones" />

  <div class="min-h-screen bg-slate-50 px-4 py-8 dark:bg-slate-900">
    <div class="mx-auto max-w-6xl">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
            Gestión de Ediciones
          </h1>
          <p class="mt-1 text-slate-600 dark:text-slate-400">
            Administra las ediciones de la Nocturna Fredes Paüls
          </p>
        </div>
        <Link href="/admin/ediciones/create">
          <Button>Nueva Edición</Button>
        </Link>
      </div>

      <!-- Lista de Ediciones -->
      <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-slate-800">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Año
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Fecha Evento
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Inscritos
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Fecha Límite
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Estado
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
              >
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
            <tr v-for="edicion in ediciones" :key="edicion.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                  {{ edicion.anio }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-slate-100">
                  {{ formatearFecha(edicion.fecha_evento) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-slate-100">
                  {{ edicion.inscripciones_count }} / {{ edicion.limite_inscritos }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-900 dark:text-slate-100">
                  {{ formatearFecha(edicion.fecha_limite_tarifa_normal) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getEstadoBadgeClass(edicion.estado)"
                  class="inline-flex rounded-full px-2 text-xs leading-5 font-semibold"
                >
                  {{ edicion.estado }}
                </span>
              </td>
              <td class="space-x-2 px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                <Link :href="`/admin/ediciones/${edicion.id}/edit`">
                  <Button variant="outline" size="sm">Editar</Button>
                </Link>
                <Link :href="`/admin/inscripciones?edicion_id=${edicion.id}`">
                  <Button variant="outline" size="sm">Ver Inscritos</Button>
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="ediciones.length === 0" class="py-12 text-center">
          <p class="text-slate-500 dark:text-slate-400">No hay ediciones creadas</p>
          <Link href="/admin/ediciones/create" class="mt-4 inline-block">
            <Button>Crear Primera Edición</Button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
