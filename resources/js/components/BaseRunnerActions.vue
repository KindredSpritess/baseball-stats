<template>
  <div class="base-runner-card" :class="{ 'empty': !runner }">
    <div class="base-label">{{ baseNames[base] }}</div>
    <template v-if="runner">
      <div class="runner-info">
        <span class="runner-name">{{ runner.person.lastName }}, {{ runner.person.firstName[0] }}</span>
      </div>
      <div class="runner-actions">
        <button v-if="!preferences.removeAdvancementOptions" @click="logRunnerAction(runner.id, 'SB')" class="action-btn">Steal</button>
        <button v-if="!preferences.removeAdvancementOptions" @click="logRunnerAction(runner.id, 'CS')" class="action-btn">CS</button>
        <button v-if="!preferences.removeAdvancementOptions" @click="logRunnerAction(runner.id, 'PO')" class="action-btn">PO</button>
        <button @click="logRunnerAction(runner.id, 'ADV')" class="action-btn">ADV</button>
      </div>
    </template>
    <template v-else>
      <div class="empty-base">
        <span>Empty</span>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  name: 'BaseRunnerActions',
  props: {
    base: Number,
    game: Object,
    state: Object,
    preferences: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    runner() {
      const baseIndex = this.base;
      const playerId = this.state.bases[baseIndex];
      console.log('Game Players:', this.game.players);
      return this.game.players.find(p => p.id === playerId);
    },
    play() {
      return this.actions.join('/');
    }
  },
  data() {
    return {
      baseNames: {
        0: '1st',
        1: '2nd',
        2: '3rd'
      },
      actions: [],
    }
  },
  methods: {
    logRunnerAction(playerId, actionCode) {
      this.$emit('log-play', `${actionCode}(${playerId})`);
    }
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