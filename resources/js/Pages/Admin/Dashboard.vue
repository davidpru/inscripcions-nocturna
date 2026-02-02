<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { GroupedBar } from '@unovis/ts';
import { VisAxis, VisGroupedBar, VisTooltip, VisXYContainer } from '@unovis/vue';
import { Calendar, ClipboardList, Euro, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';

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

interface InscripcionPorDia {
  fecha: string;
  total: number;
}

const props = defineProps<{
  stats: Stats;
  inscripcionesPorDia: InscripcionPorDia[];
}>();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
  }).format(amount);
};

// Dades per la gràfica de barres per dia
const chartData = computed(() => {
  return props.inscripcionesPorDia.map((item, index) => {
    return {
      x: index,
      y: item.total,
      label: new Date(item.fecha).toLocaleDateString('ca-ES', {
        day: 'numeric',
        month: 'numeric',
      }),
    };
  });
});

// Configuració del tooltip amb triggers
const tooltipTriggers = {
  [GroupedBar.selectors.bar]: (d: { x: number; y: number }) => {
    const item = chartData.value[d.x];
    return `
      <div style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);">
        <div style="color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">Data</div>
        <div style="color: white; font-size: 14px; font-weight: 600; margin-bottom: 8px;">${item?.label || ''}</div>
        <div style="display: flex; align-items: baseline; gap: 8px;">
          <span style="color: #f87171; font-size: 24px; font-weight: 700;">${d.y}</span>
          <span style="color: #cbd5e1; font-size: 14px;">${d.y === 1 ? 'inscripció' : 'inscripcions'}</span>
        </div>
      </div>
    `;
  },
};
</script>

<template>
  <AdminLayout>
    <Head title="Dashboard Admin" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
          <p class="mt-1 text-slate-600">Panel de administración de la Nocturna Fredes Paüls</p>
        </div>

        <!-- Stats Grid -->
        <div class="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Total Inscripciones -->
          <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
              <div class="rounded-full bg-blue-100 p-3">
                <ClipboardList class="h-6 w-6 text-blue-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Total Inscripciones</p>
                <p class="text-2xl font-bold text-slate-900">
                  {{ stats.totalInscripciones }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pagadas -->
          <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
              <div class="rounded-full bg-green-100 p-3">
                <TrendingUp class="h-6 w-6 text-green-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Pagadas</p>
                <p class="text-2xl font-bold text-slate-900">
                  {{ stats.inscripcionesPagadas }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pendientes -->
          <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
              <div class="rounded-full bg-amber-100 p-3">
                <Users class="h-6 w-6 text-amber-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Pendientes</p>
                <p class="text-2xl font-bold text-slate-900">
                  {{ stats.inscripcionesPendientes }}
                </p>
              </div>
            </div>
          </div>

          <!-- Total Recaudado -->
          <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
              <div class="rounded-full bg-purple-100 p-3">
                <Euro class="h-6 w-6 text-purple-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-slate-500">Recaudado</p>
                <p class="text-2xl font-bold text-slate-900">
                  {{ formatCurrency(stats.totalRecaudado) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Edición Actual -->
        <div v-if="stats.edicionActual" class="mb-8">
          <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">
                  Edición {{ stats.edicionActual.anio }}
                </h2>
                <p class="text-sm text-slate-500">
                  {{ stats.edicionActual.inscritos }} / {{ stats.edicionActual.limite }} inscritos
                </p>
              </div>
              <div class="text-right">
                <div class="mb-2 text-2xl font-bold text-slate-900">
                  {{
                    Math.round((stats.edicionActual.inscritos / stats.edicionActual.limite) * 100)
                  }}%
                </div>
                <div class="h-2 w-48 overflow-hidden rounded-full bg-slate-200">
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

        <!-- Gràfica d'Inscripcions -->
        <Card v-if="chartData.length > 0" class="mb-8">
          <CardHeader>
            <CardTitle>Inscripcions per dia</CardTitle>
            <CardDescription>Nombre d'inscripcions realitzades cada dia</CardDescription>
          </CardHeader>
          <CardContent>
            <VisXYContainer :data="chartData" :height="300">
              <VisGroupedBar
                :x="(d: { x: number; y: number; label: string }) => d.x"
                :y="(d: { x: number; y: number; label: string }) => d.y"
                color="#ef4444"
              />
              <VisAxis type="x" :tickFormat="(i: number) => chartData[i]?.label || ''" />
              <VisAxis type="y" label="Inscripcions" />
              <VisTooltip :triggers="tooltipTriggers" />
            </VisXYContainer>
          </CardContent>
        </Card>

        <!-- Quick Actions -->
        <div class="rounded-lg bg-white p-6 shadow">
          <h2 class="mb-4 text-lg font-semibold text-slate-900">Acciones Rápidas</h2>
          <div class="flex flex-wrap gap-4">
            <Link href="/uec-admin/inscripciones">
              <Button>
                <ClipboardList class="mr-2 h-4 w-4" />
                Ver Inscripciones
              </Button>
            </Link>
            <Link href="/uec-admin/ediciones">
              <Button variant="outline">
                <Calendar class="mr-2 h-4 w-4" />
                Gestionar Ediciones
              </Button>
            </Link>
            <Link href="/uec-admin/ediciones/create">
              <Button variant="outline"> Nueva Edición </Button>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
