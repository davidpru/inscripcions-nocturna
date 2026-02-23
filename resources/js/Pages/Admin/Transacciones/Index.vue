<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Participante {
  nombre: string;
  apellidos: string;
  dni: string;
  email: string;
}

interface Inscripcion {
  id: number;
  participante: Participante;
}

interface RedsysTransaccion {
  id: number;
  tipo: string;
  estado: string;
  numero_pedido: string | null;
  numero_autorizacion: string | null;
  importe: string | null;
  moneda: string;
  response_code: string | null;
  descripcion_error: string | null;
  es_autobus: boolean;
  payload: Record<string, unknown> | null;
  created_at: string;
  inscripcion: Inscripcion | null;
}

interface Paginacion {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  prev_page_url: string | null;
  next_page_url: string | null;
  data: RedsysTransaccion[];
}

const props = defineProps<{
  transacciones: Paginacion;
  filtros: {
    estado?: string;
    tipo?: string;
    desde?: string;
    hasta?: string;
    busqueda?: string;
  };
  undo?: {
    numero_pedido?: string | null;
    created_at?: string | null;
  } | null;
}>();

const estado = ref(props.filtros.estado || '');
const tipo = ref(props.filtros.tipo || '');
const desde = ref(props.filtros.desde || '');
const hasta = ref(props.filtros.hasta || '');
const busqueda = ref(props.filtros.busqueda || '');
const detalleDialogOpen = ref(false);
const detalleTransaccion = ref<RedsysTransaccion | null>(null);

const abrirDetalle = (tx: RedsysTransaccion) => {
  detalleTransaccion.value = tx;
  detalleDialogOpen.value = true;
};

const eliminarTransaccion = (tx: RedsysTransaccion) => {
  if (!window.confirm('Eliminar esta transaccion de error?')) return;

  router.delete(`/uec-admin/transacciones/${tx.id}`, {
    preserveScroll: true,
  });
};

const deshacerEliminacion = () => {
  router.post('/uec-admin/transacciones/undo', {}, { preserveScroll: true });
};

const aplicarFiltros = () => {
  const params = new URLSearchParams();
  if (estado.value) params.append('estado', estado.value);
  if (tipo.value) params.append('tipo', tipo.value);
  if (desde.value) params.append('desde', desde.value);
  if (hasta.value) params.append('hasta', hasta.value);
  if (busqueda.value.trim()) params.append('busqueda', busqueda.value.trim());

  window.location.href = `/uec-admin/transacciones${params.toString() ? `?${params}` : ''}`;
};

const limpiarFiltros = () => {
  estado.value = '';
  tipo.value = '';
  desde.value = '';
  hasta.value = '';
  busqueda.value = '';
  window.location.href = '/uec-admin/transacciones';
};

const formatDate = (fecha: string) => {
  return new Date(fecha).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const estadoBadge = (value: string) => {
  if (value === 'pagado') return 'bg-green-100 text-green-800';
  if (value === 'denegado') return 'bg-red-100 text-red-800';
  if (value === 'error') return 'bg-amber-100 text-amber-800';
  if (value === 'devuelto') return 'bg-purple-100 text-purple-800';
  if (value === 'devolucion_parcial') return 'bg-orange-100 text-orange-800';
  return 'bg-slate-100 text-slate-700';
};

const tipoTexto = (value: string) => {
  const map: Record<string, string> = {
    notification: 'Notificacio',
    success: 'Exit',
    error: 'Error',
    refund: 'Devolucio',
  };
  return map[value] || value;
};

const payloadTexto = (payload: Record<string, unknown> | null) => {
  if (!payload) return '';
  try {
    return JSON.stringify(payload, null, 2);
  } catch {
    return '';
  }
};

const transacciones = computed(() => props.transacciones.data);
</script>

<template>
  <AdminLayout>
    <Head title="Transaccions Redsys" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-slate-900">Transaccions Redsys Banc de Sabadell</h1>
          <p class="mt-1 text-slate-600">Pagaments i errors registrats al TPV</p>
        </div>

        <section class="mb-6 rounded-lg bg-white p-4 shadow">
          <div
            v-if="props.undo"
            class="mb-4 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              S'ha eliminat una transaccio d'error.
              <span v-if="props.undo.numero_pedido" class="ml-1 font-medium">
                Pedido {{ props.undo.numero_pedido }}
              </span>
            </div>
            <Button
              size="sm"
              variant="outline"
              class="border-amber-300"
              @click="deshacerEliminacion"
            >
              Desfer
            </Button>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Estado</label>
              <select
                v-model="estado"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
              >
                <option value="">Todos</option>
                <option value="pagado">Pagado</option>
                <option value="denegado">Denegado</option>
                <option value="error">Error</option>
                <option value="devuelto">Devuelto</option>
                <option value="devolucion_parcial">Devolucion parcial</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Tipo</label>
              <select
                v-model="tipo"
                class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
              >
                <option value="">Todos</option>
                <option value="notification">Notificacion</option>
                <option value="success">Exito</option>
                <option value="error">Error</option>
                <option value="refund">Devolucion</option>
              </select>
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Desde</label>
              <Input v-model="desde" type="date" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Hasta</label>
              <Input v-model="hasta" type="date" />
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Busqueda</label>
              <Input v-model="busqueda" placeholder="DNI, pedido, autorizacion" />
            </div>
          </div>
          <div class="mt-4 flex gap-2">
            <Button @click="aplicarFiltros">Filtrar</Button>
            <Button variant="outline" @click="limpiarFiltros">Limpiar</Button>
          </div>
        </section>

        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-white">
                <tr>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Fecha
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Inscripcion
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Pedido
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Autorizacion
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Importe
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Estado
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Tipo
                  </th>
                  <th
                    class="px-3 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Autobus
                  </th>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Detalle
                  </th>
                  <th
                    class="px-3 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="tx in transacciones" :key="tx.id">
                  <td class="px-3 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ formatDate(tx.created_at) }}
                  </td>
                  <td class="px-3 py-3 text-sm text-slate-900">
                    <div v-if="tx.inscripcion">
                      <div class="font-medium">
                        #{{ tx.inscripcion.id }}
                        {{ tx.inscripcion.participante.nombre }}
                        {{ tx.inscripcion.participante.apellidos }}
                      </div>
                      <div class="text-xs text-slate-500">
                        {{ tx.inscripcion.participante.dni }}
                      </div>
                    </div>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-3 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ tx.numero_pedido || '-' }}
                  </td>
                  <td class="px-3 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ tx.numero_autorizacion || '-' }}
                  </td>
                  <td class="px-3 py-3 text-sm whitespace-nowrap text-slate-900">
                    <span v-if="tx.importe">{{ tx.importe }} {{ tx.moneda }}</span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-3 py-3 text-sm whitespace-nowrap">
                    <span
                      class="inline-flex rounded-full px-2 text-xs font-semibold"
                      :class="estadoBadge(tx.estado)"
                    >
                      {{ tx.estado }}
                    </span>
                    <div v-if="tx.response_code" class="mt-1 text-[11px] text-slate-500">
                      Codigo: {{ tx.response_code }}
                    </div>
                    <div v-if="tx.descripcion_error" class="mt-1 text-[11px] text-slate-500">
                      {{ tx.descripcion_error }}
                    </div>
                  </td>
                  <td class="px-3 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ tipoTexto(tx.tipo) }}
                  </td>
                  <td class="px-3 py-3 text-center text-sm whitespace-nowrap">
                    <span
                      class="inline-flex rounded-full px-2 text-xs font-semibold"
                      :class="
                        tx.es_autobus ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'
                      "
                    >
                      {{ tx.es_autobus ? 'Si' : 'No' }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-xs text-slate-700">
                    <Button v-if="tx.payload" variant="outline" size="sm" @click="abrirDetalle(tx)">
                      Ver
                    </Button>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-3 py-3 text-center text-sm whitespace-nowrap">
                    <Button
                      v-if="tx.estado === 'error'"
                      variant="destructive"
                      size="icon-sm"
                      @click="eliminarTransaccion(tx)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="props.transacciones.data.length === 0" class="py-12 text-center">
            <p class="text-slate-500">No hay transacciones</p>
          </div>

          <div
            v-if="props.transacciones.last_page > 1"
            class="border-t border-slate-200 bg-white px-4 py-3"
          >
            <div class="flex items-center justify-between">
              <div class="text-sm text-slate-700">
                Mostrando
                {{ (props.transacciones.current_page - 1) * props.transacciones.per_page + 1 }} -
                {{
                  Math.min(
                    props.transacciones.current_page * props.transacciones.per_page,
                    props.transacciones.total
                  )
                }}
                de {{ props.transacciones.total }} resultados
              </div>
              <div class="flex gap-2">
                <Button
                  v-if="props.transacciones.current_page > 1"
                  variant="outline"
                  size="sm"
                  as="a"
                  :href="props.transacciones.prev_page_url || ''"
                >
                  Anterior
                </Button>
                <Button
                  v-if="props.transacciones.current_page < props.transacciones.last_page"
                  variant="outline"
                  size="sm"
                  as="a"
                  :href="props.transacciones.next_page_url || ''"
                >
                  Siguiente
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:open="detalleDialogOpen">
      <DialogContent class="max-h-[80vh] max-w-3xl overflow-auto">
        <DialogHeader>
          <DialogTitle>Detalle Redsys</DialogTitle>
          <DialogDescription v-if="detalleTransaccion">
            Pedido {{ detalleTransaccion.numero_pedido || '-' }} ·
            {{ formatDate(detalleTransaccion.created_at) }}
          </DialogDescription>
        </DialogHeader>
        <pre
          v-if="detalleTransaccion"
          class="mt-4 max-h-[60vh] overflow-auto rounded bg-slate-50 p-3 text-xs text-slate-800"
          >{{ payloadTexto(detalleTransaccion.payload) }}</pre
        >
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
