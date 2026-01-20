<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';

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
  seguro_anulacion: false,
  talla_camiseta_caro: '',
  talla_camiseta_pauls: '',
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

const enviarInscripcion = () => {
  form.post('/inscripcion', {
    onSuccess: () => {
      // Redirigirá automáticamente a la página de confirmación
    },
  });
};
</script>

<template>
  <Head title="Inscripción - Nocturna Fredes Paüls" />

  <div
    class="min-h-screen bg-linear-to-b from-slate-50 to-slate-100 px-4 py-12 dark:from-slate-900 dark:to-slate-800"
  >
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="mb-8 text-center">
        <h1 class="mb-2 text-4xl font-bold text-slate-900 dark:text-slate-100">
          Nocturna Fredes Paüls {{ edicion.anio }}
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400">Formulario de Inscripción</p>
      </div>

      <!-- Formulario -->
      <div class="rounded-lg bg-white p-8 shadow-lg dark:bg-slate-800">
        <form @submit.prevent="enviarInscripcion" class="space-y-8">
          <!-- Sección DNI -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Identificación
            </h2>
            <div class="flex gap-4">
              <div class="flex-1 space-y-2">
                <Label for="dni">DNI/NIE *</Label>
                <Input
                  id="dni"
                  v-model="form.dni"
                  @blur="buscarParticipante"
                  type="text"
                  required
                  placeholder="12345678X"
                />
              </div>
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
            <p
              v-if="participanteEncontrado"
              class="mt-2 text-sm text-green-600 dark:text-green-400"
            >
              ✓ Participante encontrado. Verifica que tus datos sean correctos.
            </p>
          </div>

          <!-- Datos Personales -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Datos Personales
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="nombre">Nombre *</Label>
                <Input id="nombre" v-model="form.nombre" type="text" required />
              </div>

              <div class="space-y-2">
                <Label for="apellidos">Apellidos *</Label>
                <Input id="apellidos" v-model="form.apellidos" type="text" required />
              </div>

              <div class="space-y-2">
                <Label for="genero">Género *</Label>
                <Select v-model="form.genero" required>
                  <SelectTrigger id="genero">
                    <SelectValue placeholder="Seleccionar..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="masculino">Masculino</SelectItem>
                    <SelectItem value="femenino">Femenino</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div class="space-y-2">
                <Label for="fecha_nacimiento">Fecha de Nacimiento *</Label>
                <Input id="fecha_nacimiento" v-model="form.fecha_nacimiento" type="date" required />
              </div>

              <div class="space-y-2">
                <Label for="telefono">Teléfono *</Label>
                <Input id="telefono" v-model="form.telefono" type="tel" required />
              </div>

              <div class="space-y-2">
                <Label for="email">Email *</Label>
                <Input id="email" v-model="form.email" type="email" required />
              </div>
            </div>
          </div>

          <!-- Dirección -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Dirección
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="space-y-2 md:col-span-2">
                <Label for="direccion">Dirección *</Label>
                <Input id="direccion" v-model="form.direccion" type="text" required />
              </div>

              <div class="space-y-2">
                <Label for="codigo_postal">Código Postal *</Label>
                <Input id="codigo_postal" v-model="form.codigo_postal" type="text" required />
              </div>

              <div class="space-y-2">
                <Label for="poblacion">Población *</Label>
                <Input id="poblacion" v-model="form.poblacion" type="text" required />
              </div>

              <div class="space-y-2 md:col-span-2">
                <Label for="provincia">Provincia *</Label>
                <Input id="provincia" v-model="form.provincia" type="text" required />
              </div>
            </div>
          </div>

          <!-- Información Deportiva -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Información Deportiva
            </h2>
            <div class="space-y-4">
              <div class="flex items-center space-x-2">
                <Checkbox id="socio_uec" v-model:checked="form.es_socio_uec" />
                <Label
                  for="socio_uec"
                  class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  ¿Eres socio de la UEC Tortosa?
                </Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox id="federado" v-model:checked="form.esta_federado" />
                <Label
                  for="federado"
                  class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                >
                  ¿Estás federado?
                </Label>
              </div>

              <div v-if="form.esta_federado" class="ml-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="numero_licencia">Número de Licencia *</Label>
                  <Input
                    id="numero_licencia"
                    v-model="form.numero_licencia"
                    type="text"
                    :required="form.esta_federado"
                  />
                </div>

                <div class="space-y-2">
                  <Label for="club">Club</Label>
                  <Input id="club" v-model="form.club" type="text" />
                </div>
              </div>
            </div>
          </div>

          <!-- Servicios Adicionales -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Servicios Adicionales
            </h2>
            <div class="space-y-4">
              <div
                class="flex items-center justify-between rounded-md border border-slate-200 p-4 dark:border-slate-700"
              >
                <div>
                  <Label for="autobus" class="text-sm font-medium"> Autobús Paüls-Fredes </Label>
                  <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ precioCalculado?.precio_autobus || 12 }}€
                  </p>
                </div>
                <Checkbox id="autobus" v-model:checked="form.necesita_autobus" />
              </div>

              <div
                class="flex items-center justify-between rounded-md border border-slate-200 p-4 dark:border-slate-700"
              >
                <div>
                  <Label for="seguro" class="text-sm font-medium"> Seguro de Anulación </Label>
                  <p class="text-sm text-slate-500 dark:text-slate-400">9€</p>
                </div>
                <Checkbox id="seguro" v-model:checked="form.seguro_anulacion" />
              </div>
            </div>
          </div>

          <!-- Tallas de Camisetas -->
          <div>
            <h2 class="mb-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">
              Tallas de Camisetas
            </h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="talla_caro">Talla Camiseta Caro *</Label>
                <Select v-model="form.talla_camiseta_caro" required>
                  <SelectTrigger id="talla_caro">
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

              <div class="space-y-2">
                <Label for="talla_pauls">Talla Camiseta Paüls *</Label>
                <Select v-model="form.talla_camiseta_pauls" required>
                  <SelectTrigger id="talla_pauls">
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

          <!-- Resumen de Precio -->
          <div v-if="precioCalculado" class="rounded-lg bg-slate-50 p-6 dark:bg-slate-700">
            <h3 class="mb-4 text-xl font-semibold text-slate-900 dark:text-slate-100">
              Resumen del Precio
            </h3>
            <div class="space-y-2">
              <div class="flex justify-between text-slate-700 dark:text-slate-300">
                <span>Inscripción:</span>
                <span>{{ precioCalculado.tarifa_base }}€</span>
              </div>
              <div
                v-if="form.necesita_autobus"
                class="flex justify-between text-slate-700 dark:text-slate-300"
              >
                <span>Autobús:</span>
                <span>{{ precioCalculado.precio_autobus }}€</span>
              </div>
              <div
                v-if="form.seguro_anulacion"
                class="flex justify-between text-slate-700 dark:text-slate-300"
              >
                <span>Seguro:</span>
                <span>{{ precioCalculado.precio_seguro }}€</span>
              </div>
              <div class="mt-2 border-t border-slate-300 pt-2 dark:border-slate-600">
                <div
                  class="flex justify-between text-lg font-bold text-slate-900 dark:text-slate-100"
                >
                  <span>TOTAL:</span>
                  <span>{{ precioCalculado.precio_total }}€</span>
                </div>
              </div>
              <p
                v-if="precioCalculado.es_tarifa_tardia"
                class="text-sm text-amber-600 dark:text-amber-400"
              >
                * Se ha aplicado tarifa tardía
              </p>
            </div>
          </div>

          <!-- Botón Submit -->
          <div class="flex justify-center pt-6">
            <Button type="submit" :disabled="form.processing" size="lg" class="px-12">
              {{ form.processing ? 'Procesando...' : 'Confirmar Inscripción' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
