<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            Week {{ currentWeek }} Fixtures
        </h2>

        <Transition name="slide-fade" mode="out-in">
            <div :key="currentWeek" class="space-y-3">
                <MatchCard
                    v-for="match in fixtures"
                    :key="match.id"
                    :match="match"
                    :loading="loadingMatchId === match.id"
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
});

defineEmits(['play-match', 'edit-match']);
</script>
