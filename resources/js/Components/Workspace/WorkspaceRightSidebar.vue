<template>
  <aside class="flex flex-col gap-5 xl:h-[calc(100vh-2rem)] xl:w-[320px]">
    <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-stone-900">Notifications</h2>
        <span class="text-xs text-stone-500">{{ notifications.length }}</span>
      </div>
      <div class="mt-4 space-y-3">
        <div v-for="notification in notifications.slice(0, 5)" :key="notification.id" class="rounded-2xl border border-stone-200 bg-stone-50 p-3.5">
          <div class="text-sm font-semibold text-stone-900">{{ notification.title }}</div>
          <div class="mt-1 text-xs uppercase tracking-wide text-stone-500">{{ notification.type }}</div>
          <div class="mt-2 text-sm leading-6 text-stone-600">{{ notification.message }}</div>
        </div>
        <div v-if="!notifications.length" class="rounded-2xl border border-dashed border-stone-300 px-4 py-6 text-sm text-stone-500">
          No notifications yet.
        </div>
      </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-1">
      <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-stone-900">Quick status</h2>
        <div class="mt-4 space-y-3 text-sm text-stone-600">
          <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
            <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Selected order</div>
            <div class="mt-2 break-words font-semibold text-stone-900">{{ selectedOrder?.order_number || 'No order selected' }}</div>
            <div v-if="selectedOrder" class="mt-2 text-sm capitalize">{{ selectedOrder.status }} · {{ selectedOrder.current_stage }}</div>
          </div>
          <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
            <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Assignments</div>
            <div class="mt-2 text-xl font-semibold text-stone-900">{{ assignments.length }}</div>
            <div class="mt-1 text-sm">Live tasks tied to the selected order.</div>
          </div>
          <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
            <div class="text-xs uppercase tracking-[0.2em] text-stone-500">Revisions</div>
            <div class="mt-2 text-xl font-semibold text-stone-900">{{ revisions.length }}</div>
            <div class="mt-1 text-sm">Use this panel for quick follow-up context.</div>
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-stone-900">Team chat</h2>
        <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-8 text-sm text-stone-500">
          Reserved for lightweight chat or quick module widgets.
        </div>
      </section>
    </div>
  </aside>
</template>

<script setup>
defineProps({
  notifications: { type: Array, default: () => [] },
  selectedOrder: { type: Object, default: null },
  assignments: { type: Array, default: () => [] },
  revisions: { type: Array, default: () => [] },
});
</script>
