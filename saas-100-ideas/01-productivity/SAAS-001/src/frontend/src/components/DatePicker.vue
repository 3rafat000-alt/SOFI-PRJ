<script setup>
import { ref } from 'vue';
import { useUiStore } from '@/stores/uiStore';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  min: { type: String, default: '' },
  max: { type: String, default: '' },
  required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const uiStore = useUiStore();

const onChange = (e) => {
  emit('update:modelValue', e.target.value);
};
</script>

<template>
  <div>
    <label v-if="label" class="input-label">
      {{ label }}
      <span v-if="required" class="text-error-500">*</span>
    </label>
    <input
      type="date"
      :value="modelValue"
      @input="onChange"
      :min="min"
      :max="max"
      class="input"
      :required="required"
    />
  </div>
</template>
