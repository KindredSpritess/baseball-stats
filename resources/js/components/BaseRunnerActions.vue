<template>
  <div class="base-runner-card" :class="{ 'empty': !runner, 'forced': forced }">
    <div class="base-label">{{ baseNames[base] }}</div>
    <template v-if="runner">
      <div class="runner-info">
        <span class="runner-name">{{ runner.person.lastName }}, {{ runner.person.firstName[0] }}<span v-if="nextBase != base"> &rarr; {{ baseNames[nextBase] }}</span></span>
      </div>
      <div class="runner-actions" v-if="step === 'actions' && nextBase < 3 && nextBase >= 0">
        <button v-if="pitch != 'f' && nextBase === 0" @click="logRunnerAction(2)" class="action-btn advance">&rarr; Second</button>
        <button v-if="pitch != 'f' && nextBase < 2" @click="logRunnerAction(3)" class="action-btn advance">&rarr; Third</button>
        <button v-if="pitch != 'f' && nextBase < 3" @click="logRunnerAction(4)" class="action-btn advance">&rarr; Home</button>
        <button @click="logRunnerAction('PO')" class="action-btn out">Put Out</button>
      </div>
      <div class="runner-actions"  v-else-if="step === 'decision'">
        <button v-if="pitch === 'x'" @click="logRunnerAction('')" class="action-btn advance">On Hit</button>
        <button v-if="!noSteal.includes(pitch)" @click="logRunnerAction('SB')" class="action-btn advance">Steal</button>
        <!-- <button v-if="!noSteal.includes(pitch)" @click="logRunnerAction('CS')" class="action-btn out">Caught</button>
        <button v-if="!noSteal.includes(pitch)" @click="logRunnerAction('PO')" class="action-btn out">Picked Off</button> -->
        <button v-if="!fouls.includes(pitch)" @click="logRunnerAction('WP')" class="action-btn advance">Wild Pitch</button>
        <button v-if="!fouls.includes(pitch)" @click="logRunnerAction('PB')" class="action-btn advance">Passed Ball</button>
        <button v-if="!['f', 'r'].includes(pitch)" @click="logRunnerAction('E')" class="action-btn advance">Advance on Error</button>
        <button v-if="!['f', 'r'].includes(pitch)" @click="logRunnerAction('FC')" class="action-btn">Fielder's Choice</button>
        <button @click="step = 'actions'" class="back-btn">Back to Actions</button>
      </div>
      <div class="runner-actions"  v-else-if="step === 'errors'">
        <button @click="decisive = true; step = 'fielders'" class="action-btn advance">Safe on Error</button>
        <button @click="decisive = false; step = 'fielders'" class="action-btn advance">Advance on Error</button>
        <button v-for="e in errors" @click="reuseError(e)" class="action-btn advance">Reuse {{ e }}</button>
      </div>
      <template v-else-if="step === 'fielders'">
        {{ fielders.map(f => positions[f]).join('-') }}
        <div class="runner-actions">
          <button v-if="error" @click="error = 'E'" class="action-btn advance" :class="{ selected: error === 'E' }">Fielding Error</button>
          <button v-if="error" @click="error = 'WT'" class="action-btn advance" :class="{ selected: error === 'WT' }">Throwing Error</button>
          <button v-for="f in [1,2,3,4,5,6,7,8,9]" :key="f" @click="fielders.push(f)" class="action-btn">{{ positions[f] }}</button>
        </div>
        <button @click="completeRunnerAction('')" class="action-btn" :class="error ? 'advance' : 'out'" :disabled="fielders.length === 0">{{ error ? 'Advance' : 'Put Out' }}</button>
        <button v-if="!['f', 'r', 'x'].includes(pitch)" @click="completeRunnerAction('PO')" class="action-btn out" :disabled="fielders.length === 0">Picked Off</button>
        <button v-if="!['f', 'r', 'x'].includes(pitch)" @click="completeRunnerAction('CS')" class="action-btn out" :disabled="fielders.length === 0">Caught Stealing</button>
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
    errors: Array,
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
      decisive: false,
      fielders: [],
      actions: [],
      fouls: ['f', 'r', '', 'x'],
      noSteal: ['x', 'f', 'r'],
      positions: POSITIONS,
      bases: '',
      nextBase: this.base,
    }
  },
  methods: {
    logRunnerAction(...actionCodes) {
      this.step = 'actions';
      for (const actionCode of actionCodes) {
        switch (actionCode) {
          case 'SB':
          case 'WP':
          case 'PB':
          case 'FC':
            this.actions.push(`${this.bases.replace('!', '')}${actionCode}`);
            this.bases = '';
            this.step = 'actions';
            break;
          case 'E':
            this.error = 'E';
            this.step = 'errors';
            break;
          case 'PO':
            this.step = 'fielders';
            break;
          case '':
            this.actions.push(this.bases);
            this.bases = '';
            this.step = 'actions';
            break;
          case 2:
          case 3:
          case 4:
            this.bases = BASES[actionCode - this.nextBase - 1];
            this.nextBase = Math.min(actionCode - 1, 3);
            if (this.preferences.removeAdvancementOptions) {
              this.actions.push(this.bases);
              this.bases = '';
              break;
            }
            // this.actions.push(BASES[actionCode - this.base - 1]);
            this.step = 'decision';
            break;
        }
      }
    },
    reuseError(e) {
      this.actions.push(`(${this.bases.replace('!', '')}${e})`);
      this.decisive = false;
      this.error = false;
      this.step = 'actions';
    },
    completeRunnerAction(outcome) {
      let action = outcome;
      let error = this.error ? `${this.error}${this.fielders.pop()}` : '';
      error = this.decisive ? error.toUpperCase() : error.toLowerCase();
      let fielders = this.fielders.join('-');
      if (fielders && error) {
        error = '-' + error;
      }
      this.actions.push(`${action}${fielders}${error}`);
      if (error) {
        this.$emit('error', fielders + error.toLowerCase());
      }
      this.step = 'actions';
      this.fielders = [];
      this.error = false;
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
  padding: 12px 8px;
  border: 2px solid #007bff;
  border-radius: 8px;
  background-color: #f8f9fa;
  color: #007bff;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.action-btn:hover {
  background-color: #007bff;
  color: white;
}

.action-btn.advance {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}

.action-btn.advance:hover {
  background-color: #218838;
  border-color: #218838;
}

.action-btn.out {
  background-color: #dc3545;
  color: white;
  border-color: #dc3545;
}

.action-btn.out:hover {
  background-color: #c82333;
  border-color: #c82333;
}

.error-btn {
  background-color: #dc3545;
  color: white;
  border-color: #dc3545;
}

.error-btn:hover, .error-btn.selected {
  background-color: #c82333;
  border-color: #c82333;
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