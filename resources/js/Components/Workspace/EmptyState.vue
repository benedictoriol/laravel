<template>
  <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50/60 px-4 py-8 text-sm text-stone-500">
    <div class="font-medium text-stone-700">{{ title }}</div>
    <div v-if="description" class="mt-2 leading-6">{{ description }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
