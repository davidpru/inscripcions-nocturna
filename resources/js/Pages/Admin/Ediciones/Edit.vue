<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Bus, PencilLine } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Autobus {
  nombre: string;
  plazas: number;
  parada: 'pauls' | 'tortosa';
}

interface Edicion {
  id: number;
  anio: number;
  fecha_inicio_inscripciones: string | null;
  fecha_evento: string;
  limite_inscritos: number;
  fecha_limite_tarifa_normal: string;
  estado: 'abierta' | 'cerrada';
  activa: boolean;
  autobuses: Autobus[];
  precio_inscripcion_socio_normal: number;
  precio_inscripcion_publico_normal: number;
  precio_inscripcion_socio_tardia: number;
  precio_inscripcion_publico_tardia: number;
  precio_licencia_federativa_socio: number;
  precio_licencia_federativa_publico: number;
  precio_autobus_normal: number;
  precio_autobus_tardia: number;
  precio_seguro: number;
}

const props = defineProps<{
  edicion: Edicion;
  plazasAutobusVendidas: number;
  plazasPorParada: Record<string, number>;
}>();

const defaultAutobuses: Autobus[] = [
  { nombre: 'Autobús 1', plazas: 55, parada: 'pauls' },
  { nombre: 'Autobús 2', plazas: 55, parada: 'tortosa' },
];

const form = useForm({
  anio: props.edicion.anio ?? '',
  fecha_inicio_inscripciones: props.edicion.fecha_inicio_inscripciones ?? '',
  fecha_evento: props.edicion.fecha_evento ?? '',
  limite_inscritos: props.edicion.limite_inscritos ?? '',
  fecha_limite_tarifa_normal: props.edicion.fecha_limite_tarifa_normal ?? '',
  estado: props.edicion.estado ?? 'abierta',
  activa: props.edicion.activa ?? false,
  autobuses: props.edicion.autobuses?.length ? props.edicion.autobuses : defaultAutobuses,
  precio_inscripcion_socio_normal: props.edicion.precio_inscripcion_socio_normal ?? 30,
  precio_inscripcion_publico_normal: props.edicion.precio_inscripcion_publico_normal ?? 35,
  precio_inscripcion_socio_tardia: props.edicion.precio_inscripcion_socio_tardia ?? 35,
  precio_inscripcion_publico_tardia: props.edicion.precio_inscripcion_publico_tardia ?? 40,
  precio_licencia_federativa_socio: props.edicion.precio_licencia_federativa_socio ?? 5,
  precio_licencia_federativa_publico: props.edicion.precio_licencia_federativa_publico ?? 5,
  precio_autobus_normal: props.edicion.precio_autobus_normal ?? 12,
  precio_autobus_tardia: props.edicion.precio_autobus_tardia ?? 14,
  precio_seguro: props.edicion.precio_seguro ?? 9,
});

const nuevoAutobus = useForm({
  nombre: '',
  plazas: 55,
  parada: 'pauls' as 'pauls' | 'tortosa',
});

const totalPlazas = computed(() => {
  return form.autobuses.reduce((total, bus) => total + (bus.plazas || 0), 0);
});

const plazasDisponibles = computed(() => {
  return totalPlazas.value - props.plazasAutobusVendidas;
});

const errorAutobus = ref('');

const afegirAutobus = () => {
  const nombre = nuevoAutobus.nombre.trim() || `Autobús ${form.autobuses.length + 1}`;
  form.autobuses.push({
    nombre,
    plazas: nuevoAutobus.plazas || 55,
    parada: nuevoAutobus.parada,
  });
  nuevoAutobus.nombre = '';
  nuevoAutobus.plazas = 55;
  nuevoAutobus.parada = 'pauls';
  errorAutobus.value = '';
};

const eliminarAutobus = (index: number) => {
  const plazasDelBus = form.autobuses[index].plazas || 0;
  const plazasDespuesDeEliminar = totalPlazas.value - plazasDelBus;

  if (plazasDespuesDeEliminar < props.plazasAutobusVendidas) {
    errorAutobus.value = `No es pot eliminar aquest autobús. Hi ha ${props.plazasAutobusVendidas} places venudes i només quedarien ${plazasDespuesDeEliminar} places.`;
    return;
  }

  form.autobuses.splice(index, 1);
  errorAutobus.value = '';
};

const enviarFormulario = () => {
  form.put(`/admin/ediciones/${props.edicion.id}`);
};

// Computed: Tarifas finales (inscripción + licencia federativa para no federados)
const tarifaFinalSocioNormalNoFederado = computed(
  () =>
    Number(form.precio_inscripcion_socio_normal || 0) +
    Number(form.precio_licencia_federativa_socio || 0)
);

const tarifaFinalPublicoNormalNoFederado = computed(
  () =>
    Number(form.precio_inscripcion_publico_normal || 0) +
    Number(form.precio_licencia_federativa_publico || 0)
);

const tarifaFinalSocioTardiaNoFederado = computed(
  () =>
    Number(form.precio_inscripcion_socio_tardia || 0) +
    Number(form.precio_licencia_federativa_socio || 0)
);

const tarifaFinalPublicoTardiaNoFederado = computed(
  () =>
    Number(form.precio_inscripcion_publico_tardia || 0) +
    Number(form.precio_licencia_federativa_publico || 0)
);

// Calcular distribución de asientos ocupados por autobús
const calcularAsientosOcupados = computed(() => {
  const totalAsientos = totalPlazas.value;
  if (totalAsientos === 0) return [];

  // Asegurar que todos los buses tienen parada definida (fallback a 'pauls')
  const busesConParada = form.autobuses.map((bus) => ({
    ...bus,
    parada: bus.parada || 'pauls',
  }));

  // Separar buses por parada
  const busesPauls = busesConParada.filter((b) => b.parada === 'pauls');
  const busesTortosa = busesConParada.filter((b) => b.parada === 'tortosa');

  // Obtener plazas vendidas por parada
  const plazasPauls = props.plazasPorParada?.['pauls'] || 0;
  const plazasTortosa = props.plazasPorParada?.['tortosa'] || 0;

  const distribucion: Array<{
    nombre: string;
    total: number;
    ocupados: number;
    disponibles: number;
    parada: 'pauls' | 'tortosa';
  }> = [];

  // Distribuir plazas de Paüls
  let plazasRestantesPauls = plazasPauls;
  busesPauls.forEach((bus) => {
    const ocupados = Math.min(plazasRestantesPauls, bus.plazas);
    plazasRestantesPauls -= ocupados;
    distribucion.push({
      nombre: bus.nombre,
      total: bus.plazas,
      ocupados: ocupados,
      disponibles: bus.plazas - ocupados,
      parada: bus.parada as 'pauls' | 'tortosa',
    });
  });

  // Distribuir plazas de Tortosa
  let plazasRestantesTortosa = plazasTortosa;
  busesTortosa.forEach((bus) => {
    const ocupados = Math.min(plazasRestantesTortosa, bus.plazas);
    plazasRestantesTortosa -= ocupados;
    distribucion.push({
      nombre: bus.nombre,
      total: bus.plazas,
      ocupados: ocupados,
      disponibles: bus.plazas - ocupados,
      parada: bus.parada as 'pauls' | 'tortosa',
    });
  });

  // Ordenar por el orden original de form.autobuses
  return busesConParada
    .map((bus) => distribucion.find((d) => d.nombre === bus.nombre))
    .filter((d): d is NonNullable<typeof d> => d !== undefined);
});
</script>

<template>
  <AdminLayout>
    <Head :title="`Editar Edició ${edicion.anio}`" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-slate-900">Editar Edició {{ edicion.anio }}</h1>
          <p class="mt-1 text-slate-600">les dades de l'edició</p>
        </div>

        <form @submit.prevent="enviarFormulario">
          <Tabs default-value="edicio" class="w-full">
            <TabsList class="mb-6">
              <TabsTrigger value="edicio" class="">
                <span class="flex items-center gap-2 px-10">
                  <PencilLine :size="16" />
                  Edició i Preus
                </span>
              </TabsTrigger>
              <TabsTrigger value="autobusos" class="flex items-center gap-2">
                <span class="flex items-center gap-2 px-10">
                  <Bus :size="16" />
                  Autobusos
                  <Badge variant="default" class="ml-1">
                    {{ form.autobuses.length }}
                  </Badge>
                </span>
              </TabsTrigger>
            </TabsList>

            <!-- Tab: Edició i Preus -->
            <TabsContent value="edicio">
              <div class="grid gap-6 lg:grid-cols-2">
                <!-- Columna 1: Dades de l'Edició -->
                <div class="rounded-lg bg-white p-6 shadow">
                  <h3 class="mb-4 text-lg font-semibold text-slate-900">Dades de l'Edició</h3>
                  <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700"> Any * </label>
                        <input
                          v-model.number="form.anio"
                          type="number"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                        <p v-if="form.errors.anio" class="mt-1 text-sm text-red-600">
                          {{ form.errors.anio }}
                        </p>
                      </div>

                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Estat *
                        </label>
                        <select
                          v-model="form.estado"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        >
                          <option value="abierta">Oberta</option>
                          <option value="cerrada">Tancada</option>
                        </select>
                      </div>
                    </div>

                    <div>
                      <label class="flex cursor-pointer items-center gap-2">
                        <input
                          v-model="form.activa"
                          type="checkbox"
                          class="text-primary focus:ring-primary h-4 w-4 rounded border-slate-300"
                        />
                        <span class="text-sm font-medium text-slate-700">
                          Edició activa (visible a la pàgina principal)
                        </span>
                      </label>
                      <p class="mt-1 text-xs text-slate-500">
                        Només pot haver una edició activa alhora
                      </p>
                    </div>

                    <div>
                      <label class="mb-2 block text-sm font-medium text-slate-700">
                        Data i Hora d'Inici d'Inscripcions
                      </label>
                      <input
                        v-model="form.fecha_inicio_inscripciones"
                        type="datetime-local"
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                      />
                      <p class="mt-1 text-xs text-slate-500">Deixar buit = obertes immediatament</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Data de l'Esdeveniment *
                        </label>
                        <input
                          v-model="form.fecha_evento"
                          type="date"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                      </div>

                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Límit d'Inscrits *
                        </label>
                        <input
                          v-model.number="form.limite_inscritos"
                          type="number"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                      </div>
                    </div>

                    <div>
                      <label class="mb-2 block text-sm font-medium text-slate-700">
                        Data Límit Tarifa Normal *
                      </label>
                      <input
                        v-model="form.fecha_limite_tarifa_normal"
                        type="date"
                        required
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                      />
                      <p class="mt-1 text-xs text-slate-500">Després s'aplicarà tarifa tardana</p>
                    </div>
                  </div>
                </div>

                <!-- Columna 2: Tarifes i Serveis -->
                <div class="space-y-6">
                  <!-- Tarifes (Normals, Tardanes i Llicències) -->
                  <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-6 text-lg font-semibold text-slate-900">
                      Tarifes d'Inscripció i Llicències (€)
                    </h3>

                    <!-- Tarifes Normals -->
                    <div class="mb-6">
                      <h4 class="text-destructive mb-3 text-sm font-semibold">Tarifes Normals</h4>
                      <div class="grid grid-cols-2 gap-4">
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Soci UEC
                          </label>
                          <input
                            v-model.number="form.precio_inscripcion_socio_normal"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Públic
                          </label>
                          <input
                            v-model.number="form.precio_inscripcion_publico_normal"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Tarifes Tardanes -->
                    <div class="mb-6">
                      <h4 class="text-destructive mb-3 text-sm font-semibold">Tarifes Tardanes</h4>
                      <div class="grid grid-cols-2 gap-4">
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Soci UEC
                          </label>
                          <input
                            v-model.number="form.precio_inscripcion_socio_tardia"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Públic
                          </label>
                          <input
                            v-model.number="form.precio_inscripcion_publico_tardia"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Llicències Federatives -->
                    <div>
                      <h4 class="text-destructive mb-1 text-sm font-semibold">
                        Llicències Federatives
                      </h4>
                      <p class="mb-3 text-xs text-slate-600">
                        Cost de la llicència per a participants no federats
                      </p>
                      <div class="grid grid-cols-2 gap-4">
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Llicència Soci UEC
                          </label>
                          <input
                            v-model.number="form.precio_licencia_federativa_socio"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                        <div>
                          <label class="mb-2 block text-sm font-medium text-slate-700">
                            Llicència Públic
                          </label>
                          <input
                            v-model.number="form.precio_licencia_federativa_publico"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Previsualització Tarifes Finals -->
                  <div class="rounded-lg bg-blue-50 p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-blue-900">
                      Previsualització Tarifes Finals
                    </h3>

                    <div class="overflow-hidden rounded-lg border border-slate-300 bg-white">
                      <table class="w-full">
                        <thead class="bg-slate-100">
                          <tr>
                            <th
                              class="border-r border-slate-300 px-4 py-3 text-left text-sm font-semibold text-slate-700"
                            >
                              Tarifes
                            </th>
                            <th
                              class="border-r border-slate-300 px-4 py-3 text-left text-sm font-semibold text-slate-700"
                            >
                              Federativa
                            </th>
                            <th
                              class="border-r border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700"
                            >
                              Preu Normal
                            </th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">
                              Preu Tardà
                            </th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                          <tr>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-900">
                              Públic
                            </td>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-700">
                              Federats
                            </td>
                            <td
                              class="border-r border-slate-300 bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
                            >
                              {{ Number(form.precio_inscripcion_publico_normal || 0).toFixed(2) }}€
                            </td>
                            <td
                              class="bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
                            >
                              {{ Number(form.precio_inscripcion_publico_tardia || 0).toFixed(2) }}€
                            </td>
                          </tr>
                          <tr>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-900">
                              Públic
                            </td>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-700">
                              No Federats
                            </td>
                            <td
                              class="border-r border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-900"
                            >
                              {{ tarifaFinalPublicoNormalNoFederado.toFixed(2) }}€
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-slate-900">
                              {{ tarifaFinalPublicoTardiaNoFederado.toFixed(2) }}€
                            </td>
                          </tr>
                          <tr>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-900">
                              Socis UEC Tortosa
                            </td>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-700">
                              Federats
                            </td>
                            <td
                              class="border-r border-slate-300 bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
                            >
                              {{ Number(form.precio_inscripcion_socio_normal || 0).toFixed(2) }}€
                            </td>
                            <td
                              class="bg-green-50 px-4 py-3 text-center text-sm font-semibold text-green-700"
                            >
                              {{ Number(form.precio_inscripcion_socio_tardia || 0).toFixed(2) }}€
                            </td>
                          </tr>
                          <tr>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-900">
                              Socis UEC Tortosa
                            </td>
                            <td class="border-r border-slate-300 px-4 py-3 text-sm text-slate-700">
                              No Federats
                            </td>
                            <td
                              class="border-r border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-900"
                            >
                              {{ tarifaFinalSocioNormalNoFederado.toFixed(2) }}€
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-slate-900">
                              {{ tarifaFinalSocioTardiaNoFederado.toFixed(2) }}€
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <p class="mt-3 text-xs text-blue-700">
                      * Les tarifes per a federats només inclouen la inscripció. Les tarifes per a
                      no federats inclouen inscripció + llicència federativa ({{
                        Number(form.precio_licencia_federativa_publico || 0).toFixed(2)
                      }}€).
                    </p>
                  </div>

                  <!-- Serveis Addicionals -->
                  <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900">
                      Serveis Addicionals (€)
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Autobús Normal
                        </label>
                        <input
                          v-model.number="form.precio_autobus_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                      </div>
                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Autobús Tardà
                        </label>
                        <input
                          v-model.number="form.precio_autobus_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                      </div>
                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Assegurança
                        </label>
                        <input
                          v-model.number="form.precio_seguro"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>

            <!-- Tab: Autobusos -->
            <TabsContent value="autobusos">
              <div class="grid gap-6 lg:grid-cols-2">
                <!-- Columna 1: Configuració -->
                <div class="space-y-4">
                  <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900">
                      Configuració d'Autobusos
                    </h3>

                    <!-- Info de plazas vendidas -->
                    <div
                      v-if="plazasAutobusVendidas > 0"
                      class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-700"
                    >
                      <strong>⚠️ Atenció:</strong> Hi ha
                      <strong>{{ plazasAutobusVendidas }}</strong> places d'autobús ja venudes. No
                      podràs reduir el total per sota d'aquesta quantitat.
                    </div>

                    <!-- Error message -->
                    <div
                      v-if="errorAutobus || form.errors.autobuses"
                      class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700"
                    >
                      {{ errorAutobus || form.errors.autobuses }}
                    </div>

                    <!-- Lista de autobuses existentes -->
                    <div v-if="form.autobuses.length > 0" class="mb-4 space-y-3">
                      <div
                        v-for="(bus, index) in form.autobuses"
                        :key="index"
                        class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3"
                      >
                        <div
                          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-medium text-slate-600"
                        >
                          {{ index + 1 }}
                        </div>
                        <div class="flex-1">
                          <input
                            v-model="bus.nombre"
                            type="text"
                            placeholder="Nom de l'autobús"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900"
                          />
                        </div>
                        <div class="w-24">
                          <input
                            v-model.number="bus.plazas"
                            type="number"
                            min="1"
                            placeholder="Places"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900"
                          />
                        </div>
                        <div class="w-28">
                          <select
                            v-model="bus.parada"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900"
                          >
                            <option value="pauls">Paüls</option>
                            <option value="tortosa">Tortosa</option>
                          </select>
                        </div>
                        <span class="text-sm text-slate-500">{{
                          bus.parada === 'pauls' ? 'Paüls' : 'Tortosa'
                        }}</span>
                        <button
                          type="button"
                          @click="eliminarAutobus(index)"
                          class="rounded p-1 text-red-500 hover:bg-red-100"
                          title="Eliminar autobús"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                          >
                            <path
                              fill-rule="evenodd"
                              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                              clip-rule="evenodd"
                            />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- Añadir nuevo autobús -->
                    <div
                      class="flex items-end gap-3 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50/50 p-4"
                    >
                      <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                          Nom (opcional)
                        </label>
                        <input
                          v-model="nuevoAutobus.nombre"
                          type="text"
                          :placeholder="`Autobús ${form.autobuses.length + 1}`"
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900"
                        />
                      </div>
                      <div class="w-24">
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                          Places
                        </label>
                        <input
                          v-model.number="nuevoAutobus.plazas"
                          type="number"
                          min="1"
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900"
                        />
                      </div>
                      <div class="w-28">
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                          Parada
                        </label>
                        <select
                          v-model="nuevoAutobus.parada"
                          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900"
                        >
                          <option value="pauls">Paüls</option>
                          <option value="tortosa">Tortosa</option>
                        </select>
                      </div>
                      <Button type="button" variant="secondary" @click="afegirAutobus">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="mr-1 h-4 w-4"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Afegir
                      </Button>
                    </div>
                  </div>

                  <!-- Total plazas -->
                  <div class="rounded-lg bg-blue-50 p-4">
                    <div class="flex items-center justify-between text-blue-700">
                      <div class="flex items-center gap-2">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="h-5 w-5"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                          <path
                            fill-rule="evenodd"
                            d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        <span class="font-medium">Resum</span>
                      </div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-4 text-center">
                      <div>
                        <p class="text-2xl font-bold text-blue-700">
                          {{ form.autobuses.length }}
                        </p>
                        <p class="text-xs text-blue-600">Autobusos</p>
                      </div>
                      <div>
                        <p class="text-2xl font-bold text-blue-700">
                          {{ totalPlazas }}
                        </p>
                        <p class="text-xs text-blue-600">Totals</p>
                      </div>
                      <div>
                        <p
                          class="text-2xl font-bold"
                          :class="plazasDisponibles >= 0 ? 'text-green-600' : 'text-red-600'"
                        >
                          {{ plazasDisponibles }}
                        </p>
                        <p class="text-xs text-blue-600">Disponibles</p>
                      </div>
                    </div>
                    <p
                      v-if="plazasAutobusVendidas > 0"
                      class="mt-3 text-center text-sm text-blue-600"
                    >
                      ({{ plazasAutobusVendidas }} places ja venudes)
                    </p>
                  </div>
                </div>

                <!-- Columna 2: Visualització d'Autobusos -->
                <div class="rounded-lg bg-white p-6 shadow">
                  <h3 class="mb-4 text-lg font-semibold text-slate-900">
                    Visualització d'Ocupació
                  </h3>

                  <!-- Leyenda -->
                  <div class="mb-6 flex items-center gap-4 text-xs text-slate-600">
                    <div class="flex items-center gap-1.5">
                      <div class="h-3 w-3 rounded bg-red-500"></div>
                      <span>Ocupat</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <div class="h-3 w-3 rounded border-2 border-slate-300 bg-slate-50"></div>
                      <span>Disponible</span>
                    </div>
                    <div class="ml-auto flex items-center gap-3">
                      <Badge variant="" class="gap-1">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        Paüls
                      </Badge>
                      <Badge variant="" class="gap-1">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Tortosa
                      </Badge>
                    </div>
                  </div>

                  <div v-if="form.autobuses.length === 0" class="py-8 text-center text-slate-500">
                    <Bus :size="48" class="mx-auto mb-2 opacity-30" />
                    <p>No hi ha autobusos configurats</p>
                  </div>

                  <div v-else class="space-y-6">
                    <div
                      v-for="(autobusInfo, busIndex) in calcularAsientosOcupados"
                      :key="busIndex"
                      class="rounded-lg border border-slate-200 p-4"
                    >
                      <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                          <h4 class="font-medium text-slate-900">
                            {{ autobusInfo.nombre }}
                          </h4>
                          <Badge
                            :variant="autobusInfo.parada === 'pauls' ? 'default' : 'secondary'"
                            :class="
                              autobusInfo.parada === 'pauls'
                                ? 'bg-blue-500 hover:bg-blue-600'
                                : 'bg-green-500 hover:bg-green-600'
                            "
                          >
                            {{ autobusInfo.parada === 'pauls' ? 'Paüls' : 'Tortosa' }}
                          </Badge>
                        </div>
                        <div class="text-sm text-slate-600">
                          <span class="font-semibold text-red-600">
                            {{ autobusInfo.ocupados }}
                          </span>
                          /
                          <span>{{ autobusInfo.total }}</span>
                          places
                          <span class="ml-2 text-xs opacity-60">
                            (Total venudes: {{ plazasAutobusVendidas }})
                          </span>
                        </div>
                      </div>

                      <!-- Grid de asientos (vista desde arriba horizontal) -->
                      <div class="flex flex-wrap justify-center gap-4">
                        <template v-for="row in Math.ceil(autobusInfo.total / 4)" :key="row">
                          <div class="flex flex-col items-center gap-1">
                            <!-- Seient esquerre superior -->
                            <div
                              class="h-4 w-4 rounded"
                              :class="
                                (row - 1) * 4 + 1 <= autobusInfo.total
                                  ? (row - 1) * 4 + 1 <= autobusInfo.ocupados
                                    ? 'bg-red-500'
                                    : 'border-2 border-slate-300 bg-slate-50'
                                  : 'invisible'
                              "
                              :title="
                                (row - 1) * 4 + 1 <= autobusInfo.total
                                  ? (row - 1) * 4 + 1 <= autobusInfo.ocupados
                                    ? `Seient ${(row - 1) * 4 + 1} - Ocupat`
                                    : `Seient ${(row - 1) * 4 + 1} - Disponible`
                                  : ''
                              "
                            ></div>

                            <!-- Seient esquerre inferior -->
                            <div
                              class="h-4 w-4 rounded"
                              :class="
                                (row - 1) * 4 + 2 <= autobusInfo.total
                                  ? (row - 1) * 4 + 2 <= autobusInfo.ocupados
                                    ? 'bg-red-500'
                                    : 'border-2 border-slate-300 bg-slate-50'
                                  : 'invisible'
                              "
                              :title="
                                (row - 1) * 4 + 2 <= autobusInfo.total
                                  ? (row - 1) * 4 + 2 <= autobusInfo.ocupados
                                    ? `Seient ${(row - 1) * 4 + 2} - Ocupat`
                                    : `Seient ${(row - 1) * 4 + 2} - Disponible`
                                  : ''
                              "
                            ></div>

                            <!-- Passadís -->
                            <div class="h-3"></div>

                            <!-- Seient dret superior -->
                            <div
                              class="h-4 w-4 rounded"
                              :class="
                                (row - 1) * 4 + 3 <= autobusInfo.total
                                  ? (row - 1) * 4 + 3 <= autobusInfo.ocupados
                                    ? 'bg-red-500'
                                    : 'border-2 border-slate-300 bg-slate-50'
                                  : 'invisible'
                              "
                              :title="
                                (row - 1) * 4 + 3 <= autobusInfo.total
                                  ? (row - 1) * 4 + 3 <= autobusInfo.ocupados
                                    ? `Seient ${(row - 1) * 4 + 3} - Ocupat`
                                    : `Seient ${(row - 1) * 4 + 3} - Disponible`
                                  : ''
                              "
                            ></div>

                            <!-- Seient dret inferior -->
                            <div
                              class="h-4 w-4 rounded"
                              :class="
                                (row - 1) * 4 + 4 <= autobusInfo.total
                                  ? (row - 1) * 4 + 4 <= autobusInfo.ocupados
                                    ? 'bg-red-500'
                                    : 'border-2 border-slate-300 bg-slate-50'
                                  : 'invisible'
                              "
                              :title="
                                (row - 1) * 4 + 4 <= autobusInfo.total
                                  ? (row - 1) * 4 + 4 <= autobusInfo.ocupados
                                    ? `Seient ${(row - 1) * 4 + 4} - Ocupat`
                                    : `Seient ${(row - 1) * 4 + 4} - Disponible`
                                  : ''
                              "
                            ></div>
                          </div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>
          </Tabs>

          <!-- Botones de acción (fuera de los tabs) -->
          <div class="mt-6 flex justify-end gap-4">
            <Button variant="outline" as="a" href="/uec-admin/ediciones"> Cancel·lar </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Guardant...' : 'Guardar Canvis' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
