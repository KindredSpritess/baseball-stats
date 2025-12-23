<template>
  <!-- TODO: 
    * Push state of play to plays, when clicking further advance.
    * Make the fielding outcome step pretty.
    * When completing play from fielding outcome, go to further advance only if error.
    * Remove options based on logged in user's scoring preferences.
      - That is allowed for massive simplification.
      - Simplify trajectories - to G and F.
      - Remove errors.
      - Remove advanced pitch types.
      - Remove intentional walks.
      - Remove SB / CS / WP / PB from advancement options, just advance them.
  -->
  <div class="batter-actions">
    <div class="at-bat-status">
      <div class="count-display">
        <span class="balls">{{ currentBalls }}</span> - <span class="strikes">{{ currentStrikes }}</span>
      </div>
      <div class="play-sequence">
        <strong>Play:</strong> {{ finalPlay || 'Start of at-bat' }}
      </div>
    </div>

    <!-- Pitch Selection (main interface) -->
    <div v-if="stage === 'pitch'" class="step">
      <h3>Select Pitch Outcome</h3>
      <div class="options-grid">
        <button v-for="(description, code) in pitchOutcomes" 
                :key="code" 
                @click="addPitch(code)" 
                class="option-btn">
          {{ description }}
        </button>
        <button v-for="(description, code) in outcomes"
                :key="code" 
                @click="addResult(code)" 
                class="option-btn">
          {{ description }}
        </button>
      </div>
      <div class="pitch-actions">
        <button v-if="currentPlay" @click="undoLastPitch" class="undo-btn">↶ Undo Last Pitch</button>
        <button @click="endAtBat" class="end-btn">End At-Bat</button>
      </div>
    </div>

    <div v-else-if="stage === 'trajectory'" class="step">
      <h3>Select Ball Trajectory</h3>
      <div class="options-grid">
        <button @click="selectTrajectory('G')" class="option-btn primary">Ground Ball</button>
        <button @click="selectTrajectory('L')" class="option-btn primary">Line Drive</button>
        <button @click="selectTrajectory('F')" class="option-btn primary">Fly Ball</button>
        <button @click="selectTrajectory('P')" class="option-btn primary">Pop Up</button>
      </div>
      <button @click="resumePitching" class="back-btn">← Back to Pitching</button>
    </div>

    <div v-else-if="stage === 'location'" class="step">
      <h3>Select Hit Location</h3>
      <div class="options-grid">
        <!-- SVG Field -->
        <FieldSvg :location="location" :onTouch="selectLocation"/>
      </div>
    </div>

    <!-- Scoring Descision -->
    <div v-else-if="stage === 'scoring-decision'" class="step">
      <h3>Select Scoring Decision</h3>
      <div class="options-grid">
        <button @click="makeDecision('H')" class="option-btn">Hit</button>
        <button @click="makeDecision('FC')" class="option-btn">Fielder's Choice</button>
        <button @click="makeDecision('E')" class="option-btn">Error</button>
        <button @click="makeDecision('PO')" class="option-btn">Put Out</button>
        <button @click="makeDecision('CI')" class="option-btn">Catcher's Interference</button>
        <button @click="makeDecision('POR')" class="option-btn">Put Out by Rule</button>
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
        <button @click="selectBases(1)" class="option-btn">One</button>
        <button @click="selectBases(2)" class="option-btn">Two</button>
        <button @click="selectBases(3)" class="option-btn">Three</button>
        <button @click="selectBases(4)" class="option-btn">Four</button>
      </div>
      <button @click="stage = 'scoring-decision'" class="back-btn">← Back to Scoring Decision</button>
    </div>

    <div v-else-if="stage === 'fielding-outcome'" class="step">
      <h3>Select Fielding Outcome</h3>
      <div class="options-grid" v-if="this.decision === 'E'">
        <button @click="error = 'WT'" class="option-btn error" :class="{ 'selected-error': error === 'WT' }">Throwing Error</button>
        <button @click="error = 'E'" class="option-btn error" :class="{ 'selected-error': error === 'E' }">Fielding Error</button>
      </div>
      <div class="options-grid">
        <button @click="fielders.push(7)" class="option-btn">LF</button>
        <button @click="fielders.push(8)" class="option-btn">CF</button>
        <button @click="fielders.push(9)" class="option-btn">RF</button>
      </div>
      <div class="options-grid">
        <button @click="fielders.push(6)" class="option-btn">SS</button>
        <button @click="fielders.push(4)" class="option-btn">2B</button>
      </div>
      <div class="options-grid">
        <button @click="fielders.push(5)" class="option-btn">3B</button>
        <button @click="fielders.push(1)" class="option-btn">P</button>
        <button @click="fielders.push(3)" class="option-btn">1B</button>
      </div>
      <div class="options-grid">
        <button @click="fielders.push(2)" class="option-btn">C</button>
      </div>
      <button @click="this.fielders.pop()" class="undo-btn">↶ Undo Last Fielder</button>
      <button @click="stage = 'total-bases'" class="back-btn">← Back to Total Bases</button>
      <button @click="stage = decision === 'E' ? 'further-advance' : 'at-bat-ended'" class="submit-btn">Complete</button>
    </div>

    <!-- At-Bat Result Selection (when at-bat ends) -->
    <div v-else-if="stage === 'further-advance'" class="step">
      <h3>Further Advancement?</h3>
      <div class="options-grid">
        <!-- In Play results -->
        <button @click="bases = 1; stage = 'fielding-outcome'" class="option-btn">Advance on Error</button>
        <button @click="plays.push('FC')" class="option-btn">Advance on Fielder's Choice</button>
        <button @click="bases = 0; stage = 'fielding-outcome'" class="option-btn">Put Out at Advancing</button>
        <button @click="bases = -1; stage = 'fielding-outcome'" class="option-btn">Put Out at Retreating</button>
      </div>
      <button @click="stage = 'at-bat-ended'" class="submit-btn">Done Advancing</button>
    </div>

    <!-- Submit Current Play -->
    <div v-else-if="stage === 'at-bat-ended'" class="current-play">
      <strong>Final Play:</strong> {{ finalPlay }}
      <button @click="submitPlay" class="submit-btn">Submit Play</button>
      <button @click="resetAtBat" class="reset-btn">Reset At-Bat</button>
    </div>

    <!-- Custom Play Input -->
    <div class="custom-play">
      <input v-model="customPlay" placeholder="Or enter custom play code directly" />
      <button @click="logCustomPlay" class="play-btn">Log Custom</button>
    </div>
  </div>
</template>

<script>

import FieldSvg from './FieldSvg.vue';

const balls = ['.', 'b', 'i', 'p'];
const strikes = ['s', 'c', 'r', 'f', 't'];
const STAGES = [
  'pitch',
  'trajectory',
  'location',
  'scoring-decision',
  'total-bases',
  'fielding-outcome',
  'further-advance',
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
  emits: ['log-play'],
  props: {
    state: Object,
    runnerPlays: {
      type: Array,
      default: () => []
    }
  },
  components: {
    FieldSvg,
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
      fielding: '',
      error: '',
      fielders: [],
      plays: [''],
      customPlay: '',
      location: null,
      decision: '',
      pitchOutcomes: {
        's': 'Swinging Strike',
        'c': 'Called Strike', 
        '.': 'Ball',
        'f': 'Foul Ball',
        'x': 'Ball In Play',
        'r': 'Foul (runner going)',
        'b': 'Ball in dirt',
        'p': 'Pitchout',
        'i': 'Intentional Ball',
      }
    }
  },
  computed: {
    currentBalls() {
      return this.state.balls + this.balls;
    },
    currentStrikes() {
      return this.state.strikes + this.strikes;
    },
    currentPlay() {
      return this.pitchSequence;
    },
    outcomes() {
      const lastPitch = this.pitchSequence.at(-1);
      if (strikes.includes(lastPitch)) return { 'K': 'Strikeout', 'CI': "Catcher's Interference", 'INT2': 'Interference' };
      if (balls.includes(lastPitch)) return { 'BB': 'Walk', 'IBB': 'Intentional Walk', 'HBP': 'Hit by Pitch', 'CI': "Catcher's Interference", 'INT2': 'Interference' };
      return {};
    },
    hittingPlay() {
      if (this.plays[0]) {
        return this.plays;
      };
      return [`${this.trajectory}${BASES[this.bases]}${this.fielders.join('-').replace(/(-?)(\d)$/, `$1${this.error}$2`)}`, ...this.plays.slice(1)];
    },
    finalPlay() {
      const plays = [this.pitchSequence, this.hittingPlay.join('/'), ...this.runnerPlays, this.location ? `${this.location.x}:${this.location.y}` : ''];
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
    addPitch(code) {
      // Add pitch to sequence
      this.pitchSequence += code;

      // Update count based on pitch
      if (code === 'b' || code === 'i' || code === 'p' || code === '.') {
        this.balls++;
      } else if (code === 's' || code === 'c' || code === 'v') {
        this.strikes++;
      } else if (code === 'f') {
        // Foul ball - only increases strikes if strikes < 2
        if (this.strikes < 2) {
          this.strikes++;
        }
      } else if (code === 'x') {
        // Ball in play - ends at-bat
        this.stage = 'trajectory';
      }

      // Check for automatic at-bat endings
      if (this.balls >= 4) {
        this.atBatEnded = true;
      } else if (this.strikes >= 3) {
        this.atBatEnded = true;
      }
    },

    addResult(result) {
      this.plays[0] = result;
    },

    selectTrajectory(trajectory) {
      this.trajectory = trajectory;
      this.stage = 'location';
    },

    selectLocation(event) {
      const { offsetX, offsetY } = event;
      // Draw a circle centered on pos, scaled to viewBox.
      const cx = (offsetX / event.currentTarget.clientWidth * 447.94775).toFixed(2);
      const cy = (offsetY / event.currentTarget.clientHeight * 448.12701).toFixed(2);
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
        this.plays[0] = 'CI';
        this.stage = 'further-advance';
      } else {
        this.fielders = [];
        this.error = this.decision === 'E' ? 'E' : '';
        this.stage = 'fielding-outcome';
      }
    },

    selectBases(bases) {
      this.bases = bases;
      if (['H', 'FC'].includes(this.decision)) {
        return this.stage = 'further-advance';
      }
      this.stage = 'fielding-outcome';
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
      
      // Reset at-bat ended flag if we're undoing
      this.atBatEnded = false;
      this.finalPlay = '';
    },
    
    endAtBat() {
      this.atBatEnded = true;
    },
    
    selectResult(result) {
      this.finalPlay = this.pitchSequence + '.' + result;
    },
    
    resumePitching() {
      this.atBatEnded = false;
      this.finalPlay = '';
    },
    
    submitPlay() {
      if (this.finalPlay) {
        this.$emit('log-play', this.finalPlay);
        this.resetAtBat();
      }
    },
    
    resetAtBat() {
      this.pitchSequence = '';
      this.balls = 0;
      this.strikes = 0;
      this.stage = 'pitch';
      this.plays = [''];
      this.trajectory = '';
      this.bases = 0;
      this.fielding = '';
      this.error = '';
      this.fielders = [];
      this.finalPlay = '';
    },
    
    logCustomPlay() {
      if (this.customPlay.trim()) {
        this.$emit('log-play', this.customPlay.trim());
        this.customPlay = '';
        this.resetAtBat();
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
</style>