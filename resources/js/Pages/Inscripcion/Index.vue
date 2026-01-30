<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { PARADAS, getParadaShortLabel } from '@/constants/paradas';
import { Link, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import { onMounted, onUnmounted, ref, watch } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  fecha_evento: string;
  limite_inscritos: number;
  fecha_limite_tarifa_normal: string;
  estado: string;
}

interface ParticipanteData {
  dni: string;
  nombre: string;
  apellidos: string;
  genero: 'masculino' | 'femenino';
  fecha_nacimiento: string;
  telefono: string;
  email: string;
  direccion: string;
  codigo_postal: string;
  poblacion: string;
  provincia: string;
}

const props = defineProps<{
  edicion: Edicion;
  inscripcionesAbiertas: boolean;
  fechaInicioInscripciones: string | null;
  dni?: string;
  participante?: string;
}>();

// Countdown timer
const countdown = ref({ dias: 0, horas: 0, minutos: 0, segundos: 0 });
const inscripcionesAbiertas = ref(props.inscripcionesAbiertas);
let countdownInterval: ReturnType<typeof setInterval> | null = null;

const actualizarCountdown = () => {
  if (!props.fechaInicioInscripciones) {
    inscripcionesAbiertas.value = true;
    return;
  }

  const ahora = new Date().getTime();
  const fechaInicio = new Date(props.fechaInicioInscripciones).getTime();
  const diferencia = fechaInicio - ahora;

  if (diferencia <= 0) {
    inscripcionesAbiertas.value = true;
    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
    return;
  }

  countdown.value = {
    dias: Math.floor(diferencia / (1000 * 60 * 60 * 24)),
    horas: Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
    minutos: Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60)),
    segundos: Math.floor((diferencia % (1000 * 60)) / 1000),
  };
};

onMounted(() => {
  if (props.fechaInicioInscripciones && !props.inscripcionesAbiertas) {
    actualizarCountdown();
    countdownInterval = setInterval(actualizarCountdown, 1000);
  }
});

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
});

const buscandoDNI = ref(false);
const participanteEncontrado = ref(false);
const yaInscrito = ref(false);
const precioCalculado = ref<any>(null);
const codigoCupon = ref('');
const cuponValidado = ref<{
  id: number;
  codigo: string;
  incluye_autobus: boolean;
  descuento: number;
} | null>(null);
const validandoCupon = ref(false);
const errorCupon = ref('');

const form = useForm({
  // Datos participante
  dni: '',
  nombre: '',
  apellidos: '',
  genero: '' as 'masculino' | 'femenino' | '',
  fecha_nacimiento: '',
  telefono: '',
  email: '',
  direccion: '',
  codigo_postal: '',
  poblacion: '',
  provincia: '',

  // Datos inscripción
  edicion_id: props.edicion.id,
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
  acepta_reglamento: false,
  codigo_cupon: '',
});

// Cargar datos iniciales desde props (pasados desde Home)
onMounted(() => {
  if (props.dni) {
    form.dni = props.dni;
  }

  if (props.participante) {
    try {
      const datos = JSON.parse(props.participante) as ParticipanteData;
      form.nombre = datos.nombre || '';
      form.apellidos = datos.apellidos || '';
      form.genero = datos.genero || '';
      // Convertir fecha al formato yyyy-MM-dd
      form.fecha_nacimiento = datos.fecha_nacimiento ? datos.fecha_nacimiento.split('T')[0] : '';
      form.telefono = datos.telefono || '';
      form.email = datos.email || '';
      form.direccion = datos.direccion || '';
      form.codigo_postal = datos.codigo_postal || '';
      form.poblacion = datos.poblacion || '';
      form.provincia = datos.provincia || '';
      participanteEncontrado.value = true;
    } catch (e) {
      console.error('Error parsing participante data:', e);
    }
  }

  // Calcular precio inicial al cargar la página
  calcularPrecio();
});

// Búsqueda automática de DNI con debounce
const buscarParticipanteDebounced = useDebounceFn(() => {
  buscarParticipante();
}, 800);

watch(
  () => form.dni,
  (newDni) => {
    // Si tiene longitud suficiente, buscar
    if (newDni && newDni.length >= 8) {
      buscarParticipanteDebounced();
    }
    // Si borra, resetear estado encontrado
    else if (!newDni || newDni.length < 5) {
      participanteEncontrado.value = false;
      yaInscrito.value = false;
      form.clearErrors(); // Limpiar errores al resetear
    }
  }
);

const buscarParticipante = async () => {
  if (!form.dni || form.dni.length < 3) return;

  console.log('Iniciando búsqueda de participante para DNI:', form.dni);
  buscandoDNI.value = true;
  yaInscrito.value = false;

  try {
    const response = await axios.post('/inscripcion/buscar-participante', {
      dni: form.dni,
      edicion_id: form.edicion_id,
    });

    console.log('Respuesta búsqueda:', response.data);

    // Si ya está inscrito, marcarlo
    if (response.data.ya_inscrito) {
      yaInscrito.value = true;
      participanteEncontrado.value = true; // Mostramos que le hemos encontrado
      form.clearErrors(); // Limpiar errores si ya está inscrito
    }

    if (response.data.encontrado && response.data.datos) {
      const datos = response.data.datos as ParticipanteData;

      // Autocompletar datos
      form.nombre = datos.nombre;
      form.apellidos = datos.apellidos;
      form.genero = datos.genero;
      // Convertir fecha al formato yyyy-MM-dd
      form.fecha_nacimiento = datos.fecha_nacimiento ? datos.fecha_nacimiento.split('T')[0] : '';
      form.telefono = datos.telefono;
      form.email = datos.email;
      form.direccion = datos.direccion;
      form.codigo_postal = datos.codigo_postal;
      form.poblacion = datos.poblacion;
      form.provincia = datos.provincia;

      participanteEncontrado.value = true;
    } else {
      participanteEncontrado.value = false;
    }
  } catch (error) {
    console.error('Error al buscar participante:', error);
  } finally {
    buscandoDNI.value = false;
  }
};

const calcularPrecio = async () => {
  try {
    const response = await axios.post('/inscripcion/calcular-precio', {
      edicion_id: form.edicion_id,
      es_socio_uec: form.es_socio_uec,
      esta_federado: form.esta_federado,
      necesita_autobus: form.necesita_autobus,
      seguro_anulacion: form.seguro_anulacion,
      codigo_cupon: cuponValidado.value?.codigo || '',
    });

    precioCalculado.value = response.data;
  } catch (error) {
    console.error('Error al calcular precio:', error);
  }
};

const validarCupon = async () => {
  if (!codigoCupon.value || codigoCupon.value.length < 3) {
    errorCupon.value = '';
    cuponValidado.value = null;
    form.codigo_cupon = '';
    calcularPrecio();
    return;
  }

  validandoCupon.value = true;
  errorCupon.value = '';

  try {
    const response = await axios.post('/inscripcion/validar-cupon', {
      codigo: codigoCupon.value.toUpperCase(),
      edicion_id: form.edicion_id,
      es_socio_uec: form.es_socio_uec,
      esta_federado: form.esta_federado,
    });

    if (response.data.valido) {
      cuponValidado.value = response.data.cupon;
      form.codigo_cupon = response.data.cupon.codigo;

      // Si el cupón incluye autobús, activarlo automáticamente
      if (response.data.cupon.incluye_autobus && !form.necesita_autobus) {
        form.necesita_autobus = true;
      }

      calcularPrecio();
    } else {
      errorCupon.value = response.data.mensaje || 'Cupón no válido';
      cuponValidado.value = null;
      form.codigo_cupon = '';
    }
  } catch (error: any) {
    errorCupon.value = error.response?.data?.mensaje || 'Error al validar el cupón';
    cuponValidado.value = null;
    form.codigo_cupon = '';
  } finally {
    validandoCupon.value = false;
  }
};

const quitarCupon = () => {
  codigoCupon.value = '';
  cuponValidado.value = null;
  form.codigo_cupon = '';
  errorCupon.value = '';
  calcularPrecio();
};

// Calcular precio cuando cambian opciones
watch(
  () => [form.es_socio_uec, form.esta_federado, form.necesita_autobus, form.seguro_anulacion],
  () => {
    calcularPrecio();
  }
);

// Autocompletar club cuando es socio UEC
watch(
  () => form.es_socio_uec,
  (esSocio) => {
    if (esSocio) {
      form.club = 'UEC Tortosa';
    } else {
      form.club = '';
    }
  }
);

const enviarInscripcion = () => {
  form.post('/inscripcion', {
    preserveScroll: true, // Mantener scroll para que vea el error si está cerca, o dejar false si queremos que suba.
    // Dejamos preserveScroll en false (por defecto) o true?
    // Si hay errores, Inertia por defecto hace scroll a top si no se indica lo contrario? No, Inertia mantiene scroll en validation errors (422).
    // Si el usuario dice que "le devuelve arriba", es que Inertia está haciendo scroll reset.
    // Vamos a forzar scroll al mensaje de error si hay errores.
    onSuccess: () => {
      // Redirigirá automáticamente a la página de confirmación
    },
    onError: (errors) => {
      console.log('Errores de validación:', errors);
      // Forzar scroll al top suavemente para ver el mensaje de error
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
  });
};
</script>

<template>
  <Header></Header>

  <div class="min-h-screen">
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="my-8 text-center">
        <h2 class="mb-2 text-2xl font-bold text-balance text-slate-900">
          Nocturna <span>Fredes-Paüls {{ edicion.anio }}</span>
        </h2>
        <p class="text-md text-slate-900">Formulari d'Inscripció</p>
      </div>

      <!-- Formulario de inscripción -->
      <main class="mb-20 rounded-lg bg-white p-8 shadow-lg">
        <Link href="/">
          <Button variant="ghost" class="mb-4"> ← Tornar </Button>
        </Link>

        <!-- Countdown - inscripciones no abiertas -->
        <div
          v-if="!inscripcionesAbiertas"
          class="flex flex-col items-center justify-center py-12 text-center"
        >
          <div class="mb-6 rounded-full bg-amber-100 p-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-12 w-12 text-amber-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <h2 class="mb-2 text-2xl font-bold text-slate-900">Inscripcions encara no obertes</h2>
          <p class="mb-8 text-slate-600">Les inscripcions s'obriran en:</p>

          <div class="mb-8 flex gap-4">
            <div class="flex flex-col items-center rounded-lg bg-slate-100 px-6 py-4">
              <span class="text-4xl font-bold text-slate-900">{{ countdown.dias }}</span>
              <span class="text-sm text-slate-600">dies</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-slate-100 px-6 py-4">
              <span class="text-4xl font-bold text-slate-900">{{
                String(countdown.horas).padStart(2, '0')
              }}</span>
              <span class="text-sm text-slate-600">hores</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-slate-100 px-6 py-4">
              <span class="text-4xl font-bold text-slate-900">{{
                String(countdown.minutos).padStart(2, '0')
              }}</span>
              <span class="text-sm text-slate-600">minuts</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-slate-100 px-6 py-4">
              <span class="text-4xl font-bold text-slate-900">{{
                String(countdown.segundos).padStart(2, '0')
              }}</span>
              <span class="text-sm text-slate-600">segons</span>
            </div>
          </div>

          <p class="text-sm text-slate-500">
            Prepara't! Quan s'obrin les inscripcions, aquesta pàgina s'actualitzarà automàticament.
          </p>
        </div>

        <!-- Formulario normal -->
        <form v-else @submit.prevent="enviarInscripcion" class="space-y-8">
          <!-- Mensaje de errores general -->
          <div
            v-if="Object.keys(form.errors).length > 0 && !yaInscrito"
            class="rounded-md bg-red-50 p-4"
          >
            <div class="flex">
              <div class="shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Hi ha errors al formulari</h3>
                <div class="mt-2 text-sm text-red-700">
                  <ul class="list-disc space-y-1 pl-5">
                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección DNI -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold">Identificació</h2>
            <div class="flex gap-4">
              <Field class="flex-1">
                <Label for="dni">DNI/NIE *</Label>
                <Input
                  id="dni"
                  v-model="form.dni"
                  @blur="buscarParticipante"
                  type="text"
                  required
                  placeholder="12345678X"
                  :class="{ 'border-red-500': form.errors.dni }"
                />
                <p v-if="form.errors.dni" class="mt-1 text-sm text-red-500">
                  {{ form.errors.dni }}
                </p>
              </Field>
              <div class="flex items-end">
                <Button
                  type="button"
                  @click="buscarParticipante"
                  :disabled="buscandoDNI"
                  variant="outline"
                >
                  {{ buscandoDNI ? 'Cercant...' : 'Cercar' }}
                </Button>
              </div>
            </div>
            <p v-if="participanteEncontrado && !yaInscrito" class="mt-2 text-sm text-green-600">
              ✓ Participant trobat. Verifica que les teves dades siguin correctes.
            </p>

            <div v-if="yaInscrito" class="mt-4 rounded-md border border-green-200 bg-green-50 p-4">
              <div class="flex">
                <div class="shrink-0">
                  <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path
                      fill-rule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-green-800">
                    Ja estàs inscrit en aquesta edició
                  </h3>
                  <div class="mt-2 text-sm text-green-700">
                    <p class="mb-4">El DNI indicat ja té una inscripció activa.</p>
                    <Link href="/inscripcion/consulta">
                      <Button variant="default" size="lg"> Comprovar la meva inscripció </Button>
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!yaInscrito" class="space-y-8">
            <!-- Datos Personales -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Dades personals
              </FieldLegend>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <Label for="nombre">Nom *</Label>
                  <Input id="nombre" v-model="form.nombre" type="text" required />
                </Field>

                <Field>
                  <Label for="apellidos">Cognoms *</Label>
                  <Input id="apellidos" v-model="form.apellidos" type="text" required />
                </Field>

                <Field>
                  <Label for="genero">Gènere *</Label>
                  <NativeSelect id="genero" v-model="form.genero" required class="w-full">
                    <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                    <NativeSelectOption value="masculino">Masculí</NativeSelectOption>
                    <NativeSelectOption value="femenino">Femení</NativeSelectOption>
                  </NativeSelect>
                </Field>

                <Field>
                  <Label for="fecha_nacimiento">Data de Naixement *</Label>
                  <Input
                    id="fecha_nacimiento"
                    v-model="form.fecha_nacimiento"
                    type="date"
                    required
                  />
                </Field>

                <Field>
                  <Label for="telefono">Telèfon *</Label>
                  <Input id="telefono" v-model="form.telefono" type="tel" required />
                </Field>

                <Field>
                  <Label for="email">Correu electrònic *</Label>
                  <Input id="email" v-model="form.email" type="email" required />
                </Field>
              </div>
            </FieldSet>

            <!-- Dirección -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Adreça
              </FieldLegend>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field class="md:col-span-2">
                  <Label for="direccion">Adreça *</Label>
                  <Input id="direccion" v-model="form.direccion" type="text" required />
                </Field>
              </div>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field>
                  <Label for="codigo_postal">Codi Postal *</Label>
                  <Input id="codigo_postal" v-model="form.codigo_postal" type="text" required />
                </Field>

                <Field>
                  <Label for="poblacion">Població *</Label>
                  <Input id="poblacion" v-model="form.poblacion" type="text" required />
                </Field>

                <Field>
                  <Label for="provincia">Província *</Label>
                  <Input id="provincia" v-model="form.provincia" type="text" required />
                </Field>
              </div>
            </FieldSet>

            <!-- Información Deportiva -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Informació esportiva
              </FieldLegend>
              <div class="space-y-4">
                <Field>
                  <Label>Ets soci de la UEC Tortosa? *</Label>
                  <RadioGroup
                    :model-value="form.es_socio_uec ? 'si' : 'no'"
                    class="mt-2 flex space-x-6"
                    @update:model-value="(val: string) => (form.es_socio_uec = val === 'si')"
                  >
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="socio-no" value="no" />
                      <Label for="socio-no" class="cursor-pointer font-normal">No</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="socio-si" value="si" />
                      <Label for="socio-si" class="cursor-pointer font-normal">Sí</Label>
                    </div>
                  </RadioGroup>
                </Field>

                <Field class="">
                  <Label for="club">Club</Label>
                  <Input id="club" v-model="form.club" type="text" class="w-lg!" />
                </Field>

                <Separator class="my-4" />

                <Field>
                  <Label>Estàs federat? *</Label>
                  <RadioGroup
                    :model-value="form.esta_federado ? 'si' : 'no'"
                    class="mt-2 flex space-x-6"
                    @update:model-value="(val: string) => (form.esta_federado = val === 'si')"
                  >
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="federado-no" value="no" />
                      <Label for="federado-no" class="cursor-pointer font-normal">No</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="federado-si" value="si" />
                      <Label for="federado-si" class="cursor-pointer font-normal">Sí</Label>
                    </div>
                  </RadioGroup>
                </Field>

                <Field v-if="form.esta_federado">
                  <Label for="numero_licencia">Número de Llicència *</Label>
                  <Input
                    id="numero_licencia"
                    v-model="form.numero_licencia"
                    type="text"
                    class="w-lg!"
                    :required="form.esta_federado"
                  />
                </Field>
              </div>
            </FieldSet>
            <!-- Servicios Adicionales -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Serveis addicionals
              </FieldLegend>
              <div class="space-y-4">
                <div class="rounded-md border border-slate-200 p-4">
                  <Field orientation="horizontal">
                    <Checkbox id="autobus" v-model="form.necesita_autobus" />
                    <div>
                      <Label for="autobus">Servei d'autobús cap a Fredes</Label>
                      <p class="text-sm text-slate-500">
                        {{ precioCalculado?.precio_autobus || 12 }}€
                      </p>
                    </div>
                  </Field>

                  <div v-if="form.necesita_autobus" class="mt-4 ml-6">
                    <Field>
                      <Label class="mb-0">Parada d'Autobús *</Label>
                      <p class="text-xs text-slate-600">
                        Podeu canviar-la fins a 2 dies abans de l'esdeveniment.
                      </p>
                      <RadioGroup
                        v-model="form.parada_autobus"
                        :required="form.necesita_autobus"
                        class="flex flex-col space-y-3"
                      >
                        <div
                          v-for="parada in PARADAS"
                          :key="parada.value"
                          class="flex items-start space-x-2"
                        >
                          <RadioGroupItem
                            :id="`parada-${parada.value}`"
                            :value="parada.value"
                            class="mt-1"
                          />
                          <div class="flex flex-col">
                            <Label
                              :for="`parada-${parada.value}`"
                              class="cursor-pointer font-normal"
                              >{{ parada.label }}</Label
                            >
                            <p class="text-sm text-slate-500">{{ parada.descripcion }}</p>
                          </div>
                        </div>
                      </RadioGroup>
                    </Field>
                  </div>
                </div>

                <div
                  class="flex items-start justify-between rounded-md border border-slate-200 p-4"
                >
                  <Field orientation="horizontal">
                    <Checkbox id="seguro" v-model="form.seguro_anulacion" />
                    <div>
                      <Label for="seguro">Assegurança d'anul·lació</Label>
                      <p class="text-sm text-slate-500">9€</p>
                    </div>
                  </Field>
                </div>
              </div>
            </FieldSet>

            <!-- Tallas de Camisetas -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Talles de samarretes
              </FieldLegend>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Field>
                  <Label for="talla_caro">Talla Samarreta Caro *</Label>
                  <NativeSelect
                    id="talla_caro"
                    v-model="form.talla_camiseta_caro"
                    required
                    class="w-full"
                    :class="{ 'border-red-500': form.errors.talla_camiseta_caro }"
                  >
                    <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                    <NativeSelectOption value="XS">XS</NativeSelectOption>
                    <NativeSelectOption value="S">S</NativeSelectOption>
                    <NativeSelectOption value="M">M</NativeSelectOption>
                    <NativeSelectOption value="L">L</NativeSelectOption>
                    <NativeSelectOption value="XL">XL</NativeSelectOption>
                    <NativeSelectOption value="XXL">XXL</NativeSelectOption>
                  </NativeSelect>
                  <FieldDescription> Tallatge normal </FieldDescription>
                  <p v-if="form.errors.talla_camiseta_caro" class="mt-1 text-sm text-red-500">
                    {{ form.errors.talla_camiseta_caro }}
                  </p>
                </Field>

                <Field>
                  <Label for="talla_pauls">Talla Samarreta Paüls *</Label>
                  <NativeSelect
                    id="talla_pauls"
                    v-model="form.talla_camiseta_pauls"
                    required
                    class="w-full"
                    :class="{ 'border-red-500': form.errors.talla_camiseta_pauls }"
                  >
                    <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                    <NativeSelectOption value="XS">XS</NativeSelectOption>
                    <NativeSelectOption value="S">S</NativeSelectOption>
                    <NativeSelectOption value="M">M</NativeSelectOption>
                    <NativeSelectOption value="L">L</NativeSelectOption>
                    <NativeSelectOption value="XL">XL</NativeSelectOption>
                    <NativeSelectOption value="XXL">XXL</NativeSelectOption>
                  </NativeSelect>
                  <FieldDescription> Recomanem una talla més </FieldDescription>
                  <p v-if="form.errors.talla_camiseta_pauls" class="mt-1 text-sm text-red-500">
                    {{ form.errors.talla_camiseta_pauls }}
                  </p>
                </Field>
              </div>
            </FieldSet>

            <!-- Información adicional -->
            <FieldSet>
              <FieldLegend
                variant="legend"
                class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
              >
                Informació addicional
              </FieldLegend>
              <div class="space-y-4">
                <Field>
                  <Label>Ets celíac? *</Label>
                  <RadioGroup v-model="form.es_celiaco" class="mt-2 flex space-x-6">
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="celiaco-no" value="no" />
                      <Label for="celiaco-no" class="cursor-pointer font-normal">No</Label>
                    </div>
                    <div class="flex items-center space-x-2">
                      <RadioGroupItem id="celiaco-si" value="si" />
                      <Label for="celiaco-si" class="cursor-pointer font-normal">Sí</Label>
                    </div>
                  </RadioGroup>
                  <p class="mt-1 text-sm text-slate-500">
                    Indica si necessites menú sense gluten a l'avituallament
                  </p>
                </Field>
              </div>
            </FieldSet>

            <!-- Política de devolución y protección de datos -->
            <div class="">
              <h3 class="mb-3 font-semibold text-slate-900">
                Política de devolució i protecció de dades
              </h3>
              <div
                class="h-48 overflow-y-auto rounded border border-slate-300 bg-slate-50 p-4 text-xs text-slate-700"
              >
                <h4 class="mb-1 font-bold text-slate-800">PROTECCIÓ DE DADES:</h4>
                <p class="mb-1">
                  <strong>Responsable:</strong> Unió Excursionista de Catalunya de Tortosa (UEC
                  Tortosa).
                </p>
                <p class="mb-1">
                  <strong>Finalitat:</strong> Gestió de la inscripció a la cursa Nocturna
                  Fredes-Paüls {{ edicion.anio }}, comunicació d'informació relacionada amb
                  l'esdeveniment i publicació de resultats i classificacions.
                </p>
                <p class="mb-1">
                  <strong>Legitimació:</strong> Consentiment de l'interessat mitjançant l'acceptació
                  d'aquest formulari.
                </p>
                <p class="mb-1">
                  <strong>Destinataris:</strong> Les dades no seran cedides a tercers excepte per
                  obligació legal o per a la gestió de l'assegurança esportiva i serveis necessaris
                  per a l'organització de l'esdeveniment.
                </p>
                <p class="mb-1">
                  <strong>Drets:</strong> Pots exercir els teus drets d'accés, rectificació,
                  supressió, portabilitat, limitació i oposició enviant un correu electrònic a
                  activitats@uectortosa.cat
                </p>
                <p class="mb-1">
                  <strong>Imatges:</strong> Amb la inscripció, autoritzes l'organització a captar i
                  publicar imatges de l'esdeveniment amb finalitats promocionals i informatives.
                </p>
                <h4 class="mt-3 mb-2 font-bold text-slate-800">POLÍTICA DE DEVOLUCIÓ I CANVIS:</h4>
                <p class="mb-1">
                  <strong>ANUL·LACIÓ DE LA INSCRIPCIÓ:</strong> Fins el 15 d'abril de 2026 es
                  retornarà el 100% del total. A partir del 16 d'abril de 2026 no es retornarà cap
                  inscripció.
                </p>
                <p class="mb-1">
                  <strong>ASSEGURANÇA D'ANUL·LACIÓ:</strong> Si contractes l'assegurança
                  d'anul·lació (9€), podràs cancel·lar la teva inscripció des del 16 d'abril fins al
                  10 de maig (sense incloure l'assegurança).
                </p>
              </div>
            </div>

            <!-- Aceptación de reglamento -->
            <div class="rounded-lg border border-slate-200 p-4">
              <Field orientation="horizontal" class="items-start">
                <Checkbox
                  id="acepta_reglamento"
                  v-model="form.acepta_reglamento"
                  required
                  class="mt-1.5"
                  :class="{ 'border-red-500': form.errors.acepta_reglamento }"
                />
                <div>
                  <Label
                    for="acepta_reglamento"
                    class="cursor-pointer text-sm leading-relaxed text-balance"
                  >
                    Accepto el
                    <a
                      href="https://nocturna.uectortosa.cat/reglament-i-recomanacions"
                      target="_blank"
                      class="text-gray-600 underline hover:text-gray-800"
                    >
                      reglament de la cursa i el material obligatori</a
                    >
                    i el
                    <a
                      href="https://nocturna.uectortosa.cat/responsabilitats"
                      target="_blank"
                      class="text-gray-600 underline hover:text-gray-800"
                    >
                      plec de descàrrec de responsabilitats i aptitud física</a
                    >
                    per participar a la Nocturna Fredes-Paüls {{ edicion.anio }}. *
                  </Label>
                  <p v-if="form.errors.acepta_reglamento" class="mt-1 text-sm text-red-500">
                    {{ form.errors.acepta_reglamento }}
                  </p>
                </div>
              </Field>
            </div>

            <!-- Campo de Cupón de Descuento -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
              <h3 class="mb-3 text-sm font-semibold text-slate-900">Tens un cupó de descompte?</h3>

              <div v-if="!cuponValidado" class="flex gap-2">
                <Input
                  v-model="codigoCupon"
                  placeholder="Introdueix el codi"
                  class="flex-1 uppercase"
                  :class="{ 'border-red-500': errorCupon }"
                  @keyup.enter="validarCupon"
                />
                <Button
                  type="button"
                  variant="outline"
                  :disabled="validandoCupon || !codigoCupon"
                  @click="validarCupon"
                >
                  {{ validandoCupon ? 'Validant...' : 'Aplicar' }}
                </Button>
              </div>

              <div v-else class="flex items-center justify-between rounded-lg bg-green-50 p-3">
                <div class="flex items-center gap-2">
                  <svg
                    class="h-5 w-5 text-green-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  <div>
                    <span class="font-mono font-semibold text-green-700">{{
                      cuponValidado.codigo
                    }}</span>
                    <span class="ml-2 text-sm text-green-600">aplicat!</span>
                  </div>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  class="text-red-600 hover:text-red-700"
                  @click="quitarCupon"
                >
                  Treure
                </Button>
              </div>

              <p v-if="errorCupon" class="mt-2 text-sm text-red-500">{{ errorCupon }}</p>

              <p
                v-if="cuponValidado && precioCalculado?.descuento_cupon"
                class="mt-2 text-xs text-green-600"
              >
                Descompte de {{ precioCalculado.descuento_cupon }}€ (llicència federativa<span
                  v-if="cuponValidado.incluye_autobus"
                >
                  + autobús</span
                >)
              </p>
            </div>

            <!-- Resumen de Precio -->
            <div v-if="precioCalculado" class="rounded-lg bg-slate-50 p-6">
              <h3 class="text-md mb-4 font-semibold text-slate-900">Resum de la teva inscripció</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-700">
                  <span>
                    Inscripció ({{ precioCalculado.nombre_tarifa
                    }}<span v-if="!form.esta_federado">, inclou federativa</span>):
                  </span>
                  <span>{{ precioCalculado.tarifa_base }}€</span>
                </div>
                <div
                  v-if="precioCalculado.descuento_cupon > 0"
                  class="flex justify-between text-green-600"
                >
                  <span>Descompte cupó ({{ cuponValidado?.codigo }}):</span>
                  <span>-{{ precioCalculado.descuento_cupon }}€</span>
                </div>
                <div v-if="form.necesita_autobus" class="flex justify-between text-slate-700">
                  <span>Autobús ({{ getParadaShortLabel(form.parada_autobus) }}):</span>
                  <span>{{ precioCalculado.precio_autobus }}€</span>
                </div>
                <div v-if="form.seguro_anulacion" class="flex justify-between text-slate-700">
                  <span>Assegurança d'anul·lació:</span>
                  <span>{{ precioCalculado.precio_seguro }}€</span>
                </div>
                <div class="mt-2 border-t border-slate-300 pt-2">
                  <div class="text-md flex justify-between font-bold text-slate-900">
                    <span>Total</span>
                    <span>{{ precioCalculado.precio_total }}€</span>
                  </div>
                </div>
                <p v-if="precioCalculado.es_tarifa_tardia" class="text-sm text-amber-600">
                  * S'ha aplicat tarifa tardana
                </p>
              </div>
            </div>

            <!-- Botón Submit -->
            <div class="flex flex-col pt-6 text-center">
              <Button
                type="submit"
                :disabled="form.processing || yaInscrito"
                size="xl"
                class="w-full bg-red-500 px-12 py-8 text-lg"
                :class="{ 'cursor-not-allowed opacity-50': yaInscrito }"
              >
                {{
                  form.processing
                    ? 'Processant...'
                    : yaInscrito
                      ? 'Ja inscrit'
                      : 'Confirmar Inscripció'
                }}
              </Button>

              <div class="mt-4 text-xs text-balance md:text-sm">
                Seràs redirigit a la passarel·la de pagament segura Redsys del Banc Sabadell
              </div>
              <div class="mt-4 flex justify-center gap-4">
                <img src="@/assets/logos/logo-redsys-simple.svg" alt="Logo Redsys" class="h-6.5" />
                <img src="@/assets/logos/logo-sabadell.svg" alt="Logo Sabadell" class="h-5" />
              </div>
            </div>
          </div>
        </form>
      </main>
      <Footer />
    </div>
  </div>
</template>
