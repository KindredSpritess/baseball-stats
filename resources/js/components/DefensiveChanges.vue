<template>
  <div class="defensive-changes">
    <div class="header">
      <h3>Defensive Changes</h3>
      <button @click="$emit('close')" class="close-btn">×</button>
    </div>
    <div class="field-container">
      <div class="field-svg-wrapper">
        <FieldSvg :players="currentDefense" :onFielderTouch="selectPosition" />
      </div>
    </div>

    <!-- Player selection modal -->
    <div v-if="showPlayerSelect" class="player-select-modal">
      <div class="modal-overlay" @click="closePlayerSelect"></div>
      <div class="modal-content">
        <h4>Select new {{ POSITIONS[selectedPosition] }}</h4>
        <div class="player-list">
          <!-- Players without position first -->
          <div
            v-for="player in availablePlayers"
            :key="player.id"
            class="player-option"
            @click="makeDefensiveChange(player)"
          >
            {{ player.person.lastName }}, {{ player.person.firstName[0] }} <span v-if="player.position">({{ player.position }})</span>
          </div>
        </div>
        <button @click="closePlayerSelect" class="cancel-btn">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script>
import FieldSvg from './FieldSvg.vue';

const SHORT_POSITIONS = {
  1: 'P',
  2: 'C',
  3: '1B',
  4: '2B',
  5: '3B',
  6: 'SS',
  7: 'LF',
  8: 'CF',
  9: 'RF'
};

export default {
  name: 'DefensiveChanges',
  components: {
    FieldSvg,
  },
  props: {
    game: Object,
    state: Object,
  },
  emits: ['defensive-change'],
  data() {
    return {
      showPlayerSelect: false,
      selectedPosition: null,
      POSITIONS: {
        1: 'Pitcher',
        2: 'Catcher',
        3: 'First Base',
        4: 'Second Base',
        5: 'Third Base',
        6: 'Shortstop',
        7: 'Left Field',
        8: 'Center Field',
        9: 'Right Field'
      },
    };
  },
  computed: {
    currentDefense() {
      // Get current defensive positions from state
      const defense = this.state.defense[(this.state.half + 1) % 2];
      return Object.fromEntries(
        Object.entries(defense || {})
          .map(([pos, playerId]) => [pos, this.game.players.find(p => p.id === playerId)?.person])
          .filter(([pos, person]) => person)
          .map(([pos, person]) => [pos, person.lastName + ', ' + person.firstName[0]])
        );
    },
    availablePlayers() {
      if (!this.selectedPosition) return [];

      const currentTeam = this.game[this.state.half ? 'away_team' : 'home_team'];
      const defense = this.state.defense[(this.state.half + 1) % 2] || {};
      const players = Object.fromEntries(this.game.players.filter(player => player.team_id === currentTeam.id).map(player => [player.id, player]));

      // Separate players with and without positions
      const playersWithPosition = [];
      Object.entries(defense).forEach(([pos, playerId]) => {
        if (!(pos in SHORT_POSITIONS)) return;
        const player = players[playerId];
        delete players[playerId];
        pos != this.selectedPosition && playersWithPosition.push({...player, position: SHORT_POSITIONS[pos] });
      });

      const playersWithoutPosition = Object.values(players); // Include current player at position
      playersWithoutPosition.sort((a, b) => {
        return a.person.lastName.localeCompare(b.person.lastName) || a.person.firstName.localeCompare(b.person.firstName);
      });

      // Return players without position first, then others
      return [...playersWithoutPosition, ...playersWithPosition];
    },
  },
  methods: {
    getPlayerName(playerId) {
      const team = this.game[this.state.half ? 'home_team' : 'away_team'];
      const player = team.players?.find(p => p.id === playerId);
      if (player) {
        return `${player.person.lastName}, ${player.person.firstName[0]}.`;
      }
      return '';
    },
    getLineupPosition(player) {
      // Find player's position in the lineup (1-based)
      const lineup = this.state.lineup?.[(this.state.half + 1) % 2] || [];
      const index = lineup.findIndex(item => item.at(-1) === player.id);
      return index >= 0 ? index + 1 : '?';
    },
    selectPosition(position) {
      this.selectedPosition = position;
      this.showPlayerSelect = true;
    },
    closePlayerSelect() {
      this.showPlayerSelect = false;
      this.selectedPosition = null;
    },
    makeDefensiveChange(player) {
      const lineupPosition = this.getLineupPosition(player);
      const command = `DC #${lineupPosition} -> ${this.selectedPosition}`;
      this.$emit('defensive-change', command);
      this.closePlayerSelect();
    },
  },
};
</script>

<style scoped>
.defensive-changes {
  margin: 20px 0;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #6c757d;
}

.close-btn:hover {
  background: #f8f9fa;
  color: #000;
}

.field-container {
  position: relative;
  display: inline-block;
  margin: 0 auto;
  max-height: calc(70vh);
  width: 100%;
}

.field-svg-wrapper {
  position: relative;
  max-height: calc(70vh);
  width: calc(70vh);
}

.position-marker {
  position: absolute;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.9);
  border: 2px solid #007bff;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  text-align: center;
  transition: all 0.2s;
}

.position-marker:hover {
  background: rgba(255, 255, 255, 1);
  transform: scale(1.1);
}

.player-name {
  pointer-events: none;
  line-height: 1.2;
}

.player-select-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1000;
}

.modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 400px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-content h4 {
  margin: 0 0 15px 0;
  text-align: center;
}

.player-list {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 15px;
}

.player-option {
  padding: 10px;
  margin: 5px 0;
  border: 1px solid #ddd;
  background: #f9f9f9;
  cursor: pointer;
  border-radius: 4px;
  text-align: center;
}

.player-option:hover {
  background: #e9e9e9;
}

.cancel-btn {
  display: block;
  width: 100%;
  padding: 10px;
  border: 1px solid #6c757d;
  background: #f8f9fa;
  color: #6c757d;
  cursor: pointer;
  border-radius: 4px;
  text-align: center;
}

.cancel-btn:hover {
  background: #e9ecef;
}
</style>