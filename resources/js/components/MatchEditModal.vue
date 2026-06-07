<template>
    <div
        v-if="match"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 md:items-center"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
            <h3 class="mb-4 text-lg font-bold">Edit Match Score</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                {{ match.home_team.name }} vs {{ match.away_team.name }}
            </p>

            <div class="mb-6 grid grid-cols-2 gap-4">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ match.home_team.short_name }}</span>
                    <input
                        v-model.number="homeGoals"
                        type="number"
                        min="0"
                        max="20"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 dark:border-slate-600 dark:bg-slate-800"
                    >
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ match.away_team.short_name }}</span>
                    <input
                        v-model.number="awayGoals"
                        type="number"
                        min="0"
                        max="20"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 dark:border-slate-600 dark:bg-slate-800"
                    >
                </label>
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    class="min-h-[44px] flex-1 rounded-xl border border-slate-300 px-4 py-2 font-semibold dark:border-slate-600"
                    @click="$emit('close')"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="min-h-[44px] flex-1 rounded-xl bg-emerald-600 px-4 py-2 font-semibold text-white"
                    :disabled="saving"
                    @click="save"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    match: { type: Object, default: null },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const homeGoals = ref(0);
const awayGoals = ref(0);

watch(
    () => props.match,
    (value) => {
        if (value) {
            homeGoals.value = value.home_goals ?? 0;
            awayGoals.value = value.away_goals ?? 0;
        }
    },
    { immediate: true },
);

const save = () => {
    emit('save', {
        match: props.match,
        homeGoals: homeGoals.value,
        awayGoals: awayGoals.value,
    });
};
</script>
