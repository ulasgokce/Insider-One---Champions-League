import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

export const leagueApi = {
    getState: () => api.get('/league/state'),
    getTeams: () => api.get('/teams'),
    start: (payload = {}) => api.post('/league/start', payload),
    configureTeams: (teamIds) => api.post('/league/configure-teams', { team_ids: teamIds }),
    playMatch: (id) => api.post(`/matches/${id}/play`),
    playWeek: () => api.post('/league/play-week'),
    nextWeek: () => api.post('/league/next-week'),
    playAll: () => api.post('/league/play-all'),
    updateMatch: (id, homeGoals, awayGoals) => api.patch(`/matches/${id}`, {
        home_goals: homeGoals,
        away_goals: awayGoals,
    }),
};

export default api;
