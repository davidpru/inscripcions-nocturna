<script setup lang="ts">
import Header from '@/components/ui-layout/header.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Separator } from '@/components/ui/separator';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

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
  dni?: string;
  participante?: string;
}>();

const buscandoDNI = ref(false);
const participanteEncontrado = ref(false);
const precioCalculado = ref<any>(null);

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
      form.fecha_nacimiento = datos.fecha_nacimiento || '';
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
});

const buscarParticipante = async () => {
  if (!form.dni || form.dni.length < 3) return;

  buscandoDNI.value = true;

  try {
    const response = await axios.post('/inscripcion/buscar-participante', {
      dni: form.dni,
    });

    if (response.data.encontrado && response.data.datos) {
      const datos = response.data.datos as ParticipanteData;

      // Autocompletar datos
      form.nombre = datos.nombre;
      form.apellidos = datos.apellidos;
      form.genero = datos.genero;
      form.fecha_nacimiento = datos.fecha_nacimiento;
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
    });

    precioCalculado.value = response.data;
  } catch (error) {
    console.error('Error al calcular precio:', error);
  }
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
    onSuccess: () => {
      // Redirigirá automáticamente a la página de confirmación
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
        <p class="text-md text-slate-900">Formulario de Inscripción</p>
      </div>

      <!-- Formulario de inscripción -->
      <div class="rounded-lg bg-white p-8 shadow-lg">
        <Link href="/">
          <Button variant="ghost" class="mb-4"> ← Volver </Button>
        </Link>
        <form @submit.prevent="enviarInscripcion" class="space-y-8">
          <!-- Sección DNI -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold">Identificación</h2>
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
                />
              </Field>
              <div class="flex items-end">
                <Button
                  type="button"
                  @click="buscarParticipante"
                  :disabled="buscandoDNI"
                  variant="outline"
                >
                  {{ buscandoDNI ? 'Buscando...' : 'Buscar' }}
                </Button>
              </div>
            </div>
            <p v-if="participanteEncontrado" class="mt-2 text-sm text-green-600">
              ✓ Participante encontrado. Verifica que tus datos sean correctos.
            </p>
          </div>

          <!-- Datos Personales -->
          <FieldSet>
            <FieldLegend
              variant="legend"
              class="mb-6 w-full border-b border-gray-300 pb-2 text-lg! font-semibold text-red-700"
            >
              Datos personales
            </FieldLegend>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Field>
                <Label for="nombre">Nombre *</Label>
                <Input id="nombre" v-model="form.nombre" type="text" required />
              </Field>

              <Field>
                <Label for="apellidos">Apellidos *</Label>
                <Input id="apellidos" v-model="form.apellidos" type="text" required />
              </Field>

              <Field>
                <Label for="genero">Género *</Label>
                <NativeSelect id="genero" v-model="form.genero" required class="w-full">
                  <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                  <NativeSelectOption value="masculino">Masculino</NativeSelectOption>
                  <NativeSelectOption value="femenino">Femenino</NativeSelectOption>
                </NativeSelect>
              </Field>

              <Field>
                <Label for="fecha_nacimiento">Fecha de Nacimiento *</Label>
                <Input id="fecha_nacimiento" v-model="form.fecha_nacimiento" type="date" required />
              </Field>

              <Field>
                <Label for="telefono">Teléfono *</Label>
                <Input id="telefono" v-model="form.telefono" type="tel" required />
              </Field>

              <Field>
                <Label for="email">Email *</Label>
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
              Dirección
            </FieldLegend>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Field class="md:col-span-2">
                <Label for="direccion">Dirección *</Label>
                <Input id="direccion" v-model="form.direccion" type="text" required />
              </Field>

              <Field>
                <Label for="codigo_postal">Código Postal *</Label>
                <Input id="codigo_postal" v-model="form.codigo_postal" type="text" required />
              </Field>

              <Field>
                <Label for="poblacion">Población *</Label>
                <Input id="poblacion" v-model="form.poblacion" type="text" required />
              </Field>

              <Field class="md:col-span-2">
                <Label for="provincia">Provincia *</Label>
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
              Información deportiva
            </FieldLegend>
            <div class="space-y-4">
              <Field orientation="horizontal">
                <Checkbox id="socio_uec" v-model="form.es_socio_uec" />
                <Label for="socio_uec">¿Eres socio de la UEC Tortosa?</Label>
              </Field>

              <Field class="">
                <Label for="club">Club</Label>
                <Input id="club" v-model="form.club" type="text" class="w-lg!" />
              </Field>

              <Separator class="my-4" />

              <Field orientation="horizontal">
                <Checkbox id="federado" v-model="form.esta_federado" />
                <Label for="federado">¿Estás federado?</Label>
              </Field>

              <Field v-if="form.esta_federado">
                <Label for="numero_licencia">Número de Licencia *</Label>
                <Input
                  id="numero_licencia"
                  v-model="form.numero_licencia"
                  type="text"
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
              Servicios adicionales
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
                    <Label>Parada de Autobús *</Label>
                    <RadioGroup
                      v-model="form.parada_autobus"
                      :required="form.necesita_autobus"
                      class="flex flex-col space-y-3"
                    >
                      <div class="flex items-start space-x-2">
                        <RadioGroupItem id="parada-tortosa" value="tortosa" class="mt-1" />
                        <div class="flex flex-col">
                          <Label for="parada-tortosa" class="cursor-pointer font-normal"
                            >Salida desde Tortosa</Label
                          >
                          <p class="text-sm text-slate-500">Rotonda Quatre Camins</p>
                        </div>
                      </div>
                      <div class="flex items-start space-x-2">
                        <RadioGroupItem id="parada-pauls" value="pauls" class="mt-1" />
                        <div class="flex flex-col">
                          <Label for="parada-pauls" class="cursor-pointer font-normal"
                            >Salida desde Paüls</Label
                          >
                          <p class="text-sm text-slate-500">Bàscula municipal, entrada de Paüls</p>
                        </div>
                      </div>
                    </RadioGroup>
                  </Field>
                </div>
              </div>

              <div class="flex items-start justify-between rounded-md border border-slate-200 p-4">
                <Field orientation="horizontal">
                  <Checkbox id="seguro" v-model="form.seguro_anulacion" />
                  <div>
                    <Label for="seguro">Seguro de Anulación</Label>
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
              Tallas de Camisetas
            </FieldLegend>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <Field>
                <Label for="talla_caro">Talla Camiseta Caro *</Label>
                <NativeSelect
                  id="talla_caro"
                  v-model="form.talla_camiseta_caro"
                  required
                  class="w-full"
                >
                  <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                  <NativeSelectOption value="XS">XS</NativeSelectOption>
                  <NativeSelectOption value="S">S</NativeSelectOption>
                  <NativeSelectOption value="M">M</NativeSelectOption>
                  <NativeSelectOption value="L">L</NativeSelectOption>
                  <NativeSelectOption value="XL">XL</NativeSelectOption>
                  <NativeSelectOption value="XXL">XXL</NativeSelectOption>
                </NativeSelect>
              </Field>

              <Field>
                <Label for="talla_pauls">Talla Camiseta Paüls *</Label>
                <NativeSelect
                  id="talla_pauls"
                  v-model="form.talla_camiseta_pauls"
                  required
                  class="w-full"
                >
                  <NativeSelectOption value="" disabled>Seleccionar...</NativeSelectOption>
                  <NativeSelectOption value="XS">XS</NativeSelectOption>
                  <NativeSelectOption value="S">S</NativeSelectOption>
                  <NativeSelectOption value="M">M</NativeSelectOption>
                  <NativeSelectOption value="L">L</NativeSelectOption>
                  <NativeSelectOption value="XL">XL</NativeSelectOption>
                  <NativeSelectOption value="XXL">XXL</NativeSelectOption>
                </NativeSelect>
              </Field>
            </div>
          </FieldSet>

          <!-- Resumen de Precio -->
          <div v-if="precioCalculado" class="rounded-lg bg-slate-50 p-6">
            <h3 class="mb-4 text-xl font-semibold text-slate-900">Resumen del Precio</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-slate-700">
                <span>Inscripción:</span>
                <span>{{ precioCalculado.tarifa_base }}€</span>
              </div>
              <div v-if="form.necesita_autobus" class="flex justify-between text-slate-700">
                <span>Autobús:</span>
                <span>{{ precioCalculado.precio_autobus }}€</span>
              </div>
              <div v-if="form.seguro_anulacion" class="flex justify-between text-slate-700">
                <span>Seguro:</span>
                <span>{{ precioCalculado.precio_seguro }}€</span>
              </div>
              <div class="mt-2 border-t border-slate-300 pt-2">
                <div class="flex justify-between text-lg font-bold text-slate-900">
                  <span>TOTAL:</span>
                  <span>{{ precioCalculado.precio_total }}€</span>
                </div>
              </div>
              <p v-if="precioCalculado.es_tarifa_tardia" class="text-sm text-amber-600">
                * Se ha aplicado tarifa tardía
              </p>
            </div>
          </div>

          <!-- Botón Submit -->
          <div class="flex justify-center pt-6">
            <Button type="submit" :disabled="form.processing" size="xl" class="px-12">
              {{ form.processing ? 'Procesando...' : 'Confirmar Inscripción' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
