<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Switch } from '@/components/ui/switch';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Shield, ShieldAlert, ShieldCheck, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type TipoAdministrador = 'super_admin' | 'admin' | 'editor';

interface Administrador {
  id: number;
  nombre: string;
  email: string;
  tipo: TipoAdministrador;
  tipo_nombre: string;
  activo: boolean;
  ultimo_acceso: string | null;
  created_at: string;
}

const props = defineProps<{
  administradores: Administrador[];
}>();

const page = usePage();
const currentUserId = computed(
  () => (page.props.auth as { administrador: Administrador } | undefined)?.administrador?.id
);

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const editingUser = ref<Administrador | null>(null);
const deletingUser = ref<Administrador | null>(null);

const form = useForm({
  nombre: '',
  email: '',
  password: '',
  password_confirmation: '',
  tipo: 'admin' as TipoAdministrador,
  activo: true,
});

const tiposAdministrador = [
  { value: 'super_admin', label: 'Super Admin', icon: ShieldAlert },
  { value: 'admin', label: 'Admin', icon: ShieldCheck },
  { value: 'editor', label: 'Editor', icon: Shield },
] as const;

const getTipoVariant = (tipo: TipoAdministrador) => {
  switch (tipo) {
    case 'super_admin':
      return 'destructive';
    case 'admin':
      return 'default';
    case 'editor':
      return 'secondary';
    default:
      return 'outline';
  }
};

const openCreateDialog = () => {
  editingUser.value = null;
  form.reset();
  form.tipo = 'admin';
  form.activo = true;
  showDialog.value = true;
};

const openEditDialog = (user: Administrador) => {
  editingUser.value = user;
  form.nombre = user.nombre;
  form.email = user.email;
  form.password = '';
  form.password_confirmation = '';
  form.tipo = user.tipo;
  form.activo = user.activo;
  showDialog.value = true;
};

const openDeleteDialog = (user: Administrador) => {
  deletingUser.value = user;
  showDeleteDialog.value = true;
};

const submit = () => {
  if (editingUser.value) {
    form.put(`/admin/usuarios/${editingUser.value.id}`, {
      onSuccess: () => {
        showDialog.value = false;
        form.reset();
      },
    });
  } else {
    form.post('/uec-admin/usuarios', {
      onSuccess: () => {
        showDialog.value = false;
        form.reset();
      },
    });
  }
};

const confirmDelete = () => {
  if (deletingUser.value) {
    router.delete(`/uec-admin/usuarios/${deletingUser.value.id}`, {
      onSuccess: () => {
        showDeleteDialog.value = false;
        deletingUser.value = null;
      },
    });
  }
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ca-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};
</script>

<template>
  <Head title="Usuaris Administradors" />

  <AdminLayout>
    <div class="mx-auto mt-10 max-w-7xl space-y-6 px-4">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900">
            <ShieldCheck class="h-7 w-7" />
            Administradors
          </h1>
          <p class="mt-1 text-slate-500">
            Gestiona els usuaris amb accés al panell d'administració
          </p>
        </div>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nou Administrador
        </Button>
      </div>

      <!-- Table -->
      <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nom</TableHead>
              <TableHead>Correu electrònic</TableHead>
              <TableHead>Tipus</TableHead>
              <TableHead class="text-center">Estat</TableHead>
              <TableHead>Últim accés</TableHead>
              <TableHead class="text-right">Accions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="admin in administradores" :key="admin.id">
              <TableCell class="font-medium">
                {{ admin.nombre }}
                <Badge v-if="admin.id === currentUserId" variant="outline" class="ml-2 text-xs">
                  Tu
                </Badge>
              </TableCell>
              <TableCell>{{ admin.email }}</TableCell>
              <TableCell>
                <Badge :variant="getTipoVariant(admin.tipo)">
                  {{ admin.tipo_nombre }}
                </Badge>
              </TableCell>
              <TableCell class="text-center">
                <Badge
                  :variant="admin.activo ? 'default' : 'secondary'"
                  :class="
                    admin.activo
                      ? 'bg-green-100 text-green-700 hover:bg-green-100'
                      : 'bg-slate-100 text-slate-500'
                  "
                >
                  {{ admin.activo ? 'Actiu' : 'Inactiu' }}
                </Badge>
              </TableCell>
              <TableCell>
                {{ admin.ultimo_acceso ? formatDate(admin.ultimo_acceso) : 'Mai' }}
              </TableCell>
              <TableCell class="space-x-2 text-right">
                <Button variant="outline" size="icon-sm" @click="openEditDialog(admin)">
                  <Pencil class="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  size="icon-sm"
                  class="text-destructive hover:text-destructive"
                  :disabled="admin.id === currentUserId"
                  @click="openDeleteDialog(admin)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
            <TableRow v-if="administradores.length === 0">
              <TableCell colspan="6" class="py-8 text-center text-slate-500">
                No hi ha administradors
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <!-- Dialog crear/editar usuario -->
    <Dialog v-model:open="showDialog">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>
            {{ editingUser ? 'Editar Administrador' : 'Nou Administrador' }}
          </DialogTitle>
          <DialogDescription>
            {{
              editingUser
                ? "Modifica les dades de l'administrador"
                : 'Crea un nou administrador amb accés al panell'
            }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Nom -->
          <div class="space-y-2">
            <Label for="nombre">Nom</Label>
            <Input
              id="nombre"
              v-model="form.nombre"
              type="text"
              placeholder="Nom de l'administrador"
              :class="{ 'border-destructive': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-destructive text-xs">
              {{ form.errors.nombre }}
            </p>
          </div>

          <!-- Email -->
          <div class="space-y-2">
            <Label for="email">Correu electrònic</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="admin@example.com"
              :class="{ 'border-destructive': form.errors.email }"
            />
            <p v-if="form.errors.email" class="text-destructive text-xs">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Tipo de administrador -->
          <div class="space-y-2">
            <Label for="tipo">Tipus d'administrador</Label>
            <Select v-model="form.tipo">
              <SelectTrigger id="tipo" :class="{ 'border-destructive': form.errors.tipo }">
                <SelectValue placeholder="Selecciona un tipus" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="tipo in tiposAdministrador"
                  :key="tipo.value"
                  :value="tipo.value"
                >
                  <div class="flex items-center gap-2">
                    <component :is="tipo.icon" class="h-4 w-4" />
                    {{ tipo.label }}
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.tipo" class="text-destructive text-xs">
              {{ form.errors.tipo }}
            </p>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <Label for="password">
              Contrasenya
              <span v-if="editingUser" class="font-normal text-slate-400">
                (deixa en blanc per mantenir)
              </span>
            </Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              :class="{ 'border-destructive': form.errors.password }"
            />
            <p v-if="form.errors.password" class="text-destructive text-xs">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Confirmar Password -->
          <div class="space-y-2">
            <Label for="password_confirmation">Confirmar contrasenya</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
            />
          </div>

          <!-- Activo (solo en edición) -->
          <div v-if="editingUser" class="flex items-center justify-between rounded-lg border p-3">
            <div>
              <Label for="activo" class="cursor-pointer">Administrador actiu</Label>
              <p class="mt-0.5 text-xs text-slate-500">
                Els administradors inactius no poden accedir al panell
              </p>
            </div>
            <Switch
              id="activo"
              :checked="form.activo"
              :disabled="editingUser?.id === currentUserId"
              @update:checked="form.activo = $event"
            />
          </div>
          <p v-if="form.errors.activo" class="text-destructive text-xs">
            {{ form.errors.activo }}
          </p>

          <DialogFooter class="pt-4">
            <Button type="button" variant="outline" @click="showDialog = false">
              Cancel·lar
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ editingUser ? 'Guardar Canvis' : 'Crear Administrador' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Alert Dialog eliminar usuario -->
    <AlertDialog v-model:open="showDeleteDialog">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Eliminar administrador?</AlertDialogTitle>
          <AlertDialogDescription>
            Estàs segur que vols eliminar l'administrador
            <strong>{{ deletingUser?.nombre }}</strong
            >? Aquesta acció no es pot desfer.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel·lar</AlertDialogCancel>
          <AlertDialogAction
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            @click="confirmDelete"
          >
            Eliminar
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AdminLayout>
</template>
