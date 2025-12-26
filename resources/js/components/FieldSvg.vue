<template>
  <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"
    viewBox="0 0 447.94775 448.12701" xml:space="preserve" fill="#00000000" stroke="#00000000"
    @click="onTouch ? onTouch($event) : null">
    <g shape-rendering="auto" image-rendering="auto" color-rendering="auto" color-interpolation="sRGB">
      <!-- Border -->
      <path style="fill:#ffffff"
        d="m 224.02066,0 c -51.375,0 -104.961,18.027 -145.843997,50.219 -37.233,29.316 -61.724,68.332 -77.84399963,120.094 -0.785,2.639 -0.16,30.431 1.65600003,32.5 L 180.08366,380.845 c -2.605,5.889 -4.125,12.355 -4.125,19.188 0,26.418 21.648,48.094 48.063,48.094 26.415,0 47.969,-21.678 47.969,-48.094 0,-6.807 -1.486,-13.25 -4.063,-19.125 l 178.031,-178.031 c 1.816,-2.068 2.442,-29.922 1.656,-32.561 -15.25,-51.344 -40.622,-90.785 -77.844,-120.094 -40.883,-32.192 -94.374,-50.219 -145.75,-50.219 z">
      </path>
      <path style="fill:#d89b75"
        d="m 224.00866,159.639 121.063,121.187 -86.344,86.344 c -8.749,-9.23 -21.048,-15.064 -34.719,-15.064 -13.677,0 -26.001,5.83 -34.781,15.064 l -86.375,-86.375 z">
      </path>
      <!-- Border around the border. -->
      <path style="fill:none;stroke:#000000;stroke-width:2"
        d="m 224.02066,1 c -50.375,0 -103.961,17.027 -144.843997,49.219 -37.233,29.316 -61.724,68.332 -77.84399963,120.094 -0.785,2.639 -0.16,30.431 1.65600003,32.5 L 180.08366,381.845 c -2.605,5.889 -4.125,12.355 -4.125,19.188 0,26.418 21.648,48.094 48.063,48.094 26.415,0 47.969,-21.678 47.969,-48.094 0,-6.807 -1.486,-13.25 -4.063,-19.125 l 178.031,-178.031 c 1.816,-2.068 2.442,-29.922 1.656,-32.561 -15.25,-51.344 -40.622,-90.785 -77.844,-120.094 -40.883,-32.192 -94.374,-49.219 -145.75,-49.219 z">
      </path>
      <!-- Outfield -->
      <path style="fill:#75d89b"
        d="m 224.00866,16.075 c 47.618,0 98.056,16.903 135.844,46.655 33.519,26.396 63.19668,78.8981 77.79468,125.6151 l -80.29468,79.7909 -128.125,-125.154 c -1.632,-1.404 -3.761,-2.092 -5.906,-1.906 -1.706,0.125 -3.327,0.793 -4.625,1.906 L 91.518069,269.4604 7.1171623,185.0595 C 22.508162,137.8195 54.602663,89.084 88.070663,62.731 125.85766,32.977 176.39066,16.074 224.00866,16.075 Z">
      </path>
      <!-- Infield -->
      <path style="fill:#75d89b"
        d="m 223.99966,181.055 99.295,99.771 -67.255,69.37521 c -7.176,-7.57 -20.828,-13.76821 -32.04,-13.76821 -11.217,0 -24.789,5.49011 -31.991,13.06111 l -67.381,-68.66811 z">
      </path>
      <!-- Dirt -->
      <path style="fill:#d89b75"
        d="m 224.009,246.014 c 8.931,0 15.969,7.131 15.969,16.063 0,8.932 -7.037,15.938 -15.969,15.938 -8.931,0 -16.062,-7.006 -16.062,-15.938 -0.001,-8.932 7.13,-16.063 16.062,-16.063 z">
      </path>
      <path style="fill:#d89b75"
        d="m 224.009,368.043 c 17.768,0 32.031,14.232 32.031,32 0,17.768 -14.263,32.031 -32.031,32.031 -17.768,0 -32,-14.264 -32,-32.031 0,-17.767 14.232,-32 32,-32 z">
      </path>
      <g v-if="location">
        <circle :cx="location.x" :cy="location.y" r="10" fill="#ff0000aa" stroke="#000000" stroke-width="2"/>
      </g>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=260 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(1)" v-if="players">{{ players[1] ?? 'Pitcher' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=435 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(2)" v-if="players">{{ players[2] ?? 'Catcher' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=344 y=310 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(3)" v-if="players">{{ players[3] ?? 'First Base' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=284 y=210 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(4)" v-if="players">{{ players[4] ?? 'Second Base' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=104 y=310 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(5)" v-if="players">{{ players[5] ?? 'Third Base' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=164 y=210 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(6)" v-if="players">{{ players[6] ?? 'Shortstop' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=104 y=130 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(7)" v-if="players">{{ players[7] ?? 'Left Field' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=80  font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(8)" v-if="players">{{ players[8] ?? 'Center Field' }}</text>
      <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=344 y=130 font-weight="bold" class="fielder-text" @click="onFielderTouch && onFielderTouch(9)" v-if="players">{{ players[9] ?? 'Right Field' }}</text>
    </g>
  </svg>
</template>

<script>
export default {
  name: 'FieldSvg',
  props: {
    location: {
      type: Object,
      default: null
    },
    players: {
      type: Object,
      default: null,
    },
    onTouch: {
      type: Function,
      default: null
    },
    onFielderTouch: {
      type: Function,
      default: null
    }
  }
};
</script>

<style scoped>
  .fielder-text {
    fill: var(--fielding-primary, white);
    stroke: var(--fielding-secondary, black);
    stroke-width: 1px;
    cursor: pointer;
  }
</style>