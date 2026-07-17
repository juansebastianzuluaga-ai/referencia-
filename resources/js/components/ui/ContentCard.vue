<template>
  <div class="flex-1 min-h-0 flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden animate-fade-in">
    
    <!-- Header -->
    <div class="shrink-0 flex items-center justify-between gap-4 p-4 lg:py-3.5 lg:px-5 border-b border-gray-200 bg-white flex-wrap">
      <div class="flex items-start justify-between gap-4 flex-wrap w-full">
        <div>
          <h1 class="text-[17px] font-semibold text-gray-900 leading-tight">
            <slot name="title">{{ title }}</slot>
          </h1>
          <p v-if="subtitle" class="text-xs text-gray-400 mt-0.5">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <slot name="actions"></slot>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="flex-1 min-h-0 overflow-y-auto p-4 lg:py-4 lg:px-5 custom-scrollbar">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title?: string;
  subtitle?: string;
}>();
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-gray-300, #cbd5e1); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

.animate-fade-in {
  animation: fadeIn 0.2s ease both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
