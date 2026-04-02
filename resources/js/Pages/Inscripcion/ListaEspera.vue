<script setup lang="ts">
import Footer from '@/components/ui-layout/footer.vue';
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { PARADAS, getParadaShortLabel } from '@/constants/paradas';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

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
  club?: string;
  numero_licencia?: string;
}

const props = defineProps<{
  edicion: Edicion;
}>();

const buscandoDNI = ref(false);
const participanteEncontrado = ref(false);
const yaEnListaEspera = ref(false);
const precioCalculado = ref<any>(null);

const form = useForm({
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
});

onMounted(() => {
  calcularPrecio();
});

const buscarParticipanteDebounced = useDebounceFn(() => {
  buscarParticipante();
}, 800);

watch(
  () => form.dni,
  (newDni) => {
    if (newDni && newDni.length >= 8) {
      buscarParticipanteDebounced();
    } else if (!newDni || newDni.length < 5) {
      participanteEncontrado.value = false;
      yaEnListaEspera.value = false;
      form.clearErrors();
    }
  }
);

const buscarParticipante = async () => {
  if (!form.dni || form.dni.length < 3) return;

  buscandoDNI.value = true;
  yaEnListaEspera.value = false;

  try {
    const response = await axios.post('/inscripcio/buscar-participante', {
      dni: form.dni,
      edicion_id: form.edicion_id,
    });

    if (response.data.ya_inscrito) {
      yaEnListaEspera.value = true;
      participanteEncontrado.value = true;
      form.clearErrors();
    }

    if (response.data.encontrado && response.data.datos) {
      const datos = response.data.datos as ParticipanteData;
      form.nombre = datos.nombre;
      form.apellidos = datos.apellidos;
      form.genero = datos.genero;
      form.fecha_nacimiento = datos.fecha_nacimiento ? datos.fecha_nacimiento.split('T')[0] : '';
      form.telefono = datos.telefono;
      form.email = datos.email;
      form.direccion = datos.direccion;
      form.codigo_postal = datos.codigo_postal;
      form.poblacion = datos.poblacion;
      form.provincia = datos.provincia;

      if (datos.club) {
        form.club = datos.club;
        form.es_socio_uec = datos.club === 'UEC Tortosa';
      }
      if (datos.numero_licencia) {
        form.numero_licencia = datos.numero_licencia;
        form.esta_federado = true;
      }

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
    const response = await axios.post('/inscripcio/calcular-precio', {
      edicion_id: form.edicion_id,
      es_socio_uec: form.es_socio_uec,
      esta_federado: form.esta_federado,
      necesita_autobus: form.necesita_autobus,
      seguro_anulacion: form.seguro_anulacion,
      codigo_cupon: '',
    });

    precioCalculado.value = response.data;
  } catch (error) {
    console.error('Error al calcular precio:', error);
  }
};

watch(
  () => [form.es_socio_uec, form.esta_federado, form.necesita_autobus, form.seguro_anulacion],
  () => {
    calcularPrecio();
  }
);

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

const esMenorDeEdad = computed(() => {
  if (!form.fecha_nacimiento) return false;

  const fechaNacimiento = new Date(form.fecha_nacimiento);
  const fechaEvento = new Date(props.edicion.fecha_evento);

  let edad = fechaEvento.getFullYear() - fechaNacimiento.getFullYear();
  const mes = fechaEvento.getMonth() - fechaNacimiento.getMonth();

  if (mes < 0 || (mes === 0 && fechaEvento.getDate() < fechaNacimiento.getDate())) {
    edad--;
  }

  return edad < 18;
});

const enviarInscripcion = () => {
  form.post('/llista-espera', {
    preserveScroll: true,
    onError: () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
  });
};
</script>

<template>
  <Head title="Llista d'Espera" />
  <Header />

  <div class="min-h-screen">
    <div class="mx-auto max-w-4xl">
      <div class="my-8 text-center">
        <h2 class="font-expanded mb-2 text-2xl font-bold text-balance text-slate-900">
          Nocturna <span>Fredes-Paüls {{ edicion.anio }}</span>
        </h2>
        <p class="text-md text-slate-900">Llista d'Espera</p>
      </div>

      <Card class="mx-4 mb-10">
        <CardContent class="p-4 md:p-8">
          <Link href="/">
            <Button variant="ghost" size="sm" class="mb-6"> ← Tornar </Button>
          </Link>

          <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-balance text-amber-800">
              <strong>Llista d'espera:</strong> Completa el formulari per apuntar-te a la llista
              d'espera. L'organització es posarà en contacte amb tu si hi ha plaça disponible.
            </p>
          </div>

          <form @submit.prevent="enviarInscripcion" class="space-y-8">
            <!-- Mensaje de errores general -->
            <div
              v-if="Object.keys(form.errors).length > 0 && !yaEnListaEspera"
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
              <p
                v-if="participanteEncontrado && !yaEnListaEspera"
                class="mt-2 text-sm text-green-600"
              >
                ✓ Participant trobat.
                <strong>Verifica que les teves dades siguin correctes.</strong>
              </p>

              <div
                v-if="yaEnListaEspera"
                class="mt-4 rounded-md border border-green-200 bg-green-50 p-4"
              >
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
                      Ja estàs inscrit o a la llista d'espera
                    </h3>
                    <div class="mt-2 text-sm text-green-700">
                      <p>El DNI indicat ja té una inscripció o està a la llista d'espera.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="!yaEnListaEspera" class="space-y-8">
              <!-- Datos Personales -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Dades personals
                </h3>
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

                  <div
                    v-if="esMenorDeEdad"
                    class="rounded-md border border-dashed border-yellow-300 bg-yellow-50 p-4 md:col-span-2"
                  >
                    <p class="text-sm text-yellow-800">
                      Si el dia de la Nocturna Fredes - Paüls (30/05/2026) encara no hauràs complert
                      18 anys, hauràs d'enviar
                      <a
                        href="https://nocturna.uectortosa.cat/wp-content/uploads/sites/6/2026/02/autoritzacio-menors-2025.pdf"
                        class="font-medium text-yellow-800 underline"
                        >aquest document</a
                      >
                      degudament signat per un tutor legal a
                      <a
                        href="mailto:activitats@uectortosa.cat"
                        class="font-medium text-yellow-800 underline"
                      >
                        activitats@uectortosa.cat</a
                      >
                    </p>
                  </div>

                  <Field>
                    <Label for="telefono">Telèfon *</Label>
                    <Input id="telefono" v-model="form.telefono" type="tel" required />
                  </Field>

                  <Field>
                    <Label for="email">Correu electrònic *</Label>
                    <Input id="email" v-model="form.email" type="email" required />
                  </Field>
                </div>
              </div>

              <!-- Dirección -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Adreça
                </h3>
                <div class="space-y-4">
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
                </div>
              </div>

              <!-- Información Deportiva -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Informació esportiva
                </h3>
                <div class="space-y-4">
                  <Field>
                    <Label>Ets soci de la UEC Tortosa? *</Label>
                    <RadioGroup
                      :model-value="form.es_socio_uec ? 'si' : 'no'"
                      class="mt-2 flex space-x-6"
                      @update:model-value="(val) => (form.es_socio_uec = String(val) === 'si')"
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

                  <Field>
                    <Label for="club">Club</Label>
                    <Input id="club" v-model="form.club" type="text" class="w-lg!" />
                  </Field>

                  <Separator class="my-4" />

                  <Field>
                    <Label>Estàs federat? *</Label>
                    <RadioGroup
                      :model-value="form.esta_federado ? 'si' : 'no'"
                      class="mt-2 flex space-x-6"
                      @update:model-value="(val) => (form.esta_federado = String(val) === 'si')"
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

                  <Field>
                    <Label for="numero_licencia">
                      Número de Llicència <span v-if="form.esta_federado">*</span>
                    </Label>
                    <Input
                      id="numero_licencia"
                      v-model="form.numero_licencia"
                      type="text"
                      class="w-sm!"
                      :disabled="!form.esta_federado"
                      :required="form.esta_federado"
                      :placeholder="form.esta_federado ? '' : 'Només per federats'"
                    />
                  </Field>
                </div>
              </div>

              <!-- Servicios Adicionales -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Serveis addicionals
                </h3>
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
                        <p class="text-xs">
                          Vàlida per anul·lar fins abans del 10 de maig de 2026.
                        </p>
                        <p class="text-sm text-slate-500">9€</p>
                      </div>
                    </Field>
                  </div>
                </div>
              </div>

              <!-- Tallas de Camisetas -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Talla de samarretes
                </h3>
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
                    <Label for="talla_pauls">
                      Talla Samarreta Paüls
                      <span class="font-semibold text-red-800">Jo tota! per a Finishers</span> *
                    </Label>
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
              </div>

              <!-- Información adicional -->
              <div>
                <h3 class="mb-4 border-b border-gray-200 pb-2 text-lg font-semibold text-red-700">
                  Informació addicional
                </h3>
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
              </div>

              <Separator />

              <!-- Política de devolución y protección de datos -->
              <div>
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
                    <strong>Legitimació:</strong> Consentiment de l'interessat mitjançant
                    l'acceptació d'aquest formulari.
                  </p>
                  <p class="mb-1">
                    <strong>Destinataris:</strong> Les dades no seran cedides a tercers excepte per
                    obligació legal o per a la gestió de l'assegurança esportiva i serveis
                    necessaris per a l'organització de l'esdeveniment.
                  </p>
                  <p class="mb-1">
                    <strong>Drets:</strong> Pots exercir els teus drets d'accés, rectificació,
                    supressió, portabilitat, limitació i oposició enviant un correu electrònic a
                    activitats@uectortosa.cat
                  </p>
                  <p class="mb-1">
                    <strong>Imatges:</strong> Amb la inscripció, autoritzes l'organització a captar
                    i publicar imatges de l'esdeveniment amb finalitats promocionals i informatives.
                  </p>
                  <h4 class="mt-3 mb-2 font-bold text-slate-800">
                    POLÍTICA DE DEVOLUCIÓ I CANVIS:
                  </h4>
                  <p class="mb-1">
                    <strong>ANUL·LACIÓ DE LA INSCRIPCIÓ:</strong> Fins el 15 d'abril de 2026 es
                    retornarà el 100% del total. A partir del 16 d'abril de 2026 no es retornarà cap
                    inscripció.
                  </p>
                  <p class="mb-1">
                    <strong>ASSEGURANÇA D'ANUL·LACIÓ:</strong> Si contractes l'assegurança
                    d'anul·lació (9€), podràs cancel·lar la teva inscripció des del 16 d'abril fins
                    al 10 de maig (sense incloure l'assegurança).
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

              <!-- Resumen de Precio -->
              <div
                v-if="precioCalculado"
                class="rounded-lg border border-dashed border-slate-200 bg-slate-50/50 p-6"
              >
                <h3 class="text-md mb-4 font-semibold text-slate-900">
                  Resum de la teva inscripció
                </h3>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between text-slate-700">
                    <span>
                      Inscripció ({{ precioCalculado.nombre_tarifa
                      }}<span v-if="!form.esta_federado"
                        >, inclou federativa {{ precioCalculado.precio_licencia }}€</span
                      >):
                    </span>
                    <span>{{ precioCalculado.tarifa_base }}€</span>
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
                  :disabled="form.processing || yaEnListaEspera"
                  size="xl"
                  class="w-full bg-amber-600 px-12 py-8 text-lg hover:bg-amber-700"
                  :class="{ 'cursor-not-allowed opacity-50': yaEnListaEspera }"
                >
                  {{
                    form.processing
                      ? 'Processant...'
                      : yaEnListaEspera
                        ? 'Ja a la llista'
                        : "Apuntar-me a la llista d'espera"
                  }}
                </Button>

                <div class="mt-4 text-xs text-balance text-slate-500 md:text-sm">
                  No es realitzarà cap cobrament. L'organització es posarà en contacte amb tu si hi
                  ha plaça disponible.
                </div>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
      <Footer />
    </div>
  </div>
</template>
