<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Bus, PencilLine, Hash } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  fecha_inicio_inscripciones: string | null;
  fecha_evento: string;
  limite_inscritos: number;
  limite_tarifa_tardia_inscritos: number;
  fecha_limite_tarifa_normal: string;
  estado: 'abierta' | 'cerrada';
  lista_espera_cerrada: boolean;
  dorsal_primer_masculino_id: number | null;
  dorsal_primera_femenina_id: number | null;
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

interface CandidatoDorsal {
  id: number;
  nombre: string;
  dni: string;
  genero: string | null;
  numero_dorsal: number | null;
}

const props = defineProps<{
  edicion: Edicion;
  plazasAutobusVendidas: number;
  plazasPorParada: Record<string, number>;
  plazasAutobusDisponibles: number;
  candidatosDorsal: CandidatoDorsal[];
  dorsalesStats: { asignados: number; pendientes: number };
}>();

const form = useForm({
  anio: props.edicion.anio ?? '',
  fecha_inicio_inscripciones: props.edicion.fecha_inicio_inscripciones ?? '',
  fecha_evento: props.edicion.fecha_evento ?? '',
  limite_inscritos: props.edicion.limite_inscritos ?? '',
  limite_tarifa_tardia_inscritos: props.edicion.limite_tarifa_tardia_inscritos ?? 650,
  fecha_limite_tarifa_normal: props.edicion.fecha_limite_tarifa_normal ?? '',
  estado: props.edicion.estado ?? 'abierta',
  lista_espera_cerrada: props.edicion.lista_espera_cerrada ?? false,
  dorsal_primer_masculino_id: props.edicion.dorsal_primer_masculino_id ?? null,
  dorsal_primera_femenina_id: props.edicion.dorsal_primera_femenina_id ?? null,
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

const asignandoDorsales = ref(false);

const formatLabel = (c: CandidatoDorsal) =>
  `${c.nombre} (${c.dni})${c.numero_dorsal ? ` — dorsal #${c.numero_dorsal}` : ''}`;

const inputMasc = ref('');
const inputFem = ref('');

const sincronizarInputDesdeId = (
  inputRef: typeof inputMasc,
  id: number | null,
) => {
  if (id === null) {
    inputRef.value = '';
    return;
  }
  const c = props.candidatosDorsal.find((x) => x.id === id);
  inputRef.value = c ? formatLabel(c) : '';
};

sincronizarInputDesdeId(inputMasc, form.dorsal_primer_masculino_id);
sincronizarInputDesdeId(inputFem, form.dorsal_primera_femenina_id);

const buscarPorTexto = (texto: string): CandidatoDorsal | null => {
  if (!texto) return null;
  const exact = props.candidatosDorsal.find((c) => formatLabel(c) === texto);
  if (exact) return exact;
  const q = texto.toLowerCase().trim();
  const candidatos = props.candidatosDorsal.filter(
    (c) => c.nombre.toLowerCase().includes(q) || c.dni.toLowerCase().includes(q),
  );
  return candidatos.length === 1 ? candidatos[0] : null;
};

const seleccionarMasc = () => {
  const c = buscarPorTexto(inputMasc.value);
  form.dorsal_primer_masculino_id = c ? c.id : null;
  if (c) inputMasc.value = formatLabel(c);
};
const seleccionarFem = () => {
  const c = buscarPorTexto(inputFem.value);
  form.dorsal_primera_femenina_id = c ? c.id : null;
  if (c) inputFem.value = formatLabel(c);
};

const generoSeleccionadoMasc = computed(() => {
  const sel = props.candidatosDorsal.find((c) => c.id === form.dorsal_primer_masculino_id);
  return sel?.genero ?? null;
});
const generoSeleccionadoFem = computed(() => {
  const sel = props.candidatosDorsal.find((c) => c.id === form.dorsal_primera_femenina_id);
  return sel?.genero ?? null;
});

const asignarDorsales = () => {
  if (!confirm('Assignar dorsals? Es respectaran els ja assignats i es forçaran #1 i #2 als seleccionats.')) return;
  asignandoDorsales.value = true;
  router.post(
    `/uec-admin/ediciones/${props.edicion.id}/asignar-dorsales`,
    {},
    { onFinish: () => (asignandoDorsales.value = false) },
  );
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
              <TabsTrigger value="dorsals" class="flex-1 sm:flex-none">
                <span class="flex items-center gap-2 px-4 sm:px-10">
                  <Hash :size="16" />
                  Dorsals
                  <Badge variant="default" class="ml-1">
                    {{ dorsalesStats.asignados }}
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
                      <label class="flex cursor-pointer items-center gap-2">
                        <input
                          v-model="form.lista_espera_cerrada"
                          type="checkbox"
                          class="text-primary focus:ring-primary h-4 w-4 rounded border-slate-300"
                        />
                        <span class="text-sm font-medium text-slate-700">
                          Tancar llista d'espera
                        </span>
                      </label>
                      <p class="mt-1 text-xs text-slate-500">
                        Bloqueja noves sol·licituds i mostra l'estat tancat a la pàgina principal
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

            <!-- Tab: Dorsals -->
            <TabsContent value="dorsals">
              <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-4">
                  <h3 class="text-lg font-semibold text-slate-900">Assignació de dorsals</h3>
                  <p class="text-sm text-slate-500">
                    Per ordre d'inscripció (created_at). Reserva manual del #1 (primer masculí) i del #2 (primera femenina).
                  </p>
                </div>

                <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
                  <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium text-slate-500">Assignats</p>
                    <p class="text-2xl font-bold text-slate-900">{{ dorsalesStats.asignados }}</p>
                  </div>
                  <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium text-slate-500">Pendents sense dorsal</p>
                    <p class="text-2xl font-bold text-slate-900">{{ dorsalesStats.pendientes }}</p>
                  </div>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                  <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                      Dorsal #1 — Primer masculí
                    </label>
                    <input
                      v-model="inputMasc"
                      type="text"
                      list="candidatos-dorsal-list"
                      placeholder="Escriu nom o DNI..."
                      autocomplete="off"
                      class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                      @change="seleccionarMasc"
                      @blur="seleccionarMasc"
                    />
                    <p class="mt-1 text-xs text-slate-500">
                      Seleccionat: ID #{{ form.dorsal_primer_masculino_id ?? '—' }}
                    </p>
                    <p
                      v-if="generoSeleccionadoMasc && generoSeleccionadoMasc !== 'masculino'"
                      class="mt-1 text-sm text-amber-700"
                    >
                      ⚠ Aquest participant no consta com a masculí (gènere: {{ generoSeleccionadoMasc }})
                    </p>
                  </div>

                  <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                      Dorsal #2 — Primera femenina
                    </label>
                    <input
                      v-model="inputFem"
                      type="text"
                      list="candidatos-dorsal-list"
                      placeholder="Escriu nom o DNI..."
                      autocomplete="off"
                      class="block w-full rounded-md border-slate-300 text-sm shadow-sm"
                      @change="seleccionarFem"
                      @blur="seleccionarFem"
                    />
                    <p class="mt-1 text-xs text-slate-500">
                      Seleccionada: ID #{{ form.dorsal_primera_femenina_id ?? '—' }}
                    </p>
                    <p
                      v-if="generoSeleccionadoFem && generoSeleccionadoFem !== 'femenino'"
                      class="mt-1 text-sm text-amber-700"
                    >
                      ⚠ Aquest participant no consta com a femení (gènere: {{ generoSeleccionadoFem }})
                    </p>
                  </div>
                </div>

                <datalist id="candidatos-dorsal-list">
                  <option
                    v-for="c in candidatosDorsal"
                    :key="c.id"
                    :value="`${c.nombre} (${c.dni})${c.numero_dorsal ? ` — dorsal #${c.numero_dorsal}` : ''}`"
                  />
                </datalist>

                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                  <p class="font-medium">Important:</p>
                  <ul class="ml-4 mt-1 list-disc space-y-1">
                    <li>Primer desa els canvis (Guardar Canvis) per fixar els seleccionats.</li>
                    <li>Després prem <strong>Assignar dorsals</strong> per executar l'assignació.</li>
                    <li>Es respecten els dorsals ja assignats; només s'omplen els buits.</li>
                    <li>Els canvis al #1/#2 es forcen: l'anterior holder queda sense dorsal i rebrà el següent en la pròxima execució.</li>
                  </ul>
                </div>

                <div class="mt-4 flex justify-end">
                  <Button type="button" :disabled="asignandoDorsales" @click="asignarDorsales">
                    {{ asignandoDorsales ? 'Assignant...' : 'Assignar dorsals' }}
                  </Button>
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
