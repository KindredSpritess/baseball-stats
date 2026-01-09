<template>
  <div class="lineup-changes-overlay">
    <div class="lineup-changes-modal">
      <div class="header">
        <h3>Lineup Changes</h3>
        <button @click="$emit('close')" class="close-btn">×</button>
      </div>

      <div class="lineup-container">
        <div class="main-content">
          <div class="lineup-column">
            <div class="lineup-list">
              <div
                v-for="(spot, index) in currentLineup"
                :key="index"
                class="lineup-spot"
                :class="{ selected: selectedSpot === index }"
                @click="selectSpot(index)"
              >
                <div class="spot-number">{{ index + 1 }}</div>
                <div class="player-info">
                  <div class="player-name" @click.stop="selectPlayer(index)">
                    <span :class="[changes[index]?.type]">
                      {{ getPlayerName(spot) }}
                    </span>
                    <span v-if="changes[index]?.type === 'substitution'">
                      &nbsp;→&nbsp;{{ changes[index].newPlayerName }}
                    </span>
                  </div>
                  <div class="player-position">
                    {{ SHORT_POSITIONS[getPlayerPosition(spot)] }}
                    <span v-if="changes[index]?.type === 'position'" class="position-arrow">
                      &nbsp;→&nbsp;{{ SHORT_POSITIONS[changes[index].newPosition] || SHORT_POSITIONS[getPlayerPosition(changes[index].newPlayerId)] }}
                    </span>
                  </div>
                </div>
              </div>
              <div
               v-if="currentDefense.DH && currentDefense[1]"
               class="lineup-spot"
               @click="selectPlayer(-1)"
              >
                <div class="spot-number">P</div>
                <div class="player-info">
                  <div class="player-name">
                    {{ getPlayerName(currentDefense[1]) }}
                  </div>
                  <div class="player-position">
                    P
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedSpot !== null && selectedSpot !== -1" class="position-column">
            <div class="position-selector">
              <h4>Select New Position for {{ getPlayerName(currentLineup[selectedSpot]) }}</h4>
              <div class="position-buttons">
                <button
                  v-for="(posName, posNum) in POSITIONS"
                  :key="posNum"
                  :class="{ selected: !(posNum in nextDefense) }"
                  @click="setProposedPosition(parseInt(posNum))"
                  class="position-btn"
                >
                  {{ posName }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Player selection modal -->
      <div v-if="showPlayerSelect" class="player-select-modal">
        <div class="modal-overlay" @click="closePlayerSelect"></div>
        <div class="modal-content">
          <h4>Replace {{ getPlayerName(selectedSpot === -1 ? currentDefense[1] : currentLineup[selectedSpot]) }}</h4>
          <div class="player-list">
            <div
              v-for="player in availablePlayers"
              :key="player.id"
              class="player-option"
              @click="replacePlayer(player)"
            >
              {{ player.person.lastName }}, {{ player.person.firstName[0] }}
            </div>
          </div>
          <button @click="closePlayerSelect" class="cancel-btn">Cancel</button>
        </div>
      </div>

      <!-- <div class="changes-summary" v-if="Object.keys(changes).length > 0">
        <h4>Pending Changes:</h4>
        <ul>
          <li v-for="(change, _) in changes" :key="change.id">
            {{ change.command }}
          </li>
        </ul>
      </div> -->

      <div class="action-buttons" v-if="Object.keys(changes).length">
        <button @click="applyChanges" class="apply-btn">
          Apply Changes
        </button>
        <button @click="clearChanges" class="clear-btn">Clear All</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LineupChanges',
  props: {
    game: Object,
    state: Object,
    preferences: Object,
  },
  emits: ['lineup-change', 'close'],
  data() {
    const output = {
      selectedSpot: null,
      showPlayerSelect: false,
      changes: {},
      changeId: 0,
      teamPlayers: [],
      loadingPlayers: false,
      POSITIONS: {
        1: 'Pitcher',
        2: 'Catcher',
        3: 'First Base',
        4: 'Second Base',
        5: 'Third Base',
        6: 'Shortstop',
        7: 'Left Field',
        8: 'Center Field',
        9: 'Right Field',
      },
      SHORT_POSITIONS: {
        1: 'P',
        2: 'C',
        3: '1B',
        4: '2B',
        5: '3B',
        6: 'SS',
        7: 'LF',
        8: 'CF',
        9: 'RF',
        DH: 'DH',
        EH: 'EH',
      },
    };
    if (this.preferences?.use_dh) {
      output.POSITIONS['DH'] = 'Designated Hitter';
    }
    return output;
  },
  computed: {
    currentLineup() {
      const lineup = this.state.lineup?.[(this.state.half + 1) % 2] || [];
      return lineup.map(spot => spot.at(-1));
    },
    currentDefense() {
      return this.state.defense?.[(this.state.half + 1) % 2] || {};
    },
    nextDefense() {
      const defense = {...this.state.defense?.[(this.state.half + 1) % 2]};
      Object.values(this.changes).forEach(change => {
        if (defense[change.oldPosition] === change.playerId) {
          delete defense[change.oldPosition];
        }
        defense[change.newPosition] = change.playerId;
      });
      return defense;
    },
    availablePlayers() {
      if (this.selectedSpot === null) return [];

      // Convert teamPlayers object to array format compatible with the template
      return Object.entries(this.teamPlayers).map(([name, playerData]) => ({
        id: playerData.person.id,
        person: playerData.person,
        number: playerData.number,
      })).sort((a, b) => {
        return a.person.lastName.localeCompare(b.person.lastName) ||
               a.person.firstName.localeCompare(b.person.firstName);
      });
    },
  },
  mounted() {
    this.fetchTeamPlayers();
  },
  methods: {
    async fetchTeamPlayers() {
      this.loadingPlayers = true;
      try {
        const currentTeam = this.game[this.state.half ? 'away_team' : 'home_team'];
        const response = await fetch(`/api/players/team/${currentTeam.id}`);
        const data = await response.json();
        this.teamPlayers = data;
      } catch (error) {
        console.error('Error fetching team players:', error);
      } finally {
        this.loadingPlayers = false;
      }
    },
    getPlayerName(playerId) {
      const player = this.game.players.find(p => p.id === playerId);
      if (player) {
        return `${player.person.lastName}, ${player.person.firstName}`;
      }
      return 'Unknown Player';
    },
    getPlayerPosition(playerId) {
      const position = Object.entries(this.currentDefense).find(([pos, pId]) => pId === playerId);
      if (position) {
        return position[0];
      }
      return 'EH'; // Extra Hitter
    },
    selectSpot(spotIndex) {
      this.selectedSpot = spotIndex;
    },
    selectPlayer(spot) {
      this.selectedSpot = spot;
      this.showPlayerSelect = true;
    },
    setProposedPosition(position) {
      // Add change to list
      const currentPlayerId = this.currentLineup[this.selectedSpot];
      const currentPosition = this.getPlayerPosition(currentPlayerId);

      // If there was a player replacement pending, remove it
      Object.entries(this.changes).forEach(([key, change]) => {
        if (position === change.newPosition) {
          delete this.changes[key];
        }
      });

      this.changes[this.selectedSpot] = {
        id: ++this.changeId,
        type: 'position',
        lineupSpot: this.selectedSpot + 1,
        playerId: currentPlayerId,
        oldPosition: currentPosition,
        newPosition: position,
        command: `DC #${this.selectedSpot + 1} -> ${position}`,
      };

      // If there was a player at the new position and they haven't been changed yet, create a change for them to EH.
      const defense = this.currentDefense;
      const playerAtNewPos = Object.entries(defense).find(([pos, pId]) => pos == position && pId !== currentPlayerId);
      if (playerAtNewPos) {
        const playerIdAtNewPos = playerAtNewPos[1];
        const lineupSpotAtNewPos = this.currentLineup.findIndex(spot => spot === playerIdAtNewPos);
        if (lineupSpotAtNewPos !== -1 && !this.changes[lineupSpotAtNewPos]) {
          this.changes[lineupSpotAtNewPos] = {
            id: ++this.changeId,
            type: 'position',
            lineupSpot: lineupSpotAtNewPos + 1,
            playerId: playerIdAtNewPos,
            oldPosition: position,
            newPosition: 'EH',
            command: `DC #${lineupSpotAtNewPos + 1} -> EH`,
          };
        }
      }
    },
    closePlayerSelect() {
      this.showPlayerSelect = false;
    },
    replacePlayer(newPlayer) {
      const defenseTeam = this.game[this.state.half ? 'away_team' : 'home_team'];
      let command = `@${defenseTeam.short_name} ${newPlayer.person.lastName}, ${newPlayer.person.firstName}`;
      if (newPlayer.number) {
        command += ` #${newPlayer.number}`;
      }

      let currentPlayerId;
      let currentPosition;
      if (this.selectedSpot === -1) {
        // Relief pitcher
        currentPlayerId = this.currentDefense[1];
        currentPosition = 1;
        command += ': 1';
      } else {
        currentPlayerId = this.currentLineup[this.selectedSpot];
        console.log('Setting position', this.selectedSpot, 'for player', currentPlayerId);
        currentPosition = this.getPlayerPosition(currentPlayerId);
        // Defensive substitution
        command = `DSUB ${command}: ${currentPosition}`;
      }

      this.changes[this.selectedSpot] = {
        id: ++this.changeId,
        type: 'substitution',
        lineupSpot: this.selectedSpot + 1,
        oldPlayerId: currentPlayerId,
        newPlayerId: newPlayer.id,
        newPlayerName: `${newPlayer.person.lastName}, ${newPlayer.person.firstName}`,
        position: currentPosition,
        command: command,
      };

      this.closePlayerSelect();
    },
    applyChanges() {
      // Emit each change
      this.$emit('log-play', ...Object.values(this.changes).map(({ command }) => command));

      // Clear changes after applying
      this.changes = {};
      this.selectedSpot = null;
      this.$emit('close');
    },
    clearChanges() {
      this.changes = {};
      this.selectedSpot = null;
    },
  },
};
</script>

<style scoped>
.lineup-changes-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lineup-changes-modal {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 95vw;
  max-height: 90vh;
  width: 900px;
  padding: 20px;
  overflow-y: auto;
  position: relative;
}

.lineup-changes {
  margin: 20px 0;
  max-width: 600px;
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

.lineup-container {
  margin-bottom: 20px;
}

.main-content {
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

@media (max-width: 768px) {
  .main-content {
    flex-direction: column;
  }
  
  .position-column {
    flex: none;
    min-width: auto;
  }
  
  .lineup-changes-modal {
    width: 95vw;
    max-width: 95vw;
  }
}

.lineup-column {
  flex: 1;
  min-width: 0;
}

.position-column {
  flex: 0 0 250px;
  min-width: 200px;
}

.lineup-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}

.lineup-spot {
  display: flex;
  align-items: center;
  padding: 10px;
  border: 2px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.lineup-spot:hover {
  border-color: #007bff;
  background: #f8f9ff;
}

.lineup-spot.selected {
  border-color: #007bff;
  background: #e7f3ff;
}

.spot-number {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #007bff;
  color: white;
  border-radius: 50%;
  font-weight: bold;
  margin-right: 15px;
}

.player-info {
  flex: 1;
}

.player-name {
  font-weight: bold;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: background 0.2s;
}

.player-name .substitution {
  text-decoration: line-through;
}

.player-name .position {
  color: #28a745;
  text-decoration: underline;
}

.player-name:hover {
  background: rgba(0, 123, 255, 0.1);
}

.player-position {
  font-size: 0.9em;
  color: #666;
  margin-top: 2px;
}

.position-arrow {
  font-weight: bold;
  color: #007bff;
  margin-left: 10px;
}

.position-selector {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background: #f9f9f9;
}

.position-selector h4 {
  margin: 0 0 15px 0;
  text-align: center;
}

.position-buttons {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}

.position-btn {
  padding: 10px 8px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 4px;
  text-align: center;
  transition: all 0.2s;
  font-size: 14px;
  white-space: nowrap;
}

.position-btn:hover {
  background: #f0f0f0;
}

.position-btn.selected {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.player-select-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1001;
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

.changes-summary {
  margin: 20px 0;
  padding: 15px;
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 4px;
}

.changes-summary h4 {
  margin: 0 0 10px 0;
}

.changes-summary ul {
  margin: 0;
  padding-left: 20px;
}

.changes-summary li {
  margin: 5px 0;
}

.action-buttons {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.apply-btn, .clear-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.apply-btn {
  background: #28a745;
  color: white;
}

.apply-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.apply-btn:hover:not(:disabled) {
  background: #218838;
}

.clear-btn {
  background: #dc3545;
  color: white;
}

.clear-btn:hover {
  background: #c82333;
}
</style>