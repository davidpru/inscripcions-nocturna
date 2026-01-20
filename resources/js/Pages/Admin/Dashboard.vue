<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, ClipboardList, Euro, TrendingUp, Users } from 'lucide-vue-next';

interface Stats {
  totalInscripciones: number;
  inscripcionesPagadas: number;
  inscripcionesPendientes: number;
  totalRecaudado: number;
  edicionActual: {
    id: number;
    anio: number;
    inscritos: number;
    limite: number;
  } | null;
}

const props = defineProps<{
  stats: Stats;
}>();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
  }).format(amount);
};
</script>

<template>
  <AdminLayout>
    <Head title="Dashboard Admin" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Dashboard</h1>
          <p class="mt-1 text-slate-600 dark:text-slate-400">
            Panel de administración de la Nocturna Fredes Paüls
          </p>
        </div>

        <!-- Stats Grid -->
        <div class="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Total Inscripciones -->
          <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
            <div class="flex items-center">
              <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                <ClipboardList class="h-6 w-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                  Total Inscripciones
                </p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                  {{ stats.totalInscripciones }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pagadas -->
          <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
            <div class="flex items-center">
              <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                <TrendingUp class="h-6 w-6 text-green-600 dark:text-green-400" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pagadas</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                  {{ stats.inscripcionesPagadas }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pendientes -->
          <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
            <div class="flex items-center">
              <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900">
                <Users class="h-6 w-6 text-amber-600 dark:text-amber-400" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pendientes</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                  {{ stats.inscripcionesPendientes }}
                </p>
              </div>
            </div>
          </div>

          <!-- Total Recaudado -->
          <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
            <div class="flex items-center">
              <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                <Euro class="h-6 w-6 text-purple-600 dark:text-purple-400" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Recaudado</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                  {{ formatCurrency(stats.totalRecaudado) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Edición Actual -->
        <div v-if="stats.edicionActual" class="mb-8">
          <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                  Edición {{ stats.edicionActual.anio }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  {{ stats.edicionActual.inscritos }} / {{ stats.edicionActual.limite }} inscritos
                </p>
              </div>
              <div class="text-right">
                <div class="mb-2 text-2xl font-bold text-slate-900 dark:text-slate-100">
                  {{
                    Math.round((stats.edicionActual.inscritos / stats.edicionActual.limite) * 100)
                  }}%
                </div>
                <div class="h-2 w-48 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                  <div
                    class="h-full rounded-full bg-red-600 transition-all"
                    :style="{
                      width: `${Math.min((stats.edicionActual.inscritos / stats.edicionActual.limite) * 100, 100)}%`,
                    }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-800">
          <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
            Acciones Rápidas
          </h2>
          <div class="flex flex-wrap gap-4">
            <Link href="/admin/inscripciones">
              <Button>
                <ClipboardList class="mr-2 h-4 w-4" />
                Ver Inscripciones
              </Button>
            </Link>
            <Link href="/admin/ediciones">
              <Button variant="outline">
                <Calendar class="mr-2 h-4 w-4" />
                Gestionar Ediciones
              </Button>
            </Link>
            <Link href="/admin/ediciones/create">
              <Button variant="outline"> Nueva Edición </Button>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
