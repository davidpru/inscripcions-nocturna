<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
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
  return estado === 'abierta' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};
</script>

<template>
  <AdminLayout>
    <Head title="Gestió d'Edicions" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Gestió d'Edicions</h1>
            <p class="mt-1 text-slate-600">Administra les edicions de la Nocturna Fredes Paüls</p>
          </div>
          <Link href="/uec-admin/ediciones/create">
            <Button>Nova Edició</Button>
          </Link>
        </div>

        <!-- Llista d'Edicions -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Any
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Data Esdeveniment
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Inscrits
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Data Límit
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Estat
                  </th>
                  <th
                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Accions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="edicion in ediciones" :key="edicion.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900">
                      {{ edicion.anio }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">
                      {{ formatearFecha(edicion.fecha_evento) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">
                      {{ edicion.inscripciones_count }} / {{ edicion.limite_inscritos }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">
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
                    <Link :href="`/uec-admin/ediciones/${edicion.id}/edit`">
                      <Button variant="outline" size="sm">Editar</Button>
                    </Link>
                    <Link :href="`/uec-admin/inscripciones?edicion_id=${edicion.id}`">
                      <Button variant="outline" size="sm">Veure Inscrits</Button>
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="ediciones.length === 0" class="py-12 text-center">
            <p class="text-slate-500">No hi ha edicions creades</p>
            <Link href="/uec-admin/ediciones/create" class="mt-4 inline-block">
              <Button>Crear Primera Edició</Button>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
