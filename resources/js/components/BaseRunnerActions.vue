<template>
  <div class="base-runner-card" :class="{ 'empty': !runner, 'forced': forced }">
    <div class="base-label">{{ baseNames[base] }}</div>
    <template v-if="runner">
      <div class="runner-info">
        <span class="runner-name">{{ runner.person.lastName }}, {{ runner.person.firstName[0] }}<span v-if="nextBase != base"> &rarr; {{ baseNames[nextBase] }}</span></span>
      </div>
      <div class="runner-actions" v-if="step === 'actions' && nextBase < 3 && nextBase >= 0">
        <button v-if="!preferences.removeAdvancementOptions && !noSteal.includes(pitch)" @click="logRunnerAction('SB')" class="action-btn">Steal </button>
        <!-- <button v-if="!preferences.removeAdvancementOptions && !noSteal.includes(pitch)" @click="logRunnerAction('CS')" class="action-btn">Caught</button>
        <button v-if="!preferences.removeAdvancementOptions && !noSteal.includes(pitch)" @click="logRunnerAction('PO')" class="action-btn">Picked Off</button> -->
        <button v-if="!preferences.removeAdvancementOptions && !fouls.includes(pitch)" @click="logRunnerAction('WP')" class="action-btn">Wild Pitch</button>
        <button v-if="!preferences.removeAdvancementOptions && !fouls.includes(pitch)" @click="logRunnerAction('PB')" class="action-btn">Passed Ball</button>
        <button v-if="(preferences.removeAdvancementOptions || pitch == 'x') && base === 0" @click="logRunnerAction(2)" class="action-btn">Advance to Second</button>
        <button v-if="(preferences.removeAdvancementOptions || pitch == 'x') && base < 2" @click="logRunnerAction(3)" class="action-btn">Advance to Third</button>
        <button v-if="(preferences.removeAdvancementOptions || pitch == 'x') && base < 3" @click="logRunnerAction(4)" class="action-btn">Advance to Home</button>
        <button @click="logRunnerAction('PO')" class="action-btn">Put Out</button>
        <button v-if="!preferences.removeAdvancementOptions && !['f', 'r'].includes(pitch)" @click="logRunnerAction('E')" class="action-btn">Advance on Error</button>
        <button v-if="!preferences.removeAdvancementOptions && !['f', 'r'].includes(pitch)" @click="logRunnerAction('FC')" class="action-btn">Fielder's Choice</button>
      </div>
      <template v-else-if="step === 'fielders'">
        {{ fielders.map(f => positions[f]).join(' -> ') }}
        <div class="runner-actions">
          <button v-if="error" @click="error = 'E'" class="action-btn error-btn" :class="{ selected: error === 'E' }">Fielding Error</button>
          <button v-if="error" @click="error = 'WT'" class="action-btn error-btn" :class="{ selected: error === 'WT' }">Throwing Error</button>
          <button v-for="f in [1,2,3,4,5,6,7,8,9]" :key="f" @click="fielders.push(f)" class="action-btn">{{ positions[f] }}</button>
        </div>
        <button @click="completeRunnerAction('')" class="action-btn">{{ error ? 'Advance' : 'Put Out' }}</button>
        <button @click="completeRunnerAction('PO')" class="action-btn">Picked Off</button>
        <button @click="completeRunnerAction('CS')" class="action-btn">Caught Stealing</button>
        <button @click="step = 'actions'; error = false; fielders = []" class="back-btn">← Back to Actions</button>
      </template>
    </template>
    <template v-else>
      <div class="empty-base">
        <span>Empty</span>
      </div>
    </template>
  </div>
</template>

<script>

const BASES = {
  '-1': '`', 1: '!', 2: '@', 3: '#'
};

const POSITIONS = {
  1: 'P', 2: 'C', 3: '1B', 4: '2B', 5: '3B', 6: 'SS', 7: 'LF', 8: 'CF', 9: 'RF'
};

export default {
  name: 'BaseRunnerActions',
  props: {
    base: Number,
    game: Object,
    state: Object,
    pitch: String,
    forced: Boolean,
    preferences: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    runner() {
      const baseIndex = this.base;
      const playerId = this.state.bases[baseIndex];
      return this.game.players.find(p => p.id === playerId);
    },
    play() {
      return this.state.bases[this.base] ? this.actions.join('/') : '';
    }
  },
  data() {
    return {
      baseNames: {
        '-1': 'Out',
        0: '1st',
        1: '2nd',
        2: '3rd',
        3: 'Home'
      },
      step: 'actions',
      error: false,
      fielders: [],
      actions: [],
      fouls: ['f', 'r', '', 'x'],
      noSteal: ['x', 'f', 'r'],
      positions: POSITIONS,
      nextBase: this.base,
    }
  },
  methods: {
    logRunnerAction(actionCode) {
      switch (actionCode) {
        case 'SB':
          this.actions.push('SB');
          this.nextBase = Math.min(this.nextBase + 1, 3);
          break;
        case 'E':
          this.error = 'E';
        case 'PO':
          this.step = 'fielders';
          break;
        case 'WP':
          this.actions.push('WP');
          this.nextBase = Math.min(this.nextBase + 1, 3);
          break;
        case 'PB':
          this.actions.push('PB');
          this.nextBase = Math.min(this.nextBase + 1, 3);
          break;
        case 'ADV':
          this.actions.push('ADV');
          break;
        case 'FC':
          this.actions.push('FC');
          this.nextBase = Math.min(this.nextBase + 1, 3);
          break;
        case 2:
        case 3:
        case 4:
          this.actions.push(BASES[actionCode - this.base - 1]);
          this.nextBase = Math.min(actionCode - 1, 3);
          break;
      }
    },
    completeRunnerAction(outcome) {
      let action = outcome;
      let error = this.error ? `${this.error}${this.fielders.pop()}` : '';
      let fielders = this.fielders.join('-');
      if (fielders && error) {
        error = '-' + error;
      }
      this.actions.push(`${action}${fielders}${error}`);
      this.step = 'actions';
      this.error = false;
      this.fielders = [];
      this.nextBase = this.error ? this.base + 1 : -1;
    },
    reset() {
      this.step = 'actions';
      this.error = false;
      this.fielders = [];
      this.actions = [];
      this.nextBase = this.base;
    },
  }
}
</script>

<style scoped>
.base-runner-card {
  flex: 1;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f9f9f9;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.base-runner-card.empty {
  background-color: #f0f0f0;
  border-color: #ccc;
  opacity: 0.7;
}

.base-runner-card.forced:not(.empty) {
  border-color: #007bff;
  box-shadow: 0 0 10px #007bff;
}

.base-label {
  font-weight: bold;
  text-align: center;
  margin-bottom: 10px;
  color: #333;
}

.runner-info {
  text-align: center;
  margin-bottom: 10px;
}

.runner-name {
  font-weight: bold;
  font-size: 14px;
  color: #333;
}

.runner-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 5px;
}

.action-btn {
  padding: 6px 8px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: white;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.action-btn:hover {
  background-color: #218838;
}

.error-btn {
  color: #dc3545;
  background-color: white;
  border: 1px solid #dc3545;
}
.error-btn:hover, .error-btn.selected {
  background-color: #dc3545;
  color: white;
}


.empty-base {
  text-align: center;
  color: #666;
  font-style: italic;
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>