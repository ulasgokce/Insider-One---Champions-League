<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:p-4">
        <div class="flex gap-2">
            <button
                type="button"
                class="min-h-[40px] flex-1 rounded-lg border border-slate-300 px-2 py-2 text-xs font-semibold transition-all duration-200 hover:bg-slate-100 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-800 sm:text-sm"
                :disabled="loading"
                @click="$emit('reset')"
            >
                Reset
            </button>

            <button
                type="button"
                class="min-h-[40px] flex-1 rounded-lg bg-emerald-600 px-2 py-2 text-xs font-semibold text-white transition-all duration-200 hover:bg-emerald-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                :disabled="loading || !canPlayWeek"
                @click="$emit('play-week')"
            >
                Play Week
            </button>

            <button
                type="button"
                class="min-h-[40px] flex-1 rounded-lg bg-indigo-600 px-2 py-2 text-xs font-semibold text-white transition-all duration-200 hover:bg-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                :disabled="loading || !canPlayAll"
                @click="$emit('play-all')"
            >
                Play All
            </button>
        </div>
    </section>
</template>

<script setup>
defineProps({
    loading: { type: Boolean, default: false },
    canPlayWeek: { type: Boolean, default: false },
    canPlayAll: { type: Boolean, default: false },
});

defineEmits(['reset', 'play-week', 'play-all']);
</script>
