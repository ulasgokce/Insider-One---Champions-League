<template>
    <section
        class="relative flex min-h-[220px] flex-col items-center justify-center overflow-hidden rounded-2xl border border-amber-300 bg-gradient-to-br from-amber-50 via-yellow-50 to-orange-50 px-6 py-10 text-center shadow-sm dark:border-amber-700 dark:from-amber-950/40 dark:via-yellow-950/30 dark:to-orange-950/40 md:min-h-[260px] md:px-8 md:py-12"
    >
        <div class="mb-4 flex h-20 w-20 items-center justify-center text-6xl animate-bounce md:h-24 md:w-24 md:text-7xl">
            🏆
        </div>

        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-400">
            Season Complete
        </p>

        <h2 class="mt-2 max-w-xs text-2xl font-bold leading-tight text-amber-900 dark:text-amber-100 md:max-w-md md:text-3xl">
            Champion is {{ champion.name }}
        </h2>

        <div class="mt-5 flex flex-col items-center gap-3">
            <TeamLogo :team="champion" size="lg" />
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                {{ champion.points }} pts · GD {{ champion.goal_difference > 0 ? '+' : '' }}{{ champion.goal_difference }}
            </p>
        </div>
    </section>
</template>

<script setup>
import TeamLogo from './TeamLogo.vue';

defineProps({
    champion: { type: Object, required: true },
});
</script>
