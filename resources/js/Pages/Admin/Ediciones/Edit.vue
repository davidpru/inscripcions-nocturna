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
  tarifa_publico_federado_normal: number;
  tarifa_publico_no_federado_normal: number;
  tarifa_socio_federado_normal: number;
  tarifa_socio_no_federado_normal: number;
  tarifa_publico_federado_tardia: number;
  tarifa_publico_no_federado_tardia: number;
  tarifa_socio_federado_tardia: number;
  tarifa_socio_no_federado_tardia: number;
  precio_autobus_normal: number;
  precio_autobus_tardia: number;
  precio_seguro: number;
}

const props = defineProps<{
  edicion: Edicion;
  plazasAutobusVendidas: number;
}>();

const defaultAutobuses: Autobus[] = [
  { nombre: 'Autobús 1', plazas: 55 },
  { nombre: 'Autobús 2', plazas: 55 },
];

const form = useForm({
  anio: props.edicion.anio ?? '',
  fecha_inicio_inscripciones: props.edicion.fecha_inicio_inscripciones ?? '',
  fecha_evento: props.edicion.fecha_evento ?? '',
  limite_inscritos: props.edicion.limite_inscritos ?? '',
  fecha_limite_tarifa_normal: props.edicion.fecha_limite_tarifa_normal ?? '',
  estado: props.edicion.estado ?? 'abierta',
  autobuses: props.edicion.autobuses?.length ? props.edicion.autobuses : defaultAutobuses,
  tarifa_publico_federado_normal: props.edicion.tarifa_publico_federado_normal ?? 35,
  tarifa_publico_no_federado_normal: props.edicion.tarifa_publico_no_federado_normal ?? 40,
  tarifa_socio_federado_normal: props.edicion.tarifa_socio_federado_normal ?? 30,
  tarifa_socio_no_federado_normal: props.edicion.tarifa_socio_no_federado_normal ?? 35,
  tarifa_publico_federado_tardia: props.edicion.tarifa_publico_federado_tardia ?? 40,
  tarifa_publico_no_federado_tardia: props.edicion.tarifa_publico_no_federado_tardia ?? 45,
  tarifa_socio_federado_tardia: props.edicion.tarifa_socio_federado_tardia ?? 35,
  tarifa_socio_no_federado_tardia: props.edicion.tarifa_socio_no_federado_tardia ?? 40,
  precio_autobus_normal: props.edicion.precio_autobus_normal ?? 12,
  precio_autobus_tardia: props.edicion.precio_autobus_tardia ?? 14,
  precio_seguro: props.edicion.precio_seguro ?? 9,
});

const nuevoAutobus = useForm({
  nombre: '',
  plazas: 55,
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
  });
  nuevoAutobus.nombre = '';
  nuevoAutobus.plazas = 55;
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
</script>

<template>
  <AdminLayout>
    <Head :title="`Editar Edició ${edicion.anio}`" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-6xl">
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
            Editar Edició {{ edicion.anio }}
          </h1>
          <p class="mt-1 text-slate-600 dark:text-slate-400">Modifica les dades de l'edició</p>
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
                <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
                  <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                    Dades de l'Edició
                  </h3>
                  <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Any *
                        </label>
                        <input
                          v-model.number="form.anio"
                          type="number"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                        <p v-if="form.errors.anio" class="mt-1 text-sm text-red-600">
                          {{ form.errors.anio }}
                        </p>
                      </div>

                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Estat *
                        </label>
                        <select
                          v-model="form.estado"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        >
                          <option value="abierta">Oberta</option>
                          <option value="cerrada">Tancada</option>
                        </select>
                      </div>
                    </div>

                    <div>
                      <label
                        class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                      >
                        Data i Hora d'Inici d'Inscripcions
                      </label>
                      <input
                        v-model="form.fecha_inicio_inscripciones"
                        type="datetime-local"
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                      />
                      <p class="mt-1 text-xs text-slate-500">Deixar buit = obertes immediatament</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Data de l'Esdeveniment *
                        </label>
                        <input
                          v-model="form.fecha_evento"
                          type="date"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>

                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Límit d'Inscrits *
                        </label>
                        <input
                          v-model.number="form.limite_inscritos"
                          type="number"
                          required
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                    </div>

                    <div>
                      <label
                        class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                      >
                        Data Límit Tarifa Normal *
                      </label>
                      <input
                        v-model="form.fecha_limite_tarifa_normal"
                        type="date"
                        required
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                      />
                      <p class="mt-1 text-xs text-slate-500">Després s'aplicarà tarifa tardana</p>
                    </div>
                  </div>
                </div>

                <!-- Columna 2: Tarifes i Serveis -->
                <div class="space-y-6">
                  <!-- Tarifes Normals -->
                  <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                      Tarifes Normals (€)
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Públic Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_publico_federado_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Públic No Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_publico_no_federado_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Soci UEC Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_socio_federado_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Soci UEC No Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_socio_no_federado_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Tarifes Tardanes -->
                  <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                      Tarifes Tardanes (€)
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Públic Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_publico_federado_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Públic No Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_publico_no_federado_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Soci UEC Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_socio_federado_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Soci UEC No Federat
                        </label>
                        <input
                          v-model.number="form.tarifa_socio_no_federado_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Serveis Addicionals -->
                  <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
                    <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                      Serveis Addicionals (€)
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Autobús Normal
                        </label>
                        <input
                          v-model.number="form.precio_autobus_normal"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Autobús Tardà
                        </label>
                        <input
                          v-model.number="form.precio_autobus_tardia"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                      <div>
                        <label
                          class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                          Assegurança
                        </label>
                        <input
                          v-model.number="form.precio_seguro"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>

            <!-- Tab: Autobusos -->
            <TabsContent value="autobusos">
              <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                  Configuració d'Autobusos
                </h3>

                <!-- Info de plazas vendidas -->
                <div
                  v-if="plazasAutobusVendidas > 0"
                  class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                >
                  <strong>⚠️ Atenció:</strong> Hi ha
                  <strong>{{ plazasAutobusVendidas }}</strong> places d'autobús ja venudes. No
                  podràs reduir el total per sota d'aquesta quantitat.
                </div>

                <!-- Error message -->
                <div
                  v-if="errorAutobus || form.errors.autobuses"
                  class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300"
                >
                  {{ errorAutobus || form.errors.autobuses }}
                </div>

                <!-- Lista de autobuses existentes -->
                <div v-if="form.autobuses.length > 0" class="mb-4 space-y-3">
                  <div
                    v-for="(bus, index) in form.autobuses"
                    :key="index"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-700/50"
                  >
                    <div
                      class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-medium text-slate-600 dark:bg-slate-600 dark:text-slate-300"
                    >
                      {{ index + 1 }}
                    </div>
                    <div class="flex-1">
                      <input
                        v-model="bus.nombre"
                        type="text"
                        placeholder="Nom de l'autobús"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                      />
                    </div>
                    <div class="w-24">
                      <input
                        v-model.number="bus.plazas"
                        type="number"
                        min="1"
                        placeholder="Places"
                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                      />
                    </div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">places</span>
                    <button
                      type="button"
                      @click="eliminarAutobus(index)"
                      class="rounded p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30"
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
                  class="flex items-end gap-3 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50/50 p-4 dark:border-slate-600 dark:bg-slate-700/30"
                >
                  <div class="flex-1">
                    <label
                      class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                    >
                      Nom (opcional)
                    </label>
                    <input
                      v-model="nuevoAutobus.nombre"
                      type="text"
                      :placeholder="`Autobús ${form.autobuses.length + 1}`"
                      class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                    />
                  </div>
                  <div class="w-28">
                    <label
                      class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                    >
                      Places
                    </label>
                    <input
                      v-model.number="nuevoAutobus.plazas"
                      type="number"
                      min="1"
                      class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                    />
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

                <!-- Total plazas -->
                <div class="mt-6 rounded-md bg-blue-50 p-4 dark:bg-blue-900/30">
                  <div class="flex items-center justify-between text-blue-700 dark:text-blue-300">
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
                      <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                        {{ form.autobuses.length }}
                      </p>
                      <p class="text-xs text-blue-600 dark:text-blue-400">Autobusos</p>
                    </div>
                    <div>
                      <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                        {{ totalPlazas }}
                      </p>
                      <p class="text-xs text-blue-600 dark:text-blue-400">Places Totals</p>
                    </div>
                    <div>
                      <p
                        class="text-2xl font-bold"
                        :class="
                          plazasDisponibles >= 0
                            ? 'text-green-600 dark:text-green-400'
                            : 'text-red-600 dark:text-red-400'
                        "
                      >
                        {{ plazasDisponibles }}
                      </p>
                      <p class="text-xs text-blue-600 dark:text-blue-400">Disponibles</p>
                    </div>
                  </div>
                  <p
                    v-if="plazasAutobusVendidas > 0"
                    class="mt-3 text-center text-sm text-blue-600 dark:text-blue-400"
                  >
                    ({{ plazasAutobusVendidas }} places ja venudes)
                  </p>
                </div>
              </div>
            </TabsContent>
          </Tabs>

          <!-- Botones de acción (fuera de los tabs) -->
          <div class="mt-6 flex justify-end gap-4">
            <Button variant="outline" as="a" href="/admin/ediciones"> Cancel·lar </Button>
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Guardant...' : 'Guardar Canvis' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
