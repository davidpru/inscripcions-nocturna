<script setup lang="ts">
import { onMounted, ref } from 'vue';

interface Inscripcion {
  id: number;
  precio_total: number;
  participante: {
    nombre: string;
    apellidos: string;
  };
  edicion: {
    anio: number;
  };
}

interface FormInput {
  name: string;
  value: string;
}

const props = defineProps<{
  inscripcion: Inscripcion;
  formAction: string;
  formInputs: FormInput[];
}>();

const formRef = ref<HTMLFormElement | null>(null);

const submitForm = () => {
  if (formRef.value) {
    formRef.value.submit();
  }
};

onMounted(() => {
  // Auto-enviar el formulario al cargar la página
  submitForm();
});
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-50 p-4">
    <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow-lg">
      <div class="mb-6">
        <div
          class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100"
        >
          <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            />
          </svg>
        </div>
        <h1 class="mb-2 text-2xl font-bold text-slate-900">Redirigiendo al pago</h1>
        <p class="text-slate-600">Serás redirigido a la pasarela de pago segura de Redsys...</p>

        <div class="mt-4">
          <button
            @click="submitForm"
            class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline"
          >
            Si no eres redirigido automáticamente, haz clic aquí
          </button>
        </div>
      </div>

      <div class="mb-6 rounded-lg bg-slate-50 p-4">
        <div class="mb-2 flex justify-between text-sm">
          <span class="text-slate-600">Inscripción:</span>
          <span class="font-semibold text-slate-900"
            >{{ props.inscripcion.participante.nombre }}
            {{ props.inscripcion.participante.apellidos }}</span
          >
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-slate-600">Importe:</span>
          <span class="font-bold text-slate-900">{{ props.inscripcion.precio_total }}€</span>
        </div>
      </div>

      <!-- Formulario oculto que se envía automáticamente -->
      <form ref="formRef" :action="props.formAction" method="POST">
        <input
          v-for="input in props.formInputs"
          :key="input.name"
          type="hidden"
          :name="input.name"
          :value="input.value"
        />
      </form>

      <!-- Debug info (temporal) -->
      <div v-if="false" class="mt-4 text-xs text-red-500">
        <p>Debug:</p>
        <p>URL: {{ props.formAction }}</p>
        <p>Inputs: {{ props.formInputs.length }}</p>
      </div>

      <div class="flex items-center justify-center space-x-2 text-sm text-slate-500">
        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <span>Procesando...</span>
      </div>
    </div>
  </div>
</template>
