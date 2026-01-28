<template>
  <div class="fielder-selection full-screen">
    <h3>Touch Fielders Involved in Order</h3>
    <div class="selected-fielders">
      <p>Selected Fielders: {{ selectedFielders.map(f => positionNames[f]).join(' → ') }}</p>
    </div>
    <div class="field-container">
      <FieldSvg :players="playersObj" :onFielderTouch="selectFielder" />
      <button @click="undoFielder" class="undo-btn">↶ Undo Last Fielder</button>
    </div>
    <div class="actions">
      <button v-for="(name, action) in actions" :key="action" @click="selectAction(action)" :disabled="selectedFielders.length === 0" class="action-btn">{{ name }}</button>
    </div>
  </div>
</template>

<script>
import FieldSvg from './FieldSvg.vue';

const positionNames = {
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
  name: 'FielderSelection',
  components: { FieldSvg },
  props: {
    defense: {
      type: Array,
      required: true
    },
    players: {
      type: Array,
      required: true
    },
    actions: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      selectedFielders: []
    };
  },
  computed: {
    positionNames() {
      return positionNames;
    },
    playersObj() {
      const obj = {};
      for (let pos = 1; pos <= 9; pos++) {
        const playerId = this.defense[pos];
        if (playerId) {
          const player = this.players.find(p => p.id === playerId);
          if (player) {
            obj[pos] = `${player.person.lastName}, ${player.person.firstName[0]}`;
          }
        }
      }
      return obj;
    }
  },
  methods: {
    selectFielder(position) {
      if (!this.selectedFielders.includes(position)) {
        this.selectedFielders.push(position);
      }
    },
    undoFielder() {
      this.selectedFielders.pop();
    },
    selectAction(action) {
      this.$emit('action-selected', { fielders: this.selectedFielders, action });
    }
  }
};
</script>

<style scoped>
.fielder-selection {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.fielder-selection h3 {
  flex-shrink: 0;
}

.field-container {
  flex-shrink: 1;
  flex-grow: 1;
  width: 80vw;
  height: 1px;
  margin: 0 auto;
}

.field-container svg {
  width: auto;
  height: 100%;
}

.undo-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 8px 12px;
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

.selected-fielders {
  padding: 10px;
  background: #f0f0f0;
  text-align: center;
  flex-shrink: 0;
}

.selected-fielders p {
  margin: 0;
}

.actions {
  display: flex;
  gap: 10px;
  padding: 10px;
  justify-content: center;
  flex-wrap: wrap;
  flex-shrink: 0;
}

.action-btn {
  padding: 8px 16px;
  border: 1px solid #007bff;
  border-radius: 4px;
  background-color: #f8f9fa;
  color: #007bff;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.action-btn:hover {
  background-color: #007bff;
  color: white;
}

.full-screen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: white;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}
</style>
