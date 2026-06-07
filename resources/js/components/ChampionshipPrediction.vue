<template>
    <section
        v-if="visible"
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Championship Predictions
        </h2>

        <div class="space-y-4">
            <div v-for="item in predictions" :key="item.team_id">
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span class="font-medium">{{ item.short_name }}</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ item.percentage }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-700 ease-out"
                        :style="{ width: `${item.percentage}%` }"
                    />
                </div>
            </div>
        </div>
    </section>

    <section
        v-else
        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
    >
        Championship predictions unlock in the last 3 weeks (from Week 4).
    </section>
</template>

<script setup>
defineProps({
    predictions: { type: Array, default: () => [] },
    visible: { type: Boolean, default: false },
});
</script>
