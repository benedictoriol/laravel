<template>
  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
    <div class="text-xs font-medium uppercase tracking-[0.2em] text-stone-500">{{ label }}</div>
    <div class="mt-2 text-2xl font-semibold text-stone-900">{{ value }}</div>
    <div v-if="hint" class="mt-2 text-sm text-stone-500">{{ hint }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  hint: { type: String, default: '' },
});
</script>
