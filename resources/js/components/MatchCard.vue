<template>
    <button
        type="button"
        class="group w-full rounded-2xl border p-4 text-left transition-all duration-300 active:scale-[0.99]"
        :class="match.status === 'played'
            ? 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60'
            : 'border-emerald-200 bg-white hover:border-emerald-400 hover:shadow-md dark:border-emerald-900 dark:bg-slate-900 dark:hover:border-emerald-600'"
        :disabled="loading || match.status === 'played'"
        @click="handleClick"
    >
        <div class="flex flex-col items-center gap-3 md:flex-row md:justify-between">
            <div class="w-full text-center md:w-1/3 md:text-right">
                <p class="font-semibold">{{ match.home_team.short_name }}</p>
                <p class="hidden text-xs text-slate-500 md:block dark:text-slate-400">{{ match.home_team.name }}</p>
            </div>

            <div class="flex min-h-[48px] min-w-[48px] flex-col items-center justify-center">
                <template v-if="match.status === 'played'">
                    <p class="text-2xl font-bold tracking-wider transition-all duration-500">
                        {{ match.home_goals }} - {{ match.away_goals }}
                    </p>
                    <button
                        type="button"
                        class="mt-2 text-xs text-emerald-600 underline dark:text-emerald-400"
                        @click.stop="$emit('edit', match)"
                    >
                        Edit score
                    </button>
                </template>
                <template v-else>
                    <span
                        class="text-3xl transition-transform duration-300"
                        :class="{ 'animate-pulse': loading }"
                    >
                        ⚽
                    </span>
                    <span class="mt-1 text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400 md:hidden">
                        Start the Game
                    </span>
                    <span class="mt-1 hidden text-xs font-semibold uppercase tracking-wide text-emerald-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-emerald-400 md:block">
                        Start the Game
                    </span>
                </template>
            </div>

            <div class="w-full text-center md:w-1/3 md:text-left">
                <p class="font-semibold">{{ match.away_team.short_name }}</p>
                <p class="hidden text-xs text-slate-500 md:block dark:text-slate-400">{{ match.away_team.name }}</p>
            </div>
        </div>
    </button>
</template>

<script setup>
const props = defineProps({
    match: { type: Object, required: true },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['play', 'edit']);

const handleClick = () => {
    if (props.match.status !== 'played') {
        emit('play', props.match);
    }
};
</script>
