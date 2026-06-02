<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
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
let hemisphericLight = null;
let floodLights = [];
let skyTexture = null;
let lastLightingMinute = null;
let bulbMat = null;

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
  home: null,
  mound: null,
}

const homePlatePos = new BABYLON.Vector3(224, 0, 343);
const leftFieldPos = new BABYLON.Vector3(224 - 233.35, 0, 343 - 233.35);
const rightFieldPos = new BABYLON.Vector3(224 + 233.35, 0, 343 - 233.35);

const getDisplayHour = () => {
  return 22;
  const gameTimeZone = props.game?.timeZone;
  const hourInGameTimezone = (date) => {
    if (!gameTimeZone) {
      return date.getHours() + (date.getMinutes() / 60);
    }
    const parts = new Intl.DateTimeFormat('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: gameTimeZone,
    }).formatToParts(date);
    const hour = parseInt(parts.find((part) => part.type === 'hour')?.value ?? '0', 10);
    const minute = parseInt(parts.find((part) => part.type === 'minute')?.value ?? '0', 10);
    return hour + (minute / 60);
  }

  if (!props.game?.ended) {
    return hourInGameTimezone(new Date());
  }

  const completionTime = props.game?.metadata?.LP ?? props.game?.metadata?.FP ?? props.game?.firstPitch;
  if (typeof completionTime === 'string') {
    const metadataHour = completionTime.match(/\b(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?\b/i);
    if (metadataHour) {
      const parsedHour = parseInt(metadataHour[1], 10);
      const parsedMinute = parseInt(metadataHour[2], 10);
      const meridiem = metadataHour[3]?.toUpperCase();

      let hour = parsedHour % 24;
      if (meridiem === 'PM' && parsedHour < 12) {
        hour += 12;
      }
      if (meridiem === 'AM' && parsedHour === 12) {
        hour = 0;
      }
      return hour + (parsedMinute / 60);
    }
  }

  const date = new Date(completionTime);
  if (Number.isNaN(date.getTime())) {
    return 12;
  }
  return hourInGameTimezone(date);
}

const lerp = (start, end, amount) => start + ((end - start) * amount)

const smoothstep = (edge0, edge1, value) => {
  const normalized = Math.min(1, Math.max(0, (value - edge0) / (edge1 - edge0)))
  return normalized * normalized * (3 - (2 * normalized))
}

const getLightingProfile = (hour) => {
  const wrappedHour = ((hour % 24) + 24) % 24

  const keyframes = [
    { hour: 0, clearColor: new BABYLON.Color4(0.04, 0.06, 0.12, 1), skyColor: '#0B1D3A', cloudOpacity: 0.28, ambientIntensity: 0.25, floodLightStrength: 1 },
    { hour: 5.5, clearColor: new BABYLON.Color4(0.04, 0.06, 0.12, 1), skyColor: '#0B1D3A', cloudOpacity: 0.28, ambientIntensity: 0.25, floodLightStrength: 1 },
    { hour: 6.75, clearColor: new BABYLON.Color4(0.95, 0.55, 0.32, 1), skyColor: '#F19466', cloudOpacity: 0.55, ambientIntensity: 0.55, floodLightStrength: 0.55 },
    { hour: 8, clearColor: new BABYLON.Color4(0.53, 0.81, 0.92, 1), skyColor: '#87CEEB', cloudOpacity: 0.8, ambientIntensity: 0.95, floodLightStrength: 0 },
    { hour: 17, clearColor: new BABYLON.Color4(0.53, 0.81, 0.92, 1), skyColor: '#87CEEB', cloudOpacity: 0.8, ambientIntensity: 0.95, floodLightStrength: 0 },
    { hour: 18.5, clearColor: new BABYLON.Color4(0.95, 0.55, 0.32, 1), skyColor: '#F19466', cloudOpacity: 0.55, ambientIntensity: 0.55, floodLightStrength: 0.55 },
    { hour: 20, clearColor: new BABYLON.Color4(0.04, 0.06, 0.12, 1), skyColor: '#0B1D3A', cloudOpacity: 0.28, ambientIntensity: 0.25, floodLightStrength: 1 },
    { hour: 24, clearColor: new BABYLON.Color4(0.04, 0.06, 0.12, 1), skyColor: '#0B1D3A', cloudOpacity: 0.28, ambientIntensity: 0.25, floodLightStrength: 1 },
  ]

  for (let i = 0; i < keyframes.length - 1; i++) {
    const start = keyframes[i]
    const end = keyframes[i + 1]
    if (wrappedHour < start.hour || wrappedHour > end.hour) {
      continue
    }

    const rawProgress = (wrappedHour - start.hour) / (end.hour - start.hour || 1)
    const progress = smoothstep(0, 1, rawProgress)

    const startSky = BABYLON.Color3.FromHexString(start.skyColor)
    const endSky = BABYLON.Color3.FromHexString(end.skyColor)

    return {
      clearColor: new BABYLON.Color4(
        lerp(start.clearColor.r, end.clearColor.r, progress),
        lerp(start.clearColor.g, end.clearColor.g, progress),
        lerp(start.clearColor.b, end.clearColor.b, progress),
        1
      ),
      skyColor: new BABYLON.Color3(
        lerp(startSky.r, endSky.r, progress),
        lerp(startSky.g, endSky.g, progress),
        lerp(startSky.b, endSky.b, progress)
      ).toHexString(),
      cloudOpacity: lerp(start.cloudOpacity, end.cloudOpacity, progress),
      ambientIntensity: lerp(start.ambientIntensity, end.ambientIntensity, progress),
      floodLightStrength: lerp(start.floodLightStrength, end.floodLightStrength, progress),
    }
  }

  return keyframes[0]
}

const drawSkyTexture = (baseColor, cloudOpacity) => {
  if (!skyTexture) {
    return;
  }

  const ctxSky = skyTexture.getContext()
  ctxSky.fillStyle = baseColor
  ctxSky.fillRect(0, 0, 16, 16)

  ctxSky.fillStyle = `rgba(255, 255, 255, ${cloudOpacity})`
  for (let i = 0; i < 30; i++) {
    const x = Math.random() * 16
    const y = Math.random() * 16
    const radius = Math.random() * 0.625 + 0.156
    ctxSky.beginPath()
    ctxSky.arc(x, y, radius, 0, 2 * Math.PI)
    ctxSky.fill()
    if (Math.random() > 0.5) {
      ctxSky.beginPath()
      ctxSky.arc(x + Math.random() * 1 - 0.5, y + Math.random() * 1 - 0.5, radius * 0.7, 0, 2 * Math.PI)
      ctxSky.fill()
    }
  }
  skyTexture.update()
}

// new BABYLON.Vector3(224 - 233.35, 0, 343 - 233.35),
//       // new BABYLON.Vector3(224 - 233.35 + 106.07, 0, 343 - 233.35 - 106.07),

//       new BABYLON.Vector3(100, 0, -0.85),
//       new BABYLON.Vector3(110, 0, -11.29),
//       new BABYLON.Vector3(120, 0, -20.10),
//       new BABYLON.Vector3(130, 0, -27.61),
//       new BABYLON.Vector3(140, 0, -34.02),
//       new BABYLON.Vector3(150, 0, -39.48),
//       new BABYLON.Vector3(160, 0, -44.08),
//       new BABYLON.Vector3(170, 0, -47.91),
//       new BABYLON.Vector3(180, 0, -51.03),
//       new BABYLON.Vector3(190, 0, -53.46),
//       new BABYLON.Vector3(200, 0, -55.25),
//       new BABYLON.Vector3(210, 0, -56.40),
//       new BABYLON.Vector3(220, 0, -56.95),
// new BABYLON.Vector3(224 + 233.35, 0, 343 - 233.35),

  // const foulPoints1 = [
  //   new BABYLON.Vector3(224, 0.1, 343),
  //   new BABYLON.Vector3(224 - 233.35, 0, 343 - 233.35),
  // ]
  // BABYLON.MeshBuilder.CreateLines('foul1', {points: foulPoints1}, scene)

  // const foulPoints3 = [
  //   new BABYLON.Vector3(224, 0.1, 343),
  //   new BABYLON.Vector3(224 + 233.35, 0, 343 - 233.35),
  // ]

// 224, 343

const infieldTarget = new BABYLON.Vector3(224, 0, 286)
const homePlatePosition = new BABYLON.Vector3(224, 0, 343)
const centerFieldPosition = new BABYLON.Vector3(224, 0, -57)
const floodlightConfigs = [
  { position: new BABYLON.Vector3(20, 80, 60), target: homePlatePosition, angle: Math.PI / 2, range: 450 },  // RF
  { position: new BABYLON.Vector3(80, 80, 0), target: homePlatePosition, angle: Math.PI / 2, range: 450 },   // RCF
  { position: new BABYLON.Vector3(367, 80, 0), target: homePlatePosition, angle: Math.PI / 2, range: 450 },
  { position: new BABYLON.Vector3(427, 80, 60), target: homePlatePosition, angle: Math.PI / 2, range: 450 },
  
  { position: new BABYLON.Vector3(149.75, 80, 346.54), target: new BABYLON.Vector3(367, -20, 0), angle: Math.PI / 4.5, range: 900 },
  { position: new BABYLON.Vector3(298.25, 80, 346.54), target: new BABYLON.Vector3(80, -20, 0), angle: Math.PI / 4.5, range: 900 },

  { position: new BABYLON.Vector3(388.76, 80, 234.81), target: new BABYLON.Vector3(80, -20, 0), angle: Math.PI / 3, range: 900 },
  { position: new BABYLON.Vector3(59.24, 80, 234.81), target: new BABYLON.Vector3(367, -20, 0), angle: Math.PI / 3, range: 900 },
];
const bulbOnColour = new BABYLON.Color3(1, 1, 0.85);
const bulbOffColour = new BABYLON.Color3(0.15, 0.15, 0.15);

// Helper to draw a light pole and flood light array
const drawLightPoleWithArray = (position, direction, scene, index) => {
  // Draw pole
  const poleHeight = position.y;
  const poleDiameter = 2.5;
  const pole = BABYLON.MeshBuilder.CreateCylinder(`lightPole${index}`, {height: poleHeight, diameterBottom: poleDiameter, diameterTop: poleDiameter * 0.8, enclose: true}, scene);
  const poleMat = new BABYLON.StandardMaterial(`lightPoleMat${index}`, scene);
  poleMat.diffuseColor = new BABYLON.Color3(0.7, 0.7, 0.7);
  pole.material = poleMat;
  pole.position = new BABYLON.Vector3(position.x, position.y - poleHeight / 2, position.z);

  // Draw flood light array (6 lights in a grid)
  const arrayOrigin = new BABYLON.Vector3(position.x, position.y + 2, position.z);
  const arrayDir = direction.normalize();
  const up = new BABYLON.Vector3(0, 1, 0);
  const right = BABYLON.Vector3.Cross(arrayDir, up).normalize();
  const lightSpacing = 3.5;
  let lightMeshes = [];
  for (let row = 0; row < 2; row++) {
    for (let col = 0; col < 3; col++) {
      const offset = right.scale((col - 1) * lightSpacing).add(up.scale(row * lightSpacing));
      const bulbPos = arrayOrigin.add(offset);
      const bulb = BABYLON.MeshBuilder.CreateSphere(`floodBulb${index}_${row}_${col}`, {diameter: 2.2}, scene);
      bulb.position = bulbPos;
      bulb.material = bulbMat;
      bulbMat.diffuseColor = bulbOffColour;
      bulbMat.emissiveColor = bulbOffColour;
      lightMeshes.push(bulb);
    }
  }
  return { pole, lightMeshes };
};

const applyTimeOfDayLighting = () => {
  if (!scene || !hemisphericLight) {
    return;
  }

  const hour = getDisplayHour()
  const profile = getLightingProfile(hour)

  floodLights.forEach(light => light.dispose())
  floodLights = []

  scene.clearColor = profile.clearColor
  hemisphericLight.intensity = profile.ambientIntensity
  drawSkyTexture(profile.skyColor, profile.cloudOpacity)

  if (profile.floodLightStrength <= 0.01) {
    bulbMat.diffuseColor = bulbOffColour;
    bulbMat.emissiveColor = bulbOffColour;
    return;
  } else {
    bulbMat.diffuseColor = bulbOnColour;
    bulbMat.emissiveColor = bulbOnColour;
  }

  floodLights = floodlightConfigs.map(({ position, target, angle, range }, index) => {
    const direction = target.subtract(position).normalize();
    // Draw the light pole and flood light array
    const light = new BABYLON.SpotLight(`floodLight${index}`, position, direction, angle, 1, scene);
    light.diffuse = new BABYLON.Color3(1, 0.95, 0.8);
    light.specular = new BABYLON.Color3(0.15, 0.15, 0.15);
    light.intensity = lerp(0.55, 1.75, profile.floodLightStrength);
    light.range = range;
    return light;
  });
}

// Initialize the 3D scene
const initScene = () => {
  if (!canvasRef.value) return

  engine = new BABYLON.Engine(canvasRef.value, true)
  scene = new BABYLON.Scene(engine)

  // Camera
  // camera = new BABYLON.FreeCamera('camera', new BABYLON.Vector3(224, 600, 160), scene)
  // camera.setTarget(new BABYLON.Vector3(224, 0, 160))
  camera = new BABYLON.FreeCamera('camera', new BABYLON.Vector3(224, 35, 425), scene)
  camera.setTarget(new BABYLON.Vector3(224, 0, 240))
  // camera.setFocalLength(24);

  // Light
  hemisphericLight = new BABYLON.HemisphericLight('light', new BABYLON.Vector3(0, 1, 0), scene)

  // Sky
  skyTexture = new BABYLON.DynamicTexture('skyTexture', {width: 16, height: 16}, scene)
  const skyLayer = new BABYLON.Layer('skyLayer', null, scene)
  skyLayer.texture = skyTexture;

  // TODO Remove Debug Spot
  // Draw a red sphere at infield target.
  const spot = BABYLON.MeshBuilder.CreateSphere('spot', {diameter: 4}, scene);
  const spotMaterial = new BABYLON.StandardMaterial('spotMat', scene);
  spotMaterial.diffuseColor = BABYLON.Color3.Red();
  spot.material = spotMaterial;
  spot.position = new BABYLON.Vector3(145, 80, -28);

  // Light Poles
  bulbMat = new BABYLON.StandardMaterial('floodBulbMat', scene);
  const lightPoles = floodlightConfigs.map(({ position, target, angle }, index) => {
    const direction = target.subtract(position).normalize();
    // Draw the light pole and flood light array
    return drawLightPoleWithArray(position, direction, scene, index);
  });

  applyTimeOfDayLighting()
  lastLightingMinute = new Date().getMinutes()

  createField();
  createStatusDisplay();

  // Render loop
  engine.runRenderLoop(() => {
    if (!props.game?.ended) {
      const minute = new Date().getMinutes()
      if (minute !== lastLightingMinute) {
        applyTimeOfDayLighting()
        lastLightingMinute = minute
      }
    }
    animateStatusLights();
    scene.render()
  })
}

// Create the baseball field
const createField = () => {
  // Field (outfield)
  const field = BABYLON.MeshBuilder.CreateGround('field', {width: 1000, height: 1000}, scene)
  const fieldMaterial = new BABYLON.StandardMaterial('fieldMat', scene)
  // Create grass texture using /green-grass-texture.jpg 64x64 and repeat it across the field
  const grassTexture = new BABYLON.Texture('/green-grass-texture.jpg', scene)
  grassTexture.uScale = 1000 / 16
  grassTexture.vScale = 1000 / 16
  fieldMaterial.diffuseTexture = grassTexture
  field.material = fieldMaterial
  field.position = new BABYLON.Vector3(224, 0, 250)

  // Dirt material
  const dirtMaterial = new BABYLON.StandardMaterial('dirtMat', scene)
  dirtMaterial.diffuseColor = new BABYLON.Color3(0.85, 0.61, 0.46) // #d89b75

  // Pitcher's mound
  const moundDirt = BABYLON.MeshBuilder.CreateCylinder('moundDirt', {diameter: 18, height: 0.1}, scene)
  moundDirt.material = dirtMaterial
  moundDirt.position = new BABYLON.Vector3(224, 0.05, 277)
  basePositions.mound = moundDirt.position.clone().add(new BABYLON.Vector3(0, 2, 0))

  // Home plate dirt
  const plateDirt = BABYLON.MeshBuilder.CreateCylinder('plateDirt', {diameter: 32, height: 0.1}, scene)
  plateDirt.material = dirtMaterial
  plateDirt.position = new BABYLON.Vector3(224, 0.05, 343)

  const plateShape = [
      new BABYLON.Vector3(0, 0, 0),
      new BABYLON.Vector3(2.5, -2.5, 0),
      new BABYLON.Vector3(5, -2.5, 0),
      new BABYLON.Vector3(5, 2.5, 0),
      new BABYLON.Vector3(2.5, 2.5, 0),
  ];
  const platePath = [
      new BABYLON.Vector3(224, 0, 343),
      new BABYLON.Vector3(224, 0.1, 343),
  ];

  // Bases
  const baseMaterial = new BABYLON.StandardMaterial('baseMat', scene)
  baseMaterial.diffuseColor = BABYLON.Color3.White()

  const plate = BABYLON.MeshBuilder.ExtrudeShape('plate', {shape: plateShape, path: platePath, sideOrientation: BABYLON.Mesh.DOUBLESIDE, closeShape: true, cap: BABYLON.Mesh.CAP_END}, scene);
  plate.material = baseMaterial;

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
  const foulPoints1 = [homePlatePos, leftFieldPos];
  BABYLON.MeshBuilder.CreateLines('foul1', {points: foulPoints1}, scene);

  const foulPoints3 = [homePlatePos, rightFieldPos];
  BABYLON.MeshBuilder.CreateLines('foul3', {points: foulPoints3}, scene);

  const foulPoleMat = new BABYLON.StandardMaterial('foulMat', scene);
  foulPoleMat.diffuseColor = BABYLON.Color3.Yellow();
  const foulPoleL = BABYLON.MeshBuilder.CreateCylinder('foulPole1', {diameter: 1, height: 60}, scene);
  foulPoleL.material = foulPoleMat;
  foulPoleL.position = leftFieldPos.add(new BABYLON.Vector3(0, 30, 0));
  const foulPoleR = BABYLON.MeshBuilder.CreateCylinder('foulPole2', {diameter: 1, height: 60}, scene);
  foulPoleR.material = foulPoleMat;
  foulPoleR.position = rightFieldPos.add(new BABYLON.Vector3(0, 30, 0));

  // outfield fence
  const fenceShape = [
      new BABYLON.Vector3(0, 0, 0),
      new BABYLON.Vector3(0, 20, 0),
      new BABYLON.Vector3(0.5, 20, 0),
      new BABYLON.Vector3(0.5, 0, 0)
  ];

  const fencePath = [
      // new BABYLON.Vector3(224, 0, 343),
      new BABYLON.Vector3(224 - 233.35, 0, 343 - 233.35),
      // new BABYLON.Vector3(224 - 233.35 + 106.07, 0, 343 - 233.35 - 106.07),

      new BABYLON.Vector3(100, 0, -0.85),
      new BABYLON.Vector3(110, 0, -11.29),
      new BABYLON.Vector3(120, 0, -20.10),
      new BABYLON.Vector3(130, 0, -27.61),
      new BABYLON.Vector3(140, 0, -34.02),
      new BABYLON.Vector3(150, 0, -39.48),
      new BABYLON.Vector3(160, 0, -44.08),
      new BABYLON.Vector3(170, 0, -47.91),
      new BABYLON.Vector3(180, 0, -51.03),
      new BABYLON.Vector3(190, 0, -53.46),
      new BABYLON.Vector3(200, 0, -55.25),
      new BABYLON.Vector3(210, 0, -56.40),
      new BABYLON.Vector3(220, 0, -56.95),

      new BABYLON.Vector3(230, 0, -56.89),
      new BABYLON.Vector3(240, 0, -56.22),
      new BABYLON.Vector3(250, 0, -54.94),
      new BABYLON.Vector3(260, 0, -53.02),
      new BABYLON.Vector3(270, 0, -50.46),
      new BABYLON.Vector3(280, 0, -47.21),
      new BABYLON.Vector3(290, 0, -43.22),
      new BABYLON.Vector3(300, 0, -38.45),
      new BABYLON.Vector3(310, 0, -32.82),
      new BABYLON.Vector3(320, 0, -26.20),
      new BABYLON.Vector3(330, 0, -18.45),
      new BABYLON.Vector3(340, 0, -9.34),

      // new BABYLON.Vector3(224 + 233.35 - 106.07, 0, 343 - 233.35 - 106.07),
      new BABYLON.Vector3(224 + 233.35, 0, 343 - 233.35),
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
  const statusPlane = BABYLON.MeshBuilder.CreatePlane("statusPlane", {width: 220, height: 90}, scene)
  statusPlane.position = new BABYLON.Vector3(224, 60, -85)
  const statusTexture = new BABYLON.DynamicTexture("statusTexture", {width: 440, height: 180}, scene)
  statusPlane.material = new BABYLON.StandardMaterial("statusMat", scene)
  statusPlane.material.diffuseTexture = statusTexture
  statusPlane.material.diffuseTexture.hasAlpha = true
  statusPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL
}

// Animate runner through actions
const animateRunner = (playerId, startBase, actions, color, waitForPitch) => {
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

  if (waitForPitch) {
    // Wait before starting animation
    setTimeout(() => {
      animateNext();
    }, 500);
  } else {
    animateNext();
  }
  return keyFrames.length + (waitForPitch ? 0.5 : 0); // Return number of frames for animation
}

const animateBall = (battedBall) => {
  if (!scene) return;

  const ball = BABYLON.MeshBuilder.CreateSphere('battedBall', {diameter: 4}, scene);
  const ballMaterial = new BABYLON.StandardMaterial('ballMat', scene);
  ballMaterial.diffuseColor = BABYLON.Color3.White();
  ball.material = ballMaterial;
  ball.position = basePositions.mound.clone().add(new BABYLON.Vector3(0, 4, 0));

  const baseDistance = battedBall ? Math.sqrt(
    Math.pow(battedBall.position[0] - 224, 2) +
    Math.pow(battedBall.position[1] - 405, 2)
  ) : 0;

  const d = battedBall?.distance || 0;
  const targetPosition = basePositions.home.add(new BABYLON.Vector3(
    battedBall ? -(battedBall.position[0] - 224) / baseDistance * d : 0,
    battedBall ? 0 : 4,
    battedBall ? (battedBall.position[1] - 405) / baseDistance * d : 10
  ));

  const trajectory = {
    'F': { height: d * 0.3, duration: d / 73 * 30, parabolic: true, bounces: 0 },
    'L': { height: d * 0.15, duration: d / 102 * 30, parabolic: true, bounces: 0 },
    'G': { height: 5, duration: d / 102 * 30, parabolic: true, bounces: 2 },
    'B': { height: 6, duration: d / 90 * 30, parabolic: true, bounces: 3 },
    'P': { height: 80, duration: d / 50 * 30, parabolic: true, bounces: 0 },
  }[battedBall?.type] ?? { height: 0, duration: 2, parabolic: true, bounces: 0 };
  console.log('Animating ball to', targetPosition, 'with trajectory', trajectory);

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
    for (let i = 0; i < 15; i++) {
      const t = i / 15;
      const x = ball.position.x + (basePositions.home.x - ball.position.x) * t;
      const z = ball.position.z + (basePositions.home.z - ball.position.z) * t;
      const y = ball.position.y; // Ball to home travels flat.
      keys.push({ frame: i, value: new BABYLON.Vector3(x, y, z) });
    }
    for (let i = 0; i <= frames; i++) {
      const t = i / frames;
      const x = basePositions.home.x + (targetPosition.x - basePositions.home.x) * t;
      const z = basePositions.home.z + (targetPosition.z - basePositions.home.z) * t;
      let y;
      if (trajectory.bounces > 0) {
        // Bouncing ground ball
        const bounceHeight = trajectory.height * (1 - t);
        y = basePositions.home.y + (targetPosition.y - basePositions.home.y) * t + bounceHeight * Math.abs(Math.sin(t * Math.PI * (trajectory.bounces + 1)));
      } else {
        // Parabolic arc
        y = basePositions.home.y + (targetPosition.y - basePositions.home.y) * t + trajectory.height * Math.sin(t * Math.PI);
      }
      keys.push({ frame: i + 15, value: new BABYLON.Vector3(x, y, z) });
    }

    animation.setKeys(keys);
    ball.animations = [animation];
    scene.beginAnimation(ball, 0, frames + 15, false, 1, () => {
      setTimeout(() => ball.dispose(), battedBall ? 500 : 0);
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

  return trajectory.duration / 30 + 0.5; // Return number of seconds for animation
}

const FOUL_BALL = {
  position: [225.97, 442.13],
  distance: 100,
  type: 'G',
};

// Update status display
const updateStatus = (status, play) => {
  if (!scene) return;

  const { state, fielders, runners, hitting } = status;
  const { actions, ball_in_play, command, play: playDetails } = play || { play: ''};
  const runsSum = state.score.reduce((a, b) => a + b, 0);

  // Check if counts changed for animation
  const countsChanged = !previousCounts ||
    state.balls !== previousCounts.balls ||
    state.strikes !== previousCounts.strikes ||
    state.outs !== previousCounts.outs ||
    state.inning !== previousCounts.inning ||
    state.half !== previousCounts.half ||
    runsSum !== (previousCounts.runsSum || 0);

  isAnimating = true;
  if (countsChanged) {
    animationProgress = 0
    target = {
      balls: state.balls,
      strikes: state.strikes,
      outs: state.outs,
      inning: state.inning,
      half: state.half,
      runsSum,
    }
  }

  if (state.ended) {
    // return; // No further updates if game ended
  }

  // Update runners using actions
  const runnerColor = state.half ? props.homeColor : props.awayColor;
  let delayFrames = 0;

  const hasPitch = !command && playDetails.split(',')[0] != '';
  const foul_ball = !command && playDetails.split(',')[0].endsWith('f') ? FOUL_BALL : null;

  const nextRunners = {};
  // Animate runners based on actions
  for (const playerId in actions) {
    const playerActions = actions[playerId];
    const startBase = previousRunners[playerId] ?? null;
    if (startBase === null) continue; // No previous position, skip
    delayFrames = Math.max(delayFrames, animateRunner(playerId, startBase, playerActions, runnerColor, hasPitch));
    const lastPosition = playerActions[playerActions.length - 1];
    // If the runner is still on base, include in nextRunners.
    if (lastPosition !== -1 && lastPosition !== 3) {
      nextRunners[playerId] = lastPosition;
    }
    delete previousRunners[playerId];
  }

  // If we have a set of actions, copy across any runners not in actions.
  if (actions?.length && state.inning === previousCounts?.inning && state.half === previousCounts?.half) {
    for (const playerId in previousRunners) {
      if (!(playerId in nextRunners) && !(playerId in actions)) {
        nextRunners[playerId] = previousRunners[playerId];
      }
    }
  }

  // If we have a batted ball, animate the batted ball.
  if (hasPitch) {
    delayFrames = Math.max(delayFrames, animateBall(ball_in_play ?? foul_ball));
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
    delete previousRunners[playerId];
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
  const awayScores = [...props.state.linescore[0]]
  const homeScores = [...props.state.linescore[1]]

  // Total runs (R column)
  const awayTotal = awayScores.reduce((sum, score) => sum + score, 0)
  const homeTotal = homeScores.reduce((sum, score) => sum + score, 0)
  if (props.state.ended) {
    // Potentially there's an extra score entry.
    if (props.state.half) {
      // If game ended at bottom of inning, remove last away score (replace with an X)
      if (awayScores.length > props.state.inning) {
        awayScores.pop();
      } else {
        // Otherwise the game ended while the home team was batting, so append an X
        homeScores.push(homeScores.pop() + 'X');
      }
    } else {
      // Game ended at top of inning, remove last home score (replace with an X)
      if (homeScores.length === props.state.inning) {
        homeScores.pop();
        homeScores.push('X');
      }
    }
  }

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

  const totalX = 70 + 9 * colWidth - 10
  ctx.fillText('R', totalX, linescoreY - lineHeight)
  ctx.fillText(awayTotal ?? 0, totalX, linescoreY)
  ctx.fillText(homeTotal ?? 0, totalX, linescoreY + lineHeight)
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
  // for (let i = 0; i < 3; i++) {
  //   ctx.fillStyle = 'black'
  //   ctx.beginPath()
  //   ctx.arc(lines[1].x + 26 + i * 28, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
  //   ctx.fill()
  //   ctx.fillStyle = 'green'
  //   ctx.globalAlpha = calcAlpha('balls', i)
  //   ctx.beginPath()
  //   ctx.arc(lines[1].x + 26 + i * 28, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI)
  //   ctx.fill()
  //   ctx.globalAlpha = 1
  //   ctx.strokeStyle = 'black'
  //   ctx.stroke()
  // }

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

  for (let i = 0; i < 3; i++) {
    drawLight(lines[1].x + 26 + i * 28, LIGHT_Y, '#00a200', calcAlpha('balls', i))
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

watch(
  () => [
    props.game?.ended,
    props.game?.firstPitch,
    props.game?.timeZone,
    props.game?.metadata?.LP,
    props.game?.metadata?.FP,
  ],
  () => {
    if (!scene || !hemisphericLight) {
      return
    }
    applyTimeOfDayLighting()
    lastLightingMinute = new Date().getMinutes()
  }
)

// Expose updateStatus method
defineExpose({
  updateStatus,
  toast,
})

const gameDelayed = computed(() => {
  // Get first unended delay from game meta.
  const delay = Object.entries(props.game?.metadata ?? {}).find(
    ([key], _, arr) => /^DELAY_\d+_BEGIN$/.test(key) && !(key.replace('_BEGIN', '_END') in props.game.metadata));
  if (delay) {
    return props.game.metadata[delay[0].replace('_BEGIN', '_REASON')] ?? 'Game delayed';
  }
  return null;
});

</script>

<template>
  <div style="width:100%; position: relative;">
    <canvas ref="canvasRef" style="width: 100%; height: 100%;"></canvas>
    <Transition name="toast">
      <div v-if="toastVisible" class="toast-notification">
        {{ toastMessage }}
      </div>
    </Transition>
    <div v-if="gameDelayed && !toastVisible" class="delay-notification">
      {{ gameDelayed }}
    </div>
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

.delay-notification {
  position: absolute;
  text-align: center;
  top: 10px;
  right: 50px;
  left: 50px;
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
