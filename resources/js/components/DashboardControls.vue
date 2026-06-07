<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:static md:rounded-2xl">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <button
                type="button"
                class="min-h-[44px] rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold transition-all duration-200 hover:bg-slate-100 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:hover:bg-slate-800"
                :disabled="loading"
                @click="$emit('reset')"
            >
                Reset Season
            </button>

            <button
                type="button"
                class="min-h-[44px] rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-emerald-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="loading || !canPlayWeek"
                @click="$emit('play-week')"
            >
                Play This Week
            </button>

            <button
                type="button"
                class="min-h-[44px] rounded-xl bg-cyan-600 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-cyan-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="loading || !canNextWeek"
                @click="$emit('next-week')"
            >
                Next Week
            </button>

            <button
                type="button"
                class="min-h-[44px] rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:bg-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
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
    canNextWeek: { type: Boolean, default: false },
    canPlayAll: { type: Boolean, default: false },
});

defineEmits(['reset', 'play-week', 'next-week', 'play-all']);
</script>
