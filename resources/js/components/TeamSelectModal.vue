<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-6"
            @click.self="$emit('close')"
        >
            <div
                class="flex w-full max-w-5xl flex-col rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
                aria-labelledby="team-modal-title"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-6 sm:py-4">
                    <div class="min-w-0">
                        <h2 id="team-modal-title" class="text-lg font-bold sm:text-xl">Choose Your Teams</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            Pick exactly 4 clubs ·
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ selectedIds.length }} / 4 selected</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                        aria-label="Close"
                        @click="$emit('close')"
                    >
                        ✕
                    </button>
                </div>

                <div class="px-4 py-3 sm:px-6">
                    <p
                        class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs leading-relaxed text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100 sm:text-sm"
                        role="note"
                    >
                        <strong>Mix and match any 4 teams</strong> — swap clubs, apply, and reset the season to compare results.
                    </p>
                </div>

                <div class="px-4 pb-4 sm:px-6 sm:pb-5">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5 sm:gap-3">
                        <button
                            v-for="team in teams"
                            :key="team.id"
                            type="button"
                            class="relative flex items-center gap-2 rounded-xl border px-2 py-2 text-left transition-all duration-200 sm:flex-col sm:items-center sm:px-3 sm:py-3 sm:text-center"
                            :class="isSelected(team.id)
                                ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500 dark:bg-emerald-950/40'
                                : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600'"
                            :title="isDefaultTeam(team.id) ? 'Part of the default lineup' : `Select ${team.name}`"
                            @click="toggleTeam(team.id)"
                        >
                            <span
                                v-if="isDefaultTeam(team.id)"
                                class="absolute right-1 top-1 rounded-full bg-sky-100 px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wide text-sky-700 dark:bg-sky-900 dark:text-sky-300 sm:right-2 sm:top-2 sm:text-[9px]"
                            >
                                Default
                            </span>
                            <TeamLogo :team="team" size="md" />
                            <div class="min-w-0 flex-1 sm:flex-none">
                                <span class="block truncate text-xs font-semibold sm:text-sm">{{ team.short_name }}</span>
                                <span class="hidden truncate text-[10px] text-slate-500 dark:text-slate-400 sm:block">{{ team.name }}</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-6 sm:py-4">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold dark:border-slate-600 sm:text-sm"
                        @click="resetToDefault"
                    >
                        Reset to default
                    </button>
                    <div class="ml-auto flex flex-1 justify-end gap-2 sm:flex-none">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold dark:border-slate-600"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            :disabled="selectedIds.length !== 4 || saving"
                            @click="save"
                        >
                            {{ saving ? 'Saving…' : 'Apply & Reset' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import TeamLogo from './TeamLogo.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    teams: { type: Array, default: () => [] },
    initialSelectedIds: { type: Array, default: () => [] },
    defaultTeamIds: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save']);

const selectedIds = ref([]);

const resolveSelection = () => {
    if (props.initialSelectedIds.length === 4) {
        return [...props.initialSelectedIds];
    }

    if (props.defaultTeamIds.length === 4) {
        return [...props.defaultTeamIds];
    }

    return [];
};

watch(
    () => [props.open, props.initialSelectedIds, props.defaultTeamIds],
    () => {
        if (props.open) {
            selectedIds.value = resolveSelection();
        }
    },
    { immediate: true, deep: true },
);

const isSelected = (id) => selectedIds.value.includes(id);

const isDefaultTeam = (id) => props.defaultTeamIds.includes(id);

const toggleTeam = (id) => {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter((teamId) => teamId !== id);
        return;
    }

    if (selectedIds.value.length >= 4) {
        return;
    }

    selectedIds.value = [...selectedIds.value, id];
};

const resetToDefault = () => {
    if (props.defaultTeamIds.length === 4) {
        selectedIds.value = [...props.defaultTeamIds];
    }
};

const save = () => {
    if (selectedIds.value.length === 4) {
        emit('save', [...selectedIds.value]);
    }
};
</script>
