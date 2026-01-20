<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Check } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
import { computed } from 'vue';

const props = defineProps<{
  defaultValue?: boolean | 'indeterminate';
  modelValue?: boolean | 'indeterminate' | null;
  disabled?: boolean;
  value?: string;
  id?: string;
  asChild?: boolean;
  as?: any;
  name?: string;
  required?: boolean;
  class?: any;
}>();

const emits = defineEmits<{
  'update:modelValue': [value: boolean | 'indeterminate'];
}>();

const checkboxValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value as boolean | 'indeterminate'),
});
</script>

<template>
  <CheckboxRoot
    v-model="checkboxValue"
    :id="id"
    :name="name"
    :disabled="disabled"
    :required="required"
    :value="value"
    :default-value="defaultValue"
    :class="
      cn(
        'peer ring-offset-background focus-visible:ring-ring data-[state=checked]:bg-primary data-[state=checked]:border-primary data-[state=checked]:text-primary-foreground h-4 w-4 shrink-0 rounded-sm border-2 border-slate-300 bg-transparent focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600',
        props.class
      )
    "
  >
    <CheckboxIndicator class="flex items-center justify-center text-current">
      <Check class="h-4 w-4" />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
