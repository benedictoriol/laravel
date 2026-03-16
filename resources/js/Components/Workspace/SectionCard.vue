<template>
  <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
    <div v-if="title || description || $slots.actions" class="flex flex-col gap-4 border-b border-stone-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 v-if="title" class="text-lg font-semibold text-stone-900">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-sm leading-6 text-stone-500">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <div :class="title || description || $slots.actions ? 'pt-5' : ''">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
});
</script>
