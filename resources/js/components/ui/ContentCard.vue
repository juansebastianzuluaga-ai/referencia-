<template>
  <div class="flex-1 min-h-0 flex flex-col rounded-2xl overflow-hidden animate-fade-in" style="background:#e8ecf1; box-shadow: 10px 10px 20px #c5c9d0, -10px -10px 20px #ffffff;">

    <!-- Header -->
    <div class="shrink-0 flex items-center justify-between gap-4 p-4 lg:py-4 lg:px-5 flex-wrap" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
      <div class="flex items-start justify-between gap-4 flex-wrap w-full">
        <div>
          <h1 class="text-[17px] font-semibold leading-tight" style="color:#1e2d55;">
            <slot name="title">{{ title }}</slot>
          </h1>
          <p v-if="subtitle" class="text-xs mt-0.5" style="color:#8a9ab5;">{{ subtitle }}</p>
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
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

.animate-fade-in {
  animation: fadeIn 0.2s ease both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
