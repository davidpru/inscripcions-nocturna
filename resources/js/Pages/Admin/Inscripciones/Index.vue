<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ref } from 'vue';

interface Participante {
  dni: string;
  nombre: string;
  apellidos: string;
  email: string;
  telefono: string;
}

interface Edicion {
  id: number;
  anio: number;
}

interface Inscripcion {
  id: number;
  precio_total: number;
  estado_pago: string;
  created_at: string;
  participante: Participante;
  edicion: Edicion;
  es_socio_uec: boolean;
  esta_federado: boolean;
  necesita_autobus: boolean;
}

interface Paginacion {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  data: Inscripcion[];
}

const props = defineProps<{
  inscripciones: Paginacion;
  ediciones: Edicion[];
  filtros: {
    edicion_id?: number;
  };
}>();

const edicionSeleccionada = ref(props.filtros.edicion_id || '');

const filtrarPorEdicion = () => {
  window.location.href = `/admin/inscripciones?edicion_id=${edicionSeleccionada.value}`;
};

const formatearFecha = (fecha: string) => {
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getEstadoPagoBadgeClass = (estado: string) => {
  if (estado === 'pagado') {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
  } else if (estado === 'cancelado') {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  } else {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
  }
};
</script>

<template>
  <Head title="Gestión de Inscripciones" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8 px-4">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
          Gestión de Inscripciones
        </h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">
          Total: {{ inscripciones.total }} inscripciones
        </p>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4 mb-6">
        <div class="flex gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
              Filtrar por Edición
            </label>
            <select
              v-model="edicionSeleccionada"
              class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100"
            >
              <option value="">Todas las ediciones</option>
              <option v-for="edicion in ediciones" :key="edicion.id" :value="edicion.id">
                {{ edicion.anio }}
              </option>
            </select>
          </div>
          <div>
            <Button @click="filtrarPorEdicion">Filtrar</Button>
          </div>
          <div>
            <Button variant="outline">Exportar</Button>
          </div>
        </div>
      </div>

      <!-- Tabla de Inscripciones -->
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  #
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Participante
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  DNI
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Edición
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Precio
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Estado Pago
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Fecha
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="inscripcion in inscripciones.data" :key="inscripcion.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ inscripcion.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-100">
                    {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
                  </div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">
                    {{ inscripcion.participante.email }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ inscripcion.participante.dni }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ inscripcion.edicion.anio }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ inscripcion.precio_total }}€
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getEstadoPagoBadgeClass(inscripcion.estado_pago)"
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  >
                    {{ inscripcion.estado_pago }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  {{ formatearFecha(inscripcion.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <Link :href="`/admin/inscripciones/${inscripcion.id}`">
                    <Button variant="outline" size="sm">Ver</Button>
                  </Link>
                  <Link :href="`/admin/inscripciones/${inscripcion.id}/edit`">
                    <Button variant="outline" size="sm">Editar</Button>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="inscripciones.data.length === 0" class="text-center py-12">
          <p class="text-slate-500 dark:text-slate-400">No hay inscripciones</p>
        </div>

        <!-- Paginación -->
        <div v-if="inscripciones.last_page > 1" class="bg-white dark:bg-slate-800 px-4 py-3 border-t border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-300">
              Mostrando {{ (inscripciones.current_page - 1) * inscripciones.per_page + 1 }} - 
              {{ Math.min(inscripciones.current_page * inscripciones.per_page, inscripciones.total) }}
              de {{ inscripciones.total }} resultados
            </div>
            <div class="flex gap-2">
              <Button
                v-if="inscripciones.current_page > 1"
                variant="outline"
                size="sm"
                as="a"
                :href="`/admin/inscripciones?page=${inscripciones.current_page - 1}${filtros.edicion_id ? '&edicion_id=' + filtros.edicion_id : ''}`"
              >
                Anterior
              </Button>
              <Button
                v-if="inscripciones.current_page < inscripciones.last_page"
                variant="outline"
                size="sm"
                as="a"
                :href="`/admin/inscripciones?page=${inscripciones.current_page + 1}${filtros.edicion_id ? '&edicion_id=' + filtros.edicion_id : ''}`"
              >
                Siguiente
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
