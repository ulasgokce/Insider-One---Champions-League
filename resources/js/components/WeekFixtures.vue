<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-1 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Week {{ currentWeek }} Fixtures
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <template v-if="!allowEdit">
                        Season complete — results are locked.
                    </template>
                    <template v-else>
                        Play matches here. Edit any past score in <strong class="font-medium text-slate-600 dark:text-slate-300">Season Progress</strong> above.
                    </template>
                </p>
            </div>
            <button
                v-if="canNextWeek"
                type="button"
                class="shrink-0 rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white transition-all hover:bg-cyan-500 active:scale-95 disabled:opacity-50 sm:text-sm"
                :disabled="loading || loadingMatchId !== null"
                @click="$emit('next-week')"
            >
                Next Week →
            </button>
        </div>

        <Transition name="slide-fade" mode="out-in">
            <div :key="currentWeek" class="mt-4 space-y-3">
                <MatchCard
                    v-for="match in fixtures"
                    :key="match.id"
                    :match="match"
                    :loading="loadingMatchId === match.id"
                    :blocked="loadingMatchId !== null && loadingMatchId !== match.id"
                    :allow-edit="allowEdit"
                    @play="$emit('play-match', $event)"
                    @edit="$emit('edit-match', $event)"
                />
            </div>
        </Transition>
    </section>
</template>

<script setup>
import MatchCard from './MatchCard.vue';

defineProps({
    fixtures: { type: Array, default: () => [] },
    currentWeek: { type: Number, required: true },
    loadingMatchId: { type: Number, default: null },
    canNextWeek: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    allowEdit: { type: Boolean, default: true },
});

defineEmits(['play-match', 'edit-match', 'next-week']);
</script>
