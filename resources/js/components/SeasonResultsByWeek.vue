<template>
    <section
        ref="sectionRef"
        class="scroll-mt-4 scroll-mb-24 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:scroll-mb-6 lg:col-span-2"
    >
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Season Results by Week
        </h2>

        <div class="space-y-3">
            <details
                v-for="week in resultsByWeek"
                :key="week.week"
                class="rounded-xl border border-slate-200 p-3 dark:border-slate-700"
                open
            >
                <summary class="cursor-pointer text-sm font-semibold">
                    Week {{ week.week }}
                </summary>
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="match in week.matches"
                        :key="match.id"
                        class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2 py-2 text-sm dark:bg-slate-800/50"
                    >
                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                            <TeamLogo :team="match.home_team" size="sm" />
                            <span class="truncate font-medium">{{ match.home_team.short_name }}</span>
                        </div>
                        <span class="shrink-0 font-bold tabular-nums">
                            {{ match.home_goals }} - {{ match.away_goals }}
                        </span>
                        <div class="flex min-w-0 flex-1 items-center justify-end gap-1.5">
                            <span class="truncate font-medium">{{ match.away_team.short_name }}</span>
                            <TeamLogo :team="match.away_team" size="sm" />
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                            title="Edit score"
                            @click="$emit('edit-match', match)"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </details>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import TeamLogo from './TeamLogo.vue';

defineProps({
    resultsByWeek: { type: Array, default: () => [] },
});

defineEmits(['edit-match']);

const sectionRef = ref(null);

defineExpose({ sectionRef });
</script>
