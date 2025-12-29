<template>
  <div :class="teamClasses" style="padding:10px 0 0 0;">
    <h3 :id="team?.short_name" :style="headerStyle">
      {{ team?.name || 'Loading?..?.' }}
    </h3>
    <div v-if="!game" style="color: red;">No game data</div>
    <div v-else-if="!team" style="color: red;">No team data</div>
    <div v-else-if="lineup?.length === 0" style="padding: 10px; color: #666;">
      Loading lineup data...
    </div>
    <table style="text-align:center">
      <thead>
        <tr>
          <th style="text-align:left;" :style="thStyle">Name</th>
          <th :style="thSecondaryStyle" class="mobile-hide">PA</th>
          <th :style="thSecondaryStyle">AB</th>
          <th :style="thSecondaryStyle">R</th>
          <th :style="thSecondaryStyle">H</th>
          <th :style="thSecondaryStyle">RBI</th>
          <th :style="thSecondaryStyle">SO</th>
          <th :style="thSecondaryStyle">BB</th>
          <th :style="thSecondaryStyle" class="mobile-hide">&nbsp;</th>
          <th :style="thSecondaryStyle" class="mobile-hide">PO</th>
          <th :style="thSecondaryStyle" class="mobile-hide">A</th>
          <th :style="thSecondaryStyle" class="mobile-hide">E</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(spot, i) in lineup" :key="i">
          <template v-for="(player, j) in spot" :key="`${i}-${j}`">
            <tr :class="i === atbat ? 'atbat' : ''">
              <td style="text-align:left;">
                <span v-if="player?.number"><sup>#{{ player?.number }}</sup></span>
                <a :href="`/person/${player?.person?.id}`">
                  <span style="text-transform:uppercase;font-weight:520">{{ player?.person?.lastName }}</span>,&nbsp;{{ player?.person?.firstName }}
                </a>
                <span v-if="j === spot?.length - 1">
                  <sup>{{ defenders[player?.id] || 'EH' }}</sup>
                </span>
              </td>
              <td class="mobile-hide">{{ stats[player?.id]?.PA || 0 }}</td>
              <td>{{ stats[player?.id]?.AB || 0 }}</td>
              <td>{{ stats[player?.id]?.R || 0 }}</td>
              <td>{{ (stats[player?.id]?.['1'] || 0) + (stats[player?.id]?.['2'] || 0) + (stats[player?.id]?.['3'] || 0) + (stats[player?.id]?.['4'] || 0) }}</td>
              <td>{{ stats[player?.id]?.RBI || 0 }}</td>
              <td>{{ stats[player?.id]?.SO || stats[player?.id]?.Ks || 0 }}</td>
              <td>{{ stats[player?.id]?.BBs || 0 }}</td>
              <td class="mobile-hide">&nbsp;</td>
              <td class="mobile-hide">{{ stats[player?.id]?.PO || 0 }}</td>
              <td class="mobile-hide">{{ stats[player?.id]?.A || 0 }}</td>
              <td class="mobile-hide">{{ stats[player?.id]?.E || 0 }}</td>
            </tr>
          </template>
        </template>
        <tr style="font-weight: bold;">
          <td class="scorers" colspan="2" style="text-align:left;">Total</td>
          <td class="viewers" style="text-align:left;">Total</td>
          <td class="mobile-hide">{{ totals?.PA ?? 0 }}</td>
          <td>{{ totals?.AB ?? 0 }}</td>
          <td>{{ totals?.R ?? 0 }}</td>
          <td>{{ totals?.H ?? 0}}</td>
          <td>{{ totals?.RBI ?? 0 }}</td>
          <td>{{ totals?.SO ?? 0 }}</td>
          <td>{{ totals?.BBs ?? 0 }}</td>
          <td class="mobile-hide">&nbsp;</td>
          <td class="mobile-hide">{{ totals?.PO ?? 0 }}</td>
          <td class="mobile-hide">{{ totals?.A ?? 0 }}</td>
          <td class="mobile-hide">{{ totals?.E ?? 0 }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Extra stats -->
    <div v-if="totals?.['2']" class="viewers extra-stats">
      <b>2B:</b>
      {{ lineupStat('2') }}.
    </div>
    <div v-if="totals?.['3']" class="viewers extra-stats">
      <b>3B:</b>
      {{ lineupStat('3') }}.
    </div>
    <div v-if="totals?.['4']" class="viewers extra-stats">
      <b>HR:</b>
      {{ lineupStat('4') }}.
    </div>
    <div v-if="totals?.HPB" class="viewers extra-stats">
      <b>HBP:</b>
      {{ lineupStat('HPB') }}.
    </div>
    <div v-if="totals?.TB" class="viewers extra-stats">
      <b>Total Bases:</b>
      {{ lineupStat('TB') }}.
    </div>
    <div v-if="totals?.SB" class="viewers extra-stats">
      <b>Stolen Bases:</b>
      {{ lineupStat('SB') }}.
    </div>
    <div v-if="totals?.CS" class="viewers extra-stats">
      <b>Caught Stealing:</b>
      {{ lineupStat('CS') }}.
    </div>

    <!-- Fielding extra stats -->
    <div v-if="totals?.PB" class="viewers extra-stats">
      <b>Passed Balls:</b>
      {{ lineupStat('PB') }}.
    </div>
    <div v-if="totals?.E" class="viewers extra-stats">
      <b>Errors:</b>
      {{ fielderStat('E') }}.
    </div>

    <!-- Pitching Stats -->
    <h4 :style="headerStyle">Pitching</h4>
    <table style="text-align:center">
      <thead>
        <tr>
          <th class="scorers" :style="thStyle">#</th>
          <th style="text-align:left;" :style="thStyle">Name</th>
          <th :style="thSecondaryStyle">INN</th>
          <th :style="thSecondaryStyle">ER</th>
          <th :style="thSecondaryStyle">R</th>
          <th :style="thSecondaryStyle">H</th>
          <th :style="thSecondaryStyle">K</th>
          <th :style="thSecondaryStyle">BB</th>
          <th :style="thSecondaryStyle" class="mobile-hide">TBF</th>
          <th :style="thSecondaryStyle" class="mobile-hide">B</th>
          <th :style="thSecondaryStyle" class="mobile-hide">S</th>
          <th :style="thSecondaryStyle">Pit</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="player in pitchers" :key="player?.id">
          <td class="scorers">{{ player?.number }}</td>
          <td style="text-align:left;">
            <span style="text-transform:uppercase;font-weight:520">{{ player?.person?.lastName }}</span>,&nbsp;{{ player?.person?.firstName }}
          </td>
          <td>{{ inningsFormat((player?.stats?.TO || 0) / 3) }}</td>
          <td>{{ player.stats.ER || 0 }}</td>
          <td>{{ player.stats.RA || 0 }}</td>
          <td>{{ player.stats.HA || 0 }}</td>
          <td>{{ player.stats.K || 0 }}</td>
          <td>{{ player.stats.BB || 0 }}</td>
          <td class="mobile-hide">{{ player.stats.BFP || 0 }}</td>
          <td class="mobile-hide">{{ player.stats.Balls || 0 }}</td>
          <td class="mobile-hide">{{ player.stats.Strikes || 0 }}</td>
          <td>{{ (player.stats.Balls || 0) + (player.stats.Strikes || 0) }}</td>
        </tr>
        <tr style="font-weight: bold;">
          <td class="scorers" colspan="2" style="text-align:left;">Total</td>
          <td class="viewers" style="text-align:left;">Total</td>
          <td>{{ inningsFormat(totals?.IP) }}</td>
          <td>{{ totals.ER ?? 0 }}</td>
          <td>{{ totals.RA ?? 0 }}</td>
          <td>{{ totals.HA ?? 0 }}</td>
          <td>{{ totals.K ?? 0 }}</td>
          <td>{{ totals.BB ?? 0 }}</td>
          <td class="mobile-hide">{{ totals.BFP ?? 0 }}</td>
          <td class="mobile-hide">{{ totals.Balls ?? 0 }}</td>
          <td class="mobile-hide">{{ totals.Strikes ?? 0 }}</td>
          <td>{{ totals.Pitches ?? 0 }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Pitching extra stats -->
    <div v-if="totals?.HBP" class="viewers extra-stats">
      <b>HBP:</b>
      {{ pitchersStat('HBP') }}.
    </div>
    <div v-if="totals?.WP" class="viewers extra-stats">
      <b>Wild Pitches:</b>
      {{ pitchersStat('WP') }}.
    </div>
    <div class="viewers extra-stats">
      <b>Strikes-balls:</b>
      {{ pitchersStat('Strikes', 'Balls') }}.
    </div>
    <div class="viewers extra-stats">
      <b>Groundouts-Flyouts:</b>
      {{ pitchersStat('GO', 'AO') }}.
    </div>
    <div class="viewers extra-stats">
      <b>Batters faced:</b>
      {{ pitchersStat('BFP') }}.
    </div>
    <div v-if="totals?.IR" class="viewers extra-stats">
      <b>Inherited Runners-Scored:</b>
      {{ pitchersStat('IR', 'IRS') }}.
    </div>
  </div>
</template>

<script>
const POSITIONS = {
  1: 'P',
  2: 'C',
  3: '1B',
  4: '2B',
  5: '3B',
  6: 'SS',
  7: 'LF',
  8: 'CF',
  9: 'RF',
  'DH': 'DH',
  'EH': 'EH',
  'PH': 'PH',
  'PR': 'PR',
  'PR1': 'PR',
  'PR2': 'PR',
  'PR3': 'PR',
};

export default {
  name: 'BoxScore',
  props: {
    game: {
      type: Object,
      required: true
    },
    home: {
      type: Boolean,
      required: true
    },
    state: {
      type: Object,
      required: false,
      default: () => ({})
    },
    stats: {
      type: Object,
      required: false,
      default: () => ({})
    }
  },
  computed: {
    team() {
      return this?.home ? this?.game?.home_team : this?.game?.away_team;
    },
    lineup() {
      return this?.state?.lineup?.[this?.home ? 1 : 0].map(
        spots => spots.map(id => this.team.players.find(p => p.id === id))
      ) || [];
    },
    pitchers() {
      const pitchers = this?.state?.pitchers?.[this.home ? 1 : 0]?.map(
        id => this.team.players.find(p => p.id === id)
      ) || [];
      return pitchers;
    },
    atbat() {
      return this?.state?.atBat?.[this?.home ? 1 : 0] ?? null;
    },
    defending() {
      return this?.home ? !this?.game?.half : this?.game?.half;
    },
    players() {
      return new Set(this.team?.players?.map(player => player.id.toString()) || []);
    },
    teamClasses() {
      const isAway = this?.team?.id === this?.game?.away_team?.id;
      return isAway ? 'away-team-colors box-score-away' : 'home-team-colors box-score-home';
    },
    headerStyle() {
      return {
        color: 'var(--team-primary)',
        'border-bottom': '2px solid var(--team-secondary)',
        'padding-bottom': '5px'
      };
    },
    thStyle() {
      return {
        'background-color': 'var(--team-primary)',
        color: 'white'
      };
    },
    thSecondaryStyle() {
      return {
        'background-color': 'var(--team-secondary)',
        color: 'var(--team-primary)'
      };
    },
    totals() {
      const teamStats = (Object.entries(this.stats).filter(([key]) => this.players.has(key)).reduce((acc, [_, stats]) => {
        Object.entries(stats).forEach(([stat, value]) => {
          acc[stat] = (acc[stat] || 0) + value;
        });
        return acc;
      }, {}));
      teamStats['H'] = (teamStats['1'] || 0) + (teamStats['2'] || 0) + (teamStats['3'] || 0) + (teamStats['4'] || 0);
      return teamStats;
    },
    defenders() {
      const defense = this.state.defense[this?.home ? 1 : 0] ?? {};
      const defenseMap = Object?.entries(defense || {})
        .map(([pos, playerId]) => [playerId, POSITIONS[pos] ?? 'EH'])

      return Object.fromEntries(defenseMap);
    }
  },
  methods: {
    inningsFormat(n) {
      const w = Math?.floor(n);
      const p = n - w;
      if (!p) {
        return w;
      } else if (p < 0.5) {
        return `${w}⅓`;
      } else {
        return `${w}⅔`;
      }
    },
    playersStat(players, ...stats) {
      return players
        .filter(player => player && stats.some(stat => this?.getPlayerStat(player, stat) > 0))
        .map(player => {
          const value = stats.map(stat => this?.getPlayerStat(player, stat)).join('-');
          return `${player?.person?.lastName}${value != '1' ? ' ' + value : ''}`;
        })
        .join(', ');
    },
    lineupStat(...stats) {
      return this.playersStat(this?.lineup?.flat(), ...stats);
    },
    pitchersStat(...stats) {
      return this.playersStat(this?.pitchers, ...stats);
    },
    fielderStat(...stats) {
      const defenders = [...this.lineup?.flat(), ...this.pitchers]
        .filter((player, i, players) => i === players.findIndex(p => p?.id === player?.id));
      return this.playersStat(defenders, ...stats);
    },
    getPlayerStat(player, stat) {
      return this.stats[player?.id]?.[stat] || 0;
    },
  },
  onMounted() {
    // Component mounted
    console?.log('BoxScore mounted for team:', this?.team?.name, this.home);
  }
};
</script>

<style scoped>
/* Add any component-specific styles here */
</style>