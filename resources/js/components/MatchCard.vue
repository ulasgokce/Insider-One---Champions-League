<template>
    <component
        :is="match.status === 'played' ? 'div' : 'button'"
        :type="match.status === 'played' ? undefined : 'button'"
        class="group relative w-full rounded-2xl border p-3 text-left transition-all duration-300"
        :class="match.status === 'played'
            ? 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60'
            : 'border-emerald-200 bg-white hover:border-emerald-400 hover:shadow-md active:scale-[0.99] dark:border-emerald-900 dark:bg-slate-900 dark:hover:border-emerald-600'"
        :disabled="loading"
        @click="handleClick"
    >
        <button
            v-if="match.status === 'played'"
            type="button"
            class="absolute right-2 top-2 rounded-lg p-1.5 text-slate-400 opacity-70 transition-opacity hover:bg-slate-200 hover:text-slate-700 hover:opacity-100 dark:hover:bg-slate-700 dark:hover:text-slate-200"
            title="Edit score"
            @click.stop="$emit('edit', match)"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </button>

        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                <div class="flex items-center gap-2">
                    <TeamLogo :team="match.home_team" size="md" />
                    <span class="text-sm font-bold">{{ match.home_team.short_name }}</span>
                </div>
                <p class="max-w-full truncate text-center text-xs text-slate-500 dark:text-slate-400">
                    {{ match.home_team.name }}
                </p>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    Home
                </span>
            </div>

            <div class="flex shrink-0 flex-col items-center justify-center px-1">
                <template v-if="match.status === 'played'">
                    <p class="score-pop text-xl font-bold tracking-wider md:text-2xl">
                        {{ match.home_goals }} - {{ match.away_goals }}
                    </p>
                </template>
                <template v-else>
                    <span
                        class="inline-block text-2xl md:text-3xl"
                        :class="{ 'ball-spin': loading }"
                    >
                        ⚽
                    </span>
                    <span class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                        {{ loading ? 'Playing…' : 'Play' }}
                    </span>
                </template>
            </div>

            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold">{{ match.away_team.short_name }}</span>
                    <TeamLogo :team="match.away_team" size="md" />
                </div>
                <p class="max-w-full truncate text-center text-xs text-slate-500 dark:text-slate-400">
                    {{ match.away_team.name }}
                </p>
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    Away
                </span>
            </div>
        </div>
    </component>
</template>

<script setup>
import TeamLogo from './TeamLogo.vue';

const props = defineProps({
    match: { type: Object, required: true },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['play', 'edit']);

const handleClick = () => {
    if (props.match.status !== 'played' && !props.loading) {
        emit('play', props.match);
    }
};
</script>
