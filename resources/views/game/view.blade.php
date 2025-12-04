<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="/jquery.min.js"></script>
    <link rel="stylesheet" href="/styles.css" />
    <link rel="stylesheet" href="/game.css" />
    <script src="https://kit.fontawesome.com/cc3e56010d.js" crossorigin="anonymous"></script>
    <script src="https://cdn.babylonjs.com/babylon.js"></script>
    <!-- define style vars for team colors -->
    <style>
        :root {
            --away-primary: {{ $game->away_team->primary_color ?? '#1e88eA' }};
            --away-secondary: {{ $game->away_team->secondary_color ?? '#ffffff' }};
            --home-primary: {{ $game->home_team->primary_color ?? '#43a047' }};
            --home-secondary: {{ $game->home_team->secondary_color ?? '#fdd835' }};
            --fielding-primary: {{ $game->half ? 'var(--away-primary)' : 'var(--home-primary)' }};
            --fielding-secondary: {{ $game->half ? 'var(--away-secondary)' : 'var(--home-secondary)' }};
            --batting-primary: {{ $game->half ? 'var(--home-primary)' : 'var(--away-primary)' }};
            --batting-secondary: {{ $game->half ? 'var(--home-secondary)' : 'var(--away-secondary)' }};
        }
    </style>
    <title>{{ $game->away_team->name }} @ {{ $game->home_team->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
</head>
<body>
<div id="app">
    <div class="mobile-menu">
        <div class="mobile-menu-away"><a href="#away">@{{ game.away_team.short_name }}</a></div>
        <div class="mobile-menu-play"><a href="#plays-main">Plays</a></div>
        <div class="mobile-menu-home"><a href="#home">@{{ game.home_team.short_name }}</a></div>
    </div>
    <table id='game-view'>
        <tr style="max-height: 100%;">
            <td class='mobile-hide' x-column='away'>
                <x-box-score :game="$game" :home="false" />
            </td>
            <td style='text-align: center; width: 100%;' class='mobile-hide' x-column='plays-main'>
                <x-line-score :game="$game" />
                <h3 class="geotemporal">{{ Carbon\Carbon::parse($game->firstPitch)->format('M jS g:i') }} at {{ $game->location }}</h3>
                <canvas id="field-canvas"></canvas>
                @if (!$game->ended)
                <!-- Put Pitcher vs Hitter info here. -->
                <div class="pitcher-vs-hitter" v-if="hitting && pitching">
                    <div v-if="state.half">
                        @{{ pitching.person.firstName }}
                        @{{ pitching.person.lastName }}<br/>
                        (@{{ stats[pitching.id].Pitches }} pitches, @{{ stats[pitching.id].K }} Ks)
                    </div>
                    <div>
                        @{{ state.atBat[state.half] + 1 }}.
                        @{{ hitting.person.firstName }}
                        @{{ hitting.person.lastName }}<br/>
                        (@{{ stats[hitting.id].H }} for @{{ stats[hitting.id].AB ?? 0 }})<br/>
                        <!-- Stats -->
                    </div>
                    <div v-if="!state.half">
                        @{{ pitching.person.firstName }}
                        @{{ pitching.person.lastName }}<br/>
                        (@{{ stats[pitching.id].Pitches }} pitches, @{{ stats[pitching.id].K }} Ks)
                    </div>
                </div>
                @endif
                <!-- Put a clickable innings selector. -->
                <div class="innings-selector">
                  <a v-for="i in state.inning" :key="i" href="#plays-main" class="inning-link" :data-inning="i" :class="{'inning-selected': selectedInning === i}" @click="selectedInning=i">@{{ i }}</a>
                </div>
                <div id='play-by-play'>
                    <template v-for="(pa, i) in plays" :key="i">
                        <template :style="{ display: pa[0].inning === selectedInning ? 'block' : 'none' }">
                            <div :class="{
                                            'plate-appearance-container': !pa[0].game_event,
                                            'game-event-container': pa[0].game_event,
                                            'selected': selectedPlay === i,
                                         }" @click="selectedPlay = selectedPlay === i ? null : i">
                                <template v-for="(play, j) in pa" :key="j">
                                    <!-- @{{ JSON.stringify(play) }} -->
                                    <div v-if="!play.command" v-for="pitch in play.play.split(',')[0].split('')" :key="`${i}-${pitch}`" class='pitch' :class="pitch" :data-play-id="i" :data-inning="play.inning" :data-inning-half="play.inning_half">@{{ pitchDescription(pitch) }}</div>
                                    <div v-if="play.human"
                                        :class="{'run-scoring': play.run_scoring, 'plate-appearance': play.plate_appearance}"
                                        :data-play-id="i"
                                        :data-inning="play.inning"
                                        :data-inning-half="play.inning_half"
                                    >
                                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                                        @{{ play.human }}
                                    </div>
                                    <div v-if="play.game_event"
                                        class='game-event'
                                        :class="play.inning_half ? 'game-event-home' : 'game-event-away'"
                                        :data-inning="play.inning"
                                        :data-inning-half="play.inning_half"
                                    >
                                        @{{ play.game_event }}
                                    </div>
                                </template>
                                <div 
                                    v-if="!state.ended && selectedInning === state.inning && (i === plays.length - 1)"
                                    class="plate-appearance"
                                    :data-inning="state.inning" :data-inning-half="state.half"
                                >
                                    <i class="fa-solid fa-chevron-down toggle-icon"></i> @{{ hitting?.person.firstName }} @{{ hitting?.person?.lastName }} at bat
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </td>
            <td class='mobile-hide' x-column='home'>
                <x-box-score :game="$game" :home="true" />
            </td>
        </tr>
    </table>
</div>

<script>
const { createApp } = Vue;

createApp({
    data() {
      return {
        game: @json($game),
        stats: {},
        state: {},
        selectedInning: JSON.parse("{{ $game->inning }}"),
        selectedPlay: null,
        plays: [],
      }
    },
    methods: {
      pitchDescription(pitch) {
        return {
          's': 'Swinging Strike',
          'c': 'Called Strike',
          '.': 'Ball',
          'b': 'Ball in dirt',
          'x': 'In Play',
          't': 'Foul Tip',
          'r': 'Foul (runner going)',
          'f': 'Foul'
        }[pitch] || `Unknown Pitch ${pitch}`;
      },
      fetchData() {
        fetch(`/api/game/{{ $game->id }}`)
            .then(response => response.json())
            .then(data => {
                this.game = data.game;
                this.state = data.state;
                this.stats = data.stats;
                // Combine plays into plate appearances.
                const plays = [];
                let currentPA = [];
                for (const play of this.game.plays) {
                    if (play.inning === null) {
                        continue;
                    }
                    if (play.plate_appearance) {
                        currentPA.push(play);
                        plays.push(currentPA);
                        currentPA = [];
                    } else if (play.game_event) {
                        plays.push(currentPA);
                        plays.push([play]);
                        currentPA = [];
                    } else {
                        currentPA.push(play);
                    }
                }
                plays.push(currentPA);
                if (this.selectedPlay === this.plays.length - 1) {
                    this.selectedPlay = null;
                }
                this.plays = plays.filter(pa => pa.length > 0);
                this.selectedPlay ??= this.plays.length - 1;
                this.$nextTick(() => {
                    if (window.updateStatus) window.updateStatus(this);
                });
            });
        },
    },
    computed: {
      teams() {
        return [this.game.away_team, this.game.home_team];
      },
      hitting() {
        const team = this.teams[this.state.half];
        const lineup = this.state.lineup?.[this.state.half];
        const atBat = this.state.atBat?.[this.state.half];
        return team?.players?.find(player => player.id === lineup[atBat].at(-1));
      },
      pitching() {
        const team = this.teams[1 - this.state.half];
        const lineup = this.state.lineup?.[1 - this.state.half];
        const defense = this.state.defense?.[1 - this.state.half];
        return team?.players?.find(player => player.id === defense?.['1']);
      },
      fielders() {
        const team = this.teams[1 - this.state.half];
        const defense = this.state.defense?.[1 - this.state.half];
        const fielders = {};
        for (const pos in defense) {
          fielders[pos] = team?.players?.find(player => player.id === defense[pos]);
        }
        return fielders;
      },
      runners() {
        const team = this.teams[this.state.half];
        const bases = {};
        for (const base in this.state.bases) {
          const runnerId = this.state.bases[base];
          if (runnerId) {
            bases[base] = team?.players?.find(player => player.id === runnerId);
          }
        }
        return bases;
      },
    },
    mounted() {
        // Fetch updated data from API
        if (!this.game.ended) setInterval(() => { this.fetchData() }, 15000);
        this.fetchData();
    },
}).mount('#app');
</script>

<script>
$(document).ready(() => {
    if (window.innerWidth <= 768) {
        const [, column] = (window.location.hash || '#plays-main').split('#');
        $('[x-column]').hide();
        $(`[x-column=${column}]`).show();
    }
    // Click the current inning link to filter plays
    const currentInning = "{{ $game->inning }}";
    $(`.inning-link[data-inning="${currentInning}"]`).click();
});

$('.mobile-menu a').on('click', (e) => {
    const [, column] = e.target.href.split('#');
    $('.mobile-hide').hide();
    $(`[x-column=${column}]`).show();
});

// When touching an svg with a title, show the title as a tooltip
$('#Layer_1').on('touchstart', 'circle, polygon', (e) => {
    const title = e.target.querySelector('title');
    if (title) {
        alert(title.textContent);
    }
});
</script>
<script>
const canvas = document.getElementById('field-canvas');
const engine = new BABYLON.Engine(canvas, true);
const scene = new BABYLON.Scene(engine);

// camera
const camera = new BABYLON.FreeCamera('camera', new BABYLON.Vector3(224, 35, 390), scene);
camera.setTarget(new BABYLON.Vector3(224, 0, 240));
camera.setFocalLength(24);
camera.fov

// light
const light = new BABYLON.HemisphericLight('light', new BABYLON.Vector3(0, 1, 0), scene);

// field (outfield)
const field = BABYLON.MeshBuilder.CreateGround('field', {width: 1000, height: 1000}, scene);
const fieldMaterial = new BABYLON.StandardMaterial('fieldMat', scene);
fieldMaterial.diffuseColor = new BABYLON.Color3(0.46, 0.73, 0.60); // #75d89b
field.material = fieldMaterial;
field.position = new BABYLON.Vector3(224, 0, 250);

// infield dirt
const dirtMaterial = new BABYLON.StandardMaterial('dirtMat', scene);
dirtMaterial.diffuseColor = new BABYLON.Color3(0.85, 0.61, 0.46); // #d89b75

// pitcher's mound
const moundDirt = BABYLON.MeshBuilder.CreateCylinder('moundDirt', {diameter: 18, height: 0.1}, scene);
moundDirt.material = dirtMaterial;
moundDirt.position = new BABYLON.Vector3(224, 0.05, 277);

// home plate dirt
const plateDirt = BABYLON.MeshBuilder.CreateCylinder('plateDirt', {diameter: 32, height: 0.1}, scene);
plateDirt.material = dirtMaterial;
plateDirt.position = new BABYLON.Vector3(224, 0.05, 343);

// bases
const baseMaterial = new BABYLON.StandardMaterial('baseMat', scene);
baseMaterial.diffuseColor = BABYLON.Color3.White();

const base1 = BABYLON.MeshBuilder.CreateBox('base1', {width: 4, height: 0.1, depth: 4}, scene);
base1.material = baseMaterial;
base1.position = new BABYLON.Vector3(224 - 63.63961030678928 + 2.4, 0.05, 280);
base1.addRotation(0, Math.PI / 4, 0);

const base2 = BABYLON.MeshBuilder.CreateBox('base2', {width: 4, height: 0.1, depth: 4}, scene);
base2.material = baseMaterial;
base2.position = new BABYLON.Vector3(224, 0.05, 280 - 63.63961030678928);
base2.addRotation(0, Math.PI / 4, 0);

const base3 = BABYLON.MeshBuilder.CreateBox('base3', {width: 4, height: 0.1, depth: 4}, scene);
base3.material = baseMaterial;
base3.position = new BABYLON.Vector3(224 + 63.63961030678928 - 2.4, 0.05, 280);
base3.addRotation(0, Math.PI / 4, 0);

// dirt circles at bases
const baseDirt1 = BABYLON.MeshBuilder.CreateCylinder('baseDirt1', {diameter: 20, height: 0.01}, scene);
baseDirt1.material = dirtMaterial;
baseDirt1.position = base1.position.clone();
baseDirt1.position.y = 0.005;

const baseDirt2 = BABYLON.MeshBuilder.CreateCylinder('baseDirt2', {diameter: 20, height: 0.01}, scene);
baseDirt2.material = dirtMaterial;
baseDirt2.position = base2.position.clone();
baseDirt2.position.y = 0.005;

const baseDirt3 = BABYLON.MeshBuilder.CreateCylinder('baseDirt3', {diameter: 20, height: 0.01}, scene);
baseDirt3.material = dirtMaterial;
baseDirt3.position = base3.position.clone();
baseDirt3.position.y = 0.005;

// runner labels
const runnerPositions = {
    0: base1.position.clone().add(new BABYLON.Vector3(15, 2, 20)),
    1: base2.position.clone().add(new BABYLON.Vector3(0, 2, 0)),
    2: base3.position.clone().add(new BABYLON.Vector3(-15, 2, 20)),
    home: new BABYLON.Vector3(224, 2, 343)
};

for (let base in runnerPositions) {
    const texture = new BABYLON.DynamicTexture('runner' + base, {width: 256, height: 64}, scene);
    const plane = BABYLON.MeshBuilder.CreatePlane('runner' + base, {width: 40, height: 16}, scene);
    plane.material = new BABYLON.StandardMaterial('runnerMat' + base, scene);
    plane.material.diffuseTexture = texture;
    plane.material.diffuseTexture.hasAlpha = true;
    plane.position = runnerPositions[base];
    plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
}

// foul lines
const foulPoints1 = [
    new BABYLON.Vector3(224, 0.1, 343),
    new BABYLON.Vector3(224 - 226.27, 0.1, 343 - 226.27)
];
const foulLine1 = BABYLON.MeshBuilder.CreateLines('foul1', {points: foulPoints1}, scene);

const foulPoints3 = [
    new BABYLON.Vector3(224, 0.1, 343),
    new BABYLON.Vector3(224 + 226.27, 0.1, 343 - 226.27)
];
const foulLine3 = BABYLON.MeshBuilder.CreateLines('foul3', {points: foulPoints3}, scene);

// outfield fence
const fenceShape = [
    new BABYLON.Vector3(0, 0, 0),
    new BABYLON.Vector3(0, 20, 0),
    new BABYLON.Vector3(0.5, 20, 0),
    new BABYLON.Vector3(0.5, 0, 0)
];
const fencePath = [
    new BABYLON.Vector3(224 - 226.27, 0, 343 - 226.27),
    new BABYLON.Vector3(-25, 0, 65),
    new BABYLON.Vector3(50, 0, 35),
    new BABYLON.Vector3(100, 0, 12),
    new BABYLON.Vector3(150, 0, -8),
    new BABYLON.Vector3(224, 0, -38),
    new BABYLON.Vector3(300, 0, -8),
    new BABYLON.Vector3(350, 0, 12),
    new BABYLON.Vector3(400, 0, 35),
    new BABYLON.Vector3(475, 0, 65),
    new BABYLON.Vector3(224 + 226.27, 0, 343 - 226.27),
];
const fence = BABYLON.MeshBuilder.ExtrudeShape('fence', {shape: fenceShape, path: fencePath, sideOrientation: BABYLON.Mesh.DOUBLESIDE}, scene);
const fenceMat = new BABYLON.StandardMaterial('fenceMat', scene);
fenceMat.diffuseColor = BABYLON.Color3.Gray();
fence.material = fenceMat;

// fielders
const fielderPositions = {
    1: {x: 224, z: 280},
    2: {x: 224, z: 353},
    3: {x: 154, z: 270},
    4: {x: 164, z: 210},
    5: {x: 294, z: 270},
    6: {x: 284, z: 210},
    7: {x: 344, z: 130},
    8: {x: 224, z: 80},
    9: {x: 104, z: 130},
};

for (let pos = 1; pos <= 9; pos++) {
    const texture = new BABYLON.DynamicTexture('texture' + pos, {width: 256, height: 64}, scene);
    const plane = BABYLON.MeshBuilder.CreatePlane('fielder' + pos, {width: 50, height: 20}, scene);
    if (pos === 2) {
        // Make catcher texture smaller
        plane.scaling = new BABYLON.Vector3(0.4, 0.4, 0.4);
    }
    plane.material = new BABYLON.StandardMaterial('mat' + pos, scene);
    plane.material.diffuseTexture = texture;
    plane.material.diffuseTexture.hasAlpha = true;
    plane.position = new BABYLON.Vector3(fielderPositions[pos].x, 1, fielderPositions[pos].z);
    plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
}

// status display
const statusPlane = BABYLON.MeshBuilder.CreatePlane("statusPlane", {width: 110, height: 22.5}, scene);
statusPlane.position = new BABYLON.Vector3(224, 65, 280);
const statusTexture = new BABYLON.DynamicTexture("statusTexture", {width: 220, height: 45}, scene);
statusPlane.material = new BABYLON.StandardMaterial("statusMat", scene);
statusPlane.material.diffuseTexture = statusTexture;
statusPlane.material.diffuseTexture.hasAlpha = true;
statusPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;

window.updateStatus = function(status) {
    const {
        state,
        fielders,
        runners,
        hitting,
    } = status;
    statusTexture.clear();
    // Draw the words INN BALL STRIKE OUT
    // Then underneath, draw circles for counts
    const ctx = statusTexture.getContext();

    // Set border properties
    ctx.strokeStyle = "white"; // Color of the border
    ctx.fillStyle = "#1e88ea"; // Background color
    ctx.fillRect(0, 0, statusTexture.getSize().width, statusTexture.getSize().height);
    ctx.lineWidth = 4; // Width of the border

    // Draw top border
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.lineTo(statusTexture.getSize().width, 0);
    ctx.stroke();

    // Draw bottom border
    ctx.beginPath();
    ctx.moveTo(0, statusTexture.getSize().height);
    ctx.lineTo(statusTexture.getSize().width, statusTexture.getSize().height);
    ctx.stroke();

    // Draw left border
    ctx.beginPath();
    ctx.moveTo(0, 0);
    ctx.lineTo(0, statusTexture.getSize().height);
    ctx.stroke();

    // Draw right border
    ctx.beginPath();
    ctx.moveTo(statusTexture.getSize().width, 0);
    ctx.lineTo(statusTexture.getSize().width, statusTexture.getSize().height);
    ctx.stroke();

    ctx.lineWidth = 1;

    ctx.font = "bold 16px monospace";
    ctx.fillStyle = "white";
    // ctx.textAlign = "center";
    const lines = [
        {text: 'INN', x: 4},
        {text: 'BALLS', x: 4+ctx.measureText('INN ').width},
        {text: 'STRIKES', x: 4+ctx.measureText('INN BALLS ').width},
        {text: 'OUTS', x: 4+ctx.measureText('INN BALLS STRIKES ').width},
    ];
    for (const line of lines) {
        ctx.fillText(line.text, line.x, 20);
    }

    // Innings
    ctx.fillText(`${state.half ? '⬇' : '⬆'}${state.inning}`, lines[0].x, 38);
            // {text: `${state.half ? '⬇️' : '⬆️'} ${state.inning}`, x: 50},

    // Balls
    for (let i = 0; i < 3; i++) {
        ctx.beginPath();
        ctx.arc(lines[1].x + 13 + i * 13, 32, 6, 0, 2 * Math.PI);
        ctx.fillStyle = i < state.balls ? 'green' : 'white';
        ctx.fill();
        ctx.strokeStyle = 'black';
        ctx.stroke();
    }
    // Strikes
    for (let i = 0; i < 2; i++) {
        ctx.beginPath();
        ctx.arc(lines[2].x + 25 + i * 13, 32, 6, 0, 2 * Math.PI);
        ctx.fillStyle = i < state.strikes ? 'red' : 'white';
        ctx.fill();
        ctx.strokeStyle = 'black';
        ctx.stroke();
    }
    // Outs
    for (let i = 0; i < 2; i++) {
        ctx.beginPath();
        ctx.arc(lines[3].x + 11 + i * 13, 32, 6, 0, 2 * Math.PI);
        ctx.fillStyle = i < state.outs ? 'red' : 'white';
        ctx.fill();
        ctx.strokeStyle = 'black';
        ctx.stroke();
    }

    statusTexture.update();

    const awayColor = "{{ $game->away_team->primary_color ?? '#1e88eA' }}";
    const homeColor = "{{ $game->home_team->primary_color ?? '#43a047' }}";
    // Update fielders
    const fielderColor = state.half ? awayColor : homeColor;
    for (let pos = 1; pos <= 9; pos++) {
        const texture = scene.getTextureByName('texture' + pos);
        if (texture && fielders[pos]) {
            const person = fielders[pos].person;
            const text = person.lastName + ', ' + person.firstName[0];
            console.log('Updating fielder', pos, text);
            texture.clear();
            texture.drawText(
                text,
                null,
                30,
                "bold 36px monospace",
                fielderColor,
                "transparent",
                true
            );
            texture.update();
        }
    }

    // Update runners
    const runnerColor = state.half ? homeColor : awayColor;
    for (const base in runners) {
        const texture = scene.getTextureByName('runner' + base);
        if (texture) {
            texture.clear();
            const runner = runners[base];
            if (runner) {
                console.log('Updating runner at base', base, runner.person.lastName);
                const text = runner.person.lastName + ', ' + runner.person.firstName[0];
                texture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent');
            }
        }
    }

    // Update hitter
    const hitterTexture = scene.getTextureByName('runnerhome');;
    if (hitterTexture) {
        hitterTexture.clear();
        if (hitting) {
            console.log('Updating hitter', hitting.person.lastName);
            const text = hitting.person.lastName + ', ' + hitting.person.firstName[0];
            hitterTexture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent');
        }
    }
};

// render loop
engine.runRenderLoop(() => {
    scene.render();
});
</script>
</body>
</html>