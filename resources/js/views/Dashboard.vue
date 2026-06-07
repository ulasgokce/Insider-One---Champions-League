<template>
    <div class="mx-auto min-h-screen max-w-6xl pb-28 md:pb-8">
        <AppHeader />

        <main class="space-y-4 px-4 md:space-y-6 md:px-6">
            <div v-if="error" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                {{ error }}
            </div>

            <div v-if="loading && !state" class="space-y-4">
                <div class="h-24 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800" />
                <div class="h-48 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800" />
            </div>

            <template v-else-if="state">
                <Transition name="panel">
                    <WeekBanner
                        :current-week="state.season.current_week"
                        :total-weeks="state.season.total_weeks"
                    />
                </Transition>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Transition name="panel">
                        <LeagueStandings
                            :standings="state.standings"
                            :highlighted-team-id="highlightedTeamId"
                        />
                    </Transition>

                    <Transition name="panel">
                        <WeekFixtures
                            :fixtures="state.current_week_fixtures"
                            :current-week="state.season.current_week"
                            :loading-match-id="loadingMatchId"
                            @play-match="playMatch"
                            @edit-match="openEditModal"
                        />
                    </Transition>

                    <Transition name="slide-fade">
                        <ChampionshipPrediction
                            :predictions="state.predictions ?? []"
                            :visible="state.season.predictions_visible"
                        />
                    </Transition>

                    <section
                        v-if="showPlayAllResults"
                        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2"
                    >
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            Season Results by Week
                        </h2>
                        <div class="space-y-3">
                            <details
                                v-for="week in playAllResults"
                                :key="week.week"
                                class="rounded-xl border border-slate-200 p-3 dark:border-slate-700"
                                open
                            >
                                <summary class="cursor-pointer font-semibold">Week {{ week.week }}</summary>
                                <ul class="mt-3 space-y-2 text-sm">
                                    <li v-for="match in week.matches" :key="match.id">
                                        {{ match.home_team.short_name }} {{ match.home_goals }} - {{ match.away_goals }} {{ match.away_team.short_name }}
                                    </li>
                                </ul>
                            </details>
                        </div>
                    </section>
                </div>
            </template>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-4 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:static md:mt-6 md:border-0 md:bg-transparent md:p-0 md:px-6">
            <DashboardControls
                :loading="loading"
                :can-play-week="canPlayWeek"
                :can-next-week="canNextWeek"
                :can-play-all="canPlayAll"
                @reset="resetSeason"
                @play-week="playWeek"
                @next-week="nextWeek"
                @play-all="playAll"
            />
        </div>

        <MatchEditModal
            :match="editingMatch"
            :saving="loading"
            @close="editingMatch = null"
            @save="saveMatchEdit"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { leagueApi } from '../services/leagueApi';
import AppHeader from '../components/AppHeader.vue';
import WeekBanner from '../components/WeekBanner.vue';
import LeagueStandings from '../components/LeagueStandings.vue';
import WeekFixtures from '../components/WeekFixtures.vue';
import ChampionshipPrediction from '../components/ChampionshipPrediction.vue';
import DashboardControls from '../components/DashboardControls.vue';
import MatchEditModal from '../components/MatchEditModal.vue';

const state = ref(null);
const loading = ref(false);
const loadingMatchId = ref(null);
const error = ref('');
const editingMatch = ref(null);
const highlightedTeamId = ref(null);
const playAllResults = ref([]);
const showPlayAllResults = ref(false);

const canPlayWeek = computed(() => {
    if (!state.value) return false;
    return state.value.current_week_fixtures.some((match) => match.status !== 'played');
});

const canNextWeek = computed(() => {
    if (!state.value) return false;
    return state.value.season.current_week_complete
        && state.value.season.current_week < state.value.season.total_weeks;
});

const canPlayAll = computed(() => state.value?.season.has_unplayed_matches ?? false);

const loadState = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.getState();
        state.value = data;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to load league state.';
    } finally {
        loading.value = false;
    }
};

const flashHighlight = (teamId) => {
    highlightedTeamId.value = teamId;
    setTimeout(() => {
        highlightedTeamId.value = null;
    }, 1200);
};

const playMatch = async (match) => {
    loadingMatchId.value = match.id;
    error.value = '';

    try {
        const { data } = await leagueApi.playMatch(match.id);
        state.value = data;
        flashHighlight(match.home_team.id);
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to play match.';
    } finally {
        loadingMatchId.value = null;
    }
};

const playWeek = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.playWeek();
        state.value = data;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to play week.';
    } finally {
        loading.value = false;
    }
};

const nextWeek = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.nextWeek();
        state.value = data;
        showPlayAllResults.value = false;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to advance week.';
    } finally {
        loading.value = false;
    }
};

const playAll = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.playAll();
        state.value = data;
        playAllResults.value = data.play_all_results ?? data.results_by_week ?? [];
        showPlayAllResults.value = true;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to play all matches.';
    } finally {
        loading.value = false;
    }
};

const resetSeason = async () => {
    if (!window.confirm('Reset the season and start from Week 1?')) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.start();
        state.value = data;
        playAllResults.value = [];
        showPlayAllResults.value = false;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to reset season.';
    } finally {
        loading.value = false;
    }
};

const openEditModal = (match) => {
    editingMatch.value = match;
};

const saveMatchEdit = async ({ match, homeGoals, awayGoals }) => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.updateMatch(match.id, homeGoals, awayGoals);
        state.value = data;
        editingMatch.value = null;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to update match.';
    } finally {
        loading.value = false;
    }
};

onMounted(loadState);
</script>
