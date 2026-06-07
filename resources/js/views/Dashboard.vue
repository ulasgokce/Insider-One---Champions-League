<template>
    <div class="mx-auto min-h-screen max-w-6xl pb-20 md:pb-8">
        <AppHeader @change-teams="showTeamModal = true" />

        <ConfettiOverlay :trigger="showConfetti" />

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
                        :fixtures-by-week="state.results_by_week"
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

                    <div
                        class="grid grid-cols-1 gap-4 lg:col-span-2"
                        :class="state.champion ? 'lg:grid-cols-2' : ''"
                    >
                        <Transition name="slide-fade">
                            <ChampionshipPrediction
                                :predictions="state.predictions ?? []"
                                :visible="state.season.predictions_visible"
                                :standings="state.standings"
                            />
                        </Transition>

                        <div
                            ref="trophySectionRef"
                            class="scroll-mt-4 scroll-mb-24 md:scroll-mb-6"
                        >
                            <Transition name="slide-fade">
                                <ChampionshipWinner
                                    v-if="state.champion"
                                    :champion="state.champion"
                                />
                            </Transition>
                        </div>
                    </div>
                </div>
            </template>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-3 py-2 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 md:static md:mt-6 md:border-0 md:bg-transparent md:p-0 md:px-6">
            <DashboardControls
                :loading="loading"
                :can-play-week="canPlayWeek"
                :can-play-all="canPlayAll"
                @reset="resetSeason"
                @play-week="playWeek"
                @play-all="playAll"
            />
        </div>

        <MatchEditModal
            :match="editingMatch"
            :saving="loading"
            @close="editingMatch = null"
            @save="saveMatchEdit"
        />

        <TeamSelectModal
            :open="showTeamModal"
            :teams="state?.all_teams ?? []"
            :initial-selected-ids="state?.selected_team_ids ?? []"
            :default-team-ids="state?.default_team_ids ?? []"
            :saving="loading"
            @close="showTeamModal = false"
            @save="configureTeams"
        />
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { leagueApi } from '../services/leagueApi';
import AppHeader from '../components/AppHeader.vue';
import WeekBanner from '../components/WeekBanner.vue';
import LeagueStandings from '../components/LeagueStandings.vue';
import WeekFixtures from '../components/WeekFixtures.vue';
import ChampionshipPrediction from '../components/ChampionshipPrediction.vue';
import ChampionshipWinner from '../components/ChampionshipWinner.vue';
import DashboardControls from '../components/DashboardControls.vue';
import MatchEditModal from '../components/MatchEditModal.vue';
import TeamSelectModal from '../components/TeamSelectModal.vue';
import ConfettiOverlay from '../components/ConfettiOverlay.vue';
import { confirmApplyTeams, confirmResetSeason } from '../utils/swal';

const state = ref(null);
const loading = ref(false);
const loadingMatchId = ref(null);
const error = ref('');
const editingMatch = ref(null);
const highlightedTeamId = ref(null);
const showTeamModal = ref(false);
const showConfetti = ref(false);
const confettiFired = ref(false);
const trophySectionRef = ref(null);
const initialLoadComplete = ref(false);

const scrollToTrophy = () => {
    nextTick(() => {
        requestAnimationFrame(() => {
            const el = trophySectionRef.value;
            if (!el) {
                return;
            }

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            el.scrollIntoView({
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });
        });
    });
};

const canPlayWeek = computed(() => {
    if (!state.value) return false;
    return state.value.current_week_fixtures.some((match) => match.status !== 'played');
});

const canPlayAll = computed(() => state.value?.season.has_unplayed_matches ?? false);

watch(
    () => state.value?.season?.is_finished,
    (finished) => {
        if (!initialLoadComplete.value) {
            return;
        }

        if (finished && state.value?.champion && !confettiFired.value) {
            showConfetti.value = true;
            confettiFired.value = true;
            scrollToTrophy();
            setTimeout(() => {
                showConfetti.value = false;
            }, 4500);
        }

        if (!finished) {
            confettiFired.value = false;
        }
    },
);

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

const playAll = async () => {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.playAll();
        state.value = data;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to play all matches.';
    } finally {
        loading.value = false;
    }
};

const resetSeason = async () => {
    const confirmed = await confirmResetSeason();
    if (!confirmed) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.start({
            team_ids: state.value?.selected_team_ids,
        });
        state.value = data;
        confettiFired.value = false;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to reset season.';
    } finally {
        loading.value = false;
    }
};

const configureTeams = async (teamIds) => {
    const confirmed = await confirmApplyTeams();
    if (!confirmed) {
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const { data } = await leagueApi.configureTeams(teamIds);
        state.value = data;
        showTeamModal.value = false;
        confettiFired.value = false;
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Failed to update teams.';
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

onMounted(async () => {
    await loadState();
    initialLoadComplete.value = true;
});
</script>
