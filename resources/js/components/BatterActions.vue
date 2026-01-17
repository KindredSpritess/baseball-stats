<template>
  <div class="batter-actions">
    <div class="at-bat-status">
      <div class="hitter-name">
        <strong>Hitter:</strong> {{ hitter ? hitter : 'N/A' }}
        <span v-if="base"> ({{ BASES[base] }})</span>
      </div>
      <div class="count-display">
        <span class="balls">{{ currentBalls }}</span> - <span class="strikes">{{ currentStrikes }}</span>
      </div>
      <div class="play-sequence">
        <strong>Play:</strong> {{ finalPlay || (currentBalls + currentStrikes === 0 ? 'Start of at-bat' : 'In progress at-bat') }}
      </div>
      <div class="pitcher-name">
        <strong>Pitcher:</strong> {{ pitcher ? pitcher : 'N/A' }} ({{ pitchCount }} pitches)
      </div>
    </div>

    <!-- Pitch Selection (main interface) -->
    <div v-if="stage === 'pitch' && !runnerPlays.some(x => x.length)" class="step">
      <h3>Select Pitch Outcome</h3>
      <div class="options-grid">
        <button v-for="(description, code) in pitchOutcomes" 
                :key="code" 
                @click="addPitch(code)" 
                :class="['option-btn', basePitchClasses[code]]">
          {{ description }}
        </button>
        <button v-for="(description, code) in outcomes"
                :key="code" 
                @click="addResult(code)" 
                :class="['option-btn', basePitchClasses[code]]">
          {{ description }}
        </button>
      </div>
      <div class="pitch-actions">
        <button v-if="pitchSequence" @click="undoLastPitch" class="undo-btn">↶ Undo Last Pitch</button>
      </div>
    </div>

    <div v-else-if="stage === 'trajectory'" class="step">
      <h3>Select Ball Trajectory</h3>
      <div class="options-grid">
        <button @click="selectTrajectory('G')" class="option-btn primary">Ground Ball</button>
        <button v-if="!preferences.simplifyTrajectories" @click="selectTrajectory('L')" class="option-btn primary">Line Drive</button>
        <button @click="selectTrajectory('F')" class="option-btn primary">Fly Ball</button>
        <button v-if="!preferences.simplifyTrajectories" @click="selectTrajectory('P')" class="option-btn primary">Pop Up</button>
      </div>
      <button @click="undoLastPitch(); stage = 'pitch'" class="back-btn">← Back to Pitching</button>
    </div>

    <div v-else-if="stage === 'location'" class="step location-full">
      <h3 v-if="trajectory === 'G'">Touch where ball was fielded</h3>
      <h3 v-else>Touch where ball landed</h3>
      <FieldSvg :location="location" :onTouch="selectLocation"/>
    </div>

    <!-- Scoring Descision -->
    <div v-else-if="stage === 'scoring-decision'" class="step">
      <h3>Select Scoring Decision</h3>
      <div class="options-grid">
        <button @click="makeDecision('H')" class="option-btn advance">Hit</button>
        <button @click="makeDecision('FC')" class="option-btn advance">Fielder's Choice</button>
        <button v-if="!preferences.removeErrors" @click="makeDecision('E')" class="option-btn advance">Error</button>
        <button @click="makeDecision('CI')" class="option-btn advance">Catcher's Interference</button>
        <button @click="makeDecision('PO')" class="option-btn out">Put Out</button>
      </div>
      <button @click="stage = 'location'" class="back-btn">← Back to Location</button>
    </div>

    <!-- Select rule put out by. -->
    <div v-else-if="stage === 'select-rule'" class="step">
      <h3>Select Rule for Put Out</h3>
      <div class="options-grid">
        <button @click="selectRule('BOB')" class="option-btn">Batting out of Box</button>
        <button @click="selectRule('INT')" class="option-btn">Interference</button>
      </div>
      <button @click="stage = 'scoring-decision'" class="back-btn">← Back to Scoring Decision</button>
    </div>

    <div v-else-if="stage === 'total-bases'" class="step">
      <h3>Select Total Bases Gained</h3>
      <div class="options-grid">
        <button @click="selectBases(1)" class="option-btn advance">One</button>
        <button @click="selectBases(2)" class="option-btn advance">Two</button>
        <button @click="selectBases(3)" class="option-btn advance">Three</button>
        <button @click="selectBases(4)" class="option-btn advance">Four</button>
      </div>
      <button @click="stage = 'scoring-decision'" class="back-btn">← Back to Scoring Decision</button>
    </div>

    <div v-else-if="stage === 'strikeout-options'" class="step">
      <h3>Strikeout Options</h3>
      <div class="options-grid">
        <button @click="addResult('K2')" class="option-btn out">Put Out by Catcher</button>
        <button @click="stage = 'fielding-outcome'" class="option-btn out">Put Out Other</button>
        <button @click="addResult('KWP')" class="option-btn advance">Reached on Wild Pitch</button>
        <button @click="addResult('KPB')" class="option-btn advance">Reached on Passed Ball</button>
        <button @click="decision = 'E'; decisive = true; stage = 'fielding-outcome'" class="option-btn advance">Reached on Error</button>
      </div>
      <button @click="stage = 'pitch'; trajectory = ''; undoLastPitch()" class="back-btn">← Undo Last Pitch</button>
    </div>

    <div v-else-if="stage === 'fielding-outcome'" class="step">
      <FielderSelection :defense="state.defense[(state.half+1)%2]" :players="game.players" :actions="fieldingActions" @action-selected="handleFieldingAction" />
    </div>

    <!-- Do we need to advance further? -->
    <div v-else-if="stage === 'further-advance?'" class="step">
      <h3>Further Advancement?</h3>
      <div class="options-grid">
        <!-- In Play results -->
        <button @click="stage = 'further-advance'" class="option-btn advance">Advanced Further</button>
        <button @click="stage = 'at-bat-ended'" class="submit-btn">Done Advancing</button>
      </div>
    </div>

    <!-- How do they advance? -->
    <div v-else-if="stage === 'further-advance' && base < 4" class="step">
      <h3>Further Advancement?</h3>
      <div class="options-grid">
        <!-- In Play results -->
        <button @click="advance('E')" class="option-btn advance">Advance on Error</button>
        <button @click="advance('FC')" class="option-btn">Advance on Fielder's Choice</button>
        <button @click="advance('POA')" class="option-btn out">Put Out at Advancing</button>
        <button @click="advance('POR')" class="option-btn out">Put Out at Retreating</button>
      </div>
      <button @click="stage = 'at-bat-ended'" class="submit-btn">Done Advancing</button>
    </div>

    <div v-else-if="stage === 'error-selection'" class="step">
      <h3>Select Error Type</h3>
      <div class="options-grid">
        <button @click="decisive = true; stage = 'fielding-outcome'" class="option-btn advance">Safe on Error</button>
        <button @click="decisive = false; stage = 'fielding-outcome'" class="option-btn advance">Advance on Error</button>
        <button v-for="e in errors" @click="reuseError(e)" class="option-btn advance">Reuse {{ e }}</button>
      </div>
    </div>

    <!-- Submit Current Play -->
    <div v-if="stage === 'at-bat-ended' || base > 3 || runnerPlays.some(x => x.length)" class="current-play">
      <strong>Final Play:</strong> {{ finalPlay }}
      <button @click="submitPlay" class="submit-btn">Submit Play</button>
      <button @click="resetAtBat" class="reset-btn">Reset Play</button>
    </div>
  </div>
</template>

<script>

import FieldSvg from './FieldSvg.vue';
import FielderSelection from './FielderSelection.vue';

const balls = ['.', 'b', 'i', 'p'];
const strikes = ['s', 'c', 'r', 'f', 't'];
const STAGES = [
  'pitch',
  'trajectory',
  'location',
  'scoring-decision',
  'total-bases',
  'fielding-outcome',
  'further-advance?',
  'further-advance',
  'at-bat-ended',
];
const BASES = {
  '-1': '`',
  0: '',
  1: '!',
  2: '@',
  3: '#',
  4: '$',
};

export default {
  name: 'BatterActions',
  // emits: ['log-play'],
  props: {
    game: Object,
    state: Object,
    runnerPlays: {
      type: Array,
      default: () => []
    },
    preferences: {
      type: Object,
      default: () => ({})
    },
    errors: {
      type: Array,
      default: () => []
    }
  },
  components: {
    FieldSvg,
    FielderSelection,
  },
  data() {
    return {
      pitchSequence: '',
      stage: 'pitch',
      balls: 0,
      strikes: 0,
      atBatEnded: false,
      trajectory: '',
      bases: 0,
      base: 0,
      fielding: '',
      error: '',
      decisive: false,
      fielders: [],
      plays: [''],
      customPlay: '',
      location: null,
      decision: '',
      basePitchOutcomes: {
        '.': 'Ball',
        's': 'Swinging Strike',
        'c': 'Called Strike', 
        'f': 'Foul Ball',
        't': 'Foul Tip',
        'g': 'Foul (bunt)',
        'x': 'Ball In Play',
        'r': 'Foul (runner going)',
        'b': 'Ball in dirt',
        'p': 'Pitchout',
        'i': 'Intentional Ball',
        'blk': 'Balk',
        'blk!': 'Balk (counts toward pitch count)',
      },
      basePitchClasses: {
        '.': 'advance',
        's': 'out',
        'c': 'out', 
        'f': 'out',
        'g': 'out',
        't': 'out',
        'x': 'in-play',
        'r': 'out',
        'b': 'advance',
        'p': 'advance',
        'i': 'advance',
        'CI': 'advance',
        'HBP': 'advance',
        'INT2': 'out',
        'blk': 'advance',
        'blk!': 'advance',
      },
      BASES: {
        1: '1st',
        2: '2nd',
        3: '3rd',
        4: 'Home',
        '-1': 'Out',
      },
    }
  },
  computed: {
    hitter() {
      const lineup = this.state.lineup[this.state.half];
      const hitter = lineup[this.state.atBat[this.state.half]].at(-1);
      const person = this.game?.players?.find(p => p.id === hitter)?.person;
      return person ? `${person.lastName}, ${person.firstName[0]}` : null;
    },
    pitcher() {
      const defense = this.state.defense[(this.state.half + 1) % 2];
      const pitcher = defense[1];
      const person = this.game?.players?.find(p => p.id === pitcher)?.person;
      return person ? `${person.lastName}, ${person.firstName[0]}` : null;
    },
    pitchCount() {
      const defense = this.state.defense[(this.state.half + 1) % 2];
      const pitcher = defense[1];
      const player = this.game?.players?.find(p => p.id === pitcher);
      return this.pitchSequence.length + (player?.stats?.Strikes || 0) + (player?.stats?.Balls || 0) + (player?.stats?.Pitch || 0);
    },
    pitchOutcomes() {
      const outcomes = { ...this.basePitchOutcomes };
      if (this.preferences.removeAdvancedPitchTypes) {
        delete outcomes['r'];
        delete outcomes['b'];
        delete outcomes['p'];
        delete outcomes['i'];
        delete outcomes['t'];
      }
      if (!this.state.bases.some(base => base !== null)) {
        delete outcomes['r']; // No runners to be going.
        delete outcomes['p']; // No runners to pitch out for.
        delete outcomes['blk']; // No runners to balk with.
        delete outcomes['blk!']; // No runners to balk with.
      }
      if (this.preferences.removeBalks) {
        delete outcomes['blk'];
        delete outcomes['blk!'];
      }
      if (!this.preferences.balksCanCountTowardPitchCount) {
        delete outcomes['blk!'];
      }
      if (this.currentBalls >= 3) {
        outcomes['.'] = 'Walk';
        if (!this.preferences.removeAdvancedPitchTypes) {
          outcomes['b'] = 'Walk (ball in dirt)';
          outcomes['i'] = 'Intentional Walk';
        }
      }
      return outcomes;
    },
    currentBalls() {
      return this.state.balls + this.balls;
    },
    currentStrikes() {
      return this.state.strikes + this.strikes;
    },
    currentPlay() {
      let error = this.decisive ? this.error.toUpperCase() : this.error.toLowerCase();
      return `${this.trajectory}${BASES[this.bases]}${this.fielders.join('-').replace(/(-?)(\d)$/, `$1${error}$2`)}`;
      // return this.pitchSequence;
    },
    outcomes() {
      const lastPitch = this.pitchSequence.at(-1);
      if (strikes.includes(lastPitch)) return { 'HBP': 'Hit By Pitch', 'CI': "Catcher's Interference", 'INT2': 'Interference' };
      if (balls.includes(lastPitch)) return { 'HBP': 'Hit By Pitch', 'CI': "Catcher's Interference", 'INT2': 'Interference' };
      return {'HBP': 'Hit By Pitch'};
    },
    fieldingActions() {
      if (this.decision === 'E') {
        return { 'E': 'Fielding Error', 'WT': 'Throwing Error' };
      }
      
      // Build actions based on trajectory
      const actions = { '': 'Put Out' };
      
      // Add sacrifice fly option for fly balls
      if (this.trajectory === 'F') {
        actions['SAF'] = 'Sacrifice Fly';
      }
      
      // Add sacrifice bunt option for ground balls
      if (this.trajectory === 'G') {
        actions['SAB'] = 'Sacrifice Bunt';
      }
      
      return actions;
    },
    finalPlay() {
      // Make sure runners plays don't count stats for the same wild pitches, passed balls, etc.
      const runnerPlays = [...this.runnerPlays];
      const hasPlay = {'WP': false, 'PB': false};
      for (let i = 0; i < runnerPlays.length; i++) {
        let play = runnerPlays[i];
        for (let p in hasPlay) {
          if (play.includes(p)) {
            if (hasPlay[p]) {
              runnerPlays[i] = play.replace(p, `(${p})`);
            }
            hasPlay[p] = true;
          }
        }
      }

      const plays = [this.pitchSequence, [...this.plays, this.currentPlay].filter(x => x).join('/'), ...runnerPlays, this.location ? `${this.location.x}:${this.location.y}` : ''];
      return plays.reduceRight((acc, play) => {
        if (acc.length || play.length) {
          acc.unshift(play);
          return acc;
        }
        return acc;
      }, []).join(',');
    }
  },
  methods: {
    async addPitch(code) {
      // Add pitch to sequence
      // If balk we need to submit the current status and then submit a balk.
      if (code.match(/^blk!?$/)) {
        this.$emit('log-play', this.finalPlay, code);
        this.resetAtBat();
        return;
      }
      this.pitchSequence += code;

      // Update count based on pitch
      if (code === 'b' || code === 'i' || code === 'p' || code === '.') {
        this.balls++;
      } else if (code === 's' || code === 'c' || code === 't' || code === 'g') {
        this.strikes++;
      } else if (code === 'f') {
        // Foul ball - only increases strikes if strikes < 2
        if (this.currentStrikes < 2) {
          this.strikes++;
        }
      } else if (code === 'x') {
        // Ball in play - ends at-bat
        this.stage = 'trajectory';
      }

      // Check for automatic at-bat endings
      if (this.currentBalls >= 4) {
        this.addResult(code === 'i' ? 'IBB' : 'BB');
        this.$emit('force');
      } else if (this.currentStrikes >= 3) {
        this.addResult('K');
      }
    },

    addResult(result) {
      if (result === 'K') {
        if (this.pitchSequence.at(-1) === 'g') {
          result = 'KBTS';
        } else if (this.pitchSequence.at(-1) === 't') {
          result = 'K2';
        } else if ((this.preferences.allowDropThirdStrikes ?? true) && (this.state.outs > 1 || this.state.bases[0] === null)) {
          this.trajectory = 'K';
          this.stage = 'strikeout-options';
          return;
        } else {
          result = 'K2';
        }
      }
      this.trajectory = '';
      if (result === 'HBP') {
        this.$emit('force');
        this.pitchSequence += '.';
      }
      this.plays[0] = result;
      this.stage = 'at-bat-ended';
    },

    selectTrajectory(trajectory) {
      this.trajectory = trajectory;
      this.stage = 'location';
    },

    selectLocation(event) {
      const { offsetX, offsetY } = event;
      // Draw a circle centered on pos, scaled to viewBox.
      const cx = (offsetX / event.currentTarget.clientWidth * 527.94775).toFixed(2) - 40;
      const cy = (offsetY / event.currentTarget.clientHeight * 528.12701).toFixed(2) - 40;
      this.location = { x: cx, y: cy };
      this.stage = 'scoring-decision';
    },

    makeDecision(decision) {
      this.decision = decision;
      if (decision === 'H') {
        this.stage = 'total-bases';
        this.fielders = [];
      } else if (decision === 'POR') {
        this.stage = 'select-rule';
      } else if (decision === 'FC') {
        this.fielders = ['FC'];
        this.stage = 'total-bases';
      } else if (decision === 'CI') {
        this.trajectory = '';
        this.plays = ['CI'];
        this.base = 1;
        this.$emit('force');
        this.stage = 'at-bat-ended';
      } else {
        this.fielders = [];
        this.error = this.decision === 'E' ? 'E' : '';
        this.decisive = this.decision === 'E' ? true : false;
        this.stage = this.decision === 'E' ? 'total-bases' : 'fielding-outcome';
      }
    },

    selectBases(bases) {
      this.bases = bases;
      this.base += this.bases;
      if (['H', 'FC'].includes(this.decision)) {
        return this.stage = 'further-advance?';
      }
      if (this.trajectory || this.decision !== 'E') {
        this.stage = 'fielding-outcome';
      } else {
        this.stage = 'error-selection';
      }
    },

    reuseError(e) {
      this.plays.push(`(${BASES[this.bases]}${e})`);
      this.decisive = false;
      this.error = '';
      this.bases = 0;
      this.stage = 'further-advance?';
    },

    completeFielding() {
      switch (this.decision) {
        case 'E':
          this.stage = 'further-advance?';
          this.$emit('error', this.fielders.join('-').replace(/(-?)(\d)$/, `$1${this.error.toLowerCase()}$2`));
          break;
        default:
          this.stage = 'at-bat-ended';
          break;
      }
    },

    advance(reason) {
      // First push current play if any.
      if (this.currentPlay) {
        this.plays.push(this.currentPlay);
      }

      this.trajectory = '';
      this.decisive = reason === 'E' ? true : false;
      reason = reason.toUpperCase();
      this.error = reason === 'E' ? 'E' : '';
      this.decision = reason === 'E' ? 'E' : '';
      this.fielders = [];
      this.bases = reason === 'POR' ? -1 : 0;

      if (reason === 'FC') {
        this.plays.push('FC');
        this.base += 1;
        this.stage = 'further-advance?';
        return;
      }

      this.base = reason === 'E' ? this.base : -1;
      this.stage = reason === 'E' ? 'total-bases' : 'fielding-outcome';
    },

    undoLastPitch() {
      if (this.pitchSequence.length === 0) return;

      const lastPitch = this.pitchSequence.slice(-1);
      this.pitchSequence = this.pitchSequence.slice(0, -1);

      // Reverse the count change
      if (balls.includes(lastPitch)) {
        this.balls = Math.max(0, this.balls - 1);
      } else if (strikes.includes(lastPitch)) {
        this.strikes = Math.max(0, this.strikes - 1);
      } else if (lastPitch === 'f') {
        // Foul ball - only decrease if it actually increased strikes
        if (this.strikes < 2) {
          this.strikes = Math.max(0, this.strikes - 1);
        }
      }
    },
    
    submitPlay() {
      if (this.finalPlay) {
        this.$emit('log-play', this.finalPlay);
        this.resetAtBat();
      }
    },

    resetAtBat() {
      this.pitchSequence = '';
      this.base = 0;
      this.balls = 0;
      this.strikes = 0;
      this.stage = 'pitch';
      this.plays = [''];
      this.trajectory = '';
      this.bases = 0;
      this.fielding = '';
      this.error = '';
      this.fielders = [];
      this.location = null;
      this.$emit('reset-play');
    },

    logCustomPlay() {
      if (this.customPlay.trim()) {
        this.$emit('log-play', this.customPlay.trim());
        this.customPlay = '';
        this.resetAtBat();
      }
    },

    handleFieldingAction(event) {
      this.fielders = event.fielders;
      if (this.decision === 'E') {
        this.error = event.action;
        this.stage = 'further-advance?';
        this.$emit('error', this.fielders.join('-').replace(/(-?)(\d)$/, `$1${this.error.toLowerCase()}$2`));
      } else if (event.action === 'SAF' || event.action === 'SAB') {
        // For sacrifice fly or bunt, replace the trajectory and mark as out
        this.trajectory = event.action;
        this.bases = -1; // Batter is out
        this.stage = 'at-bat-ended';
      } else {
        this.stage = 'at-bat-ended';
      }
    }
  }
}
</script>

<style scoped>
.batter-actions {
  margin: 20px 0;
}

.decision-tree {
  margin-bottom: 30px;
}

.step {
  text-align: center;
}

.step h3 {
  margin-bottom: 20px;
  color: #333;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.option-btn {
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
  min-width: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.option-btn.primary {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.option-btn.error {
  background-color: white;
  color: #dc3545;
  border-color: #dc3545;
}

.option-btn.error.selected-error {
  background-color: #dc3545;
  color: white;
  border-color: #dc3545;
}

.option-btn.error:hover {
  background-color: #a71d2a;
  color: white;
  border-color: #a71d2a;
}

.option-btn.primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.option-btn.advance {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}

.option-btn.advance:hover {
  background-color: #218838;
  border-color: #218838;
}

.option-btn.out {
  background-color: #dc3545;
  color: white;
  border-color: #dc3545;
}

.option-btn.out:hover {
  background-color: #c82333;
  border-color: #c82333;
}

.back-btn {
  padding: 8px 16px;
  border: 1px solid #6c757d;
  border-radius: 4px;
  background-color: #f8f9fa;
  color: #6c757d;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.back-btn:hover {
  background-color: #e9ecef;
}

.current-play {
  background-color: #e7f3ff;
  border: 1px solid #b3d7ff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.submit-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: white;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.submit-btn:hover {
  background-color: #218838;
}

.reset-btn {
  padding: 8px 16px;
  border: 1px solid #dc3545;
  border-radius: 4px;
  background-color: #f8f9fa;
  color: #dc3545;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.batter-actions {
  margin: 20px 0;
}

.at-bat-status {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
}

.count-display {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.count-display .balls {
  color: #28a745;
}

.count-display .strikes {
  color: #dc3545;
}

.play-sequence {
  font-size: 16px;
  color: #666;
}

.step {
  text-align: center;
  margin-bottom: 20px;
}

.step h3 {
  margin-bottom: 20px;
  color: #333;
}

.options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.option-btn {
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

.option-btn:hover {
  background-color: #007bff;
  color: white;
}

.pitch-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 20px;
}

.undo-btn {
  padding: 8px 16px;
  border: 1px solid #ffc107;
  border-radius: 4px;
  background-color: #fff3cd;
  color: #856404;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.undo-btn:hover {
  background-color: #ffeaa7;
}

.end-btn {
  padding: 8px 16px;
  border: 1px solid #dc3545;
  border-radius: 4px;
  background-color: #f8d7da;
  color: #721c24;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.end-btn:hover {
  background-color: #f5c6cb;
}

.back-btn {
  padding: 8px 16px;
  border: 1px solid #6c757d;
  border-radius: 4px;
  background-color: #f8f9fa;
  color: #6c757d;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.back-btn:hover {
  background-color: #e9ecef;
}

.current-play {
  background-color: #e7f3ff;
  border: 1px solid #b3d7ff;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.submit-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: white;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.submit-btn:hover {
  background-color: #218838;
}

.reset-btn {
  padding: 8px 16px;
  border: 1px solid #dc3545;
  border-radius: 4px;
  background-color: #f8f9fa;
  color: #dc3545;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.reset-btn:hover {
  background-color: #f5c6cb;
}

.fielding-grid {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.field-row {
  display: flex;
  /* grid-template-columns: repeat(3, 150px); */
  justify-items: center;
  gap: 10px;
}

.middle-row {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.custom-play {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.custom-play input {
  flex: 1;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.custom-play button {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: white;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.custom-play button:hover {
  background-color: #218838;
}

.location-full {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: white;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
</style>