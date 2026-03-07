<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
  ChevronDown,
  CircleCheck,
  CircleDot,
  Euro,
  Pencil,
  Plus,
  Receipt,
  Ruler,
  Tag,
  Trash2,
  TrendingUp,
  Users,
  Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Edicion {
  id: number;
  anio: number;
  distancia_km: number | null;
}

interface CategoriaGasto {
  id: number;
  nombre: string;
  color: string;
}

interface AdminUser {
  id: number;
  nombre: string;
}

interface Gasto {
  id: number;
  edicion_id: number;
  categorias: CategoriaGasto[];
  titulo: string;
  descripcion: string;
  base_imponible: string;
  tipo_iva: '0' | '4' | '10' | '21';
  total: string;
  presupuestado: boolean;
  presupuestado_por_admin: AdminUser | null;
  aceptado: boolean;
  aceptado_por_admin: AdminUser | null;
  pagado: boolean;
  pagado_por_admin: AdminUser | null;
}

const props = defineProps<{
  ediciones: Edicion[];
  edicionActual: Edicion | null;
  gastos: Gasto[];
  categorias: CategoriaGasto[];
  totalGastos: number;
  totalRecaudado: number;
  costePorKm: number | null;
  costePorCorredor: number | null;
  totalInscrits: number;
}>();

// --- Estado de diálogos ---
const showGastoDialog = ref(false);
const showDeleteDialog = ref(false);
const showCategoriaDialog = ref(false);
const showDeleteCategoriaDialog = ref(false);
const showDistanciaDialog = ref(false);

const editingGasto = ref<Gasto | null>(null);
const deletingGasto = ref<Gasto | null>(null);
const deletingCategoria = ref<CategoriaGasto | null>(null);
const editingCategoria = ref<CategoriaGasto | null>(null);

// --- Formularios ---
const gastoForm = useForm({
  edicion_id: '',
  categoria_ids: [] as number[],
  titulo: '',
  descripcion: '',
  base_imponible: 0,
  tipo_iva: '21' as string,
  presupuestado: false,
  aceptado: false,
  pagado: false,
});

const categoriaForm = useForm({
  nombre: '',
  color: '#6b7280',
});

const distanciaForm = useForm({
  distancia_km: 0,
});

// --- Computed ---
const ivaCalculado = computed(() => {
  const base = Number(gastoForm.base_imponible) || 0;
  const iva = Number(gastoForm.tipo_iva) || 0;
  return ((base * iva) / 100).toFixed(2);
});

const totalCalculado = computed(() => {
  const base = Number(gastoForm.base_imponible) || 0;
  const iva = Number(gastoForm.tipo_iva) || 0;
  return (base * (1 + iva / 100)).toFixed(2);
});

const categoriaLabels = computed(() => {
  const selected = props.categorias.filter((c) => gastoForm.categoria_ids.includes(c.id));
  if (selected.length === 0) return 'Selecciona categories';
  return selected.map((c) => c.nombre).join(', ');
});

// --- Cambio de edición ---
const cambiarEdicion = (edicionId: string) => {
  router.get('/uec-admin/gastos', { edicion_id: edicionId }, { preserveState: false });
};

// --- CRUD Gastos ---
const openCreateGasto = () => {
  editingGasto.value = null;
  gastoForm.reset();
  gastoForm.tipo_iva = '21';
  gastoForm.presupuestado = false;
  gastoForm.aceptado = false;
  gastoForm.pagado = false;
  gastoForm.categoria_ids = [];
  if (props.edicionActual) {
    gastoForm.edicion_id = String(props.edicionActual.id);
  }
  showGastoDialog.value = true;
};

const openEditGasto = (gasto: Gasto) => {
  editingGasto.value = gasto;
  gastoForm.edicion_id = String(gasto.edicion_id);
  gastoForm.categoria_ids = gasto.categorias.map((c) => c.id);
  gastoForm.titulo = gasto.titulo;
  gastoForm.descripcion = gasto.descripcion;
  gastoForm.base_imponible = Number(gasto.base_imponible);
  gastoForm.tipo_iva = gasto.tipo_iva;
  gastoForm.presupuestado = gasto.presupuestado;
  gastoForm.aceptado = gasto.aceptado;
  gastoForm.pagado = gasto.pagado;
  showGastoDialog.value = true;
};

const submitGasto = () => {
  if (editingGasto.value) {
    gastoForm.put(`/uec-admin/gastos/${editingGasto.value.id}`, {
      onSuccess: () => {
        showGastoDialog.value = false;
      },
    });
  } else {
    gastoForm.post('/uec-admin/gastos', {
      onSuccess: () => {
        showGastoDialog.value = false;
      },
    });
  }
};

const confirmDeleteGasto = (gasto: Gasto) => {
  deletingGasto.value = gasto;
  showDeleteDialog.value = true;
};

const deleteGasto = () => {
  if (!deletingGasto.value) return;
  router.delete(`/uec-admin/gastos/${deletingGasto.value.id}`, {
    onSuccess: () => {
      showDeleteDialog.value = false;
    },
  });
};

// --- CRUD Categorías ---
const openCreateCategoria = () => {
  editingCategoria.value = null;
  categoriaForm.reset();
  categoriaForm.color = '#6b7280';
  showCategoriaDialog.value = true;
};

const openEditCategoria = (cat: CategoriaGasto) => {
  editingCategoria.value = cat;
  categoriaForm.nombre = cat.nombre;
  categoriaForm.color = cat.color;
  showCategoriaDialog.value = true;
};

const submitCategoria = () => {
  if (editingCategoria.value) {
    categoriaForm.put(`/uec-admin/gastos/categorias/${editingCategoria.value.id}`, {
      onSuccess: () => {
        showCategoriaDialog.value = false;
      },
    });
  } else {
    categoriaForm.post('/uec-admin/gastos/categorias', {
      onSuccess: () => {
        showCategoriaDialog.value = false;
      },
    });
  }
};

const confirmDeleteCategoria = (cat: CategoriaGasto) => {
  deletingCategoria.value = cat;
  showDeleteCategoriaDialog.value = true;
};

const deleteCategoria = () => {
  if (!deletingCategoria.value) return;
  router.delete(`/uec-admin/gastos/categorias/${deletingCategoria.value.id}`, {
    onSuccess: () => {
      showDeleteCategoriaDialog.value = false;
    },
  });
};

// --- Distancia ---
const openDistanciaDialog = () => {
  distanciaForm.distancia_km = props.edicionActual?.distancia_km ?? 0;
  showDistanciaDialog.value = true;
};

const submitDistancia = () => {
  if (!props.edicionActual) return;
  distanciaForm.put(`/uec-admin/gastos/distancia/${props.edicionActual.id}`, {
    onSuccess: () => {
      showDistanciaDialog.value = false;
    },
  });
};

const formatIva = (tipo: string) => {
  return tipo === '0' ? 'Exempt' : `${tipo}%`;
};

const formatEur = (value: number | string) => {
  return Number(value).toFixed(2) + ' €';
};

const presetColors = [
  '#ef4444',
  '#f97316',
  '#f59e0b',
  '#84cc16',
  '#22c55e',
  '#14b8a6',
  '#06b6d4',
  '#3b82f6',
  '#6366f1',
  '#8b5cf6',
  '#a855f7',
  '#d946ef',
  '#ec4899',
  '#f43f5e',
  '#6b7280',
];
</script>

<template>
  <AdminLayout>
    <Head title="Despeses" />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Gestor de Despeses</h1>
          <p class="text-sm text-slate-500">Control de despeses per edició</p>
        </div>
        <div class="flex items-center gap-2">
          <Select
            :model-value="edicionActual ? String(edicionActual.id) : undefined"
            @update:model-value="cambiarEdicion"
          >
            <SelectTrigger class="w-35">
              <SelectValue placeholder="Edició" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="ed in ediciones" :key="ed.id" :value="String(ed.id)">
                {{ ed.anio }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <template v-if="edicionActual">
        <!-- Cards resumen -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <!-- Total recaudado inscripciones -->
          <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white p-2">
                <Euro class="h-4 w-4 text-purple-600" />
              </div>
              <div>
                <p class="text-xs font-medium text-purple-700">Inscripcions</p>
                <p class="text-lg font-bold text-purple-900">{{ formatEur(totalRecaudado) }}</p>
              </div>
            </div>
          </div>

          <!-- Total gastos -->
          <div class="rounded-lg border border-red-200 bg-red-50 p-3 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white p-2">
                <Receipt class="h-4 w-4 text-red-600" />
              </div>
              <div>
                <p class="text-xs font-medium text-red-700">Despeses</p>
                <p class="text-lg font-bold text-red-900">{{ formatEur(totalGastos) }}</p>
              </div>
            </div>
          </div>

          <!-- Cost per km -->
          <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white p-2">
                <Ruler class="h-4 w-4 text-blue-600" />
              </div>
              <div>
                <p class="text-xs font-medium text-blue-700">Cost per km</p>
                <p v-if="costePorKm !== null" class="text-lg font-bold text-blue-900">
                  {{ formatEur(costePorKm) }}
                </p>
                <button
                  v-else
                  class="text-sm text-blue-600 underline hover:text-blue-800"
                  @click="openDistanciaDialog"
                >
                  Definir distància
                </button>
              </div>
            </div>
            <p v-if="edicionActual.distancia_km" class="mt-1 text-xs text-blue-400">
              {{ edicionActual.distancia_km }} km ·
              <button class="text-blue-500 underline" @click="openDistanciaDialog">editar</button>
            </p>
          </div>

          <!-- Cost per corredor -->
          <div class="rounded-lg border border-green-200 bg-green-50 p-3 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white p-2">
                <Users class="h-4 w-4 text-green-600" />
              </div>
              <div>
                <p class="text-xs font-medium text-green-700">Cost per corredor</p>
                <p v-if="costePorCorredor !== null" class="text-lg font-bold text-green-900">
                  {{ formatEur(costePorCorredor) }}
                </p>
                <p v-else class="text-xs text-green-400">Sense inscrits</p>
              </div>
            </div>
            <p v-if="totalInscrits > 0" class="mt-1 text-xs text-green-800">
              {{ totalInscrits }} inscrits pagats
            </p>
          </div>

          <!-- Beneficis reals -->
          <div
            class="rounded-lg border p-3 shadow-sm"
            :class="
              totalRecaudado - totalGastos >= 0
                ? 'border-emerald-200 bg-emerald-50'
                : 'border-orange-200 bg-orange-50'
            "
          >
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-white p-2">
                <TrendingUp
                  class="h-4 w-4"
                  :class="
                    totalRecaudado - totalGastos >= 0 ? 'text-emerald-600' : 'text-orange-600'
                  "
                />
              </div>
              <div>
                <p
                  class="text-xs"
                  :class="
                    totalRecaudado - totalGastos >= 0
                      ? 'font-medium text-emerald-700'
                      : 'font-medium text-orange-700'
                  "
                >
                  Beneficis reals
                </p>
                <p
                  class="text-lg font-bold"
                  :class="
                    totalRecaudado - totalGastos >= 0 ? 'text-emerald-900' : 'text-orange-900'
                  "
                >
                  {{ formatEur(totalRecaudado - totalGastos) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="mb-4 flex items-center gap-2">
          <Button @click="openCreateGasto" size="" class="gap-1.5">
            <Plus class="h-4 w-4" />
            Nova despesa
          </Button>
          <Button @click="openCreateCategoria" size="" variant="outline" class="gap-1.5">
            <Tag class="h-4 w-4" />
            Nova categoria
          </Button>
        </div>

        <!-- Tabla de gastos -->
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Categoria
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Títol / Descripció
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Base
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  IVA
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Total
                </th>
                <th
                  class="px-4 py-3 text-center text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Estat
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Accions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="gasto in gastos" :key="gasto.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="cat in gasto.categorias"
                      :key="cat.id"
                      class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :style="{ backgroundColor: cat.color + '20', color: cat.color }"
                    >
                      {{ cat.nombre }}
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm font-medium text-slate-900">{{ gasto.titulo }}</p>
                  <p v-if="gasto.descripcion" class="mt-0.5 text-xs text-slate-500">
                    {{ gasto.descripcion }}
                  </p>
                </td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-slate-600">
                  {{ formatEur(gasto.base_imponible) }}
                </td>
                <td class="px-4 py-3 text-right text-sm whitespace-nowrap text-slate-600">
                  {{ formatIva(gasto.tipo_iva) }}
                </td>
                <td
                  class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap text-slate-900"
                >
                  {{ formatEur(gasto.total) }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-center gap-2">
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="
                        gasto.presupuestado
                          ? 'bg-blue-50 text-blue-700'
                          : 'bg-slate-50 text-slate-400'
                      "
                      :title="
                        gasto.presupuestado_por_admin
                          ? `Presupuestat per ${gasto.presupuestado_por_admin.nombre}`
                          : ''
                      "
                    >
                      <CircleDot class="h-3 w-3" />
                      P
                    </span>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="
                        gasto.aceptado ? 'bg-amber-50 text-amber-700' : 'bg-slate-50 text-slate-400'
                      "
                      :title="
                        gasto.aceptado_por_admin
                          ? `Acceptat per ${gasto.aceptado_por_admin.nombre}`
                          : ''
                      "
                    >
                      <CircleCheck class="h-3 w-3" />
                      A
                    </span>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="
                        gasto.pagado ? 'bg-green-50 text-green-700' : 'bg-slate-50 text-slate-400'
                      "
                      :title="
                        gasto.pagado_por_admin ? `Pagat per ${gasto.pagado_por_admin.nombre}` : ''
                      "
                    >
                      <Wallet class="h-3 w-3" />
                      $
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <Button variant="ghost" size="sm" @click="openEditGasto(gasto)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="text-red-600 hover:text-red-700"
                      @click="confirmDeleteGasto(gasto)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
              <tr v-if="gastos.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">
                  No hi ha despeses registrades per a aquesta edició.
                </td>
              </tr>
            </tbody>
            <!-- Footer con total -->
            <tfoot v-if="gastos.length > 0" class="bg-slate-50">
              <tr>
                <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">
                  Total
                </td>
                <td class="px-4 py-3 text-right text-sm font-bold whitespace-nowrap text-slate-900">
                  {{ formatEur(totalGastos) }}
                </td>
                <td></td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Categorías existentes -->
        <div class="mt-8">
          <h2 class="mb-3 text-lg font-semibold text-slate-800">Categories</h2>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="cat in categorias"
              :key="cat.id"
              class="flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm"
              :style="{ borderColor: cat.color + '40', backgroundColor: cat.color + '10' }"
            >
              <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: cat.color }" />
              <button class="text-slate-700 hover:underline" @click="openEditCategoria(cat)">
                {{ cat.nombre }}
              </button>
              <button
                class="ml-1 text-slate-400 hover:text-red-500"
                @click="confirmDeleteCategoria(cat)"
                title="Eliminar"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
            <div v-if="categorias.length === 0" class="text-sm text-slate-400">
              No hi ha categories. Crea'n una per començar.
            </div>
          </div>
        </div>
      </template>

      <div
        v-else
        class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500"
      >
        No hi ha edicions disponibles.
      </div>
    </div>

    <!-- Dialog: Crear/Editar Gasto -->
    <Dialog v-model:open="showGastoDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{{ editingGasto ? 'Editar despesa' : 'Nova despesa' }}</DialogTitle>
          <DialogDescription>
            {{
              editingGasto
                ? 'Modifica les dades de la despesa.'
                : 'Afegeix una nova línia de despesa.'
            }}
          </DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitGasto" class="space-y-4">
          <div class="space-y-2">
            <Label>Categories</Label>
            <Popover>
              <PopoverTrigger as-child>
                <Button variant="outline" class="w-full justify-between font-normal" type="button">
                  <span class="truncate">{{ categoriaLabels }}</span>
                  <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent class="w-[--reka-popover-trigger-width] p-2" align="start">
                <div class="space-y-1">
                  <label
                    v-for="cat in categorias"
                    :key="cat.id"
                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-slate-100"
                  >
                    <Checkbox
                      :model-value="gastoForm.categoria_ids.includes(cat.id)"
                      @update:model-value="
                        (val: boolean | 'indeterminate') => {
                          if (val === true) {
                            gastoForm.categoria_ids = [...gastoForm.categoria_ids, cat.id];
                          } else {
                            gastoForm.categoria_ids = gastoForm.categoria_ids.filter(
                              (id) => id !== cat.id
                            );
                          }
                        }
                      "
                    />
                    <span
                      class="h-2.5 w-2.5 rounded-full"
                      :style="{ backgroundColor: cat.color }"
                    />
                    {{ cat.nombre }}
                  </label>
                  <p v-if="categorias.length === 0" class="px-2 py-1 text-xs text-slate-400">
                    No hi ha categories.
                  </p>
                </div>
              </PopoverContent>
            </Popover>
            <p v-if="gastoForm.errors.categoria_ids" class="text-sm text-red-500">
              {{ gastoForm.errors.categoria_ids }}
            </p>
          </div>

          <div class="space-y-2">
            <Label>Títol</Label>
            <Input v-model="gastoForm.titulo" placeholder="Títol de la despesa" />
            <p v-if="gastoForm.errors.titulo" class="text-sm text-red-500">
              {{ gastoForm.errors.titulo }}
            </p>
          </div>

          <div class="space-y-2">
            <Label>Descripció</Label>
            <textarea
              v-model="gastoForm.descripcion"
              placeholder="Detalls addicionals (opcional)"
              rows="3"
              class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
            />
            <p v-if="gastoForm.errors.descripcion" class="text-sm text-red-500">
              {{ gastoForm.errors.descripcion }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Base imposable (€)</Label>
              <Input v-model.number="gastoForm.base_imponible" type="number" step="0.01" min="0" />
              <p v-if="gastoForm.errors.base_imponible" class="text-sm text-red-500">
                {{ gastoForm.errors.base_imponible }}
              </p>
            </div>
            <div class="space-y-2">
              <Label>Tipus d'IVA</Label>
              <Select v-model="gastoForm.tipo_iva">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">Exempt (0%)</SelectItem>
                  <SelectItem value="4">Superreduït (4%)</SelectItem>
                  <SelectItem value="10">Reduït (10%)</SelectItem>
                  <SelectItem value="21">General (21%)</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="gastoForm.errors.tipo_iva" class="text-sm text-red-500">
                {{ gastoForm.errors.tipo_iva }}
              </p>
            </div>
          </div>

          <!-- Preview cálculo -->
          <div class="rounded-md bg-slate-50 p-3 text-sm">
            <div class="flex justify-between text-slate-600">
              <span>Base imposable</span>
              <span>{{ Number(gastoForm.base_imponible || 0).toFixed(2) }} €</span>
            </div>
            <div class="flex justify-between text-slate-600">
              <span>IVA ({{ gastoForm.tipo_iva }}%)</span>
              <span>{{ ivaCalculado }} €</span>
            </div>
            <div
              class="mt-1 flex justify-between border-t border-slate-200 pt-1 font-semibold text-slate-900"
            >
              <span>Total</span>
              <span>{{ totalCalculado }} €</span>
            </div>
          </div>

          <!-- Checkboxes de estado -->
          <div class="space-y-3">
            <Label class="text-sm font-medium">Estat</Label>
            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 text-sm">
                <Checkbox
                  :model-value="gastoForm.presupuestado"
                  @update:model-value="
                    (val: boolean | 'indeterminate') => (gastoForm.presupuestado = val === true)
                  "
                />
                Presupuestat
              </label>
              <label class="flex items-center gap-2 text-sm">
                <Checkbox
                  :model-value="gastoForm.aceptado"
                  @update:model-value="
                    (val: boolean | 'indeterminate') => (gastoForm.aceptado = val === true)
                  "
                />
                Acceptat
              </label>
              <label class="flex items-center gap-2 text-sm">
                <Checkbox
                  :model-value="gastoForm.pagado"
                  @update:model-value="
                    (val: boolean | 'indeterminate') => (gastoForm.pagado = val === true)
                  "
                />
                Pagat
              </label>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="showGastoDialog = false">
              Cancel·lar
            </Button>
            <Button type="submit" :disabled="gastoForm.processing">
              {{ editingGasto ? 'Actualitzar' : 'Afegir' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Dialog: Confirmar eliminar gasto -->
    <Dialog v-model:open="showDeleteDialog">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Eliminar despesa</DialogTitle>
          <DialogDescription>
            Segur que vols eliminar "{{ deletingGasto?.titulo }}"? Aquesta acció no es pot desfer.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showDeleteDialog = false">Cancel·lar</Button>
          <Button variant="destructive" @click="deleteGasto">Eliminar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Dialog: Crear/Editar categoría -->
    <Dialog v-model:open="showCategoriaDialog">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ editingCategoria ? 'Editar categoria' : 'Nova categoria' }}</DialogTitle>
          <DialogDescription>{{
            editingCategoria
              ? 'Modifica el nom o el color de la categoria.'
              : 'Afegeix una nova etiqueta per classificar despeses.'
          }}</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitCategoria" class="space-y-4">
          <div class="space-y-2">
            <Label>Nom de la categoria</Label>
            <Input v-model="categoriaForm.nombre" placeholder="Ex: Logística" />
            <p v-if="categoriaForm.errors.nombre" class="text-sm text-red-500">
              {{ categoriaForm.errors.nombre }}
            </p>
          </div>
          <div class="space-y-2">
            <Label>Color</Label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="c in presetColors"
                :key="c"
                type="button"
                class="h-7 w-7 rounded-full border-2 transition-transform hover:scale-110"
                :class="
                  categoriaForm.color === c ? 'scale-110 border-slate-900' : 'border-transparent'
                "
                :style="{ backgroundColor: c }"
                @click="categoriaForm.color = c"
              />
            </div>
            <p v-if="categoriaForm.errors.color" class="text-sm text-red-500">
              {{ categoriaForm.errors.color }}
            </p>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showCategoriaDialog = false">
              Cancel·lar
            </Button>
            <Button type="submit" :disabled="categoriaForm.processing">{{
              editingCategoria ? 'Actualitzar' : 'Afegir'
            }}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Dialog: Confirmar eliminar categoría -->
    <Dialog v-model:open="showDeleteCategoriaDialog">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Eliminar categoria</DialogTitle>
          <DialogDescription>
            Segur que vols eliminar "{{ deletingCategoria?.nombre }}"? No es pot eliminar si té
            despeses associades.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="showDeleteCategoriaDialog = false">Cancel·lar</Button>
          <Button variant="destructive" @click="deleteCategoria">Eliminar</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Dialog: Distancia km -->
    <Dialog v-model:open="showDistanciaDialog">
      <DialogContent class="sm:max-w-sm">
        <DialogHeader>
          <DialogTitle>Distància de la cursa</DialogTitle>
          <DialogDescription>Defineix els quilòmetres per calcular el cost/km.</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="submitDistancia" class="space-y-4">
          <div class="space-y-2">
            <Label>Distància (km)</Label>
            <Input v-model.number="distanciaForm.distancia_km" type="number" step="0.1" min="0.1" />
            <p v-if="distanciaForm.errors.distancia_km" class="text-sm text-red-500">
              {{ distanciaForm.errors.distancia_km }}
            </p>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="showDistanciaDialog = false">
              Cancel·lar
            </Button>
            <Button type="submit" :disabled="distanciaForm.processing">Guardar</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AdminLayout>
</template>
