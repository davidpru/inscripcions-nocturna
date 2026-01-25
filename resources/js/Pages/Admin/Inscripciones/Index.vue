<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { PARADAS } from '@/constants/paradas';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Bus, Check, Pencil, RotateCcw, Save, Trash2, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Participante {
  id: number;
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
  parada_autobus: string | null;
  seguro_anulacion: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  club: string | null;
  numero_licencia: string | null;
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
const saving = ref(false);
const editingData = reactive<Record<number, any>>({});

// Tarifas (igual que en TarifaService.php)
const TARIFAS = {
  publico_federado: { normal: 35, tardia: 40 },
  publico_no_federado: { normal: 40, tardia: 45 },
  socio_federado: { normal: 30, tardia: 35 },
  socio_no_federado: { normal: 35, tardia: 40 },
};
const AUTOBUS = { normal: 12, tardia: 14 };
const SEGURO = 9;

// Calcular precio en base a las opciones
const calcularPrecio = (data: any, esTarifaTardia: boolean) => {
  const tipoTarifa = esTarifaTardia ? 'tardia' : 'normal';

  // Determinar perfil
  let perfilClave: string;
  if (data.es_socio_uec && data.esta_federado) {
    perfilClave = 'socio_federado';
  } else if (data.es_socio_uec && !data.esta_federado) {
    perfilClave = 'socio_no_federado';
  } else if (!data.es_socio_uec && data.esta_federado) {
    perfilClave = 'publico_federado';
  } else {
    perfilClave = 'publico_no_federado';
  }

  const tarifaBase = TARIFAS[perfilClave as keyof typeof TARIFAS][tipoTarifa];
  const precioAutobus = data.necesita_autobus ? AUTOBUS[tipoTarifa] : 0;
  const precioSeguro = data.seguro_anulacion ? SEGURO : 0;

  return {
    tarifa_base: tarifaBase,
    precio_autobus: precioAutobus,
    precio_seguro: precioSeguro,
    precio_total: tarifaBase + precioAutobus + precioSeguro,
    es_tarifa_tardia: esTarifaTardia,
  };
};

// Formatear fecha a YYYY-MM-DD para input date (sin problemas de zona horaria)
const formatDateForInput = (dateString: string): string => {
  if (!dateString) return '';
  // Si ya está en formato YYYY-MM-DD, devolverlo directamente
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
    return dateString;
  }
  // Si tiene formato ISO con hora (YYYY-MM-DDTHH:mm:ss), extraer solo la fecha
  if (dateString.includes('T')) {
    return dateString.split('T')[0];
  }
  // Para otros formatos, extraer los primeros 10 caracteres si es YYYY-MM-DD...
  return dateString.substring(0, 10);
};

// Formatear fecha YYYY-MM-DD a DD/MM/YYYY para mostrar (sin usar new Date)
const formatDateForDisplay = (dateString: string): string => {
  if (!dateString) return '';
  const isoDate = formatDateForInput(dateString);
  const [year, month, day] = isoDate.split('-');
  return `${day}/${month}/${year}`;
};

const startEditing = (inscripcion: Inscripcion) => {
  if (editingData[inscripcion.id]) return; // Ya iniciado
  editingData[inscripcion.id] = {
    // Datos del participante
    nombre: inscripcion.participante.nombre,
    apellidos: inscripcion.participante.apellidos,
    dni: inscripcion.participante.dni,
    email: inscripcion.participante.email,
    telefono: inscripcion.participante.telefono,
    direccion: inscripcion.participante.direccion,
    codigo_postal: inscripcion.participante.codigo_postal,
    poblacion: inscripcion.participante.poblacion,
    provincia: inscripcion.participante.provincia,
    genero: inscripcion.participante.genero,
    fecha_nacimiento: formatDateForInput(inscripcion.participante.fecha_nacimiento),
    // Datos de la inscripción
    estado_pago: inscripcion.estado_pago,
    es_socio_uec: inscripcion.es_socio_uec,
    esta_federado: inscripcion.esta_federado,
    numero_licencia: inscripcion.numero_licencia || '',
    club: inscripcion.club || '',
    necesita_autobus: inscripcion.necesita_autobus,
    parada_autobus: inscripcion.parada_autobus || '',
    seguro_anulacion: inscripcion.seguro_anulacion,
    talla_camiseta_caro: inscripcion.talla_camiseta_caro,
    talla_camiseta_pauls: inscripcion.talla_camiseta_pauls,
  };
};

const cancelEditing = (inscripcionId: number) => {
  delete editingData[inscripcionId];
};

const saveChanges = (inscripcion: Inscripcion) => {
  saving.value = true;
  router.put(`/admin/inscripciones/${inscripcion.id}`, editingData[inscripcion.id], {
    preserveScroll: true,
    onSuccess: () => {
      delete editingData[inscripcion.id];
      saving.value = false;
    },
    onError: () => {
      saving.value = false;
    },
  });
};

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

const processingRefund = ref<number | null>(null);

// Estado del diálogo de devolución
const refundDialogOpen = ref(false);
const refundInscripcion = ref<Inscripcion | null>(null);
const refundTipo = ref<'manual' | 'redsys'>('manual');
const refundImporte = ref('');
const refundError = ref('');

const maxRefundAmount = computed(() => refundInscripcion.value?.precio_total || 0);

const abrirDialogoDevolucion = (inscripcion: Inscripcion) => {
  if (inscripcion.estado_pago !== 'pagado') {
    return;
  }
  refundInscripcion.value = inscripcion;
  refundTipo.value = 'manual';
  refundImporte.value = inscripcion.precio_total.toString();
  refundError.value = '';
  refundDialogOpen.value = true;
};

const confirmarDevolucion = () => {
  if (!refundInscripcion.value) return;

  const importeNum = parseFloat(refundImporte.value);
  if (isNaN(importeNum) || importeNum <= 0 || importeNum > maxRefundAmount.value) {
    refundError.value = `El importe debe ser entre 0.01€ y ${maxRefundAmount.value}€`;
    return;
  }

  processingRefund.value = refundInscripcion.value.id;
  const ruta =
    refundTipo.value === 'manual'
      ? `/admin/inscripciones/${refundInscripcion.value.id}/devolucion-manual`
      : `/admin/inscripciones/${refundInscripcion.value.id}/devolucion`;

  router.post(
    ruta,
    { importe: importeNum },
    {
      preserveScroll: true,
      onSuccess: () => {
        refundDialogOpen.value = false;
        refundInscripcion.value = null;
      },
      onFinish: () => {
        processingRefund.value = null;
      },
      onError: (errors) => {
        refundError.value = errors.error || 'Error al procesar la devolución';
      },
    }
  );
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
  } else if (estado === 'devuelto') {
    return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
  } else {
    return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
  }
};
</script>

<template>
  <AdminLayout>
    <Head title="Gestión de Inscripciones" />

    <div class="px-4 py-8">
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
                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase dark:text-slate-300"
                  >
                    Tipo
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
                  <td
                    class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100"
                  >
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
                  <td
                    class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100"
                  >
                    {{ inscripcion.participante.dni }}
                  </td>
                  <td
                    class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100"
                  >
                    {{ inscripcion.edicion.anio }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      <TooltipProvider>
                        <!-- Federado -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-5 w-5 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.esta_federado
                                  ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                  : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                              "
                            >
                              <Check v-if="inscripcion.esta_federado" class="h-3 w-3" />
                              <X v-else class="h-3 w-3" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Federado: {{ inscripcion.esta_federado ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>

                        <!-- Socio UEC -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-5 w-5 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.es_socio_uec
                                  ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                  : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                              "
                            >
                              <Check v-if="inscripcion.es_socio_uec" class="h-3 w-3" />
                              <X v-else class="h-3 w-3" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Socio: {{ inscripcion.es_socio_uec ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>

                        <!-- Bus -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-5 w-5 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.necesita_autobus
                                  ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                                  : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                              "
                            >
                              <Bus v-if="inscripcion.necesita_autobus" class="h-3 w-3" />
                              <X v-else class="h-3 w-3" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Bus: {{ inscripcion.necesita_autobus ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
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
                  <td
                    class="px-6 py-4 text-sm whitespace-nowrap text-slate-900 dark:text-slate-100"
                  >
                    {{ formatearFecha(inscripcion.created_at) }}
                  </td>
                  <td class="space-x-2 px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                    <Sheet
                      @update:open="
                        (open: boolean) => {
                          if (open) startEditing(inscripcion);
                          else cancelEditing(inscripcion.id);
                        }
                      "
                    >
                      <SheetTrigger as-child>
                        <Button variant="default" size="sm">
                          <Pencil class="mr-2 h-4 w-4" />
                          Editar
                        </Button>
                      </SheetTrigger>
                      <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
                        <SheetHeader>
                          <div class="flex items-center justify-between">
                            <div>
                              <SheetTitle>Editar Inscripción #{{ inscripcion.id }}</SheetTitle>
                              <SheetDescription>
                                {{ formatearFecha(inscripcion.created_at) }}
                              </SheetDescription>
                            </div>
                            <div class="flex gap-2">
                              <Button
                                variant="default"
                                size="sm"
                                :disabled="saving"
                                @click="saveChanges(inscripcion)"
                              >
                                <Save class="mr-2 h-4 w-4" />
                                {{ saving ? 'Guardando...' : 'Guardar' }}
                              </Button>
                            </div>
                          </div>
                        </SheetHeader>
                        <div v-if="editingData[inscripcion.id]" class="mt-6 space-y-6 text-sm">
                          <!-- Datos personales -->
                          <div class="space-y-3">
                            <h3 class="font-semibold text-slate-900">Datos Personales</h3>
                            <div class="grid grid-cols-2 gap-4 rounded-lg border bg-slate-50 p-4">
                              <!-- Nombre -->
                              <div>
                                <Label class="text-xs text-slate-500">Nombre</Label>
                                <Input v-model="editingData[inscripcion.id].nombre" class="mt-1" />
                              </div>
                              <!-- Apellidos -->
                              <div>
                                <Label class="text-xs text-slate-500">Apellidos</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].apellidos"
                                  class="mt-1"
                                />
                              </div>
                              <!-- DNI -->
                              <div>
                                <Label class="text-xs text-slate-500">DNI</Label>
                                <Input v-model="editingData[inscripcion.id].dni" class="mt-1" />
                              </div>
                              <!-- Género -->
                              <div>
                                <Label class="text-xs text-slate-500">Género</Label>
                                <select
                                  v-model="editingData[inscripcion.id].genero"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                  <option value="masculino">Masculino</option>
                                  <option value="femenino">Femenino</option>
                                </select>
                              </div>
                              <!-- Email -->
                              <div>
                                <Label class="text-xs text-slate-500">Email</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].email"
                                  type="email"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Teléfono -->
                              <div>
                                <Label class="text-xs text-slate-500">Teléfono</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].telefono"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Fecha Nacimiento -->
                              <div>
                                <Label class="text-xs text-slate-500">Fecha Nacimiento</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].fecha_nacimiento"
                                  type="date"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Dirección (col-span-2) -->
                              <div class="col-span-2">
                                <Label class="text-xs text-slate-500">Dirección</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].direccion"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Código Postal -->
                              <div>
                                <Label class="text-xs text-slate-500">Código Postal</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].codigo_postal"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Población -->
                              <div>
                                <Label class="text-xs text-slate-500">Población</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].poblacion"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Provincia -->
                              <div class="col-span-2">
                                <Label class="text-xs text-slate-500">Provincia</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].provincia"
                                  class="mt-1"
                                />
                              </div>
                            </div>
                          </div>

                          <!-- Detalles Inscripción -->
                          <div class="space-y-3">
                            <h3 class="font-semibold text-slate-900">Detalles Inscripción</h3>
                            <div class="grid grid-cols-2 gap-4 rounded-lg border bg-slate-50 p-4">
                              <!-- Estado Pago -->
                              <div>
                                <Label class="text-xs text-slate-500">Estado Pago</Label>
                                <select
                                  v-model="editingData[inscripcion.id].estado_pago"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                  <option value="pendiente">Pendiente</option>
                                  <option value="pagado">Pagado</option>
                                  <option value="cancelado">Cancelado</option>
                                  <option value="devuelto">Devuelto</option>
                                </select>
                              </div>
                              <!-- Precio Actual (solo lectura) -->
                              <div>
                                <Label class="text-xs text-slate-500">Precio Registrado</Label>
                                <span class="block font-medium text-slate-500"
                                  >{{ inscripcion.precio_total }}€</span
                                >
                              </div>
                              <!-- Socio UEC -->
                              <div>
                                <Label class="text-xs text-slate-500">Socio UEC</Label>
                                <div class="mt-1">
                                  <input
                                    type="checkbox"
                                    v-model="editingData[inscripcion.id].es_socio_uec"
                                    class="h-4 w-4 rounded border-slate-300"
                                  />
                                </div>
                              </div>
                              <!-- Federado -->
                              <div>
                                <Label class="text-xs text-slate-500">Federado</Label>
                                <div class="mt-1">
                                  <input
                                    type="checkbox"
                                    v-model="editingData[inscripcion.id].esta_federado"
                                    class="h-4 w-4 rounded border-slate-300"
                                  />
                                </div>
                              </div>
                              <!-- Número Licencia -->
                              <div v-if="editingData[inscripcion.id]?.esta_federado">
                                <Label class="text-xs text-slate-500">Nº Licencia</Label>
                                <Input
                                  v-model="editingData[inscripcion.id].numero_licencia"
                                  class="mt-1"
                                />
                              </div>
                              <!-- Club -->
                              <div>
                                <Label class="text-xs text-slate-500">Club</Label>
                                <Input v-model="editingData[inscripcion.id].club" class="mt-1" />
                              </div>
                              <!-- Autobús -->
                              <div>
                                <Label class="text-xs text-slate-500">Necesita Autobús</Label>
                                <div class="mt-1">
                                  <input
                                    type="checkbox"
                                    v-model="editingData[inscripcion.id].necesita_autobus"
                                    class="h-4 w-4 rounded border-slate-300"
                                  />
                                </div>
                              </div>
                              <!-- Parada Autobús -->
                              <div v-if="editingData[inscripcion.id]?.necesita_autobus">
                                <Label class="text-xs text-slate-500">Parada Autobús</Label>
                                <select
                                  v-model="editingData[inscripcion.id].parada_autobus"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                  <option value="">Selecciona parada...</option>
                                  <option
                                    v-for="parada in PARADAS"
                                    :key="parada.value"
                                    :value="parada.value"
                                  >
                                    {{ parada.label }} ({{ parada.descripcion }})
                                  </option>
                                </select>
                              </div>
                              <!-- Seguro Anulación -->
                              <div>
                                <Label class="text-xs text-slate-500">Seguro Anulación</Label>
                                <div class="mt-1">
                                  <input
                                    type="checkbox"
                                    v-model="editingData[inscripcion.id].seguro_anulacion"
                                    class="h-4 w-4 rounded border-slate-300"
                                  />
                                </div>
                              </div>
                              <!-- Camiseta Caro -->
                              <div>
                                <Label class="text-xs text-slate-500">Camiseta Caro</Label>
                                <select
                                  v-model="editingData[inscripcion.id].talla_camiseta_caro"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                  <option value="XS">XS</option>
                                  <option value="S">S</option>
                                  <option value="M">M</option>
                                  <option value="L">L</option>
                                  <option value="XL">XL</option>
                                  <option value="XXL">XXL</option>
                                </select>
                              </div>
                              <!-- Camiseta Paüls -->
                              <div>
                                <Label class="text-xs text-slate-500">Camiseta Paüls</Label>
                                <select
                                  v-model="editingData[inscripcion.id].talla_camiseta_pauls"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                  <option value="XS">XS</option>
                                  <option value="S">S</option>
                                  <option value="M">M</option>
                                  <option value="L">L</option>
                                  <option value="XL">XL</option>
                                  <option value="XXL">XXL</option>
                                </select>
                              </div>
                            </div>
                          </div>

                          <!-- Resumen de Precio Calculado -->
                          <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <h3 class="mb-3 font-semibold text-slate-900">
                              Resum de l'inscripció (segons opcions)
                            </h3>
                            <div class="space-y-2 text-sm">
                              <div class="flex justify-between text-slate-700">
                                <span>Inscripció:</span>
                                <span
                                  >{{
                                    calcularPrecio(editingData[inscripcion.id], false).tarifa_base
                                  }}€</span
                                >
                              </div>
                              <div
                                v-if="editingData[inscripcion.id]?.necesita_autobus"
                                class="flex justify-between text-slate-700"
                              >
                                <span>Autobús:</span>
                                <span
                                  >{{
                                    calcularPrecio(editingData[inscripcion.id], false)
                                      .precio_autobus
                                  }}€</span
                                >
                              </div>
                              <div
                                v-if="editingData[inscripcion.id]?.seguro_anulacion"
                                class="flex justify-between text-slate-700"
                              >
                                <span>Assegurança:</span>
                                <span
                                  >{{
                                    calcularPrecio(editingData[inscripcion.id], false)
                                      .precio_seguro
                                  }}€</span
                                >
                              </div>
                              <div class="mt-2 border-t border-blue-300 pt-2">
                                <div
                                  class="flex justify-between text-base font-bold text-slate-900"
                                >
                                  <span>TOTAL CALCULAT:</span>
                                  <span
                                    >{{
                                      calcularPrecio(editingData[inscripcion.id], false)
                                        .precio_total
                                    }}€</span
                                  >
                                </div>
                              </div>
                              <p
                                v-if="
                                  calcularPrecio(editingData[inscripcion.id], false)
                                    .precio_total !== Number(inscripcion.precio_total)
                                "
                                class="mt-2 text-xs text-amber-600"
                              >
                                * El preu calculat ({{
                                  calcularPrecio(editingData[inscripcion.id], false).precio_total
                                }}€) és diferent del preu registrat ({{
                                  inscripcion.precio_total
                                }}€)
                              </p>
                            </div>
                          </div>
                        </div>
                      </SheetContent>
                    </Sheet>

                    <Button
                      v-if="inscripcion.estado_pago === 'pagado'"
                      variant="default"
                      size="sm"
                      :disabled="processingRefund === inscripcion.id"
                      @click="abrirDialogoDevolucion(inscripcion)"
                      title="Procesar devolución"
                    >
                      <RotateCcw
                        class="h-4 w-4"
                        :class="{ 'animate-spin': processingRefund === inscripcion.id }"
                      />
                    </Button>

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

      <!-- Diálogo de devolución -->
      <Dialog v-model:open="refundDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Procesar devolución</DialogTitle>
            <DialogDescription v-if="refundInscripcion">
              Devolución para {{ refundInscripcion.participante.nombre }}
              {{ refundInscripcion.participante.apellidos }}
            </DialogDescription>
          </DialogHeader>

          <div class="space-y-4 py-4">
            <!-- Tipo de devolución -->
            <div class="space-y-3">
              <div class="mb-2!">Tipo de devolución</div>
              <RadioGroup v-model="refundTipo" class="space-y-2">
                <div class="flex items-start space-x-3">
                  <RadioGroupItem id="manual" value="manual" class="mt-1" />
                  <div>
                    <Label for="manual" class="cursor-pointer font-medium">Manual</Label>
                    <div class="text-sm text-slate-500">
                      Registrar devolución hecha por transferencia o efectivo
                    </div>
                  </div>
                </div>
                <div class="flex items-start space-x-3">
                  <RadioGroupItem id="redsys" value="redsys" class="mt-1" />
                  <div>
                    <Label for="redsys" class="cursor-pointer font-medium"
                      >Automática (Redsys)</Label
                    >
                    <div class="text-sm text-slate-500">
                      Requiere TPV con devoluciones habilitadas
                    </div>
                  </div>
                </div>
              </RadioGroup>
            </div>

            <!-- Importe -->
            <div class="space-y-2">
              <Label for="refund-amount">Importe a devolver (€)</Label>
              <Input
                id="refund-amount"
                v-model="refundImporte"
                type="number"
                step="0.01"
                min="0.01"
                :max="maxRefundAmount"
                placeholder="0.00"
              />
              <p class="text-sm text-slate-500">Máximo: {{ maxRefundAmount }}€</p>
            </div>

            <!-- Error -->
            <div
              v-if="refundError"
              class="rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400"
            >
              {{ refundError }}
            </div>
          </div>

          <DialogFooter class="flex gap-2 sm:gap-0">
            <Button variant="outline" @click="refundDialogOpen = false"> Cancelar </Button>
            <Button :disabled="processingRefund !== null" @click="confirmarDevolucion">
              <RotateCcw v-if="processingRefund" class="mr-2 h-4 w-4 animate-spin" />
              Confirmar devolución
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AdminLayout>
</template>
