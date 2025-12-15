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

  basePositions.home = new BABYLON.Vector3(224, 2, 343)

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
  runnerTexture = new BABYLON.DynamicTexture('runnerTemplate', {width: 256, height: 64}, scene);
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
    const texture = new BABYLON.DynamicTexture('texture' + pos, {width: 256, height: 64}, scene)
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
  const statusPlane = BABYLON.MeshBuilder.CreatePlane("statusPlane", {width: 110, height: 45}, scene)
  statusPlane.position = new BABYLON.Vector3(224, 60, 280)
  const statusTexture = new BABYLON.DynamicTexture("statusTexture", {width: 220, height: 90}, scene)
  statusPlane.material = new BABYLON.StandardMaterial("statusMat", scene)
  statusPlane.material.diffuseTexture = statusTexture
  statusPlane.material.diffuseTexture.hasAlpha = true
  statusPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
}

// Update status display
const updateStatus = (status) => {
  if (!scene) return

  const { state, fielders, runners, hitting } = status

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

  const fielderColor = state.half ? props.awayColor : props.homeColor

  // Update fielders
  for (let pos = 1; pos <= 9; pos++) {
    const texture = scene.getTextureByName('texture' + pos)
    if (texture && fielders[pos]) {
      const person = fielders[pos].person
      const text = person.lastName + ', ' + person.firstName[0]
      texture.clear()
      texture.drawText(
        text,
        null,
        30,
        "bold 36px monospace",
        fielderColor,
        "transparent",
        true
      )
      texture.update()
    }
  }

  // Update runners
  const runnerColor = state.half ? props.homeColor : props.awayColor;
  const nextRunners = {}

  // Handle runners on bases
  for (const base in runners) {
    const runner = runners[base]
    if (runner.person_id in previousRunners) {
      const prevBase = previousRunners[runner.person_id]
      delete previousRunners[runner.person_id]
      if (prevBase !== base) {
        // Move runner
        const mesh = scene.getMeshByName(`runner${runner.person_id}`)
        if (mesh) {
          const startPosition = basePositions[prevBase]
          const targetPosition = basePositions[base]
          BABYLON.Animation.CreateAndStartAnimation(
            'moveRunner' + runner.person_id,
            mesh,
            'position',
            30,
            30,
            startPosition,
            targetPosition,
            BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
          )
        }
      }
      nextRunners[runner.person_id] = base
    } else {
      // Add new runner
      const plane = scene.getMeshByName('runnerPlaneTemplate').clone('runner' + runner.person_id)
      const texture = scene.getTextureByName('runnerTemplate').clone('runner' + runner.person_id)
      plane.material = new BABYLON.StandardMaterial('runnerMat' + runner.person_id, scene)
      plane.material.diffuseTexture = texture
      plane.material.diffuseTexture.hasAlpha = true
      plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
      const targetPosition = basePositions[base]
      plane.position = targetPosition
      const text = runner.person.lastName + ', ' + runner.person.firstName[0]
      texture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent')
      nextRunners[runner.person_id] = base
    }
  }

  // Handle hitter
  if (hitting) {
    if (hitting.person_id in previousRunners) {
      const prevBase = previousRunners[hitting.person_id]
      delete previousRunners[hitting.person_id]
      if (prevBase !== 'home') {
        const mesh = scene.getMeshByName(`runner${hitting.person_id}`)
        if (mesh) {
          const startPosition = basePositions[prevBase]
          const targetPosition = basePositions.home
          BABYLON.Animation.CreateAndStartAnimation(
            'moveRunner' + hitting.person_id,
            mesh,
            'position',
            30,
            30,
            startPosition,
            targetPosition,
            BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
          )
        }
      }
      nextRunners[hitting.person_id] = 'home'
    } else {
      const plane = scene.getMeshByName('runnerPlaneTemplate').clone('runner' + hitting.person_id)
      const texture = scene.getTextureByName('runnerTemplate').clone('runner' + hitting.person_id)
      plane.material = new BABYLON.StandardMaterial('runnerMat' + hitting.person_id, scene)
      plane.material.diffuseTexture = texture
      plane.material.diffuseTexture.hasAlpha = true
      plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
      const targetPosition = basePositions.home
      plane.position = targetPosition
      const text = hitting.person.lastName + ', ' + hitting.person.firstName[0]
      texture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent')
      nextRunners[hitting.person_id] = 'home'
    }
  }

  // Remove old runners
  for (const runnerId in previousRunners) {
    const mesh = scene.getMeshByName(`runner${runnerId}`)
    if (mesh) {
      mesh.dispose()
    }
  }

  previousRunners = nextRunners
}

// Draw linescore below the status lights
const drawLinescore = (ctx) => {
  if (!props.state.linescore) return

  const linescoreY = 25 // Start below the status lights
  const lineHeight = 12
  const colWidth = 16

  // Team abbreviations
  ctx.font = "bold 10px monospace"
  ctx.fillStyle = "white"

  // Away team
  const awayShort = props.game.away_team?.short_name || 'AWAY'
  ctx.fillText(awayShort.substring(0, 3), 4, linescoreY)

  // Home team
  const homeShort = props.game.home_team?.short_name || 'HOME'
  ctx.fillText(homeShort.substring(0, 3), 4, linescoreY + lineHeight)

  // Inning scores
  const awayScores = props.state.linescore[0] || []
  const homeScores = props.state.linescore[1] || []

  // Draw up to 9 innings
  for (let inning = 1; inning <= 9; inning++) {
    const x = 35 + (inning - 1) * colWidth

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

  const totalX = 35 + 9 * colWidth - 5
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
  ctx.font = "bold 16px monospace"
  ctx.fillStyle = "white"
  const lines = [
    {text: 'INN', x: 4},
    {text: 'BALLS', x: 4 + ctx.measureText('INN ').width},
    {text: 'STRIKES', x: 4 + ctx.measureText('INN BALLS ').width},
    {text: 'OUTS', x: 4 + ctx.measureText('INN BALLS STRIKES ').width},
  ]
  for (const line of lines) {
    ctx.fillText(line.text, line.x, 60);
  }

  // Inning
  ctx.fillText(`${target.half ? '⬇' : '⬆'}${target.inning}`, lines[0].x, 78);

  // Animated lights
  const calcAlpha = (type, i) => {
    const targetState = i < target[type]
    if (!previousCounts) return targetState ? 1 : 0
    const curState = i < previousCounts[type]
    if (curState === targetState) return targetState ? 1 : 0
    const progress = curState ? (1 - animationProgress) : animationProgress
    return progress
  }

  const LIGHT_Y = 72;
  const LIGHT_RADIUS = 6

  // Balls
  for (let i = 0; i < 3; i++) {
    ctx.fillStyle = 'black'
    ctx.beginPath()
    ctx.arc(lines[1].x + 13 + i * 14, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
    ctx.fill()
    ctx.fillStyle = 'green'
    ctx.globalAlpha = calcAlpha('balls', i)
    ctx.beginPath()
    ctx.arc(lines[1].x + 13 + i * 14, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
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
    drawLight(lines[2].x + 25 + i * 14, LIGHT_Y, 'red', calcAlpha('strikes', i))
  }

  // Outs
  for (let i = 0; i < 2; i++) {
    drawLight(lines[3].x + 11 + i * 14, LIGHT_Y, 'red', calcAlpha('outs', i))
  }

  // Draw linescore below the status lights
  drawLinescore(ctx)

  statusTexture.update()
}

const toast = (message) => {
  console.log("Toast called", message);
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
  <canvas ref="canvasRef" style="width: 100%; height: 100%;"></canvas>
</template>