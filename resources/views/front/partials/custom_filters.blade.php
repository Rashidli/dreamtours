{{-- Staff-managed filter groups (admin: Tour filters); inside a tourFilter() component. --}}
<template x-for="f in customFilters" :key="f.id">
  <fieldset class="mt-5">
    <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-ink-500" x-text="f.name"></legend>
    <div class="space-y-1.5">
      <template x-for="o in f.options" :key="o.id">
        <label class="flex items-center gap-2.5 text-sm text-ink-700">
          <input type="checkbox" :value="o.id" x-model.number="picked" class="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500" />
          <span x-text="o.name"></span>
        </label>
      </template>
    </div>
  </fieldset>
</template>
