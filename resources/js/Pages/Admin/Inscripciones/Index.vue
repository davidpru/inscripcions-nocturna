<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Participante {
  dni: string;
  nombre: string;
  apellidos: string;
  email: string;
  telefono: string;
  direccion: string;
  codigo_postal: string;
  poblacion: string;
  provincia: string;
  genero: string;
  fecha_nacimiento: string;
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
  seguro_anulacion: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  club: string;
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

const eliminarInscripcion = (id: number) => {
  if (
    confirm(
      '¿Estás seguro de que deseas eliminar esta inscripción? Esta acción no se puede deshacer.'
    )
  ) {
    router.delete(`/admin/inscripciones/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Opcional: mostrar notificación
      },
    });
  }
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

  <div class="min-h-screen bg-slate-50 px-4 py-8 dark:bg-slate-900">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
          Gestión de Inscripciones
        </h1>
        <p class="mt-1 text-slate-600 dark:text-slate-400">
          Total: {{ inscripciones.total }} inscripciones
        </p>
      </div>

      <!-- Filtros -->
      <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-slate-800">
        <div class="flex items-end gap-4">
          <div class="flex-1">
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
              Filtrar por Edición
            </label>
            <select
              v-model="edicionSeleccionada"
              class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
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
      <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-slate-800">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  #
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Participante
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  DNI
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Edición
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Precio
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Estado Pago
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Fecha
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                >
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody
              class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800"
            >
              <tr v-for="inscripcion in inscripciones.data" :key="inscripcion.id">
                <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100">
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
                <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100">
                  {{ inscripcion.participante.dni }}
                </td>
                <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100">
                  {{ inscripcion.edicion.anio }}
                </td>
                <td
                  class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-slate-900 dark:text-slate-100"
                >
                  {{ inscripcion.precio_total }}€
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="getEstadoPagoBadgeClass(inscripcion.estado_pago)"
                    class="inline-flex rounded-full px-2 text-xs leading-5 font-semibold"
                  >
                    {{ inscripcion.estado_pago }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100">
                  {{ formatearFecha(inscripcion.created_at) }}
                </td>
                <td class="space-x-2 px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                  <Sheet>
                    <SheetTrigger as-child>
                      <Button variant="outline" size="sm">
                        <Eye class="mr-2 h-4 w-4" />
                        Ver
                      </Button>
                    </SheetTrigger>
                    <SheetContent class="w-[400px] overflow-y-auto sm:w-[540px]">
                      <SheetHeader>
                        <SheetTitle>Detalle Inscripción #{{ inscripcion.id }}</SheetTitle>
                        <SheetDescription>
                          Inscripción realizada el {{ formatearFecha(inscripcion.created_at) }}
                        </SheetDescription>
                      </SheetHeader>
                      <div class="mt-6 space-y-6 text-sm">
                        <!-- Datos personales -->
                        <div class="space-y-2">
                          <h3 class="font-semibold text-slate-900">Datos Personales</h3>
                          <div class="grid grid-cols-2 gap-4 rounded-lg border bg-slate-50 p-4">
                            <div>
                              <span class="block text-xs text-slate-500">Nombre completo</span>
                              <span class="block font-medium"
                                >{{ inscripcion.participante.nombre }}
                                {{ inscripcion.participante.apellidos }}</span
                              >
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">DNI</span>
                              <span class="block font-medium">{{
                                inscripcion.participante.dni
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Email</span>
                              <span class="block font-medium">{{
                                inscripcion.participante.email
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Teléfono</span>
                              <span class="block font-medium">{{
                                inscripcion.participante.telefono
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Fecha Nacimiento</span>
                              <span class="block font-medium">{{
                                new Date(
                                  inscripcion.participante.fecha_nacimiento
                                ).toLocaleDateString('es-ES')
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Género</span>
                              <span class="block font-medium capitalize">{{
                                inscripcion.participante.genero
                              }}</span>
                            </div>
                            <div class="col-span-2">
                              <span class="block text-xs text-slate-500">Dirección</span>
                              <span class="block font-medium"
                                >{{ inscripcion.participante.direccion }},
                                {{ inscripcion.participante.codigo_postal }}
                                {{ inscripcion.participante.poblacion }} ({{
                                  inscripcion.participante.provincia
                                }})</span
                              >
                            </div>
                          </div>
                        </div>

                        <!-- Detalles Inscripción -->
                        <div class="space-y-2">
                          <h3 class="font-semibold text-slate-900">Detalles Inscripción</h3>
                          <div class="grid grid-cols-2 gap-4 rounded-lg border bg-slate-50 p-4">
                            <div>
                              <span class="block text-xs text-slate-500">Estado Pago</span>
                              <span
                                :class="getEstadoPagoBadgeClass(inscripcion.estado_pago)"
                                class="mt-1 inline-flex rounded-full px-2 text-xs leading-5 font-semibold"
                              >
                                {{ inscripcion.estado_pago }}
                              </span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Precio Total</span>
                              <span class="block font-medium">{{ inscripcion.precio_total }}€</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Socio UEC</span>
                              <span class="block font-medium">{{
                                inscripcion.es_socio_uec ? 'Sí' : 'No'
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Federado</span>
                              <span class="block font-medium">{{
                                inscripcion.esta_federado ? 'Sí' : 'No'
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Autobús</span>
                              <span class="block font-medium">{{
                                inscripcion.necesita_autobus ? 'Sí' : 'No'
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Seguro Anulación</span>
                              <span class="block font-medium">{{
                                inscripcion.seguro_anulacion ? 'Sí' : 'No'
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Camiseta Caro</span>
                              <span class="block font-medium">{{
                                inscripcion.talla_camiseta_caro
                              }}</span>
                            </div>
                            <div>
                              <span class="block text-xs text-slate-500">Camiseta Paüls</span>
                              <span class="block font-medium">{{
                                inscripcion.talla_camiseta_pauls
                              }}</span>
                            </div>
                            <div class="col-span-2" v-if="inscripcion.club">
                              <span class="block text-xs text-slate-500">Club</span>
                              <span class="block font-medium">{{ inscripcion.club }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </SheetContent>
                  </Sheet>

                  <Button
                    variant="destructive"
                    size="sm"
                    @click="eliminarInscripcion(inscripcion.id)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="inscripciones.data.length === 0" class="py-12 text-center">
          <p class="text-slate-500 dark:text-slate-400">No hay inscripciones</p>
        </div>

        <!-- Paginación -->
        <div
          v-if="inscripciones.last_page > 1"
          class="border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800"
        >
          <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-300">
              Mostrando {{ (inscripciones.current_page - 1) * inscripciones.per_page + 1 }} -
              {{
                Math.min(inscripciones.current_page * inscripciones.per_page, inscripciones.total)
              }}
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
