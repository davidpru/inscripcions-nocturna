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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Bus, Check, Pencil, RotateCcw, Save, Trash2, UserPlus, X } from 'lucide-vue-next';
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
  tarifa_socio_federado_normal: number;
  tarifa_socio_federado_tardia: number;
  tarifa_socio_no_federado_normal: number;
  tarifa_socio_no_federado_tardia: number;
  tarifa_publico_federado_normal: number;
  tarifa_publico_federado_tardia: number;
  tarifa_publico_no_federado_normal: number;
  tarifa_publico_no_federado_tardia: number;
  precio_autobus_normal: number;
  precio_autobus_tardia: number;
  precio_seguro: number;
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
  numero_pedido: string | null;
  numero_autorizacion: string | null;
  fecha_pago: string | null;
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

// Modal para nueva inscripción
const modalNuevaInscripcion = ref(false);
const buscandoParticipante = ref(false);
const participanteEncontrado = ref(false);

const nuevaInscripcionForm = useForm({
  dni: '',
  nombre: '',
  apellidos: '',
  genero: '',
  fecha_nacimiento: '',
  telefono: '',
  email: '',
  direccion: '',
  codigo_postal: '',
  poblacion: '',
  provincia: '',
  edicion_id: props.filtros.edicion_id || props.ediciones[0]?.id || 0,
  es_socio_uec: false,
  esta_federado: false,
  numero_licencia: '',
  club: '',
  necesita_autobus: false,
  parada_autobus: '',
  seguro_anulacion: false,
  talla_camiseta_caro: '',
  talla_camiseta_pauls: '',
  es_celiaco: 'no',
  estado_pago: 'pagado',
});

const buscarParticipanteManual = async () => {
  if (!nuevaInscripcionForm.dni || nuevaInscripcionForm.dni.length < 8) return;

  buscandoParticipante.value = true;
  try {
    const response = await axios.post('/inscripcion/buscar-participante', {
      dni: nuevaInscripcionForm.dni,
      edicion_id: nuevaInscripcionForm.edicion_id,
    });

    if (response.data.encontrado && response.data.datos) {
      const datos = response.data.datos;
      nuevaInscripcionForm.nombre = datos.nombre || '';
      nuevaInscripcionForm.apellidos = datos.apellidos || '';
      nuevaInscripcionForm.genero = datos.genero || '';
      nuevaInscripcionForm.fecha_nacimiento = datos.fecha_nacimiento
        ? datos.fecha_nacimiento.split('T')[0]
        : '';
      nuevaInscripcionForm.telefono = datos.telefono || '';
      nuevaInscripcionForm.email = datos.email || '';
      nuevaInscripcionForm.direccion = datos.direccion || '';
      nuevaInscripcionForm.codigo_postal = datos.codigo_postal || '';
      nuevaInscripcionForm.poblacion = datos.poblacion || '';
      nuevaInscripcionForm.provincia = datos.provincia || '';
      participanteEncontrado.value = true;
    } else {
      participanteEncontrado.value = false;
    }
  } catch (error) {
    console.error('Error al buscar participante:', error);
  } finally {
    buscandoParticipante.value = false;
  }
};

const crearInscripcionManual = () => {
  nuevaInscripcionForm.post('/admin/inscripciones', {
    preserveScroll: true,
    onSuccess: () => {
      modalNuevaInscripcion.value = false;
      resetFormNuevaInscripcion();
    },
  });
};

const resetFormNuevaInscripcion = () => {
  nuevaInscripcionForm.reset();
  participanteEncontrado.value = false;
};

const abrirModalNuevaInscripcion = () => {
  resetFormNuevaInscripcion();
  modalNuevaInscripcion.value = true;
};

// Calcular precio de nueva inscripción
const precioCalculadoNuevo = ref<any>(null);
const calculandoPrecio = ref(false);

const calcularPrecioNuevo = async () => {
  if (!nuevaInscripcionForm.edicion_id) return;

  calculandoPrecio.value = true;
  try {
    const response = await axios.post('/inscripcion/calcular-precio', {
      edicion_id: nuevaInscripcionForm.edicion_id,
      es_socio_uec: nuevaInscripcionForm.es_socio_uec,
      esta_federado: nuevaInscripcionForm.esta_federado,
      necesita_autobus: nuevaInscripcionForm.necesita_autobus,
      seguro_anulacion: nuevaInscripcionForm.seguro_anulacion,
    });
    precioCalculadoNuevo.value = response.data;
  } catch (error) {
    console.error('Error al calcular precio:', error);
  } finally {
    calculandoPrecio.value = false;
  }
};

// Calcular precio cuando cambian opciones
import { watch } from 'vue';
watch(
  () => [
    nuevaInscripcionForm.es_socio_uec,
    nuevaInscripcionForm.esta_federado,
    nuevaInscripcionForm.necesita_autobus,
    nuevaInscripcionForm.seguro_anulacion,
    nuevaInscripcionForm.edicion_id,
  ],
  () => {
    calcularPrecioNuevo();
  }
);

// Autocompletar club cuando es socio UEC
watch(
  () => nuevaInscripcionForm.es_socio_uec,
  (esSocio) => {
    if (esSocio) {
      nuevaInscripcionForm.club = 'UEC Tortosa';
    } else {
      nuevaInscripcionForm.club = '';
    }
  }
);

// Obtener la edición actual seleccionada
const getEdicionActual = () => {
  const edicionId = edicionSeleccionada.value || props.filtros.edicion_id || props.ediciones[0]?.id;
  return props.ediciones.find((e) => e.id === Number(edicionId));
};

// Calcular precio en base a las opciones (usando tarifas de la edición)
const calcularPrecio = (data: any, esTarifaTardia: boolean) => {
  const edicion = getEdicionActual();
  if (!edicion) {
    return {
      tarifa_base: 0,
      precio_autobus: 0,
      precio_seguro: 0,
      precio_total: 0,
      es_tarifa_tardia: esTarifaTardia,
    };
  }

  let tarifaBase: number;
  if (data.es_socio_uec && data.esta_federado) {
    tarifaBase = esTarifaTardia
      ? edicion.tarifa_socio_federado_tardia
      : edicion.tarifa_socio_federado_normal;
  } else if (data.es_socio_uec && !data.esta_federado) {
    tarifaBase = esTarifaTardia
      ? edicion.tarifa_socio_no_federado_tardia
      : edicion.tarifa_socio_no_federado_normal;
  } else if (!data.es_socio_uec && data.esta_federado) {
    tarifaBase = esTarifaTardia
      ? edicion.tarifa_publico_federado_tardia
      : edicion.tarifa_publico_federado_normal;
  } else {
    tarifaBase = esTarifaTardia
      ? edicion.tarifa_publico_no_federado_tardia
      : edicion.tarifa_publico_no_federado_normal;
  }

  const precioAutobus = data.necesita_autobus
    ? esTarifaTardia
      ? edicion.precio_autobus_tardia
      : edicion.precio_autobus_normal
    : 0;
  const precioSeguro = data.seguro_anulacion ? edicion.precio_seguro : 0;

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
    return 'bg-green-100 text-green-800';
  } else if (estado === 'cancelado') {
    return 'bg-red-100 text-red-800';
  } else if (estado === 'devuelto') {
    return 'bg-purple-100 text-purple-800';
  } else if (estado === 'invitado') {
    return 'bg-blue-100 text-blue-800';
  } else {
    return 'bg-amber-100 text-amber-800';
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
          <h1 class="text-3xl font-bold text-slate-900">Gestión de Inscripciones</h1>
          <p class="mt-1 text-slate-600">Total: {{ inscripciones.total }} inscripciones</p>
        </div>

        <!-- Filtros -->
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
          <div class="flex items-end gap-4">
            <div class="flex-1">
              <label class="mb-2 block text-sm font-medium text-slate-700">
                Filtrar por Edición
              </label>
              <select
                v-model="edicionSeleccionada"
                class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
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
            <div>
              <Button @click="abrirModalNuevaInscripcion">
                <UserPlus class="mr-2 h-4 w-4" />
                Nova Inscripció
              </Button>
            </div>
          </div>
        </div>

        <!-- Tabla de Inscripciones -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    #
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Participante
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    DNI
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Edición
                  </th>
                  <th
                    class="px-6 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Tipo
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Precio
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Estado Pago
                  </th>
                  <th
                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Fecha
                  </th>
                  <th
                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="inscripcion in inscripciones.data" :key="inscripcion.id">
                  <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900">
                    {{ inscripcion.id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900">
                      {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
                    </div>
                    <div class="text-sm text-slate-500">
                      {{ inscripcion.participante.email }}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900">
                    {{ inscripcion.participante.dni }}
                  </td>
                  <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900">
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
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
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
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
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
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
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
                  <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap text-slate-900">
                    {{ inscripcion.precio_total }}€
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span
                        :class="getEstadoPagoBadgeClass(inscripcion.estado_pago)"
                        class="inline-flex rounded-full px-2 text-xs leading-5 font-semibold"
                      >
                        {{ inscripcion.estado_pago }}
                      </span>
                      <Link
                        v-if="inscripcion.estado_pago === 'pendiente'"
                        :href="`/pago/${inscripcion.id}`"
                        target="_blank"
                        class="text-xs text-blue-600 underline hover:text-blue-800"
                        title="Enllaç de pagament"
                      >
                        🔗 Pagar
                      </Link>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm whitespace-nowrap text-slate-900">
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
                        <Button variant="" size="sm">
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
                                  <option value="invitado">Invitado</option>
                                </select>
                              </div>
                              <!-- Precio Actual (solo lectura) -->
                              <div>
                                <Label class="text-xs text-slate-500">Precio Registrado</Label>
                                <span class="block font-medium text-slate-500"
                                  >{{ inscripcion.precio_total }}€</span
                                >
                              </div>

                              <!-- Datos de pago Redsys -->
                              <div
                                v-if="inscripcion.numero_pedido"
                                class="col-span-2 mt-2 border-t pt-3"
                              >
                                <Label class="text-xs font-semibold text-slate-500"
                                  >Datos de Pago Redsys</Label
                                >
                                <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                                  <div>
                                    <span class="text-xs text-slate-400">Nº Pedido:</span>
                                    <span class="block font-mono text-slate-700">{{
                                      inscripcion.numero_pedido
                                    }}</span>
                                  </div>
                                  <div>
                                    <span class="text-xs text-slate-400">Cód. Auth:</span>
                                    <span class="block font-mono text-slate-700">{{
                                      inscripcion.numero_autorizacion || '-'
                                    }}</span>
                                  </div>
                                  <div>
                                    <span class="text-xs text-slate-400">Fecha Pago:</span>
                                    <span class="block text-slate-700">{{
                                      inscripcion.fecha_pago
                                        ? formatearFecha(inscripcion.fecha_pago)
                                        : '-'
                                    }}</span>
                                  </div>
                                </div>
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
            <p class="text-slate-500">No hay inscripciones</p>
          </div>

          <!-- Paginación -->
          <div
            v-if="inscripciones.last_page > 1"
            class="border-t border-slate-200 bg-white px-4 py-3"
          >
            <div class="flex items-center justify-between">
              <div class="text-sm text-slate-700">
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
            <div v-if="refundError" class="rounded-md bg-red-50 p-3 text-sm text-red-600">
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

      <!-- Modal Nueva Inscripción -->
      <Dialog v-model:open="modalNuevaInscripcion">
        <DialogContent class="max-h-[90vh] max-w-4xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Nova Inscripció Manual</DialogTitle>
            <DialogDescription>
              Introdueix el DNI per buscar el participant o emplena el formulari complet
            </DialogDescription>
          </DialogHeader>

          <form @submit.prevent="crearInscripcionManual" class="space-y-6">
            <!-- DNI + Búsqueda -->
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Cerca Participant</h3>
              <div class="flex gap-3">
                <div class="flex-1">
                  <Label for="nueva-dni">DNI/NIE *</Label>
                  <Input
                    id="nueva-dni"
                    v-model="nuevaInscripcionForm.dni"
                    type="text"
                    required
                    placeholder="12345678X"
                  />
                </div>
                <div class="flex items-end">
                  <Button
                    type="button"
                    variant="outline"
                    :disabled="buscandoParticipante"
                    @click="buscarParticipanteManual"
                  >
                    {{ buscandoParticipante ? 'Cercant...' : 'Cercar' }}
                  </Button>
                </div>
              </div>
              <p v-if="participanteEncontrado" class="mt-2 text-sm text-green-600">
                ✓ Participant trobat. Verifica les dades.
              </p>
            </div>

            <!-- Datos Personales -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Dades Personals</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="nueva-nombre">Nom *</Label>
                  <Input id="nueva-nombre" v-model="nuevaInscripcionForm.nombre" required />
                </div>
                <div>
                  <Label for="nueva-apellidos">Cognoms *</Label>
                  <Input id="nueva-apellidos" v-model="nuevaInscripcionForm.apellidos" required />
                </div>
                <div>
                  <Label for="nueva-genero">Gènere *</Label>
                  <Select v-model="nuevaInscripcionForm.genero" required>
                    <SelectTrigger id="nueva-genero">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="masculino">Masculí</SelectItem>
                      <SelectItem value="femenino">Femení</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label for="nueva-fecha">Data Naixement *</Label>
                  <Input
                    id="nueva-fecha"
                    v-model="nuevaInscripcionForm.fecha_nacimiento"
                    type="date"
                    required
                  />
                </div>
                <div>
                  <Label for="nueva-telefono">Telèfon *</Label>
                  <Input
                    id="nueva-telefono"
                    v-model="nuevaInscripcionForm.telefono"
                    type="tel"
                    required
                  />
                </div>
                <div>
                  <Label for="nueva-email">Email *</Label>
                  <Input
                    id="nueva-email"
                    v-model="nuevaInscripcionForm.email"
                    type="email"
                    required
                  />
                </div>
              </div>
            </div>

            <!-- Dirección -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Adreça</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <Label for="nueva-direccion">Adreça *</Label>
                  <Input id="nueva-direccion" v-model="nuevaInscripcionForm.direccion" required />
                </div>
                <div>
                  <Label for="nueva-codigo-postal">Codi Postal *</Label>
                  <Input
                    id="nueva-codigo-postal"
                    v-model="nuevaInscripcionForm.codigo_postal"
                    required
                  />
                </div>
                <div>
                  <Label for="nueva-poblacion">Població *</Label>
                  <Input id="nueva-poblacion" v-model="nuevaInscripcionForm.poblacion" required />
                </div>
                <div class="col-span-2">
                  <Label for="nueva-provincia">Província *</Label>
                  <Input id="nueva-provincia" v-model="nuevaInscripcionForm.provincia" required />
                </div>
              </div>
            </div>

            <!-- Opciones de Inscripción -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Opcions d'Inscripció</h3>
              <div class="space-y-3">
                <div class="flex items-center space-x-2">
                  <input
                    id="nueva-socio"
                    v-model="nuevaInscripcionForm.es_socio_uec"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                  />
                  <Label for="nueva-socio" class="cursor-pointer">És Soci UEC Tortosa</Label>
                </div>
                <div>
                  <Label for="nueva-club">Club</Label>
                  <Input id="nueva-club" v-model="nuevaInscripcionForm.club" />
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    id="nueva-federado"
                    v-model="nuevaInscripcionForm.esta_federado"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                  />
                  <Label for="nueva-federado" class="cursor-pointer">Està Federat</Label>
                </div>
                <div v-if="nuevaInscripcionForm.esta_federado" class="ml-6">
                  <Label for="nueva-licencia">Número Llicència</Label>
                  <Input id="nueva-licencia" v-model="nuevaInscripcionForm.numero_licencia" />
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    id="nueva-autobus"
                    v-model="nuevaInscripcionForm.necesita_autobus"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                  />
                  <Label for="nueva-autobus" class="cursor-pointer">Necessita Autobús</Label>
                </div>
                <div v-if="nuevaInscripcionForm.necesita_autobus" class="ml-6">
                  <Label for="nueva-parada">Parada Autobús</Label>
                  <Select v-model="nuevaInscripcionForm.parada_autobus">
                    <SelectTrigger id="nueva-parada">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="parada in PARADAS"
                        :key="parada.value"
                        :value="parada.value"
                      >
                        {{ parada.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="flex items-center space-x-2">
                  <input
                    id="nueva-seguro"
                    v-model="nuevaInscripcionForm.seguro_anulacion"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                  />
                  <Label for="nueva-seguro" class="cursor-pointer">Segur d'Anul·lació</Label>
                </div>
              </div>
            </div>

            <!-- Tallas -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Talles Samarretes</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="nueva-talla-caro">Talla Caro *</Label>
                  <Select v-model="nuevaInscripcionForm.talla_camiseta_caro" required>
                    <SelectTrigger id="nueva-talla-caro">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="XS">XS</SelectItem>
                      <SelectItem value="S">S</SelectItem>
                      <SelectItem value="M">M</SelectItem>
                      <SelectItem value="L">L</SelectItem>
                      <SelectItem value="XL">XL</SelectItem>
                      <SelectItem value="XXL">XXL</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label for="nueva-talla-pauls">Talla Paüls *</Label>
                  <Select v-model="nuevaInscripcionForm.talla_camiseta_pauls" required>
                    <SelectTrigger id="nueva-talla-pauls">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="XS">XS</SelectItem>
                      <SelectItem value="S">S</SelectItem>
                      <SelectItem value="M">M</SelectItem>
                      <SelectItem value="L">L</SelectItem>
                      <SelectItem value="XL">XL</SelectItem>
                      <SelectItem value="XXL">XXL</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <!-- Celíaco -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Intoleràncies</h3>
              <div>
                <Label for="nueva-celiaco">És Celíac? *</Label>
                <Select v-model="nuevaInscripcionForm.es_celiaco" required>
                  <SelectTrigger id="nueva-celiaco">
                    <SelectValue placeholder="Seleccionar..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="no">No</SelectItem>
                    <SelectItem value="si">Sí</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <!-- Edición y Estado -->
            <div>
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Edició i Estat Pagament</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <Label for="nueva-edicion">Edició *</Label>
                  <Select v-model="nuevaInscripcionForm.edicion_id" required>
                    <SelectTrigger id="nueva-edicion">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="edicion in ediciones"
                        :key="edicion.id"
                        :value="edicion.id"
                      >
                        {{ edicion.anio }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label for="nueva-estado">Estat Pagament *</Label>
                  <Select v-model="nuevaInscripcionForm.estado_pago" required>
                    <SelectTrigger id="nueva-estado">
                      <SelectValue placeholder="Seleccionar..." />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="pagado">Pagat</SelectItem>
                      <SelectItem value="pendiente">Pendent</SelectItem>
                      <SelectItem value="invitado">Invitat</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <!-- Resumen del precio -->
            <div
              v-if="precioCalculadoNuevo && nuevaInscripcionForm.estado_pago !== 'invitado'"
              class="rounded-lg border-2 border-blue-200 bg-blue-50 p-4"
            >
              <h3 class="mb-3 text-lg font-semibold text-blue-900">Resum del Preu</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-700">Tarifa base:</span>
                  <span class="font-medium text-slate-900"
                    >{{ precioCalculadoNuevo.tarifa_base }}€</span
                  >
                </div>
                <div v-if="nuevaInscripcionForm.necesita_autobus" class="flex justify-between">
                  <span class="text-slate-700">Autobús:</span>
                  <span class="font-medium text-slate-900"
                    >{{ precioCalculadoNuevo.precio_autobus }}€</span
                  >
                </div>
                <div v-if="nuevaInscripcionForm.seguro_anulacion" class="flex justify-between">
                  <span class="text-slate-700">Segur d'anul·lació:</span>
                  <span class="font-medium text-slate-900"
                    >{{ precioCalculadoNuevo.precio_seguro }}€</span
                  >
                </div>
                <div class="border-t border-blue-300 pt-2">
                  <div class="flex justify-between text-base">
                    <span class="font-semibold text-slate-900">Total:</span>
                    <span class="text-xl font-bold text-blue-600"
                      >{{ precioCalculadoNuevo.precio_total }}€</span
                    >
                  </div>
                </div>
                <div
                  v-if="precioCalculadoNuevo.tipo_tarifa === 'tardia'"
                  class="text-xs text-amber-700"
                >
                  * S'aplica tarifa tardana
                </div>
              </div>
            </div>

            <div
              v-if="nuevaInscripcionForm.estado_pago === 'invitado'"
              class="rounded-lg border-2 border-green-200 bg-green-50 p-4"
            >
              <p class="text-sm text-green-800">
                ℹ️ Els participants invitats no han de pagar. El preu total serà 0€.
              </p>
            </div>

            <!-- Errores -->
            <div
              v-if="Object.keys(nuevaInscripcionForm.errors).length > 0"
              class="rounded-md bg-red-50 p-4"
            >
              <h3 class="text-sm font-medium text-red-800">Errors al formulari:</h3>
              <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                <li v-for="(error, field) in nuevaInscripcionForm.errors" :key="field">
                  {{ error }}
                </li>
              </ul>
            </div>

            <!-- Footer -->
            <DialogFooter class="flex gap-2 sm:gap-0">
              <Button
                type="button"
                variant="outline"
                @click="modalNuevaInscripcion = false"
                :disabled="nuevaInscripcionForm.processing"
              >
                Cancel·lar
              </Button>
              <Button type="submit" :disabled="nuevaInscripcionForm.processing">
                <RotateCcw
                  v-if="nuevaInscripcionForm.processing"
                  class="mr-2 h-4 w-4 animate-spin"
                />
                Crear Inscripció
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  </AdminLayout>
</template>
