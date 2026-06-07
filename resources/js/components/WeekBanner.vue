<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Season Progress
            </h2>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                Week {{ currentWeek }} of {{ totalWeeks }}
            </span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div
                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-500 ease-out"
                :style="{ width: `${progress}%` }"
            />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    currentWeek: { type: Number, required: true },
    totalWeeks: { type: Number, required: true },
});

const progress = computed(() => (props.currentWeek / props.totalWeeks) * 100);
</script>
