<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="/jquery.min.js"></script>
    <link rel="stylesheet" href="/styles.css" />
    <link rel="stylesheet" href="/game.css" />
    <script src="https://kit.fontawesome.com/cc3e56010d.js" crossorigin="anonymous"></script>
    <!-- define style vars for team colors -->
    <style>
        :root {
            --away-primary: {{ $game->away_team->primary_color ?? '#1e88eA' }};
            --away-secondary: {{ $game->away_team->secondary_color ?? '#ffffff' }};
            --home-primary: {{ $game->home_team->primary_color ?? '#43a047' }};
            --home-secondary: {{ $game->home_team->secondary_color ?? '#fdd835' }};
            --fielding-primary: {{ $game->half ? 'var(--away-primary)' : 'var(--home-primary)' }};
            --fielding-secondary: {{ $game->half ? 'var(--away-secondary)' : 'var(--home-secondary)' }};
            --batting-primary: {{ $game->half ? 'var(--home-primary)' : 'var(--away-primary)' }};
            --batting-secondary: {{ $game->half ? 'var(--home-secondary)' : 'var(--away-secondary)' }};
        }
    </style>
    <title>{{ $game->away_team->name }} @ {{ $game->home_team->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
<body>
<div id="app">
    <div class="mobile-menu">
        <div class="mobile-menu-away"><a href="#away">@{{ game.away_team.short_name }}</a></div>
        <div class="mobile-menu-play"><a href="#plays-main">Plays</a></div>
        <div class="mobile-menu-home"><a href="#home">@{{ game.home_team.short_name }}</a></div>
    </div>
    <table id='game-view'>
        <tr style="max-height: 100%;">
            <td class='mobile-hide' x-column='away'>
                <x-box-score :game="$game" :home="false" />
            </td>
            <td style='text-align: center; width: 100%;' class='mobile-hide' x-column='plays-main'>
                <x-line-score :game="$game" />
                <h3 class="geotemporal">{{ Carbon\Carbon::parse($game->firstPitch)->format('M jS g:i') }} at {{ $game->location }}</h3>
                <p class="current-status" v-if="!game.ended">
                    @{{ state.half ? '⬇️' : '⬆️' }} @{{ state.inning }}
                    (@{{ state.balls }} - @{{ state.strikes }}) @{{ state.outs }} outs
                </p>
                <div>
                    <!-- <x-field-3d :game="$game" /> -->
                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 447.94775 448.12701" xml:space="preserve" fill="#00000000" stroke="#00000000">
                        <g shape-rendering="auto" image-rendering="auto" color-rendering="auto" color-interpolation="sRGB">
                            <!-- SVG content here, similar to original -->
                        </g>
                        <!-- Fielder texts -->
                        <text v-if="game.fielding && game.fielding['1']" text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=260 font-weight="bold" class="fielder-text">@{{ game.fielding['1'].person.lastName }}, @{{ game.fielding['1'].person.firstName[0] }}</text>
                        <!-- Repeat for other positions -->
                    </svg>
                </div>
                @if (!$game->ended)
                <!-- Put Pitcher vs Hitter info here. -->
                <div class="pitcher-vs-hitter" v-if="hitting && pitching">
                    <div v-if="state.half">
                        @{{ pitching.person.firstName }}
                        @{{ pitching.person.lastName }}<br/>
                        (@{{ stats[pitching.id].Pitches }} pitches, @{{ stats[pitching.id].K }} Ks)
                    </div>
                    <div>
                        @{{ state.atBat[state.half] + 1 }}.
                        @{{ hitting.person.firstName }}
                        @{{ hitting.person.lastName }}<br/>
                        (@{{ stats[hitting.id].H }} for @{{ stats[hitting.id].AB ?? 0 }})<br/>
                        <!-- Stats -->
                    </div>
                    <div v-if="!state.half">
                        @{{ pitching.person.firstName }}
                        @{{ pitching.person.lastName }}<br/>
                        (@{{ stats[pitching.id].Pitches }} pitches, @{{ stats[pitching.id].K }} Ks)
                    </div>
                </div>
                @endif
                <!-- Put a clickable innings selector. -->
                <div class="innings-selector">
                  <a v-for="i in state.inning" :key="i" href="#play-by-play" class="inning-link" :data-inning="i" @click="selectedInning=i">@{{ i }}</a>
                </div>
                <div id='play-by-play'>
                    <template v-for="(pa, i) in plays" :key="i">
                        <template :style="{ display: pa[0].inning === selectedInning ? 'block' : 'none' }">
                            <div :class="{
                                            'plate-appearance-container': !pa[0].game_event,
                                            'game-event-container': pa[0].game_event,
                                            'selected': selectedPlay === i,
                                         }" @click="selectedPlay = selectedPlay === i ? null : i">
                                <template v-for="(play, j) in pa" :key="j">
                                    <!-- @{{ JSON.stringify(play) }} -->
                                    <div v-if="!play.command" v-for="pitch in play.play.split(',')[0].split('')" :key="`${i}-${pitch}`" class='pitch' :class="pitch" :data-play-id="i" :data-inning="play.inning" :data-inning-half="play.inning_half">@{{ pitchDescription(pitch) }}</div>
                                    <div v-if="play.human"
                                        :class="{'run-scoring': play.run_scoring, 'plate-appearance': play.plate_appearance}"
                                        :data-play-id="i"
                                        :data-inning="play.inning"
                                        :data-inning-half="play.inning_half"
                                    >
                                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                                        @{{ play.human }}
                                    </div>
                                    <div v-if="play.game_event"
                                        class='game-event'
                                        :class="play.inning_half ? 'game-event-home' : 'game-event-away'"
                                        :data-inning="play.inning"
                                        :data-inning-half="play.inning_half"
                                    >
                                        @{{ play.game_event }}
                                    </div>
                                </template>
                                <div 
                                    v-if="!game.ended && selectedInning === state.inning && (i === plays.length - 1)"
                                    class="plate-appearance"
                                    :data-inning="state.inning" :data-inning-half="state.half"
                                >
                                    <i class="fa-solid fa-chevron-down toggle-icon"></i> @{{ hitting?.person.firstName }} @{{ hitting?.person?.lastName }} at bat
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </td>
            <td class='mobile-hide' x-column='home'>
                <x-box-score :game="$game" :home="true" />
            </td>
        </tr>
    </table>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
      return {
        game: @json($game),
        stats: {},
        state: {},
        selectedInning: JSON.parse("{{ $game->inning }}"),
        selectedPlay: null,
        plays: [],
      }
    },
    methods: {
      pitchDescription(pitch) {
        return {
          's': 'Swinging Strike',
          'c': 'Called Strike',
          '.': 'Ball',
          'b': 'Ball in dirt',
          'x': 'In Play',
          't': 'Foul Tip',
          'r': 'Foul (runner going)',
          'f': 'Foul'
        }[pitch] || `Unknown Pitch ${pitch}`;
      },
      fetchData() {
        fetch(`/api/game/{{ $game->id }}`)
            .then(response => response.json())
            .then(data => {
                this.game = data.game;
                this.state = data.state;
                this.stats = data.stats;
                // Combine plays into plate appearances.
                const plays = [];
                let currentPA = [];
                for (const play of this.game.plays) {
                    if (play.inning === null) {
                        continue;
                    }
                    if (play.plate_appearance) {
                        currentPA.push(play);
                        plays.push(currentPA);
                        currentPA = [];
                    } else if (play.game_event) {
                        plays.push(currentPA);
                        plays.push([play]);
                        currentPA = [];
                    } else {
                        currentPA.push(play);
                    }
                }
                plays.push(currentPA);
                if (this.selectedPlay === this.plays.length - 1) {
                    this.selectedPlay = null;
                }
                this.plays = plays.filter(pa => pa.length > 0);
                this.selectedPlay ??= this.plays.length - 1;
            });
        },
    },
    computed: {
      teams() {
        return [this.game.away_team, this.game.home_team];
      },
      hitting() {
        const team = this.teams[this.state.half];
        const lineup = this.state.lineup?.[this.state.half];
        const atBat = this.state.atBat?.[this.state.half];
        return team?.players?.find(player => player.id === lineup[atBat].at(-1));
      },
      pitching() {
        const team = this.teams[1 - this.state.half];
        const lineup = this.state.lineup?.[1 - this.state.half];
        const defense = this.state.defense?.[1 - this.state.half];
        return team?.players?.find(player => player.id === defense?.['1']);
      },
      fielders() {
        const team = this.teams[1 - this.state.half];
        const defense = this.state.defense?.[1 - this.state.half];
        const fielders = {};
        for (const pos in defense) {
          fielders[pos] = team?.players?.find(player => player.id === defense[pos]);
        }
        return fielders;
      },
      runners() {
        const team = this.teams[this.state.half];
        const bases = {};
        for (const base in this.state.bases) {
          const runnerId = this.state.bases[base];
          if (runnerId) {
            bases[base] = team?.players?.find(player => player.id === runnerId);
          }
        }
        return bases;
      },
    },
    mounted() {
        // Fetch updated data from API
        if (!this.game.ended) setInterval(() => { this.fetchData() }, 15000);
        this.fetchData();
    },
}).mount('#app');
</script>

<script>
$(document).ready(() => {
    if (window.innerWidth <= 768) {
        const [, column] = (window.location.hash || '#plays-main').split('#');
        $('[x-column]').hide();
        $(`[x-column=${column}]`).show();
    }
    // Click the current inning link to filter plays
    const currentInning = "{{ $game->inning }}";
    $(`.inning-link[data-inning="${currentInning}"]`).click();
});

$('.mobile-menu a').on('click', (e) => {
    const [, column] = e.target.href.split('#');
    $('.mobile-hide').hide();
    $(`[x-column=${column}]`).show();
});

$('.inning-link').on('click', (e) => {
    e.preventDefault();
    const inning = $(e.target).data('inning');
    $('.inning-link.inning-selected').removeClass('inning-selected');
    $(e.target).addClass('inning-selected');
    $(`#play-by-play div:not([data-inning="${inning}"]):not(.plate-appearance-container)`).hide();
    $(`#play-by-play [data-inning="${inning}"]`).show();
    $('#play-by-play').scrollTop(0);
});

// When touching an svg with a title, show the title as a tooltip
$('#Layer_1').on('touchstart', 'circle, polygon', (e) => {
    const title = e.target.querySelector('title');
    if (title) {
        alert(title.textContent);
    }
});
</script>
</body>
</html>