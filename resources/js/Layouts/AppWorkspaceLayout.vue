<template>
  <div class="min-h-screen bg-stone-100 text-stone-900">
    <div class="mx-auto flex min-h-screen max-w-[1820px] flex-col gap-4 p-3 sm:p-4 xl:grid xl:grid-cols-[240px_minmax(0,1fr)_320px] xl:items-start">
      <div class="xl:sticky xl:top-4 xl:self-start">
        <slot name="sidebar" />
      </div>

      <main class="min-w-0 space-y-4">
        <slot name="header" />
        <slot />
      </main>

      <div class="min-w-0 xl:sticky xl:top-4 xl:self-start">
        <slot name="right" />
      </div>
    </div>
  </div>
</template>
