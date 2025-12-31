<script setup>
import BoxScore from './BoxScore.vue';
import ThreedField from './ThreeDField.vue';
import { computed, onMounted, ref, nextTick } from 'vue';

const props = defineProps({
  gameId: Number,
  inning: {
    type: Number,
    required: false,
    default: null,
  },
  ended: {
    type: Boolean,
    required: false,
    default: false,
  },
  isReceiver: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const game = ref({
    id: props.gameId,
    state: 'loading',
    ended: props.ended,
});
const stats = ref({});
const state = ref({});
const selectedInning = ref(props.inning || null);
const selectedPlay = ref(null);
const selectedView = ref('plays'); // 'away', 'play', 'home'
const plays = ref([]);
const field = ref(null);
const isMobile = ref(window.innerWidth <= 768);

const castContext = ref(null);
const castSession = ref(null);
const isCasting = ref(false);

const teams = computed(() => [game.value.away_team, game.value.home_team]);

const hitting = computed(() => {
    const team = teams.value[state.value.half];
    const lineup = state.value.lineup?.[state.value.half];
    const atBat = state.value.atBat?.[state.value.half];
    return team?.players?.find(player => player.id === lineup[atBat]?.at(-1));
});

const pitching = computed(() => {
    const team = teams.value[1 - state.value.half];
    const defense = state.value.defense?.[1 - state.value.half];
    return team?.players?.find(player => player.id === defense?.['1']);
});

const fielders = computed(() => {
    const team = teams.value[1 - state.value.half];
    const defense = state.value.defense?.[1 - state.value.half];
    const fielders = {};
    for (const pos in defense) {
        fielders[pos] = team?.players?.find(player => player.id === defense[pos]);
    }
    return fielders;
});

const runners = computed(() => {
    const team = teams.value[state.value.half];
    const bases = {};
    for (const base in state.value.bases) {
        const runnerId = state.value.bases[base];
        if (runnerId) {
            bases[base] = team?.players?.find(player => player.id === runnerId);
        }
    }
    return bases;
});

const updatePlays = () => {
    let nextPlays = [];
    let currentPA = [];
    // Combine plays into plate appearances.
    for (const play of game.value.plays) {
        if (play.inning === null) {
            continue;
        }
        if (play.plate_appearance) {
            currentPA.push(play);
            nextPlays.push(currentPA);
            currentPA = [];
        } else if (play.game_event) {
            nextPlays.push(currentPA);
            nextPlays.push([play]);
            currentPA = [];
        } else {
            currentPA.push(play);
        }
    }
    nextPlays = nextPlays.filter(pa => pa.length > 0);
    nextPlays.push(currentPA);
    if (selectedPlay.value === nextPlays.length - 2) {
        selectedPlay.value = null;
    }
    plays.value = plays.value = nextPlays;
    selectedPlay.value ??= plays.value.length - 1;
};

const appendPlays = (playData) => {
    game.value.plays.push(playData);
    updatePlays();
};

const toggleCast = () => {
    if (!castContext.value) {
        return;
    }
    if (isCasting.value) {
        castContext.value.endCurrentSession(true);
    } else {
        castContext.value.requestSession().then(() => {
            castSession.value = castContext.value.getCurrentSession();
            castSession.value.sendMessage('urn:x-cast:app.statskeeper.game', {
                gameId: game.value.id,
            });
        }).catch(field.value.toast);
    }
};

const fetchData = () => {
    fetch(`/api/game/${game.value.id}`)
        .then(response => response.json())
        .then(data => {
            game.value = data.game;
            state.value = data.state;
            stats.value = data.stats;
            if (selectedInning.value === null) {
                selectedInning.value = state.value.inning;
            }
            updatePlays();
            nextTick(() => {
                if (field.value) {
                    field.value.updateStatus({
                        state: state.value,
                        fielders: fielders.value,
                        runners: runners.value,
                        hitting: hitting.value
                    });
                }
            });
        });
};

const pitchDescription = (pitch) => ({
    's': 'Swinging Strike',
    'c': 'Called Strike',
    '.': 'Ball',
    'b': 'Ball in dirt',
    'x': 'In Play',
    't': 'Foul Tip',
    'r': 'Foul (runner going)',
    'f': 'Foul',
    'p': 'Pitchout',
    'i': 'Intentional Ball',
}[pitch] || `Unknown Pitch ${pitch}`);

onMounted(() => {
    game.value = {
        id: props.gameId,
        state: 'loading',
        ended: props.ended,
    };
    switch (location.hash) {
    case '#away':
         selectedView.value = 'away';
         break;
    case '#home':
         selectedView.value = 'home';
         break;
    default:
         selectedView.value = 'plays';
    }
    // Fetch updated data from API
    if (!game.value.ended) setInterval(() => { fetchData() }, 300000);
    fetchData();
    if (!game.value.ended) {
        window.Echo.channel(`game.${props.gameId}`).listen('.game.updated', (event) => {
            if (event.full) {
                fetchData();
                return;
            }
            if (event.play) {
                appendPlays(event.play);
                event.play.human && field.value && field.value.toast(event.play.human);
            }
            if (event.state) {
                state.value = event.state;
            }
            if (event.stats) {
                // Merge stats
                for (const stat of event.stats) {
                    stats.value[stat.player_id][stat.stat] = stat.value;
                }
                // Recalculate team totals
                const teamTotal = (team) => {
                    return team.players.map(p => p.id)
                        .filter((s, i, arr) => arr.indexOf(s) === i)
                        .map(pid => stats.value[pid])
                        .filter(s => s)
                        .reduce((acc, stats) => {
                            Object.entries(stats).forEach(([stat, value]) => {
                                acc[stat] = (acc[stat] || 0) + value;
                            });
                            return acc;
                        }, {});
                };
                stats.value.home = teamTotal(game.value.home_team);
                stats.value.home['H'] = (stats.value.home['1'] ?? 0) + (stats.value.home['2'] ?? 0) + (stats.value.home['3'] ?? 0) + (stats.value.home['4'] ?? 0);
                stats.value.away = teamTotal(game.value.away_team);
                stats.value.away['H'] = (stats.value.away['1'] ?? 0) + (stats.value.away['2'] ?? 0) + (stats.value.away['3'] ?? 0) + (stats.value.away['4'] ?? 0);
            }
            nextTick(() => {
                if (field.value) {
                    field.value.updateStatus({
                        state: state.value,
                        fielders: fielders.value,
                        runners: runners.value,
                        hitting: hitting.value
                    }, event.play);
                }
            });
        });
    }

    // Cast initialization
    if (!props.isReceiver) {
        initialiseCast();
    }
});

const initialiseCast = () => {
    if (window.cast && window.cast.framework) {
        window.cast.framework.CastContext.getInstance().setOptions({
            receiverApplicationId: import.meta.env.VITE_GOOGLE_CAST_APPLICATION,
            autoJoinPolicy: window.chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
        });
        castContext.value = window.cast.framework.CastContext.getInstance();
        castContext.value.addEventListener(
            window.cast.framework.CastContextEventType.CAST_STATE_CHANGED,
            (event) => {
                if (event.castState === 'CONNECTED') {
                    isCasting.value = true;
                } else {
                    isCasting.value = false;
                }
            }
        );
    } else {
        setTimeout(initialiseCast, 1000);
    }
};

</script>

<template v-if="game.state !== 'loading'">
    <template v-if="isMobile">
        <div class="mobile-menu">
            <div class="mobile-menu-away"><a href="#away" @click="selectedView = 'away'">{{ game?.away_team?.short_name }}</a></div>
            <div class="mobile-menu-play"><a href="#plays-main" @click="selectedView = 'plays'">Plays</a></div>
            <div class="mobile-menu-home"><a href="#home" @click="selectedView = 'home'">{{ game?.home_team?.short_name }}</a></div>
        </div>
        <table id='game-view'>
            <tbody>
                <tr style="max-height: 100%;">
                    <td class='mobile-hide' :class="{ 'selected-view': selectedView === 'away' }">
                        <box-score :game="game" :home="false" :state="state" :stats="stats" />
                    </td>
                    <td style='text-align: center; width: 100%;' class='mobile-hide' :class="{ 'selected-view': selectedView === 'plays' }">
                        <!-- <line-score :game="game" :stats="stats" /> -->
                        <h3 class="geotemporal">{{ new Date(game.firstPitch).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }} at {{ game.location }}</h3>
                        <threed-field ref="field" :game="game" :state="state" :stats="stats" :home-color="game.home_team?.primary_color" :away-color="game.away_team?.primary_color" />
                        <!-- Put Pitcher vs Hitter info here. -->
                        <button v-if="castContext" id="cast-button" @click="toggleCast">{{ isCasting ? 'Stop Casting' : 'Cast to TV' }}</button>
                        <div class="pitcher-vs-hitter" v-if="hitting && pitching && !state.ended">
                            <div v-if="state.half">
                                {{ pitching.person.firstName }}
                                {{ pitching.person.lastName }}<br/>
                                ({{ (stats[pitching.id].Balls ?? 0) + (stats[pitching.id].Strikes ?? 0) }} pitches, {{ stats[pitching.id].K ?? 0 }} Ks)
                            </div>
                            <div>
                                {{ state.atBat[state.half] + 1 }}.
                                {{ hitting.person.firstName }}
                                {{ hitting.person.lastName }}<br/>
                                ({{ stats[hitting.id].H }} for {{ stats[hitting.id].AB ?? 0 }})<br/>
                                <!-- Stats -->
                            </div>
                            <div v-if="!state.half">
                                {{ pitching.person.firstName }}
                                {{ pitching.person.lastName }}<br/>
                                ({{ (stats[pitching.id].Balls ?? 0) + (stats[pitching.id].Strikes ?? 0) }} pitches, {{ stats[pitching.id].K ?? 0 }} Ks)
                            </div>
                        </div>
                        <!-- Put a clickable innings selector. -->
                        <div class="innings-selector">
                        <a v-for="i in state.inning" :key="i" href="#plays-main" class="inning-link" :data-inning="i" :class="{'inning-selected': selectedInning === i}" @click="selectedInning=i">{{ i }}</a>
                        </div>
                        <div id='play-by-play'>
                            <template v-for="(pa, i) in plays" :key="i">
                                <template :style="{ display: (pa[0]?.inning ?? state.inning) === selectedInning ? 'block' : 'none' }">
                                    <div :class="{
                                                    'plate-appearance-container': !pa[0]?.game_event,
                                                    'game-event-container': pa[0]?.game_event,
                                                    'selected': selectedPlay === i,
                                                }" @click="selectedPlay = selectedPlay === i ? null : i">
                                        <template v-for="(play, j) in pa" :key="j">
                                            <div v-if="!play.command" v-for="pitch in play.play.split(',')[0].split('')" :key="`${i}-${pitch}`" class='pitch' :class="pitch" :data-play-id="i" :data-inning="play.inning" :data-inning-half="play.inning_half">{{ pitchDescription(pitch) }}</div>
                                            <div v-if="play.human"
                                                :class="{'run-scoring': play.run_scoring, 'plate-appearance': play.plate_appearance}"
                                                :data-play-id="i"
                                                :data-inning="play.inning"
                                                :data-inning-half="play.inning_half"
                                            >
                                                <i class="fa-solid fa-chevron-down toggle-icon"></i>
                                                {{ play.human }}
                                            </div>
                                            <div v-if="play?.game_event"
                                                class='game-event'
                                                :class="play.inning_half ? 'game-event-home' : 'game-event-away'"
                                                :data-inning="play.inning"
                                                :data-inning-half="play.inning_half"
                                            >
                                                {{ play.game_event }}
                                            </div>
                                        </template>
                                        <div 
                                            v-if="!state.ended && selectedInning === state.inning && (i === plays.length - 1)"
                                            class="plate-appearance"
                                            :data-inning="state.inning" :data-inning-half="state.half"
                                        >
                                            <i class="fa-solid fa-chevron-down toggle-icon"></i> {{ hitting?.person.firstName }} {{ hitting?.person?.lastName }} at bat
                                        </div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </td>
                    <td class='mobile-hide' :class="{ 'selected-view': selectedView === 'home' }">
                        <box-score :game="game" :home="true" :state="state" :stats="stats" />
                    </td>
                </tr>
            </tbody>
        </table>
    </template>
    <template v-else>
        <div class="desktop-layout">
            <div class="field-section">
                <div class="geotemporal">
                    <h2 class="section-title">
                        {{ game?.away_team?.name }} at {{ game?.home_team?.name }}
                        <!-- Cast button -->
                        <button v-if="castContext" id="cast-button" @click="toggleCast">{{ isCasting ? 'Stop Casting' : 'Cast to TV' }}</button>
                    </h2>
                    <h4>{{ new Date(game.firstPitch).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }} at {{ game.location }}</h4>
                </div>
                <threed-field ref="field" :game="game" :state="state" :stats="stats" :home-color="game.home_team?.primary_color" :away-color="game.away_team?.primary_color" />
                <div class="pitcher-vs-hitter" v-if="hitting && pitching && !state.ended">
                    <div v-if="state.half">
                        {{ pitching.person.firstName }}
                        {{ pitching.person.lastName }}<br/>
                        ({{ (stats[pitching.id].Balls ?? 0) + (stats[pitching.id].Strikes ?? 0) }} pitches, {{ stats[pitching.id].K ?? 0 }} Ks)
                    </div>
                    <div>
                        {{ state.atBat[state.half] + 1 }}.
                        {{ hitting.person.firstName }}
                        {{ hitting.person.lastName }}<br/>
                        ({{ stats[hitting.id].H }} for {{ stats[hitting.id].AB ?? 0 }})<br/>
                        <!-- Stats -->
                    </div>
                    <div v-if="!state.half">
                        {{ pitching.person.firstName }}
                        {{ pitching.person.lastName }}<br/>
                        ({{ (stats[pitching.id].Balls ?? 0) + (stats[pitching.id].Strikes ?? 0) }} pitches, {{ stats[pitching.id].K ?? 0 }} Ks)
                    </div>
                </div>
            </div>
            <div class="sidebar">
                <div class="sidebar-menu">
                    <button @click="selectedView = 'plays'" :class="{active: selectedView === 'plays'}">Play by Play</button>
                    <button @click="selectedView = 'away'" :class="{active: selectedView === 'away', 'away-team-color': selectedView === 'away'}">{{ game?.away_team?.name }}</button>
                    <button @click="selectedView = 'home'" :class="{active: selectedView === 'home', 'home-team-color': selectedView === 'home'}">{{ game?.home_team?.name }}</button>
                </div>
                <div class="sidebar-content">
                    <div v-if="selectedView === 'plays'" id='play-by-play'>
                        <div class="innings-selector">
                            <a v-for="i in state.inning" :key="i" href="#plays-main" class="inning-link" :data-inning="i" :class="{'inning-selected': selectedInning === i}" @click="selectedInning=i">{{ i }}</a>
                        </div>
                        <template v-for="(pa, i) in plays" :key="i">
                            <template :style="{ display: (pa[0]?.inning ?? state.inning) === selectedInning ? 'block' : 'none' }">
                                <div :class="{
                                                'plate-appearance-container': !pa[0]?.game_event,
                                                'game-event-container': pa[0]?.game_event,
                                                'selected': selectedPlay === i,
                                            }" @click="selectedPlay = selectedPlay === i ? null : i">
                                    <template v-for="(play, j) in pa" :key="j">
                                        <div v-if="!play.command" v-for="pitch in play.play.split(',')[0].split('')" :key="`${i}-${pitch}`" class='pitch' :class="pitch" :data-play-id="i" :data-inning="play.inning" :data-inning-half="play.inning_half">{{ pitchDescription(pitch) }}</div>
                                        <div v-if="play.human"
                                            :class="{'run-scoring': play.run_scoring, 'plate-appearance': play.plate_appearance}"
                                            :data-play-id="i"
                                            :data-inning="play.inning"
                                            :data-inning-half="play.inning_half"
                                        >
                                            <i class="fa-solid fa-chevron-down toggle-icon"></i>
                                            {{ play.human }}
                                        </div>
                                        <div v-if="play?.game_event"
                                            class='game-event'
                                            :class="play.inning_half ? 'game-event-home' : 'game-event-away'"
                                            :data-inning="play.inning"
                                            :data-inning-half="play.inning_half"
                                        >
                                            {{ play.game_event }}
                                        </div>
                                    </template>
                                    <div 
                                        v-if="!state.ended && selectedInning === state.inning && (i === plays.length - 1)"
                                        class="plate-appearance"
                                        :data-inning="state.inning" :data-inning-half="state.half"
                                    >
                                        <i class="fa-solid fa-chevron-down toggle-icon"></i> {{ hitting?.person.firstName }} {{ hitting?.person?.lastName }} at bat
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                    <box-score v-if="selectedView === 'away'" :game="game" :home="false" :state="state" :stats="stats" />
                    <box-score v-if="selectedView === 'home'" :game="game" :home="true" :state="state" :stats="stats" />
                </div>
            </div>
        </div>
    </template>
</template>