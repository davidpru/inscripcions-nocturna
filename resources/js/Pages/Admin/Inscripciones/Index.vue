<script setup lang="ts">
import InscripcionSheetEdit from '@/components/admin/InscripcionSheetEdit.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { PARADAS } from '@/constants/paradas';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onKeyStroke } from '@vueuse/core';
import axios from 'axios';
import {
  Bus,
  Check,
  IdCard,
  Mail,
  RotateCcw,
  Search,
  ShieldCheck,
  ShieldUser,
  Trash2,
  UserPlus,
  WheatOff,
  X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

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
  precio_inscripcion_socio_normal: number;
  precio_inscripcion_socio_tardia: number;
  precio_inscripcion_publico_normal: number;
  precio_inscripcion_publico_tardia: number;
  precio_licencia_federativa_socio: number;
  precio_licencia_federativa_publico: number;
  precio_autobus_normal: number;
  precio_autobus_tardia: number;
  precio_seguro: number;
}

interface CuponInfo {
  codigo: string;
  descripcion: string | null;
  descuento_tipo: 'porcentaje' | 'fijo';
  descuento_valor: number;
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
  es_celiaco: boolean;
  talla_camiseta_caro: string;
  talla_camiseta_pauls: string;
  club: string | null;
  numero_licencia: string | null;
  numero_pedido: string | null;
  numero_autorizacion: string | null;
  fecha_pago: string | null;
  cupon_id: number | null;
  cupon?: CuponInfo | null;
  descuento_cupon: number | null;
  dorsal_recogido: boolean;
  numero_dorsal: number | null;
  importe_devolucion: number | null;
  fecha_devolucion: string | null;
  cambio_dorsal_pendent: { id: number; token: string; expires_at: string } | null;
}

const props = defineProps<{
  inscripciones: Inscripcion[];
  ediciones: Edicion[];
  filtros: {
    edicion_id?: number;
    busqueda?: string;
  };
  totalInscripcionesPagadas: number;
}>();

const edicionSeleccionada = ref(props.filtros.edicion_id || '');
const busqueda = ref(props.filtros.busqueda || '');
const saving = ref(false);
const editingData = reactive<Record<number, any>>({});

// Tabs de filtrado por estado
const tabActiva = ref<'inscrits' | 'llista_espera' | 'pendents'>('inscrits');

// Filtrado local por tab
const inscripcionesFiltradas = computed(() => {
  switch (tabActiva.value) {
    case 'inscrits':
      return props.inscripciones.filter((i) => ['pagado', 'invitado', 'compromiso'].includes(i.estado_pago));
    case 'llista_espera':
      return props.inscripciones.filter((i) => i.estado_pago === 'lista_espera');
    case 'pendents':
      return props.inscripciones.filter((i) =>
        ['pendiente', 'cancelado', 'devuelto', 'devolucion_parcial', 'fallido'].includes(
          i.estado_pago
        )
      );
    default:
      return props.inscripciones;
  }
});

const contadorTabs = computed(() => ({
  inscrits: props.inscripciones.filter((i) => ['pagado', 'invitado', 'compromiso'].includes(i.estado_pago))
    .length,
  llista_espera: props.inscripciones.filter((i) => i.estado_pago === 'lista_espera').length,
  pendents: props.inscripciones.filter((i) =>
    ['pendiente', 'cancelado', 'devuelto', 'devolucion_parcial', 'fallido'].includes(i.estado_pago)
  ).length,
}));

// Renderizado incremental: mostrar filas progresivamente para no bloquear el DOM
const RENDER_BATCH = 50;
const renderCount = ref(RENDER_BATCH);
const renderTrigger = ref<HTMLElement | null>(null);
let renderObserver: IntersectionObserver | null = null;

const inscripcionesVisibles = computed(() =>
  inscripcionesFiltradas.value.slice(0, renderCount.value)
);

watch(
  () => props.inscripciones,
  () => {
    renderCount.value = RENDER_BATCH;
  }
);

watch(tabActiva, () => {
  renderCount.value = RENDER_BATCH;
});

onMounted(() => {
  renderObserver = new IntersectionObserver(
    (entries) => {
      if (entries[0]?.isIntersecting && renderCount.value < inscripcionesFiltradas.value.length) {
        renderCount.value = Math.min(
          renderCount.value + RENDER_BATCH,
          inscripcionesFiltradas.value.length
        );
      }
    },
    { rootMargin: '400px' }
  );
  if (renderTrigger.value) {
    renderObserver.observe(renderTrigger.value);
  }
});

onBeforeUnmount(() => {
  if (renderObserver && renderTrigger.value) {
    renderObserver.unobserve(renderTrigger.value);
  }
});

// Total de inscripciones pagadas (viene del backend)
const totalInscripcionesPagadas = computed(() => props.totalInscripcionesPagadas);

const numeroInscripcionPorId = computed(() => {
  const map = new Map<number, number>();
  let pagadasVistas = 0;

  for (const inscripcion of inscripcionesFiltradas.value) {
    if (['pagado', 'invitado', 'compromiso'].includes(inscripcion.estado_pago)) {
      map.set(inscripcion.id, totalInscripcionesPagadas.value - pagadasVistas);
      pagadasVistas += 1;
    }
  }

  return map;
});

const getNumeroInscripcion = (inscripcion: Inscripcion): number | null => {
  return numeroInscripcionPorId.value.get(inscripcion.id) ?? null;
};

const modalNuevaInscripcion = ref(false);
const buscandoParticipante = ref(false);
const participanteEncontrado = ref(false);
const participanteYaInscrito = ref(false);

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
    const response = await axios.post('/inscripcio/buscar-participante', {
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
      participanteYaInscrito.value = response.data.ya_inscrito || false;
    } else {
      participanteEncontrado.value = false;
      participanteYaInscrito.value = false;
    }
  } catch (error) {
    console.error('Error al buscar participante:', error);
  } finally {
    buscandoParticipante.value = false;
  }
};

const crearInscripcionManual = () => {
  nuevaInscripcionForm.post('/uec-admin/inscripciones', {
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
  participanteYaInscrito.value = false;
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

// Detectar si una inscripción fue con tarifa tardía comparando el precio pagado
const detectarTarifaTardia = (inscripcion: any): boolean => {
  const precioNormal = calcularPrecio(inscripcion, false, inscripcion.descuento_cupon).precio_total;
  const precioTardia = calcularPrecio(inscripcion, true, inscripcion.descuento_cupon).precio_total;
  const precioPagado = Number(inscripcion.precio_total);
  // Si coincide con tarifa tardía (o está más cerca), es tardía
  return Math.abs(precioPagado - precioTardia) < Math.abs(precioPagado - precioNormal);
};

// Calcular precio en base a las opciones (usando tarifas de la edición)
// Nova estructura: preu inscripció + llicència federativa (si no federat) + extras
const calcularPrecio = (data: any, esTarifaTardia: boolean, descuentoCupon: number | null = 0) => {
  const edicion = getEdicionActual();
  if (!edicion) {
    return {
      precio_inscripcion: 0,
      precio_licencia: 0,
      tarifa_base: 0,
      precio_autobus: 0,
      precio_seguro: 0,
      descuento_cupon: 0,
      precio_total: 0,
      es_tarifa_tardia: esTarifaTardia,
    };
  }

  // Preu d'inscripció base segons si és soci o no
  let precioInscripcion: number;
  if (data.es_socio_uec) {
    precioInscripcion = esTarifaTardia
      ? edicion.precio_inscripcion_socio_tardia
      : edicion.precio_inscripcion_socio_normal;
  } else {
    precioInscripcion = esTarifaTardia
      ? edicion.precio_inscripcion_publico_tardia
      : edicion.precio_inscripcion_publico_normal;
  }

  // Preu de la llicència federativa (només si NO està federat)
  let precioLicencia = 0;
  if (!data.esta_federado) {
    precioLicencia = data.es_socio_uec
      ? edicion.precio_licencia_federativa_socio
      : edicion.precio_licencia_federativa_publico;
  }

  // Tarifa base = inscripció + llicència (si escau)
  const tarifaBase = precioInscripcion + precioLicencia;

  const precioAutobus = data.necesita_autobus
    ? esTarifaTardia
      ? edicion.precio_autobus_tardia
      : edicion.precio_autobus_normal
    : 0;
  const precioSeguro = data.seguro_anulacion ? edicion.precio_seguro : 0;
  const descuento = descuentoCupon || 0;

  return {
    precio_inscripcion: precioInscripcion,
    precio_licencia: precioLicencia,
    tarifa_base: tarifaBase,
    precio_autobus: precioAutobus,
    precio_seguro: precioSeguro,
    descuento_cupon: descuento,
    precio_total: tarifaBase + precioAutobus + precioSeguro - descuento,
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
    es_celiaco: inscripcion.es_celiaco,
    talla_camiseta_caro: inscripcion.talla_camiseta_caro,
    talla_camiseta_pauls: inscripcion.talla_camiseta_pauls,
  };
};

const cancelEditing = (inscripcionId: number) => {
  delete editingData[inscripcionId];
};

const saveChanges = (inscripcion: Inscripcion) => {
  saving.value = true;
  router.put(`/uec-admin/inscripciones/${inscripcion.id}`, editingData[inscripcion.id], {
    preserveScroll: true,
    onSuccess: () => {
      // No eliminar editingData, solo actualizar el estado
      saving.value = false;
    },
    onError: () => {
      saving.value = false;
    },
  });
};

const filtrarPorEdicion = () => {
  window.location.href = `/uec-admin/inscripciones?edicion_id=${edicionSeleccionada.value}`;
};

const aplicarBusqueda = () => {
  const params = new URLSearchParams();
  if (edicionSeleccionada.value) {
    params.append('edicion_id', edicionSeleccionada.value.toString());
  }
  if (busqueda.value.trim()) {
    params.append('busqueda', busqueda.value.trim());
  }
  window.location.href = `/uec-admin/inscripciones${params.toString() ? '?' + params.toString() : ''}`;
};

// Debounce para búsqueda automática
let busquedaTimeout: number;
watch(busqueda, () => {
  clearTimeout(busquedaTimeout);
  busquedaTimeout = setTimeout(() => {
    aplicarBusqueda();
  }, 500);
});

// Exportar inscripciones confirmadas a CSV
const exportarInscripciones = () => {
  // Construir URL con parámetros de edición y búsqueda si están seleccionados
  const params = new URLSearchParams();
  if (edicionSeleccionada.value) {
    params.append('edicion_id', edicionSeleccionada.value.toString());
  }
  if (busqueda.value.trim()) {
    params.append('busqueda', busqueda.value.trim());
  }
  const url = `/uec-admin/inscripciones/exportar${params.toString() ? '?' + params.toString() : ''}`;
  window.open(url, '_blank');
};

const reenviarCorreo = (id: number) => {
  if (confirm('¿Estás seguro de que deseas reenviar el correo de confirmación?')) {
    router.post(
      `/uec-admin/inscripciones/${id}/reenviar-correo`,
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          // Opcional: mostrar notificación
        },
      }
    );
  }
};

const eliminarInscripcion = (id: number) => {
  if (
    confirm(
      '¿Estás seguro de que deseas eliminar esta inscripción? Esta acción no se puede deshacer.'
    )
  ) {
    router.delete(`/uec-admin/inscripciones/${id}`, {
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
const refundAjustTarifa = ref(false);

const maxRefundAmount = computed(() => {
  if (!refundInscripcion.value) return 0;
  const total = refundInscripcion.value.precio_total;
  const yaDevuelto = refundInscripcion.value.importe_devolucion || 0;
  return Math.round((total - yaDevuelto) * 100) / 100;
});

const abrirDialogoDevolucion = (inscripcion: Inscripcion) => {
  if (inscripcion.estado_pago !== 'pagado' && inscripcion.estado_pago !== 'devolucion_parcial') {
    return;
  }
  refundInscripcion.value = inscripcion;
  refundTipo.value = 'manual';
  const yaDevuelto = inscripcion.importe_devolucion || 0;
  const restante = Math.round((inscripcion.precio_total - yaDevuelto) * 100) / 100;

  // Si hay descuadre de tarifes, preformatear con la diferencia
  const esTardia = detectarTarifaTardia(inscripcion);
  const precioCalculado = calcularPrecio(
    inscripcion,
    esTardia,
    inscripcion.descuento_cupon
  ).precio_total;
  const diferencia =
    Math.round((Number(inscripcion.precio_total) - precioCalculado - yaDevuelto) * 100) / 100;
  refundImporte.value = (
    diferencia > 0 && diferencia < restante ? diferencia : restante
  ).toString();

  // Auto-marcar ajust de tarifa si hay descuadre
  refundAjustTarifa.value = diferencia > 0 && diferencia < restante;

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
      ? `/uec-admin/inscripciones/${refundInscripcion.value.id}/devolucion-manual`
      : `/uec-admin/inscripciones/${refundInscripcion.value.id}/devolucion`;

  router.post(
    ruta,
    { importe: importeNum, mantener_pagado: refundAjustTarifa.value },
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
  });
};

const formatearHora = (fecha: string) => {
  return new Date(fecha).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getEstadoPagoBadgeClass = (estado: string) => {
  if (estado === 'pagado') {
    return 'bg-teal-500 text-teal-100';
  } else if (estado === 'cancelado') {
    return 'bg-red-100 text-red-800';
  } else if (estado === 'devuelto') {
    return 'bg-purple-100 text-purple-800';
  } else if (estado === 'devolucion_parcial') {
    return 'bg-orange-100 text-orange-800';
  } else if (estado === 'invitado') {
    return 'bg-purple-500 text-purple-100';
  } else if (estado === 'lista_espera') {
    return 'bg-amber-100 text-amber-800';
  } else if (estado === 'compromiso') {
    return 'bg-indigo-500 text-indigo-100';
  } else {
    return 'bg-gray-100 text-gray-700';
  }
};

const getEstadoPagoTexto = (estado: string) => {
  const textos: Record<string, string> = {
    pagado: 'Pagat',
    pendiente: 'Pendent',
    cancelado: 'Cancel·lat',
    devuelto: 'Devolt',
    devolucion_parcial: 'Devolució Parcial',
    invitado: 'Invitat',
    compromiso: 'Compromís',
    fallido: 'Fallit',
    lista_espera: 'Llista Espera',
  };
  return textos[estado] || estado;
};

const getTipoDescuentoCupon = (inscripcion: Inscripcion): string => {
  if (!inscripcion.cupon) return '-';
  return inscripcion.cupon.descuento_tipo === 'fijo'
    ? `Import fix (${inscripcion.cupon.descuento_valor}€)`
    : `Percentatge (${inscripcion.cupon.descuento_valor}%)`;
};

// Estado del diálogo de dorsal
const dorsalDialogOpen = ref(false);
const dorsalInscripcion = ref<Inscripcion | null>(null);

const abrirDialogoDorsal = (inscripcion: Inscripcion) => {
  dorsalInscripcion.value = inscripcion;
  dorsalDialogOpen.value = true;
};

// Manejar Enter cuando el modal está abierto
onKeyStroke('Enter', (e) => {
  if (dorsalDialogOpen.value) {
    e.preventDefault();
    confirmarToggleDorsal();
  }
});

const confirmarToggleDorsal = () => {
  if (!dorsalInscripcion.value) return;

  const inscripcionId = dorsalInscripcion.value.id;

  router.post(
    `/uec-admin/inscripciones/${inscripcionId}/toggle-dorsal`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        dorsalDialogOpen.value = false;
        dorsalInscripcion.value = null;
      },
      onError: (errors) => {
        console.error('Error al cambiar estado dorsal:', errors);
      },
    }
  );
};
</script>

<template>
  <AdminLayout>
    <Head title="Gestió d'Inscripcions" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-slate-900">Gestió d'Inscripcions</h1>
          <p class="mt-1 text-slate-600">
            Total: {{ totalInscripcionesPagadas }} inscripcions
            <span v-if="busqueda" class="ml-2 text-slate-500">
              ({{ inscripcionesFiltradas.length }} resultats de la cerca)
            </span>
          </p>
        </div>

        <!-- Filtros -->
        <section class="mb-6 rounded-lg bg-white p-4 shadow">
          <div class="mb-4">
            <label class="mb-2 block text-sm font-medium text-slate-700">
              Buscar Participant
            </label>
            <div class="relative">
              <Search
                class="pointer-events-none absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-slate-400"
              />
              <Input
                v-model="busqueda"
                type="text"
                placeholder="Buscar por nom, cognoms, DNI o email..."
                class="pl-10"
              />
            </div>
          </div>

          <div class="flex flex-wrap items-end gap-4">
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
              <Button variant="outline" @click="exportarInscripciones">Exportar</Button>
            </div>
            <div>
              <Button @click="abrirModalNuevaInscripcion">
                <UserPlus class="mr-2 h-4 w-4" />
                Nova Inscripció
              </Button>
            </div>
          </div>
        </section>

        <!-- Tabs -->
        <div class="mb-4 flex gap-1 rounded-lg bg-slate-100 p-1">
          <button
            type="button"
            class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="
              tabActiva === 'inscrits'
                ? 'bg-white text-slate-900 shadow'
                : 'text-slate-600 hover:text-slate-900'
            "
            @click="tabActiva = 'inscrits'"
          >
            Inscrits
            <span
              class="ml-1.5 rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700"
            >
              {{ contadorTabs.inscrits }}
            </span>
          </button>
          <button
            type="button"
            class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="
              tabActiva === 'llista_espera'
                ? 'bg-white text-slate-900 shadow'
                : 'text-slate-600 hover:text-slate-900'
            "
            @click="tabActiva = 'llista_espera'"
          >
            Llista d'Espera
            <span
              class="ml-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"
            >
              {{ contadorTabs.llista_espera }}
            </span>
          </button>
          <button
            type="button"
            class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
            :class="
              tabActiva === 'pendents'
                ? 'bg-white text-slate-900 shadow'
                : 'text-slate-600 hover:text-slate-900'
            "
            @click="tabActiva = 'pendents'"
          >
            Pendents / Altres
            <span
              class="ml-1.5 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600"
            >
              {{ contadorTabs.pendents }}
            </span>
          </button>
        </div>

        <!-- Tabla de Inscripciones -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-white">
                <tr>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Nº
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Dorsal
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    #
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Nom
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    DNI
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Ed.
                  </th>
                  <th
                    class="px-2 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Tipo
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Preu
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Estat
                  </th>
                  <th
                    class="px-2 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Data
                  </th>
                  <th
                    class="px-2 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Check
                  </th>
                  <th
                    class="px-2 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                  >
                    Accions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="inscripcion in inscripcionesVisibles" :key="inscripcion.id">
                  <td class="px-2 py-3 text-sm whitespace-nowrap text-slate-900">
                    <span
                      v-if="getNumeroInscripcion(inscripcion)"
                      class="font-semibold text-blue-600"
                    >
                      {{ getNumeroInscripcion(inscripcion) }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-2 py-3 text-sm whitespace-nowrap tabular-nums">
                    <span v-if="inscripcion.numero_dorsal" class="font-semibold text-slate-900">
                      {{ inscripcion.numero_dorsal }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-2 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ inscripcion.id }}
                  </td>
                  <td class="px-2 py-3 whitespace-nowrap">
                    <div class="text-sm font-medium text-slate-900">
                      {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
                    </div>
                    <div class="text-sm text-slate-500">
                      {{ inscripcion.participante.email }}
                    </div>
                  </td>
                  <td class="px-2 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ inscripcion.participante.dni }}
                  </td>
                  <td class="px-2 py-3 text-sm whitespace-nowrap text-slate-900">
                    {{ inscripcion.edicion.anio }}
                  </td>
                  <td class="px-2 py-3 whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      <TooltipProvider>
                        <!-- Federado -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-6 w-6 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.esta_federado
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
                              "
                            >
                              <IdCard v-if="inscripcion.esta_federado" class="h-4 w-4" />
                              <IdCard v-else class="h-4 w-4" />
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
                              class="flex h-6 w-6 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.es_socio_uec
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
                              "
                            >
                              <ShieldUser v-if="inscripcion.es_socio_uec" class="h-4 w-4" />
                              <ShieldUser v-else class="h-4 w-4" />
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
                              class="flex h-6 w-6 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.necesita_autobus
                                  ? 'bg-green-100 text-green-600'
                                  : 'bg-red-100 text-red-600'
                              "
                            >
                              <Bus v-if="inscripcion.necesita_autobus" class="h-4 w-4" />
                              <Bus v-else class="h-4 w-4" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Bus: {{ inscripcion.necesita_autobus ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>

                        <!-- Seguro de Anulación -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-6 w-6 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.seguro_anulacion
                                  ? 'bg-violet-100 text-violet-600'
                                  : 'bg-red-100 text-red-600'
                              "
                            >
                              <ShieldCheck v-if="inscripcion.seguro_anulacion" class="h-4 w-4" />
                              <ShieldCheck v-else class="h-4 w-4" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Seguro: {{ inscripcion.seguro_anulacion ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>

                        <!-- Celíaco -->
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="flex h-6 w-6 cursor-help items-center justify-center rounded"
                              :class="
                                inscripcion.es_celiaco
                                  ? 'bg-amber-100 text-amber-600'
                                  : 'bg-red-100 text-red-600'
                              "
                            >
                              <WheatOff v-if="inscripcion.es_celiaco" class="h-4 w-4" />
                              <WheatOff v-else class="h-4 w-4" />
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>Celíac: {{ inscripcion.es_celiaco ? 'Sí' : 'No' }}</p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                  </td>
                  <td class="px-2 py-3 text-sm font-semibold whitespace-nowrap">
                    <div class="flex flex-col items-center gap-1">
                      <span
                        :class="
                          inscripcion.descuento_cupon && inscripcion.descuento_cupon > 0
                            ? 'text-green-600'
                            : 'text-slate-900'
                        "
                      >
                        {{ Number(inscripcion.precio_total).toFixed(2)
                        }}<sup class="text-[10px]"> €</sup>
                      </span>
                      <TooltipProvider
                        v-if="inscripcion.descuento_cupon && inscripcion.descuento_cupon > 0"
                      >
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <span
                              class="inline-flex h-5 items-center rounded bg-green-100 px-1.5 text-xs font-medium text-green-700"
                            >
                              -{{ inscripcion.descuento_cupon }}
                            </span>
                          </TooltipTrigger>
                          <TooltipContent>
                            <p>
                              Preu amb cupó: {{ Number(inscripcion.precio_total).toFixed(2)
                              }}<sup class="text-[10px]"> €</sup>
                            </p>
                            <p>
                              Descompte aplicat: -{{ Number(inscripcion.descuento_cupon).toFixed(2)
                              }}<sup class="text-[10px]">€</sup>
                            </p>
                            <p>
                              Cupó:
                              {{ inscripcion.cupon?.codigo || `ID ${inscripcion.cupon_id ?? '-'}` }}
                            </p>
                            <p>Tipus: {{ getTipoDescuentoCupon(inscripcion) }}</p>
                            <p>
                              Descripció:
                              {{
                                inscripcion.cupon?.descripcion ||
                                (inscripcion.cupon_id
                                  ? 'Sense descripció o dades del cupó no carregades'
                                  : '-')
                              }}
                            </p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                  </td>
                  <td class="px-2 py-3 whitespace-nowrap">
                    <div class="flex flex-col items-center gap-2">
                      <span
                        :class="getEstadoPagoBadgeClass(inscripcion.estado_pago)"
                        class="text-dark inline-flex rounded-full bg-gray-100 px-2 text-xs leading-5 font-semibold"
                      >
                        {{ getEstadoPagoTexto(inscripcion.estado_pago) }}
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
                  <td class="px-2 py-3 text-sm text-slate-900">
                    <div class="flex flex-col">
                      <span>{{ formatearFecha(inscripcion.created_at) }}</span>
                      <span class="text-xs text-slate-500">{{
                        formatearHora(inscripcion.created_at)
                      }}</span>
                    </div>
                  </td>
                  <td class="px-2 py-3 whitespace-nowrap">
                    <div class="flex justify-center">
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        :class="
                          inscripcion.dorsal_recogido
                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                            : 'bg-slate-100 text-slate-400 hover:bg-slate-200'
                        "
                        @click="abrirDialogoDorsal(inscripcion)"
                        :title="
                          inscripcion.dorsal_recogido ? 'Dorsal recogido' : 'Dorsal no recogido'
                        "
                      >
                        <Check v-if="inscripcion.dorsal_recogido" class="h-4 w-4" />
                        <X v-else class="h-4 w-4" />
                      </Button>
                    </div>
                  </td>
                  <td class="space-x-2 px-2 py-3 text-right text-sm font-medium whitespace-nowrap">
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <Button
                            variant="outline"
                            size="icon-sm"
                            class="border-blue-500! bg-blue-100! text-blue-600!"
                            @click="reenviarCorreo(inscripcion.id)"
                            title="Reenviar Correu"
                          >
                            <Mail class="h-4 w-4 text-blue-600" />
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>Reenviar Correu de Confirmació</p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>

                    <InscripcionSheetEdit
                      :inscripcion="inscripcion"
                      :editing-data="editingData[inscripcion.id]"
                      :saving="saving"
                      :calcular-precio="calcularPrecio"
                      :formatear-fecha="formatearFecha"
                      @open="startEditing(inscripcion)"
                      @close="cancelEditing(inscripcion.id)"
                      @save="saveChanges(inscripcion)"
                      @reenviar-correo="reenviarCorreo(inscripcion.id)"
                    />

                    <Button
                      v-if="
                        inscripcion.estado_pago === 'pagado' ||
                        inscripcion.estado_pago === 'devolucion_parcial'
                      "
                      variant="default"
                      size="icon-sm"
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
                      size="icon-sm"
                      @click="eliminarInscripcion(inscripcion.id)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="inscripcionesFiltradas.length === 0" class="py-12 text-center">
            <p class="text-slate-500">No hay inscripciones</p>
          </div>

          <!-- Total -->
          <div v-if="inscripciones.length > 0" class="border-t border-slate-200 bg-white px-4 py-3">
            <div class="text-sm text-slate-700">
              Mostrando {{ inscripcionesVisibles.length }} de
              {{ inscripcionesFiltradas.length }} resultats
            </div>
            <div ref="renderTrigger" class="h-1"></div>
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

            <!-- Info de devoluciones previas -->
            <div
              v-if="refundInscripcion && refundInscripcion.importe_devolucion"
              class="rounded-md bg-amber-50 p-3 text-sm text-amber-700"
            >
              Ja s'han retornat {{ refundInscripcion.importe_devolucion }}€ de
              {{ refundInscripcion.precio_total }}€
            </div>

            <!-- Aviso de descuadre de tarifes -->
            <div
              v-if="
                refundInscripcion &&
                calcularPrecio(
                  refundInscripcion,
                  detectarTarifaTardia(refundInscripcion),
                  refundInscripcion.descuento_cupon
                ).precio_total !== Number(refundInscripcion.precio_total)
              "
              class="rounded-md bg-blue-50 p-3 text-sm text-blue-700"
            >
              <strong>Descuadre de tarifes:</strong> Es va cobrar
              {{ Number(refundInscripcion.precio_total).toFixed(2) }}€ però el preu actual és
              {{
                calcularPrecio(
                  refundInscripcion,
                  detectarTarifaTardia(refundInscripcion),
                  refundInscripcion.descuento_cupon
                ).precio_total
              }}€. Diferència:
              {{
                (
                  Number(refundInscripcion.precio_total) -
                  calcularPrecio(
                    refundInscripcion,
                    detectarTarifaTardia(refundInscripcion),
                    refundInscripcion.descuento_cupon
                  ).precio_total
                ).toFixed(2)
              }}€
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
              <p class="text-sm text-slate-500">Màxim a retornar: {{ maxRefundAmount }}€</p>
            </div>

            <!-- Mantener como pagado (ajuste de tarifa) -->
            <div class="flex items-start space-x-3">
              <Checkbox
                id="ajust-tarifa"
                :model-value="refundAjustTarifa"
                @update:model-value="
                  (val: boolean | 'indeterminate') => (refundAjustTarifa = val === true)
                "
              />
              <div>
                <Label for="ajust-tarifa" class="cursor-pointer font-medium"
                  >Ajust de tarifa (mantenir inscripció activa)</Label
                >
                <div class="text-sm text-slate-500">
                  La inscripció es manté com a "Pagat" amb el seu dorsal
                </div>
              </div>
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

      <!-- Diálogo de confirmación dorsal -->
      <Dialog v-model:open="dorsalDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {{
                dorsalInscripcion?.dorsal_recogido
                  ? 'Desmarcar dorsal'
                  : 'Marcar dorsal como recogido'
              }}
            </DialogTitle>
            <DialogDescription v-if="dorsalInscripcion">
              <span class="font-medium">
                {{ dorsalInscripcion.participante.nombre }}
                {{ dorsalInscripcion.participante.apellidos }}
              </span>
            </DialogDescription>
          </DialogHeader>

          <div class="py-4">
            <p class="text-sm text-slate-600">
              {{
                dorsalInscripcion?.dorsal_recogido
                  ? '¿Estás seguro de que deseas desmarcar el dorsal como no recogido?'
                  : '¿Confirmas que este participante ha recogido su dorsal?'
              }}
            </p>
            <p class="mt-2 text-xs text-slate-400">
              Presiona
              <kbd class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-slate-700">Enter</kbd>
              para confirmar
            </p>
          </div>

          <DialogFooter class="flex gap-2 sm:gap-0">
            <Button variant="outline" @click="dorsalDialogOpen = false"> Cancelar </Button>
            <Button
              :variant="dorsalInscripcion?.dorsal_recogido ? 'destructive' : 'default'"
              @click="confirmarToggleDorsal"
            >
              {{ dorsalInscripcion?.dorsal_recogido ? 'Desmarcar' : 'Confirmar entrega' }}
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
              <p
                v-if="participanteEncontrado && !participanteYaInscrito"
                class="mt-2 text-sm text-green-600"
              >
                ✓ Participant trobat. Verifica les dades.
              </p>
              <p v-if="participanteYaInscrito" class="mt-2 text-sm font-medium text-red-600">
                ⚠️ Aquest participant ja està inscrit en aquesta edició.
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
                      <SelectItem value="compromiso">Compromís</SelectItem>
                      <SelectItem value="lista_espera">Llista d'espera</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <!-- Resumen del precio -->
            <div
              v-if="precioCalculadoNuevo && !['invitado', 'compromiso'].includes(nuevaInscripcionForm.estado_pago)"
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
              v-if="['invitado', 'compromiso'].includes(nuevaInscripcionForm.estado_pago)"
              class="rounded-lg border-2 border-green-200 bg-green-50 p-4"
            >
              <p class="text-sm text-green-800">
                ℹ️ Els participants {{ nuevaInscripcionForm.estado_pago === 'compromiso' ? 'amb compromís' : 'invitats' }} no han de pagar. El preu total serà 0€.
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
              <Button
                type="submit"
                :disabled="nuevaInscripcionForm.processing || participanteYaInscrito"
              >
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
