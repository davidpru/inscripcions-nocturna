<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { GroupedBar } from '@unovis/ts';
import { VisAxis, VisGroupedBar, VisTooltip, VisXYContainer } from '@unovis/vue';
import { BarChart3, Calendar, ClipboardList, Euro, TrendingUp, Users } from 'lucide-vue-next';
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

interface EstadistiqueRow {
  total: number;
  importTotal: number;
  importInscr?: number;
  importBus?: number;
  importAsseg?: number;
}

interface Estadistiques {
  totals: EstadistiqueRow;
  publicNoFederat: EstadistiqueRow;
  publicFederat: EstadistiqueRow;
  sociNoFederat: EstadistiqueRow;
  sociFederat: EstadistiqueRow;
  placesBus: { total: number; importTotal: number };
  busTortosa: { total: number; importTotal: number };
  busPauls: { total: number; importTotal: number };
  assegurances: { total: number; importTotal: number };
}

const props = defineProps<{
  stats: Stats;
  inscripcionesPorDia: InscripcionPorDia[];
  estadistiques: Estadistiques | null;
}>();

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
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

// Accés a estadistiques
const estadistiques = computed(() => props.estadistiques);
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

        <!-- Taula d'Estadístiques -->
        <Card v-if="estadistiques" class="mb-8">
          <CardHeader class="rounded-t-lg p-4">
            <CardTitle class="flex items-center gap-2">
              <BarChart3 class="h-5 w-5" />
              Estadístiques
            </CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow class="bg-slate-50">
                    <TableHead class="font-semibold"></TableHead>
                    <TableHead class="text-center font-semibold">Total</TableHead>
                    <TableHead class="text-right font-semibold">Import total</TableHead>
                    <TableHead class="text-right font-semibold">Import inscr.</TableHead>
                    <TableHead class="text-right font-semibold">Import bus</TableHead>
                    <TableHead class="text-right font-semibold">Import asseg.</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <!-- Inscrits (tots) -->
                  <TableRow class="bg-green-200">
                    <TableCell class="font-semibold">Inscrits (tots)</TableCell>
                    <TableCell class="text-center">{{ estadistiques.totals.total }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.totals.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.totals.importInscr ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.totals.importBus ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.totals.importAsseg ?? 0)
                    }}</TableCell>
                  </TableRow>
                  <!-- Públic no federat -->
                  <TableRow class="bg-green-100">
                    <TableCell class="font-medium">Públic no federat</TableCell>
                    <TableCell class="text-center">{{
                      estadistiques.publicNoFederat.total
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicNoFederat.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicNoFederat.importInscr ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicNoFederat.importBus ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicNoFederat.importAsseg ?? 0)
                    }}</TableCell>
                  </TableRow>
                  <!-- Públic federat -->
                  <TableRow class="bg-green-100">
                    <TableCell class="font-medium">Públic federat</TableCell>
                    <TableCell class="text-center">{{
                      estadistiques.publicFederat.total
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicFederat.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicFederat.importInscr ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicFederat.importBus ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.publicFederat.importAsseg ?? 0)
                    }}</TableCell>
                  </TableRow>
                  <!-- Soci no federat -->
                  <TableRow class="bg-green-100">
                    <TableCell class="font-medium">Soci no federat</TableCell>
                    <TableCell class="text-center">{{
                      estadistiques.sociNoFederat.total
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociNoFederat.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociNoFederat.importInscr ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociNoFederat.importBus ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociNoFederat.importAsseg ?? 0)
                    }}</TableCell>
                  </TableRow>
                  <!-- Soci federat -->
                  <TableRow class="bg-green-100">
                    <TableCell class="font-medium">Soci federat</TableCell>
                    <TableCell class="text-center">{{ estadistiques.sociFederat.total }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociFederat.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociFederat.importInscr ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociFederat.importBus ?? 0)
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.sociFederat.importAsseg ?? 0)
                    }}</TableCell>
                  </TableRow>
                  <!-- Places de bus -->
                  <TableRow class="bg-purple-200">
                    <TableCell class="font-medium">Places de bus</TableCell>
                    <TableCell class="text-center">{{ estadistiques.placesBus.total }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.placesBus.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                  </TableRow>
                  <!-- Bus Tortosa -->
                  <TableRow class="bg-purple-100">
                    <TableCell class="pl-6 font-medium">↳ Bus Tortosa</TableCell>
                    <TableCell class="text-center">{{ estadistiques.busTortosa.total }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.busTortosa.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                  </TableRow>
                  <!-- Bus Paüls -->
                  <TableRow class="bg-purple-100">
                    <TableCell class="pl-6 font-medium">↳ Bus Paüls</TableCell>
                    <TableCell class="text-center">{{ estadistiques.busPauls.total }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.busPauls.importTotal)
                    }}</TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                    <TableCell class="text-right"></TableCell>
                  </TableRow>
                  <!-- Assegurances contractades -->
                  <TableRow>
                    <TableCell class="font-medium">Assegurances contr.</TableCell>
                    <TableCell class="text-center">{{
                      estadistiques.assegurances.total
                    }}</TableCell>
                    <TableCell class="text-right">{{
                      formatCurrency(estadistiques.assegurances.importTotal)
                    }}</TableCell>
                    <TableCell class="text-center"></TableCell>
                    <TableCell class="text-center"></TableCell>
                    <TableCell class="text-center"></TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
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
