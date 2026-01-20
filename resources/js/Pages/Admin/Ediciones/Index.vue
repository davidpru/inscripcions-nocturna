<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

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

  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8 px-4">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
            Gestión de Ediciones
          </h1>
          <p class="text-slate-600 dark:text-slate-400 mt-1">
            Administra las ediciones de la Nocturna Fredes Paüls
          </p>
        </div>
        <Link href="/admin/ediciones/create">
          <Button>Nueva Edición</Button>
        </Link>
      </div>

      <!-- Lista de Ediciones -->
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Año
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Fecha Evento
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Inscritos
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Fecha Límite
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Estado
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
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
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                >
                  {{ edicion.estado }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
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

        <div v-if="ediciones.length === 0" class="text-center py-12">
          <p class="text-slate-500 dark:text-slate-400">No hay ediciones creadas</p>
          <Link href="/admin/ediciones/create" class="mt-4 inline-block">
            <Button>Crear Primera Edición</Button>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
