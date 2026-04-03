<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Bus, PencilLine } from 'lucide-vue-next';
import { computed } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  fecha_inicio_inscripciones: string | null;
  fecha_evento: string;
  limite_inscritos: number;
  limite_tarifa_tardia_inscritos: number;
  fecha_limite_tarifa_normal: string;
  estado: 'abierta' | 'cerrada';
  activa: boolean;
  autobuses: Array<{ nombre: string; plazas: number; parada?: string }>;
  plazas_autobus: number;
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
  plazasAutobusDisponibles: number;
}>();

const form = useForm({
  anio: props.edicion.anio ?? '',
  fecha_inicio_inscripciones: props.edicion.fecha_inicio_inscripciones ?? '',
  fecha_evento: props.edicion.fecha_evento ?? '',
  limite_inscritos: props.edicion.limite_inscritos ?? '',
  limite_tarifa_tardia_inscritos: props.edicion.limite_tarifa_tardia_inscritos ?? 650,
  fecha_limite_tarifa_normal: props.edicion.fecha_limite_tarifa_normal ?? '',
  estado: props.edicion.estado ?? 'abierta',
  activa: props.edicion.activa ?? false,
  autobuses: props.edicion.autobuses ?? [],
  plazas_autobus: props.edicion.plazas_autobus ?? 0,
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

const plazasDisponibles = computed(() => {
  return (form.plazas_autobus || 0) - props.plazasAutobusVendidas;
});

const enviarFormulario = () => {
  form.put(`/uec-admin/ediciones/${props.edicion.id}`);
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
            <TabsList class="mb-6 w-full sm:w-auto">
              <TabsTrigger value="edicio" class="flex-1 sm:flex-none">
                <span class="flex items-center gap-2 px-4 sm:px-10">
                  <PencilLine :size="16" />
                  Edició i Preus
                </span>
              </TabsTrigger>
              <TabsTrigger value="autobusos" class="flex-1 sm:flex-none">
                <span class="flex items-center gap-2 px-4 sm:px-10">
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

                    <div class="grid gap-4 sm:grid-cols-2">
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

                      <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">
                          Límit Tarifa Tardana (inscrits) *
                        </label>
                        <input
                          v-model.number="form.limite_tarifa_tardia_inscritos"
                          type="number"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                        />
                        <p
                          v-if="form.errors.limite_tarifa_tardia_inscritos"
                          class="mt-1 text-sm text-red-600"
                        >
                          {{ form.errors.limite_tarifa_tardia_inscritos }}
                        </p>
                      </div>
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
                      </div>
                    </div>

                    <!-- Tarifes Tardanes -->
                    <div class="mb-6">
                      <h4 class="text-destructive mb-3 text-sm font-semibold">Tarifes Tardanes</h4>
                      <div class="grid grid-cols-2 gap-4">
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

                    <div class="overflow-x-auto">
                      <table
                        class="w-full min-w-[480px] rounded-lg border border-slate-300 bg-white"
                      >
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
              <div class="mx-auto max-w-xl space-y-6">
                <div class="rounded-lg bg-white p-6 shadow">
                  <h3 class="mb-4 text-lg font-semibold text-slate-900">Places d'Autobús</h3>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">
                      Total de places d'autobús
                    </label>
                    <input
                      v-model.number="form.plazas_autobus"
                      type="number"
                      min="0"
                      class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900"
                    />
                    <p v-if="form.errors.plazas_autobus" class="mt-1 text-sm text-red-600">
                      {{ form.errors.plazas_autobus }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                      Posa 0 per desactivar el límit de places d'autobús.
                    </p>
                  </div>
                </div>

                <!-- Resum -->
                <div class="rounded-lg bg-blue-50 p-4">
                  <div class="flex items-center gap-2 text-blue-700">
                    <Bus class="h-5 w-5" />
                    <span class="font-medium">Resum</span>
                  </div>
                  <div class="mt-3 grid grid-cols-3 gap-4 text-center">
                    <div>
                      <p class="text-2xl font-bold text-blue-700">
                        {{ form.plazas_autobus || 0 }}
                      </p>
                      <p class="text-xs text-blue-600">Totals</p>
                    </div>
                    <div>
                      <p class="text-2xl font-bold text-red-600">
                        {{ plazasAutobusVendidas }}
                      </p>
                      <p class="text-xs text-blue-600">Venudes</p>
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
                </div>

                <!-- Distribución por parada -->
                <div v-if="plazasAutobusVendidas > 0" class="rounded-lg bg-white p-6 shadow">
                  <h3 class="mb-4 text-lg font-semibold text-slate-900">Distribució per parada</h3>
                  <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="rounded-lg bg-slate-50 p-4">
                      <p class="text-2xl font-bold text-slate-700">
                        {{ plazasPorParada?.['pauls'] || 0 }}
                      </p>
                      <p class="text-sm text-slate-500">Paüls</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                      <p class="text-2xl font-bold text-slate-700">
                        {{ plazasPorParada?.['tortosa'] || 0 }}
                      </p>
                      <p class="text-sm text-slate-500">Tortosa</p>
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
