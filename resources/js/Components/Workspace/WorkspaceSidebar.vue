<template>
  <div class="space-y-3">
    <aside class="hidden h-[calc(100vh-2rem)] w-[240px] flex-col rounded-3xl border border-stone-200 bg-stone-900 px-4 py-5 text-stone-200 shadow-xl xl:flex">
      <div class="px-3">
        <div class="text-xs font-semibold uppercase tracking-[0.28em] text-stone-400">Embroidery</div>
        <div class="mt-2 text-xl font-semibold text-white">Operations Workspace</div>
        <div class="mt-1 text-sm text-stone-400">Clear workflows, minimal clutter.</div>
      </div>

      <nav class="mt-8 flex-1 space-y-1 overflow-y-auto pr-1">
        <button
          v-for="item in items"
          :key="item.key"
          type="button"
          class="flex w-full items-center justify-between gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium transition"
          :class="activeKey === item.key ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-300 hover:bg-stone-800 hover:text-white'"
          @click="$emit('change', item.key)"
        >
          <span class="min-w-0 truncate">{{ item.label }}</span>
          <span v-if="item.badge" class="shrink-0 rounded-full px-2 py-0.5 text-xs" :class="activeKey === item.key ? 'bg-stone-100 text-stone-700' : 'bg-stone-700 text-stone-200'">
            {{ item.badge }}
          </span>
        </button>
      </nav>

      <div class="mt-4 rounded-2xl border border-stone-800 bg-stone-950/70 p-4">
        <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Signed in as</div>
        <div class="mt-2 text-sm font-semibold text-white">{{ user?.name || 'User' }}</div>
        <div class="mt-1 text-sm capitalize text-stone-400">{{ user?.role || '—' }}</div>
        <div v-if="user?.shop_id" class="mt-1 text-xs text-stone-500">Shop #{{ user.shop_id }}</div>
      </div>
    </aside>

    <section class="xl:hidden rounded-3xl border border-stone-200 bg-stone-900 p-4 text-stone-200 shadow-sm">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="text-xs font-semibold uppercase tracking-[0.28em] text-stone-400">Embroidery</div>
          <div class="mt-1 text-lg font-semibold text-white">Operations Workspace</div>
          <div class="mt-1 text-sm text-stone-400">Responsive navigation for every screen size.</div>
        </div>
        <div class="rounded-2xl border border-stone-800 bg-stone-950/70 px-4 py-3 text-sm">
          <div class="font-semibold text-white">{{ user?.name || 'User' }}</div>
          <div class="mt-0.5 capitalize text-stone-400">{{ user?.role || '—' }}</div>
        </div>
      </div>

      <nav class="mt-4 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
        <button
          v-for="item in items"
          :key="item.key"
          type="button"
          class="flex shrink-0 items-center gap-2 rounded-2xl border px-3 py-2.5 text-sm font-medium transition"
          :class="activeKey === item.key ? 'border-white bg-white text-stone-900 shadow-sm' : 'border-stone-700 bg-stone-800/70 text-stone-200 hover:bg-stone-800'"
          @click="$emit('change', item.key)"
        >
          <span>{{ item.label }}</span>
          <span v-if="item.badge" class="rounded-full px-2 py-0.5 text-xs" :class="activeKey === item.key ? 'bg-stone-100 text-stone-700' : 'bg-stone-700 text-stone-200'">
            {{ item.badge }}
          </span>
        </button>
      </nav>
    </section>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
  activeKey: { type: String, required: true },
  user: { type: Object, default: null },
});

defineEmits(['change']);
</script>
