<template>
  <header class="rounded-3xl border border-stone-200 bg-white px-4 py-4 shadow-sm sm:px-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div class="min-w-0">
        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-stone-500">{{ eyebrow }}</div>
        <h1 class="mt-2 break-words text-2xl font-semibold tracking-tight text-stone-900 sm:text-3xl">{{ title }}</h1>
        <p class="mt-1 max-w-3xl text-sm leading-6 text-stone-500">{{ subtitle }}</p>
      </div>

      <div class="flex flex-wrap items-center justify-start gap-3 lg:justify-end">
        <slot name="actions" />
        <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-2.5">
          <div class="min-w-0 text-right">
            <div class="truncate text-sm font-semibold text-stone-900">{{ user?.name }}</div>
            <div class="text-xs capitalize text-stone-500">{{ user?.role }}</div>
          </div>
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-stone-900 text-sm font-semibold text-white">
            {{ initials }}
          </div>
        </div>        <button type="button" class="rounded-2xl bg-stone-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-stone-800" @click="$emit('logout')">
          Logout
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  eyebrow: { type: String, default: 'Embroidery operations' },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  user: { type: Object, default: null },
});

defineEmits(['logout']);

const initials = computed(() => {
  const name = props.user?.name || 'U';
  return name.split(' ').filter(Boolean).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || 'U';
});
</script>
