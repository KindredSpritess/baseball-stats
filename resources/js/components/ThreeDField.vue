<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import * as BABYLON from 'babylonjs'

// Props
const props = defineProps({
  game: {
    type: Object,
    required: true
  },
  state: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  awayColor: {
    type: String,
    default: '#1e88eA'
  },
  homeColor: {
    type: String,
    default: '#43a047'
  }
})

// Refs
const canvasRef = ref(null)

// Toast refs
const toastMessage = ref('')
const toastVisible = ref(false)

// Babylon.js variables
let engine = null;
let scene = null;
let camera = null;
let light = null;

let runnerTexture = null;
let runnerPlane = null;

// Animation variables
let isAnimating = false
let animationProgress = 0
let previousCounts = null
let previousRunners = {}
let target = {balls: 0, strikes: 0, outs: 0}

// Base positions for runners
const basePositions = {
  0: null, // Will be set after bases are created
  1: null,
  2: null,
  3: null,
  plate: null,
  home: null
}

// Initialize the 3D scene
const initScene = () => {
  if (!canvasRef.value) return

  engine = new BABYLON.Engine(canvasRef.value, true)
  scene = new BABYLON.Scene(engine)

  // Camera
  camera = new BABYLON.FreeCamera('camera', new BABYLON.Vector3(224, 35, 390), scene)
  camera.setTarget(new BABYLON.Vector3(224, 0, 240))
  camera.setFocalLength(24);

  // Light
  light = new BABYLON.HemisphericLight('light', new BABYLON.Vector3(0, 1, 0), scene)

  createField()
  createStatusDisplay()

  // Render loop
  engine.runRenderLoop(() => {
    animateStatusLights()
    scene.render()
  })
}

// Create the baseball field
const createField = () => {
  // Field (outfield)
  const field = BABYLON.MeshBuilder.CreateGround('field', {width: 1000, height: 1000}, scene)
  const fieldMaterial = new BABYLON.StandardMaterial('fieldMat', scene)
  fieldMaterial.diffuseColor = new BABYLON.Color3(0.46, 0.73, 0.60) // #75d89b
  field.material = fieldMaterial
  field.position = new BABYLON.Vector3(224, 0, 250)

  // Dirt material
  const dirtMaterial = new BABYLON.StandardMaterial('dirtMat', scene)
  dirtMaterial.diffuseColor = new BABYLON.Color3(0.85, 0.61, 0.46) // #d89b75

  // Pitcher's mound
  const moundDirt = BABYLON.MeshBuilder.CreateCylinder('moundDirt', {diameter: 18, height: 0.1}, scene)
  moundDirt.material = dirtMaterial
  moundDirt.position = new BABYLON.Vector3(224, 0.05, 277)

  // Home plate dirt
  const plateDirt = BABYLON.MeshBuilder.CreateCylinder('plateDirt', {diameter: 32, height: 0.1}, scene)
  plateDirt.material = dirtMaterial
  plateDirt.position = new BABYLON.Vector3(224, 0.05, 343)

  // Bases
  const baseMaterial = new BABYLON.StandardMaterial('baseMat', scene)
  baseMaterial.diffuseColor = BABYLON.Color3.White()

  const base1 = BABYLON.MeshBuilder.CreateBox('base1', {width: 4, height: 0.1, depth: 4}, scene)
  base1.material = baseMaterial
  base1.position = new BABYLON.Vector3(224 - 63.63961030678928 + 2.4, 0.05, 280)
  base1.addRotation(0, Math.PI / 4, 0)
  basePositions[0] = base1.position.clone().add(new BABYLON.Vector3(15, 2, 20))

  const base2 = BABYLON.MeshBuilder.CreateBox('base2', {width: 4, height: 0.1, depth: 4}, scene)
  base2.material = baseMaterial
  base2.position = new BABYLON.Vector3(224, 0.05, 280 - 63.63961030678928)
  base2.addRotation(0, Math.PI / 4, 0)
  basePositions[1] = base2.position.clone().add(new BABYLON.Vector3(0, 2, 0))

  const base3 = BABYLON.MeshBuilder.CreateBox('base3', {width: 4, height: 0.1, depth: 4}, scene)
  base3.material = baseMaterial
  base3.position = new BABYLON.Vector3(224 + 63.63961030678928 - 2.4, 0.05, 280)
  base3.addRotation(0, Math.PI / 4, 0)
  basePositions[2] = base3.position.clone().add(new BABYLON.Vector3(-15, 2, 20))

  basePositions[3] = new BABYLON.Vector3(224, 2, 343)
  basePositions.home = new BABYLON.Vector3(224, 2, 343)
  basePositions.plate = new BABYLON.Vector3(224, 2, 343)

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

  // Foul lines
  const foulPoints1 = [
    new BABYLON.Vector3(224, 0.1, 343),
    new BABYLON.Vector3(224 - 226.27, 0.1, 343 - 226.27)
  ]
  BABYLON.MeshBuilder.CreateLines('foul1', {points: foulPoints1}, scene)

  const foulPoints3 = [
    new BABYLON.Vector3(224, 0.1, 343),
    new BABYLON.Vector3(224 + 226.27, 0.1, 343 - 226.27)
  ]
  BABYLON.MeshBuilder.CreateLines('foul3', {points: foulPoints3}, scene)

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

  // Runner template
  runnerTexture = new BABYLON.DynamicTexture('runnerTemplate', {width: 512, height: 128}, scene);
  runnerTexture.updateSamplingMode(BABYLON.Texture.TRILINEAR_SAMPLINGMODE);
  runnerPlane = BABYLON.MeshBuilder.CreatePlane('runnerPlaneTemplate', {width: 40, height: 16}, scene);
  runnerPlane.material = new BABYLON.StandardMaterial('runnerMatTemplate', scene);
  runnerPlane.material.diffuseTexture = runnerTexture;
  runnerPlane.material.diffuseTexture.hasAlpha = true;
  runnerPlane.position = new BABYLON.Vector3(0, -10, 0); // off screen initially
  runnerPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;

  // Create fielder positions (placeholders)
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
  }

  for (let pos = 1; pos <= 9; pos++) {
    const texture = new BABYLON.DynamicTexture('texture' + pos, {width: 512, height: 128}, scene)
    texture.updateSamplingMode(BABYLON.Texture.TRILINEAR_SAMPLINGMODE);
    const plane = BABYLON.MeshBuilder.CreatePlane('fielder' + pos, {width: 50, height: 20}, scene)
    if (pos === 2) {
      plane.scaling = new BABYLON.Vector3(0.4, 0.4, 0.4)
    }
    plane.material = new BABYLON.StandardMaterial('mat' + pos, scene)
    plane.material.diffuseTexture = texture
    plane.material.diffuseTexture.hasAlpha = true
    plane.position = new BABYLON.Vector3(fielderPositions[pos].x, 1, fielderPositions[pos].z)
    plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
  }
}

// Create status display
const createStatusDisplay = () => {
  const statusPlane = BABYLON.MeshBuilder.CreatePlane("statusPlane", {width: 330, height: 135}, scene)
  statusPlane.position = new BABYLON.Vector3(224, 80, -55)
  const statusTexture = new BABYLON.DynamicTexture("statusTexture", {width: 440, height: 180}, scene)
  statusPlane.material = new BABYLON.StandardMaterial("statusMat", scene)
  statusPlane.material.diffuseTexture = statusTexture
  statusPlane.material.diffuseTexture.hasAlpha = true
  statusPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
}

// Animate runner through actions
const animateRunner = (playerId, startBase, actions, color) => {
  let mesh = scene.getMeshByName(`runner${playerId}`)
  if (!mesh) {
    mesh = scene.getMeshByName('runnerPlaneTemplate').clone('runner' + playerId)
    const texture = scene.getTextureByName('runnerTemplate').clone('runner' + playerId)
    texture.updateSamplingMode(BABYLON.Texture.TRILINEAR_SAMPLINGMODE);
    mesh.material = new BABYLON.StandardMaterial('runnerMat' + playerId, scene)
    mesh.material.diffuseTexture = texture
    mesh.material.diffuseTexture.hasAlpha = true
    mesh.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
    const person = [props.game.home_team.players, props.game.away_team.players]
      .flat()
      .find(p => p.id == playerId)
      ?.person;
    const text = person.lastName + ', ' + person.firstName[0]
    const ctx = texture.getContext()
    ctx.font = "64px Helvetica"
    ctx.textAlign = "center"
    // ctx.textBaseline = "middle"
    ctx.strokeStyle = "white"
    ctx.lineWidth = 4
    ctx.strokeText(text, 256, 64)
    ctx.fillStyle = color
    ctx.fillText(text, 256, 64)
    texture.update()
  }

  let currentPosition = basePositions[startBase]
  mesh.position = currentPosition.clone()

  const exitPosition = basePositions.home.clone().add(new BABYLON.Vector3(0, 0, -100))
  const keyFrames = [];
  let currentBase = startBase === 'plate' ? -1 : startBase;
  for (let i = 0; i < actions.length; i++) {
    if (actions[i] == -1) {
      keyFrames.push(-1);
    } else {
      if (i !== 0) {
        // Add a delay at the current base
        keyFrames.push(currentBase);
      }
      while (currentBase < actions[i]) {
        currentBase++;
        keyFrames.push(currentBase);
      }
      if (currentBase === 3) {
        // We'll use color as a key for his team's dugout.
        keyFrames.push(color);
      }
    }
  }

  let actionIndex = 0
  const animateNext = () => {
    if (actionIndex >= keyFrames.length) return

    const action = keyFrames[actionIndex];
    actionIndex++

    if (action == -1) {
      // Fade out
      BABYLON.Animation.CreateAndStartAnimation(
        'fadeRunner' + playerId,
        mesh,
        'visibility',
        30,
        30,
        1,
        0,
        BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT,
        null,
        () => {
          mesh.dispose()
        }
      )
    } else if (typeof action === 'string') {
      // Move to dugout position based on color
      const dugoutOffset = action === props.homeColor ? -60 : 60
      const dugoutPosition = basePositions.home.clone().add(new BABYLON.Vector3(dugoutOffset, 0, -10))
      BABYLON.Animation.CreateAndStartAnimation(
        'dugoutRunner' + playerId,
        mesh,
        'position',
        30,
        30,
        currentPosition,
        dugoutPosition,
        BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT,
        null,
        () => {
          mesh.dispose()
        }
      )
    } else if (action >= 0 && action <= 3) {
      const targetPosition = basePositions[action];
      BABYLON.Animation.CreateAndStartAnimation(
        'moveRunner' + playerId + actionIndex,
        mesh,
        'position',
        30,
        // Just a short animation if not moving
        currentPosition.equals(targetPosition) ? 15 : 30,
        currentPosition,
        targetPosition,
        BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT,
        null,
        () => {
          currentPosition = targetPosition.clone()
          animateNext()
        }
      )
    }
  }

  animateNext();
  return keyFrames.length;
}

const animateBattedBall = (battedBall) => {
  if (!battedBall || !scene) return;

  const ball = BABYLON.MeshBuilder.CreateSphere('battedBall', {diameter: 4}, scene);
  const ballMaterial = new BABYLON.StandardMaterial('ballMat', scene);
  ballMaterial.diffuseColor = BABYLON.Color3.White();
  ball.material = ballMaterial;
  ball.position = basePositions.plate.clone().add(new BABYLON.Vector3(0, 4, 0));
  
  const baseDistance = Math.sqrt(
    Math.pow(battedBall.position[0] - 224, 2) +
    Math.pow(battedBall.position[1] - 405, 2)
  );

  const d = battedBall.distance;
  const targetPosition = basePositions.home.clone().add(new BABYLON.Vector3(
    -(battedBall.position[0] - 224) / baseDistance * d,
    0,
    (battedBall.position[1] - 405) / baseDistance * d
  ));

  const trajectory = {
    'F': { height: 60, duration: d / 73 * 30, parabolic: true, bounces: 0 },
    'L': { height: 30, duration: d / 102 * 30, parabolic: true, bounces: 0 },
    'G': { height: 5, duration: d / 102 * 30, parabolic: true, bounces: 2 },
    'P': { height: 80, duration: d / 50 * 30, parabolic: true, bounces: 0 },
  }[battedBall.type] ?? { height: 0, duration: 45, parabolic: false, bounces: 0 };

  if (trajectory.parabolic) {
    // Create parabolic path
    const frames = trajectory.duration;
    const animation = new BABYLON.Animation(
      'battedBallAnim',
      'position',
      30,
      BABYLON.Animation.ANIMATIONTYPE_VECTOR3,
      BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
    );
    
    const keys = [];
    for (let i = 0; i <= frames; i++) {
      const t = i / frames;
      const x = ball.position.x + (targetPosition.x - ball.position.x) * t;
      const z = ball.position.z + (targetPosition.z - ball.position.z) * t;
      let y;
      if (trajectory.bounces > 0) {
        // Bouncing ground ball
        const bounceHeight = trajectory.height * (1 - t);
        y = ball.position.y + (targetPosition.y - ball.position.y) * t + bounceHeight * Math.abs(Math.sin(t * Math.PI * (trajectory.bounces + 1)));
      } else {
        // Parabolic arc
        y = ball.position.y + (targetPosition.y - ball.position.y) * t + trajectory.height * Math.sin(t * Math.PI);
      }
      keys.push({ frame: i, value: new BABYLON.Vector3(x, y, z) });
    }

    animation.setKeys(keys);
    ball.animations = [animation];
    scene.beginAnimation(ball, 0, frames, false, 1, () => {
      setTimeout(() => ball.dispose(), 500);
    });
  } else {
    // Straight line animation
    BABYLON.Animation.CreateAndStartAnimation(
      'battedBallAnim',
      ball,
      'position',
      30,
      trajectory.duration,
      ball.position,
      targetPosition,
      BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT,
      null,
      () => {
        setTimeout(() => ball.dispose(), 500);
      }
    );
  }

  return trajectory.duration / 30; // Return number of seconds for animation
}

// Update status display
const updateStatus = (status, play) => {
  if (!scene) return;

  const { state, fielders, runners, hitting } = status;
  const { actions, ball_in_play } = play || {};

  // Check if counts changed for animation
  const countsChanged = !previousCounts ||
    state.balls !== previousCounts.balls ||
    state.strikes !== previousCounts.strikes ||
    state.outs !== previousCounts.outs

  if (countsChanged) {
    isAnimating = true
    animationProgress = 0
    target = {
      balls: state.balls,
      strikes: state.strikes,
      outs: state.outs,
      inning: state.inning,
      half: state.half,
    }
  }

  // Update runners using actions
  const runnerColor = state.half ? props.homeColor : props.awayColor;
  let delayFrames = 0;

  const nextRunners = {};
  // Animate runners based on actions
  for (const playerId in actions) {
    const playerActions = actions[playerId];
    const startBase = previousRunners[playerId] ?? null;
    if (startBase === null) continue; // No previous position, skip
    delayFrames = Math.max(delayFrames, animateRunner(playerId, startBase, playerActions, runnerColor));
    const lastPosition = playerActions[playerActions.length - 1];
    // If the runner is still on base, include in nextRunners.
    if (lastPosition !== -1 && lastPosition !== 3) {
      nextRunners[playerId] = lastPosition;
    }
    delete previousRunners[playerId];
  }

  // If we have a set of actions, copy across any runners not in actions.
  if (actions?.length) {
    for (const playerId in previousRunners) {
      if (!(playerId in nextRunners) && !(playerId in actions)) {
        nextRunners[playerId] = previousRunners[playerId];
      }
    }
  }

  // If we have a batted ball, animate the batted ball.
  if (ball_in_play) {
    delayFrames = Math.max(delayFrames, animateBattedBall(ball_in_play));
  }

  if (delayFrames > 0) {
    setTimeout(() => updateStatus(status), 1000 * delayFrames);
    return;
  }

  // Now go through previousRunners to find any runners that should be deleted.
  for (const playerId in previousRunners) {
    if (!(playerId in nextRunners)) {
      // This runner is no longer active, remove their mesh.
      const mesh = scene.getMeshByName(`runner${playerId}`);
      if (mesh) {
        mesh.dispose();
      }
    }
  }

  // Now add any new runners that weren't in previousRunners.
  for (const base in runners) {
    const runner = runners[base];
    if (!(runner.id in previousRunners)) {
      // New runner, animate from current base to current base (no movement).
      animateRunner(runner.id, base, [], runnerColor);
      nextRunners[runner.id] = parseInt(base);
    }
  }

  // And add the hitter.
  if (hitting && !(hitting.id in previousRunners)) {
    animateRunner(hitting.id, 'plate', [], runnerColor);
    nextRunners[hitting.id] = 'plate';
  }

  previousRunners = nextRunners;

  const fielderColor = state.half ? props.awayColor : props.homeColor

  // Update fielders
  for (let pos = 1; pos <= 9; pos++) {
    const texture = scene.getTextureByName('texture' + pos)
    if (texture && fielders[pos]) {
      const person = fielders[pos].person
      const text = person.lastName + ', ' + person.firstName[0]
      texture.clear()
      const ctx = texture.getContext()
      ctx.font = "bold 64px Helvetica"
      ctx.textAlign = "center"
      // ctx.textBaseline = "middle"
      ctx.strokeStyle = "white"
      ctx.lineWidth = 4
      ctx.strokeText(text, 256, 64)
      ctx.fillStyle = fielderColor
      ctx.fillText(text, 256, 64)
      texture.update()
    }
  }
}

// Draw linescore below the status lights
const drawLinescore = (ctx) => {
  if (!props.state.linescore) return

  const linescoreY = 50 // Start below the status lights
  const lineHeight = 24
  const colWidth = 32

  // Team abbreviations
  ctx.font = "bold 20px monospace"
  ctx.fillStyle = "white"

  // Away team
  const awayShort = props.game.away_team?.short_name || 'AWAY'
  ctx.fillText(awayShort.substring(0, 3), 8, linescoreY)

  // Home team
  const homeShort = props.game.home_team?.short_name || 'HOME'
  ctx.fillText(homeShort.substring(0, 3), 8, linescoreY + lineHeight)

  // Inning scores
  const awayScores = props.state.linescore[0] || []
  const homeScores = props.state.linescore[1] || []

  // Draw up to 9 innings
  for (let inning = 1; inning <= 9; inning++) {
    const x = 70 + (inning - 1) * colWidth

    // Inning number
    ctx.fillText(inning, x, linescoreY - lineHeight);

    // Away team score for this inning
    const awayScore = awayScores[inning - 1] ?? '';
    ctx.fillText(awayScore.toString(), x, linescoreY)

    // Home team score for this inning
    const homeScore = homeScores[inning - 1] ?? '';
    ctx.fillText(homeScore.toString(), x, linescoreY + lineHeight)
  }

  // Total runs (R column)
  const awayTotal = awayScores.reduce((sum, score) => sum + score, 0)
  const homeTotal = homeScores.reduce((sum, score) => sum + score, 0)

  const totalX = 70 + 9 * colWidth - 10
  ctx.fillText('R', totalX, linescoreY - lineHeight)
  ctx.fillText(props.stats.away.R ?? 0, totalX, linescoreY)
  ctx.fillText(props.stats.home.R ?? 0, totalX, linescoreY + lineHeight)
  ctx.fillText('H', totalX + colWidth, linescoreY - lineHeight)
  ctx.fillText(props.stats.away.H ?? 0, totalX + colWidth, linescoreY)
  ctx.fillText(props.stats.home.H ?? 0, totalX + colWidth, linescoreY + lineHeight)
  ctx.fillText('E', totalX + 2 * colWidth, linescoreY - lineHeight)
  ctx.fillText(props.stats.away.E ?? 0, totalX + 2 * colWidth, linescoreY)
  ctx.fillText(props.stats.home.E ?? 0, totalX + 2 * colWidth, linescoreY + lineHeight)
}

// Animate status lights
const animateStatusLights = () => {
  if (!isAnimating || !scene) return

  animationProgress += 0.05
  if (animationProgress >= 1) {
    animationProgress = 1
    isAnimating = false
    previousCounts = { ...target }
  }

  const statusTexture = scene.getTextureByName('statusTexture')
  if (!statusTexture) return

  statusTexture.clear()
  const ctx = statusTexture.getContext()

  // Draw background
  ctx.fillStyle = "#1e88ea"
  ctx.fillRect(0, 0, statusTexture.getSize().width, statusTexture.getSize().height)
  ctx.strokeStyle = "white"
  ctx.lineWidth = 4
  ctx.strokeRect(0, 0, statusTexture.getSize().width, statusTexture.getSize().height)
  ctx.lineWidth = 1

  // Draw labels
  ctx.font = "bold 32px 'Courier New'"
  ctx.fillStyle = "white"
  const lines = [
    {text: 'INN', x: 8},
    {text: 'BALLS', x: 8 + ctx.measureText('INN ').width},
    {text: 'STRIKES', x: 8 + ctx.measureText('INN BALLS ').width},
    {text: 'OUTS', x: 8 + ctx.measureText('INN BALLS STRIKES ').width},
  ]
  for (const line of lines) {
    ctx.fillText(line.text, line.x, 120);
  }

  // Inning
  ctx.fillText(`${target.half ? '⬇' : '⬆'}${target.inning}`, lines[0].x, 156);

  // Animated lights
  const calcAlpha = (type, i) => {
    const targetState = i < target[type]
    if (!previousCounts) return targetState ? 1 : 0
    const curState = i < previousCounts[type]
    if (curState === targetState) return targetState ? 1 : 0
    const progress = curState ? (1 - animationProgress) : animationProgress
    return progress
  }

  const LIGHT_Y = 144;
  const LIGHT_RADIUS = 12

  // Balls
  for (let i = 0; i < 3; i++) {
    ctx.fillStyle = 'black'
    ctx.beginPath()
    ctx.arc(lines[1].x + 26 + i * 28, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
    ctx.fill()
    ctx.fillStyle = 'green'
    ctx.globalAlpha = calcAlpha('balls', i)
    ctx.beginPath()
    ctx.arc(lines[1].x + 26 + i * 28, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
    ctx.fill()
    ctx.globalAlpha = 1
    ctx.strokeStyle = 'black'
    ctx.stroke()
  }

  // Strikes and Outs
  const drawLight = (x, y, color, alpha) => {
    ctx.fillStyle = 'black'
    ctx.beginPath()
    ctx.arc(x, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
    ctx.fill()
    ctx.fillStyle = color
    ctx.globalAlpha = alpha
    ctx.beginPath()
    ctx.arc(x, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
    ctx.fill()
    ctx.globalAlpha = 1
    ctx.strokeStyle = 'black'
    ctx.stroke()
  }

  // Strikes
  for (let i = 0; i < 2; i++) {
    drawLight(lines[2].x + 50 + i * 28, LIGHT_Y, 'red', calcAlpha('strikes', i))
  }

  // Outs
  for (let i = 0; i < 2; i++) {
    drawLight(lines[3].x + 22 + i * 28, LIGHT_Y, 'red', calcAlpha('outs', i))
  }

  // Draw linescore below the status lights
  drawLinescore(ctx)

  statusTexture.update()
}

const toast = (message) => {
  toastMessage.value = message
  toastVisible.value = true
  setTimeout(() => {
    toastVisible.value = false
  }, 5000) // Hide after 5 seconds
};

// Lifecycle hooks
onMounted(() => {
  initScene();

  // Handle window resize
  const handleResize = () => {
    if (engine) {
      engine.resize()
    }
  }
  window.addEventListener('resize', handleResize)

  onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    if (engine) {
      engine.dispose()
    }
  })
})

// Expose updateStatus method
defineExpose({
  updateStatus,
  toast,
})
</script>

<template>
  <div style="width:100%; position: relative;">
    <canvas ref="canvasRef" style="width: 100%; height: 100%;"></canvas>
    <Transition name="toast">
      <div v-if="toastVisible" class="toast-notification">
        {{ toastMessage }}
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.toast-notification {
  position: absolute;
  bottom: 10px;
  right: 10px;
  left: 10px;
  background-color: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 10px 15px;
  border-radius: 5px;
  font-size: 14px;
  z-index: 1000;
  word-wrap: break-word;
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.5s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
}
</style>