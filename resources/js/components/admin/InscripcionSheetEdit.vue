<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PARADAS } from '@/constants/paradas';
import { Download, Eye, Mail, Pencil, QrCode, Save } from 'lucide-vue-next';

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
  descuento_cupon: number | null;
  cupon?: {
    codigo: string;
    descripcion: string | null;
    descuento_tipo: 'porcentaje' | 'fijo';
    descuento_valor: number;
  } | null;
  importe_devolucion: number | null;
}

const props = defineProps<{
  inscripcion: Inscripcion;
  editingData: any;
  saving: boolean;
  calcularPrecio: (data: any, esTarifaTardia: boolean, descuentoCupon: number | null) => any;
  formatearFecha: (fecha: string) => string;
}>();

const emit = defineEmits<{
  open: [];
  close: [];
  save: [];
  reenviarCorreo: [];
}>();

const handleOpenChange = (open: boolean) => {
  if (open) {
    emit('open');
  } else {
    emit('close');
  }
};

const getDescuentoAplicadoEnInscripcion = (data: any, descuentoCupon: number | null): number => {
  const precioInscripcion = Number(props.calcularPrecio(data, false, 0).precio_inscripcion || 0);
  const descuentoTotal = Number(descuentoCupon || 0);

  // El descuento total puede incluir extras (p. ej. bus), aquí mostramos solo la parte de inscripción.
  return Math.max(0, Math.min(descuentoTotal, precioInscripcion));
};

const getPrecioInscripcionFinal = (data: any, descuentoCupon: number | null): number => {
  const precioInscripcion = Number(props.calcularPrecio(data, false, 0).precio_inscripcion || 0);
  const descuentoInscripcion = getDescuentoAplicadoEnInscripcion(data, descuentoCupon);
  return Math.max(0, precioInscripcion - descuentoInscripcion);
};

const getTipoDescuentoCupon = () => {
  if (!props.inscripcion.cupon) return '-';
  return props.inscripcion.cupon.descuento_tipo === 'fijo'
    ? `Import fix (${props.inscripcion.cupon.descuento_valor}€)`
    : `Percentatge (${props.inscripcion.cupon.descuento_valor}%)`;
};
</script>

<template>
  <Sheet @update:open="handleOpenChange">
    <SheetTrigger as-child>
      <Button variant="" size="icon-sm">
        <Pencil class="h-4 w-4" />
      </Button>
    </SheetTrigger>
    <SheetContent class="w-full overflow-y-auto sm:max-w-xl">
      <SheetHeader>
        <div class="flex items-center justify-between">
          <div>
            <SheetTitle>Inscripción #{{ inscripcion.id }}</SheetTitle>
            <SheetDescription>
              {{ formatearFecha(inscripcion.created_at) }}
            </SheetDescription>
          </div>
        </div>
      </SheetHeader>

      <Tabs default-value="resumen" class="mt-6">
        <TabsList class="grid w-full grid-cols-2">
          <TabsTrigger value="resumen" class="px-4 text-sm">
            <span class="flex items-center gap-2">
              <Eye class="h-4 w-4" />
              Resum
            </span>
          </TabsTrigger>
          <TabsTrigger value="editar" class="px-4 text-sm">
            <span class="flex items-center gap-2">
              <Pencil class="h-4 w-4" />
              Editar
            </span>
          </TabsTrigger>
        </TabsList>

        <!-- Tab Resumen -->
        <TabsContent value="resumen" class="mt-4 space-y-6">
          <!-- QR Code -->
          <div
            v-if="inscripcion.estado_pago === 'pagado' || inscripcion.estado_pago === 'invitado'"
            class="flex flex-col items-center rounded-lg border bg-white p-4 sm:p-6"
          >
            <QrCode class="mb-2 h-15 w-15 text-slate-300 sm:h-20 sm:w-20" />
            <p class="text-sm text-slate-500">Codi QR de verificació</p>
            <div class="mt-4 flex w-full justify-center gap-2">
              <a :href="`/inscripcio/${inscripcion.id}/pdf`" target="_blank">
                <Button variant="outline" size="sm" class="gap-2">
                  <Download class="h-4 w-4" />
                  PDF
                </Button>
              </a>
              <Button
                variant="outline"
                size="sm"
                class="gap-2 border-blue-200 text-blue-600"
                @click="emit('reenviarCorreo')"
              >
                <Mail class="h-4 w-4" />
                Reenviar Correu
              </Button>
            </div>
          </div>

          <!-- Datos del participante -->
          <div class="rounded-lg border bg-slate-50 p-4">
            <h3 class="mb-3 font-semibold text-slate-900">Participant</h3>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <p class="text-xs text-slate-500">Nom</p>
                <p class="font-medium wrap-break-word">
                  {{ inscripcion.participante.nombre }} {{ inscripcion.participante.apellidos }}
                </p>
              </div>
              <div>
                <p class="text-xs text-slate-500">DNI</p>
                <p class="font-medium wrap-break-word">{{ inscripcion.participante.dni }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Email</p>
                <p class="font-medium wrap-break-word">{{ inscripcion.participante.email }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Telèfon</p>
                <p class="font-medium wrap-break-word">{{ inscripcion.participante.telefono }}</p>
              </div>
            </div>
          </div>

          <!-- Estado y Pago -->
          <div class="rounded-lg border bg-slate-50 p-4">
            <h3 class="mb-3 font-semibold text-slate-900">Estat i Pagament</h3>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <span class="text-slate-500">Estat:</span>
                <span
                  :class="{
                    'bg-green-100 text-green-800': inscripcion.estado_pago === 'pagado',
                    'bg-amber-100 text-amber-800': inscripcion.estado_pago === 'pendiente',
                    'bg-red-100 text-red-800': inscripcion.estado_pago === 'cancelado',
                    'bg-blue-100 text-blue-800': inscripcion.estado_pago === 'invitado',
                    'bg-purple-100 text-purple-800': inscripcion.estado_pago === 'devuelto',
                    'bg-orange-100 text-orange-800':
                      inscripcion.estado_pago === 'devolucion_parcial',
                  }"
                  class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
                >
                  {{ inscripcion.estado_pago.toUpperCase() }}
                </span>
              </div>
              <div>
                <span class="text-slate-500">Total:</span>
                <span class="ml-2 text-lg font-bold text-slate-900"
                  >{{ inscripcion.precio_total }}€</span
                >
              </div>
              <div v-if="inscripcion.numero_pedido">
                <span class="text-slate-500">Nº Pedido:</span>
                <span class="ml-2 font-mono text-xs">{{ inscripcion.numero_pedido }}</span>
              </div>
              <div v-if="inscripcion.fecha_pago">
                <span class="text-slate-500">Data pagament:</span>
                <span class="ml-2">{{ formatearFecha(inscripcion.fecha_pago) }}</span>
              </div>

              <div class="sm:col-span-2">
                <span class="text-slate-500">Pagament amb cupó:</span>
                <span
                  :class="
                    inscripcion.descuento_cupon && Number(inscripcion.descuento_cupon) > 0
                      ? 'bg-green-100 text-green-800'
                      : 'bg-slate-200 text-slate-700'
                  "
                  class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
                >
                  {{
                    inscripcion.descuento_cupon && Number(inscripcion.descuento_cupon) > 0
                      ? `Sí (-${Number(inscripcion.descuento_cupon).toFixed(2)}€)`
                      : 'No'
                  }}
                </span>
              </div>

              <div
                v-if="inscripcion.descuento_cupon && Number(inscripcion.descuento_cupon) > 0"
                class="rounded-md border border-green-200 bg-green-50 p-3 text-xs sm:col-span-2"
              >
                <p>
                  <span class="text-slate-500">Cupó:</span>
                  <span class="ml-1 font-medium text-slate-900">
                    {{ inscripcion.cupon?.codigo || `ID ${inscripcion.cupon_id ?? '-'}` }}
                  </span>
                </p>
                <p>
                  <span class="text-slate-500">Tipus:</span>
                  <span class="ml-1 text-slate-900">{{ getTipoDescuentoCupon() }}</span>
                </p>
                <p>
                  <span class="text-slate-500">Descripció:</span>
                  <span class="ml-1 text-slate-900">
                    {{
                      inscripcion.cupon?.descripcion ||
                      (inscripcion.cupon_id ? 'Sense descripció o dades no carregades' : '-')
                    }}
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Opciones contratadas -->
          <div class="rounded-lg border bg-slate-50 p-4">
            <h3 class="mb-3 font-semibold text-slate-900">Opcions</h3>
            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div class="flex items-center gap-2">
                <span :class="inscripcion.es_socio_uec ? 'text-green-600' : 'text-slate-400'">
                  {{ inscripcion.es_socio_uec ? '✓' : '✗' }}
                </span>
                <span>Soci UEC</span>
              </div>
              <div class="flex items-center gap-2">
                <span :class="inscripcion.esta_federado ? 'text-green-600' : 'text-slate-400'">
                  {{ inscripcion.esta_federado ? '✓' : '✗' }}
                </span>
                <span>Federat</span>
                <span v-if="inscripcion.numero_licencia" class="text-xs text-slate-500"
                  >({{ inscripcion.numero_licencia }})</span
                >
              </div>
              <div class="flex items-center gap-2">
                <span :class="inscripcion.necesita_autobus ? 'text-green-600' : 'text-slate-400'">
                  {{ inscripcion.necesita_autobus ? '✓' : '✗' }}
                </span>
                <span>Autobús</span>
                <span v-if="inscripcion.parada_autobus" class="text-xs text-slate-500"
                  >({{ inscripcion.parada_autobus }})</span
                >
              </div>
              <div class="flex items-center gap-2">
                <span :class="inscripcion.seguro_anulacion ? 'text-green-600' : 'text-slate-400'">
                  {{ inscripcion.seguro_anulacion ? '✓' : '✗' }}
                </span>
                <span>Assegurança</span>
              </div>
              <div class="flex items-center gap-2">
                <span :class="inscripcion.es_celiaco ? 'text-green-600' : 'text-slate-400'">
                  {{ inscripcion.es_celiaco ? '✓' : '✗' }}
                </span>
                <span>Celíac</span>
              </div>
            </div>
          </div>

          <!-- Tallas -->
          <div class="rounded-lg border bg-slate-50 p-4">
            <h3 class="mb-3 font-semibold text-slate-900">Samarretes</h3>
            <div class="flex flex-col gap-4 text-center sm:flex-row sm:justify-around">
              <div>
                <span class="text-xs text-slate-500">Caro</span>
                <div class="text-lg font-bold">
                  {{ inscripcion.talla_camiseta_caro?.toUpperCase() }}
                </div>
              </div>
              <div>
                <span class="text-xs text-slate-500">Paüls</span>
                <div class="text-lg font-bold">
                  {{ inscripcion.talla_camiseta_pauls?.toUpperCase() }}
                </div>
              </div>
            </div>
          </div>
        </TabsContent>

        <!-- Tab Editar -->
        <TabsContent value="editar" class="mt-4">
          <div class="mb-4 flex justify-end">
            <Button variant="default" size="sm" :disabled="saving" @click="emit('save')">
              <Save class="h-4 w-4" />
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </Button>
          </div>
          <div v-if="editingData" class="space-y-6 text-sm">
            <!-- Datos personales -->
            <div class="space-y-3">
              <h3 class="font-semibold text-slate-900">Datos Personales</h3>
              <div class="grid grid-cols-1 gap-4 rounded-lg border bg-slate-50 p-4 md:grid-cols-2">
                <!-- Nombre -->
                <div>
                  <Label class="text-xs text-slate-500">Nombre</Label>
                  <Input v-model="editingData.nombre" class="mt-1" />
                </div>
                <!-- Apellidos -->
                <div>
                  <Label class="text-xs text-slate-500">Apellidos</Label>
                  <Input v-model="editingData.apellidos" class="mt-1" />
                </div>
                <!-- DNI -->
                <div>
                  <Label class="text-xs text-slate-500">DNI</Label>
                  <Input v-model="editingData.dni" class="mt-1" />
                </div>
                <!-- Género -->
                <div>
                  <Label class="text-xs text-slate-500">Género</Label>
                  <select
                    v-model="editingData.genero"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                  >
                    <option value="masculino">Masculino</option>
                    <option value="femenino">Femenino</option>
                  </select>
                </div>
                <!-- Email -->
                <div>
                  <Label class="text-xs text-slate-500">Email</Label>
                  <Input v-model="editingData.email" type="email" class="mt-1" />
                </div>
                <!-- Teléfono -->
                <div>
                  <Label class="text-xs text-slate-500">Teléfono</Label>
                  <Input v-model="editingData.telefono" class="mt-1" />
                </div>
                <!-- Fecha Nacimiento -->
                <div>
                  <Label class="text-xs text-slate-500">Fecha Nacimiento</Label>
                  <Input v-model="editingData.fecha_nacimiento" type="date" class="mt-1" />
                </div>
                <!-- Dirección (col-span-2) -->
                <div class="md:col-span-2">
                  <Label class="text-xs text-slate-500">Dirección</Label>
                  <Input v-model="editingData.direccion" class="mt-1" />
                </div>
                <!-- Código Postal -->
                <div>
                  <Label class="text-xs text-slate-500">Código Postal</Label>
                  <Input v-model="editingData.codigo_postal" class="mt-1" />
                </div>
                <!-- Población -->
                <div>
                  <Label class="text-xs text-slate-500">Población</Label>
                  <Input v-model="editingData.poblacion" class="mt-1" />
                </div>
                <!-- Provincia -->
                <div class="md:col-span-2">
                  <Label class="text-xs text-slate-500">Provincia</Label>
                  <Input v-model="editingData.provincia" class="mt-1" />
                </div>
              </div>
            </div>

            <!-- Detalles Inscripción -->
            <div class="space-y-3">
              <h3 class="font-semibold text-slate-900">Detalles Inscripción</h3>
              <div class="grid grid-cols-1 gap-4 rounded-lg border bg-slate-50 p-4 sm:grid-cols-2">
                <!-- Estado Pago -->
                <div>
                  <Label class="text-xs text-slate-500">Estado Pago</Label>
                  <select
                    v-model="editingData.estado_pago"
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
                  class="col-span-1 mt-2 border-t pt-3 sm:col-span-2"
                >
                  <Label class="text-xs font-semibold text-slate-500">Datos de Pago Redsys</Label>
                  <div class="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
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
                        inscripcion.fecha_pago ? formatearFecha(inscripcion.fecha_pago) : '-'
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Socio UEC -->
                <div
                  class="col-span-1 mt-2 grid grid-cols-1 gap-4 border-t pt-3 sm:col-span-2 sm:grid-cols-2"
                >
                  <div>
                    <Label class="text-xs text-slate-500">Socio UEC</Label>
                    <div class="mt-1">
                      <input
                        type="checkbox"
                        v-model="editingData.es_socio_uec"
                        class="h-4 w-4 rounded border-slate-300"
                      />
                    </div>
                  </div>
                  <!-- Club -->
                  <div>
                    <Label class="text-xs text-slate-500">Club</Label>
                    <Input v-model="editingData.club" class="mt-1" />
                  </div>
                </div>
                <!-- Federado -->
                <div>
                  <Label class="text-xs text-slate-500">Federado</Label>
                  <div class="mt-1">
                    <input
                      type="checkbox"
                      v-model="editingData.esta_federado"
                      class="h-4 w-4 rounded border-slate-300"
                    />
                  </div>
                </div>
                <!-- Número Licencia -->
                <div v-if="editingData.esta_federado">
                  <Label class="text-xs text-slate-500">Nº Licencia</Label>
                  <Input v-model="editingData.numero_licencia" class="mt-1" />
                </div>

                <!-- Autobús -->
                <div
                  class="col-span-1 mt-2 grid grid-cols-1 gap-4 border-t pt-3 sm:col-span-2 sm:grid-cols-2"
                >
                  <div>
                    <Label class="text-xs text-slate-500">Necesita Autobús</Label>
                    <div class="mt-1">
                      <input
                        type="checkbox"
                        v-model="editingData.necesita_autobus"
                        class="h-4 w-4 rounded border-slate-300"
                      />
                    </div>
                  </div>
                  <!-- Parada Autobús -->
                  <div v-if="editingData.necesita_autobus">
                    <Label class="text-xs text-slate-500">Parada Autobús</Label>
                    <select
                      v-model="editingData.parada_autobus"
                      class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                      <option value="">Selecciona parada...</option>
                      <option v-for="parada in PARADAS" :key="parada.value" :value="parada.value">
                        {{ parada.label }} ({{ parada.descripcion }})
                      </option>
                    </select>
                  </div>
                </div>
                <!-- Seguro Anulación y Celíaco -->
                <div
                  class="col-span-1 mt-2 grid grid-cols-1 gap-4 border-t pt-3 sm:col-span-2 sm:grid-cols-2"
                >
                  <div>
                    <Label class="text-xs text-slate-500">Seguro Anulación</Label>
                    <div class="mt-1">
                      <input
                        type="checkbox"
                        v-model="editingData.seguro_anulacion"
                        class="h-4 w-4 rounded border-slate-300"
                      />
                    </div>
                  </div>
                  <div>
                    <Label class="text-xs text-slate-500">Celíaco</Label>
                    <div class="mt-1">
                      <input
                        type="checkbox"
                        v-model="editingData.es_celiaco"
                        class="h-4 w-4 rounded border-slate-300"
                      />
                    </div>
                  </div>
                </div>
                <!-- Camiseta Caro -->
                <div
                  class="col-span-1 mt-2 grid grid-cols-1 gap-4 border-t pt-3 sm:col-span-2 sm:grid-cols-2"
                >
                  <div>
                    <Label class="text-xs text-slate-500">Camiseta Caro</Label>
                    <select
                      v-model="editingData.talla_camiseta_caro"
                      class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                      <option value="XS">XS</option>
                      <option value="S">S</option>
                      <option value="M">M</option>
                      <option value="L">L</option>
                      <option value="XL">XL</option>
                      <option value="XXL">XXL</option>
                      <option value="XXXL">XXXL</option>
                    </select>
                  </div>
                  <!-- Camiseta Paüls -->
                  <div>
                    <Label class="text-xs text-slate-500">Camiseta Paüls</Label>
                    <select
                      v-model="editingData.talla_camiseta_pauls"
                      class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                    >
                      <option value="XS">XS</option>
                      <option value="S">S</option>
                      <option value="M">M</option>
                      <option value="L">L</option>
                      <option value="XL">XL</option>
                      <option value="XXL">XXL</option>
                      <option value="XXXL">XXXL</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Resumen de Precio Calculado -->
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
              <h3 class="mb-3 font-semibold text-slate-900">Càlcul del preu</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-700">
                  <span>Inscripció {{ editingData.es_socio_uec ? '(Soci)' : '(Públic)' }}:</span>
                  <span class="text-right">
                    {{
                      calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                        .precio_inscripcion
                    }}€
                    <template
                      v-if="
                        getDescuentoAplicadoEnInscripcion(
                          editingData,
                          inscripcion.descuento_cupon
                        ) > 0
                      "
                    >
                      <span class="ml-1 text-xs text-green-600">
                        -
                        {{
                          getDescuentoAplicadoEnInscripcion(
                            editingData,
                            inscripcion.descuento_cupon
                          )
                        }}€ (cupó) =
                      </span>
                      <span class="text-slate-900">
                        {{ getPrecioInscripcionFinal(editingData, inscripcion.descuento_cupon) }}€
                      </span>
                    </template>
                  </span>
                </div>
                <div
                  v-if="
                    !editingData.esta_federado &&
                    calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                      .precio_licencia > 0
                  "
                  class="flex justify-between text-slate-700"
                >
                  <span>Llicència federativa:</span>
                  <span
                    >{{
                      calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                        .precio_licencia
                    }}€</span
                  >
                </div>
                <div
                  v-if="editingData.necesita_autobus"
                  class="flex justify-between text-slate-700"
                >
                  <span>Autobús:</span>
                  <span
                    >{{
                      calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                        .precio_autobus
                    }}€</span
                  >
                </div>
                <div
                  v-if="editingData.seguro_anulacion"
                  class="flex justify-between text-slate-700"
                >
                  <span>Assegurança:</span>
                  <span
                    >{{
                      calcularPrecio(editingData, false, inscripcion.descuento_cupon).precio_seguro
                    }}€</span
                  >
                </div>
                <div
                  v-if="inscripcion.descuento_cupon && inscripcion.descuento_cupon > 0"
                  class="flex justify-between text-green-600"
                >
                  <span>Descompte cupó:</span>
                  <span>-{{ inscripcion.descuento_cupon }}€</span>
                </div>
                <div class="mt-2 border-t border-blue-300 pt-2">
                  <div class="flex justify-between text-base font-bold text-slate-900">
                    <span>PREU FINAL:</span>
                    <span
                      >{{
                        calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                          .precio_total
                      }}€</span
                    >
                  </div>
                </div>
                <div class="mt-3 border-t border-blue-300 pt-3">
                  <div class="flex justify-between text-sm text-slate-600">
                    <span>Preu guardat al sistema:</span>
                    <span class="font-medium"
                      >{{ Number(inscripcion.precio_total).toFixed(2) }}€</span
                    >
                  </div>
                </div>
                <!-- Descuadre cobert per devolució parcial -->
                <p
                  v-if="
                    calcularPrecio(editingData, false, inscripcion.descuento_cupon).precio_total !==
                      Number(inscripcion.precio_total) &&
                    inscripcion.importe_devolucion &&
                    Math.abs(
                      Number(inscripcion.precio_total) -
                        calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                          .precio_total -
                        Number(inscripcion.importe_devolucion)
                    ) < 0.01
                  "
                  class="mt-3 rounded-md bg-green-50 p-3 text-xs text-green-700"
                >
                  ✅ S'ha retornat {{ Number(inscripcion.importe_devolucion).toFixed(2) }}€ per
                  ajustar la diferència de tarifes.
                </p>
                <!-- Descuadre NO cobert -->
                <p
                  v-else-if="
                    calcularPrecio(editingData, false, inscripcion.descuento_cupon).precio_total !==
                    Number(inscripcion.precio_total)
                  "
                  class="mt-3 rounded-md bg-amber-50 p-3 text-xs text-amber-700"
                >
                  ⚠️ <strong>Atenció:</strong> El preu guardat ({{
                    Number(inscripcion.precio_total).toFixed(2)
                  }}€) no coincideix amb el preu calculat ({{
                    calcularPrecio(editingData, false, inscripcion.descuento_cupon).precio_total
                  }}€).
                  <template v-if="inscripcion.importe_devolucion">
                    S'han retornat {{ Number(inscripcion.importe_devolucion).toFixed(2) }}€ però
                    encara queda un descuadre de
                    {{
                      (
                        Number(inscripcion.precio_total) -
                        calcularPrecio(editingData, false, inscripcion.descuento_cupon)
                          .precio_total -
                        Number(inscripcion.importe_devolucion)
                      ).toFixed(2)
                    }}€.
                  </template>
                  <template v-else>
                    Això pot ser perquè les tarifes de l'edició han canviat des de la inscripció.
                  </template>
                </p>
              </div>
            </div>
          </div>
        </TabsContent>
      </Tabs>
    </SheetContent>
  </Sheet>
</template>
