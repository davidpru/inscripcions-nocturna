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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Bus, Pencil, Plus, RotateCcw, Ticket, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Edicion {
  id: number;
  anio: number;
}

interface Cupon {
  id: number;
  codigo: string;
  descripcion: string | null;
  edicion_id: number;
  edicion: Edicion;
  usos_maximos: number;
  usos_actuales: number;
  incluye_autobus: boolean;
  activo: boolean;
  fecha_expiracion: string | null;
}

const props = defineProps<{
  cupones: Cupon[];
  ediciones: Edicion[];
}>();

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const editingCupon = ref<Cupon | null>(null);
const deletingCupon = ref<Cupon | null>(null);

const form = useForm({
  codigo: '',
  descripcion: '',
  edicion_id: '',
  usos_maximos: 1,
  incluye_autobus: false,
  activo: true,
  fecha_expiracion: '',
});

const openCreateDialog = () => {
  editingCupon.value = null;
  form.reset();
  // Establecer valores por defecto después del reset
  form.activo = true;
  form.incluye_autobus = false;
  form.usos_maximos = 1;
  // Preseleccionar edición activa si existe
  const edicionActiva = props.ediciones.find((e) => e.anio === new Date().getFullYear());
  if (edicionActiva) {
    form.edicion_id = String(edicionActiva.id);
  } else if (props.ediciones.length > 0) {
    form.edicion_id = String(props.ediciones[0].id);
  }
  showDialog.value = true;
};

const openEditDialog = (cupon: Cupon) => {
  editingCupon.value = cupon;
  form.codigo = cupon.codigo;
  form.descripcion = cupon.descripcion || '';
  form.edicion_id = String(cupon.edicion_id);
  form.usos_maximos = cupon.usos_maximos;
  form.incluye_autobus = cupon.incluye_autobus;
  form.activo = cupon.activo;
  form.fecha_expiracion = cupon.fecha_expiracion || '';
  showDialog.value = true;
};

const openDeleteDialog = (cupon: Cupon) => {
  deletingCupon.value = cupon;
  showDeleteDialog.value = true;
};

const submitForm = () => {
  if (editingCupon.value) {
    form.put(`/admin/cupones/${editingCupon.value.id}`, {
      onSuccess: () => {
        showDialog.value = false;
        form.reset();
      },
    });
  } else {
    form.post('/admin/cupones', {
      onSuccess: () => {
        showDialog.value = false;
        form.reset();
      },
    });
  }
};

const deleteCupon = () => {
  if (deletingCupon.value) {
    router.delete(`/admin/cupones/${deletingCupon.value.id}`, {
      onSuccess: () => {
        showDeleteDialog.value = false;
        deletingCupon.value = null;
      },
    });
  }
};

const resetUsos = (cupon: Cupon) => {
  if (confirm(`¿Resetear los usos del cupón ${cupon.codigo}?`)) {
    router.post(`/admin/cupones/${cupon.id}/reset-usos`);
  }
};

const formatearFecha = (fecha: string | null) => {
  if (!fecha) return '-';
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const getUsosClass = (cupon: Cupon) => {
  if (cupon.usos_actuales >= cupon.usos_maximos) {
    return 'text-red-600 font-semibold';
  }
  if (cupon.usos_actuales > cupon.usos_maximos * 0.8) {
    return 'text-orange-600';
  }
  return 'text-slate-900';
};
</script>

<template>
  <AdminLayout>
    <Head title="Gestión de Cupones" />

    <div class="px-4 py-8">
      <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">Gestión de Cupones</h1>
            <p class="mt-1 text-slate-600">Administra los cupones de descuento</p>
          </div>
          <Button @click="openCreateDialog">
            <Plus class="mr-2 h-4 w-4" />
            Nuevo Cupón
          </Button>
        </div>

        <!-- Lista de Cupones -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Código
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Edición
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Usos
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Incluye
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Expira
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Estado
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium tracking-wider text-slate-500 uppercase"
                >
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="cupon in cupones" :key="cupon.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <Ticket class="h-4 w-4 text-slate-400" />
                    <span class="font-mono font-semibold text-slate-900">{{ cupon.codigo }}</span>
                  </div>
                  <div v-if="cupon.descripcion" class="text-xs text-slate-500">
                    {{ cupon.descripcion }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-900">{{ cupon.edicion.anio }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div :class="getUsosClass(cupon)" class="text-sm">
                    {{ cupon.usos_actuales }} / {{ cupon.usos_maximos }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex gap-2">
                    <span class="text-xs text-slate-600">Inscripción</span>
                    <span
                      v-if="cupon.incluye_autobus"
                      class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"
                    >
                      <Bus class="h-3 w-3" />
                      + Bus
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-900">
                    {{ formatearFecha(cupon.fecha_expiracion) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="
                      cupon.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    "
                    class="inline-flex rounded-full px-2 text-xs leading-5 font-semibold"
                  >
                    {{ cupon.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                  <div class="flex justify-end gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      @click="resetUsos(cupon)"
                      title="Resetear usos"
                    >
                      <RotateCcw class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="openEditDialog(cupon)">
                      <Pencil class="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      class="text-red-600 hover:text-red-700"
                      @click="openDeleteDialog(cupon)"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="cupones.length === 0" class="py-12 text-center">
            <Ticket class="mx-auto h-12 w-12 text-slate-300" />
            <p class="mt-4 text-slate-500">No hay cupones creados</p>
            <Button class="mt-4" @click="openCreateDialog"> Crear Primer Cupón </Button>
          </div>
        </div>

        <!-- Dialog Crear/Editar -->
        <Dialog v-model:open="showDialog">
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>
                {{ editingCupon ? 'Editar Cupón' : 'Nuevo Cupón' }}
              </DialogTitle>
              <DialogDescription>
                {{
                  editingCupon ? 'Modifica los datos del cupón' : 'Crea un nuevo cupón de descuento'
                }}
              </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="codigo">Código *</Label>
                  <Input
                    id="codigo"
                    v-model="form.codigo"
                    placeholder="DESCUENTO50"
                    class="uppercase"
                    required
                  />
                  <p v-if="form.errors.codigo" class="text-sm text-red-500">
                    {{ form.errors.codigo }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="edicion_id">Edición *</Label>
                  <Select v-model="form.edicion_id" required>
                    <SelectTrigger>
                      <SelectValue placeholder="Selecciona edición" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="edicion in ediciones"
                        :key="edicion.id"
                        :value="String(edicion.id)"
                      >
                        {{ edicion.anio }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="space-y-2">
                <Label for="descripcion">Descripción</Label>
                <Input
                  id="descripcion"
                  v-model="form.descripcion"
                  placeholder="Premio sorteo Instagram..."
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="usos_maximos">Usos máximos *</Label>
                  <Input
                    id="usos_maximos"
                    v-model.number="form.usos_maximos"
                    type="number"
                    min="1"
                    required
                  />
                </div>

                <div class="space-y-2">
                  <Label for="fecha_expiracion">Fecha expiración</Label>
                  <Input id="fecha_expiracion" v-model="form.fecha_expiracion" type="date" />
                </div>
              </div>

              <div class="space-y-3 rounded-lg border p-4">
                <p class="text-sm font-medium text-slate-700">El cupón descuenta:</p>

                <ul class="list-disc space-y-1 pl-4 text-sm text-slate-600">
                  <li>
                    Coste de la licencia federativa (diferencia entre tarifa no federado y federado)
                  </li>
                </ul>

                <div class="flex items-center space-x-2 pt-2">
                  <Checkbox
                    id="incluye_autobus"
                    :model-value="form.incluye_autobus"
                    @update:model-value="(val) => (form.incluye_autobus = val === true)"
                  />
                  <Label
                    for="incluye_autobus"
                    class="flex cursor-pointer items-center gap-2 font-normal"
                  >
                    <Bus class="h-4 w-4 text-blue-600" />
                    También incluye autobús
                  </Label>
                </div>

                <p class="text-xs text-slate-500">
                  El cupón solo funciona para participantes NO federados. No descuenta el seguro de
                  anulación.
                </p>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox
                  id="activo"
                  :model-value="form.activo"
                  @update:model-value="(val) => (form.activo = val === true)"
                />
                <Label for="activo" class="cursor-pointer font-normal">Cupón activo</Label>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" @click="showDialog = false">
                  Cancelar
                </Button>
                <Button type="submit" :disabled="form.processing">
                  {{ editingCupon ? 'Guardar' : 'Crear' }}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        <!-- Dialog Eliminar -->
        <Dialog v-model:open="showDeleteDialog">
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Eliminar Cupón</DialogTitle>
              <DialogDescription>
                ¿Estás seguro de que quieres eliminar el cupón
                <strong>{{ deletingCupon?.codigo }}</strong
                >? Esta acción no se puede deshacer.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button variant="outline" @click="showDeleteDialog = false"> Cancelar </Button>
              <Button variant="destructive" @click="deleteCupon"> Eliminar </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  </AdminLayout>
</template>
