<template>
    <header class="flex items-center justify-between gap-3 px-4 py-4 md:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <img
                src="/insider-logo.jpeg"
                alt="Insider One"
                class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-slate-700 md:h-11 md:w-11"
            />
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                    Insider One
                </p>
                <h1 class="truncate text-lg font-bold md:text-2xl">Champions League Simulator</h1>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button
                type="button"
                class="rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-semibold transition-colors hover:bg-slate-100 dark:border-slate-600 dark:hover:bg-slate-800 sm:px-3 sm:text-sm"
                title="Pick any 4 teams — mix and match clubs, then reset the season to see how they perform."
                @click="$emit('change-teams')"
            >
                Teams
            </button>
            <ThemeToggle />
        </div>
    </header>
</template>

<script setup>
import ThemeToggle from './ThemeToggle.vue';

defineEmits(['change-teams']);
</script>
