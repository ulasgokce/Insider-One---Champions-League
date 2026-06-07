<template>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            League Standings
        </h2>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        <th class="pb-2 pr-2">#</th>
                        <th class="pb-2">Team</th>
                        <th class="pb-2 text-center">P</th>
                        <th class="pb-2 text-center">W</th>
                        <th class="pb-2 text-center">D</th>
                        <th class="pb-2 text-center">L</th>
                        <th class="pb-2 text-center">GD</th>
                        <th class="pb-2 text-center font-bold">Pts</th>
                    </tr>
                </thead>
                <TransitionGroup tag="tbody" name="list">
                    <tr
                        v-for="row in standings"
                        :key="row.team_id"
                        class="border-b border-slate-100 transition-colors duration-300 dark:border-slate-800"
                        :class="{ 'bg-emerald-50/70 dark:bg-emerald-950/30': highlightedTeamId === row.team_id }"
                    >
                        <td class="py-3 pr-2 font-semibold">{{ row.position }}</td>
                        <td class="py-3">
                            <div class="font-medium">{{ row.name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ row.country }}</div>
                        </td>
                        <td class="py-3 text-center">{{ row.played }}</td>
                        <td class="py-3 text-center">{{ row.won }}</td>
                        <td class="py-3 text-center">{{ row.drawn }}</td>
                        <td class="py-3 text-center">{{ row.lost }}</td>
                        <td class="py-3 text-center">{{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}</td>
                        <td class="py-3 text-center font-bold">{{ row.points }}</td>
                    </tr>
                </TransitionGroup>
            </table>
        </div>

        <div class="space-y-3 md:hidden">
            <TransitionGroup name="list">
                <article
                    v-for="row in standings"
                    :key="row.team_id"
                    class="rounded-xl border border-slate-200 p-3 transition-colors duration-300 dark:border-slate-700"
                    :class="{ 'bg-emerald-50/70 dark:bg-emerald-950/30': highlightedTeamId === row.team_id }"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">#{{ row.position }}</p>
                            <h3 class="font-semibold">{{ row.short_name }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold">{{ row.points }}</p>
                            <p class="text-xs text-slate-500">GD {{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ row.played }} played · {{ row.won }}W {{ row.drawn }}D {{ row.lost }}L
                    </p>
                </article>
            </TransitionGroup>
        </div>
    </section>
</template>

<script setup>
defineProps({
    standings: { type: Array, default: () => [] },
    highlightedTeamId: { type: Number, default: null },
});
</script>
