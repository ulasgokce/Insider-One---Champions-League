<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <button
            type="button"
            class="mb-3 flex w-full items-center justify-between gap-3 text-left"
            @click="expanded = !expanded"
        >
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Season Progress
            </h2>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    Week {{ currentWeek }} of {{ totalWeeks }}
                </span>
                <svg
                    class="h-5 w-5 text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180': expanded }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div
                class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-500 ease-out"
                :style="{ width: `${progress}%` }"
            />
        </div>

        <Transition name="slide-fade">
            <div v-if="expanded" class="mt-4 space-y-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                <div
                    v-for="week in fixturesByWeek"
                    :key="week.week"
                    class="rounded-xl border border-slate-100 p-3 dark:border-slate-800"
                >
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Week {{ week.week }}
                    </h3>
                    <ul class="space-y-2">
                        <li
                            v-for="match in week.matches"
                            :key="match.id"
                            class="flex items-center justify-between gap-2 text-sm"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                <TeamLogo :team="match.home_team" size="sm" />
                                <span class="truncate font-medium">{{ match.home_team.short_name }}</span>
                                <span class="text-[10px] text-slate-400">(H)</span>
                            </div>
                            <span class="shrink-0 px-1 font-bold tabular-nums">
                                <template v-if="match.status === 'played'">
                                    {{ match.home_goals }} - {{ match.away_goals }}
                                </template>
                                <template v-else>vs</template>
                            </span>
                            <div class="flex min-w-0 flex-1 items-center justify-end gap-1.5">
                                <span class="text-[10px] text-slate-400">(A)</span>
                                <span class="truncate font-medium">{{ match.away_team.short_name }}</span>
                                <TeamLogo :team="match.away_team" size="sm" />
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </Transition>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import TeamLogo from './TeamLogo.vue';

const props = defineProps({
    currentWeek: { type: Number, required: true },
    totalWeeks: { type: Number, required: true },
    fixturesByWeek: { type: Array, default: () => [] },
    expanded: { type: Boolean, default: false },
});

const emit = defineEmits(['update:expanded']);

const expanded = ref(props.expanded);

watch(
    () => props.expanded,
    (value) => {
        expanded.value = value;
    },
);

watch(expanded, (value) => {
    emit('update:expanded', value);
});

const progress = computed(() => (props.currentWeek / props.totalWeeks) * 100);
</script>
