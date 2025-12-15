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
<div id="app" data-game-id="{{ $game->id }}" data-ended="{{ $game->ended ? 'true' : 'false' }}" data-inning="{{ $game->inning }}">
</div>
@vite(['resources/js/app.js'])
<script>
$(document).ready(() => {
    // Click the current inning link to filter plays
    const currentInning = "{{ $game->inning }}";
    $(`.inning-link[data-inning="${currentInning}"]`).click();
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
// const canvas = document.getElementById('field-canvas');
// const engine = new BABYLON.Engine(canvas, true);
// const scene = new BABYLON.Scene(engine);

// // Animation variables for status lights
// let isAnimating = false;
// let animationProgress = 0;
// let previousCounts = null;
// let previousRunners = {};
// let target = {balls: 0, strikes: 0, outs: 0};

// // camera

// // camera
// const camera = new BABYLON.FreeCamera('camera', new BABYLON.Vector3(224, 35, 390), scene);
// camera.setTarget(new BABYLON.Vector3(224, 0, 240));
// camera.setFocalLength(24);
// camera.fov

// // light
// const light = new BABYLON.HemisphericLight('light', new BABYLON.Vector3(0, 1, 0), scene);

// // field (outfield)
// const field = BABYLON.MeshBuilder.CreateGround('field', {width: 1000, height: 1000}, scene);
// const fieldMaterial = new BABYLON.StandardMaterial('fieldMat', scene);
// fieldMaterial.diffuseColor = new BABYLON.Color3(0.46, 0.73, 0.60); // #75d89b
// field.material = fieldMaterial;
// field.position = new BABYLON.Vector3(224, 0, 250);

// // infield dirt
// const dirtMaterial = new BABYLON.StandardMaterial('dirtMat', scene);
// dirtMaterial.diffuseColor = new BABYLON.Color3(0.85, 0.61, 0.46); // #d89b75

// // pitcher's mound
// const moundDirt = BABYLON.MeshBuilder.CreateCylinder('moundDirt', {diameter: 18, height: 0.1}, scene);
// moundDirt.material = dirtMaterial;
// moundDirt.position = new BABYLON.Vector3(224, 0.05, 277);

// // home plate dirt
// const plateDirt = BABYLON.MeshBuilder.CreateCylinder('plateDirt', {diameter: 32, height: 0.1}, scene);
// plateDirt.material = dirtMaterial;
// plateDirt.position = new BABYLON.Vector3(224, 0.05, 343);

// // bases
// const baseMaterial = new BABYLON.StandardMaterial('baseMat', scene);
// baseMaterial.diffuseColor = BABYLON.Color3.White();

// const base1 = BABYLON.MeshBuilder.CreateBox('base1', {width: 4, height: 0.1, depth: 4}, scene);
// base1.material = baseMaterial;
// base1.position = new BABYLON.Vector3(224 - 63.63961030678928 + 2.4, 0.05, 280);
// base1.addRotation(0, Math.PI / 4, 0);

// const base2 = BABYLON.MeshBuilder.CreateBox('base2', {width: 4, height: 0.1, depth: 4}, scene);
// base2.material = baseMaterial;
// base2.position = new BABYLON.Vector3(224, 0.05, 280 - 63.63961030678928);
// base2.addRotation(0, Math.PI / 4, 0);

// const base3 = BABYLON.MeshBuilder.CreateBox('base3', {width: 4, height: 0.1, depth: 4}, scene);
// base3.material = baseMaterial;
// base3.position = new BABYLON.Vector3(224 + 63.63961030678928 - 2.4, 0.05, 280);
// base3.addRotation(0, Math.PI / 4, 0);

// // dirt circles at bases
// const baseDirt1 = BABYLON.MeshBuilder.CreateCylinder('baseDirt1', {diameter: 20, height: 0.01}, scene);
// baseDirt1.material = dirtMaterial;
// baseDirt1.position = base1.position.clone();
// baseDirt1.position.y = 0.005;

// const baseDirt2 = BABYLON.MeshBuilder.CreateCylinder('baseDirt2', {diameter: 20, height: 0.01}, scene);
// baseDirt2.material = dirtMaterial;
// baseDirt2.position = base2.position.clone();
// baseDirt2.position.y = 0.005;

// const baseDirt3 = BABYLON.MeshBuilder.CreateCylinder('baseDirt3', {diameter: 20, height: 0.01}, scene);
// baseDirt3.material = dirtMaterial;
// baseDirt3.position = base3.position.clone();
// baseDirt3.position.y = 0.005;

// // runner labels
// const basePositions = {
//     0: base1.position.clone().add(new BABYLON.Vector3(15, 2, 20)),
//     1: base2.position.clone().add(new BABYLON.Vector3(0, 2, 0)),
//     2: base3.position.clone().add(new BABYLON.Vector3(-15, 2, 20)),
//     home: new BABYLON.Vector3(224, 2, 343)
// };

// // for (let i = 0; i < 5; i++) { // 4 runners + hitter
// const runnerTexture = new BABYLON.DynamicTexture('runnerTemplate', {width: 256, height: 64}, scene);
// const runnerPlane = BABYLON.MeshBuilder.CreatePlane('runnerPlaneTemplate', {width: 40, height: 16}, scene);
// runnerPlane.material = new BABYLON.StandardMaterial('runnerMatTemplate', scene);
// runnerPlane.material.diffuseTexture = runnerTexture;
// runnerPlane.material.diffuseTexture.hasAlpha = true;
// runnerPlane.position = new BABYLON.Vector3(0, -10, 0); // off screen initially
// runnerPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
//     // runnerPlanes.push(plane);
// // }

// // foul lines
// const foulPoints1 = [
//     new BABYLON.Vector3(224, 0.1, 343),
//     new BABYLON.Vector3(224 - 226.27, 0.1, 343 - 226.27)
// ];
// const foulLine1 = BABYLON.MeshBuilder.CreateLines('foul1', {points: foulPoints1}, scene);

// const foulPoints3 = [
//     new BABYLON.Vector3(224, 0.1, 343),
//     new BABYLON.Vector3(224 + 226.27, 0.1, 343 - 226.27)
// ];
// const foulLine3 = BABYLON.MeshBuilder.CreateLines('foul3', {points: foulPoints3}, scene);

// // outfield fence
// const fenceShape = [
//     new BABYLON.Vector3(0, 0, 0),
//     new BABYLON.Vector3(0, 20, 0),
//     new BABYLON.Vector3(0.5, 20, 0),
//     new BABYLON.Vector3(0.5, 0, 0)
// ];
// const fencePath = [
//     new BABYLON.Vector3(224 - 226.27, 0, 343 - 226.27),
//     new BABYLON.Vector3(-25, 0, 65),
//     new BABYLON.Vector3(50, 0, 35),
//     new BABYLON.Vector3(100, 0, 12),
//     new BABYLON.Vector3(150, 0, -8),
//     new BABYLON.Vector3(224, 0, -38),
//     new BABYLON.Vector3(300, 0, -8),
//     new BABYLON.Vector3(350, 0, 12),
//     new BABYLON.Vector3(400, 0, 35),
//     new BABYLON.Vector3(475, 0, 65),
//     new BABYLON.Vector3(224 + 226.27, 0, 343 - 226.27),
// ];
// const fence = BABYLON.MeshBuilder.ExtrudeShape('fence', {shape: fenceShape, path: fencePath, sideOrientation: BABYLON.Mesh.DOUBLESIDE}, scene);
// const fenceMat = new BABYLON.StandardMaterial('fenceMat', scene);
// fenceMat.diffuseColor = BABYLON.Color3.Gray();
// fence.material = fenceMat;

// // fielders
// const fielderPositions = {
//     1: {x: 224, z: 280},
//     2: {x: 224, z: 353},
//     3: {x: 154, z: 270},
//     4: {x: 164, z: 210},
//     5: {x: 294, z: 270},
//     6: {x: 284, z: 210},
//     7: {x: 344, z: 130},
//     8: {x: 224, z: 80},
//     9: {x: 104, z: 130},
// };

// for (let pos = 1; pos <= 9; pos++) {
//     const texture = new BABYLON.DynamicTexture('texture' + pos, {width: 256, height: 64}, scene);
//     const plane = BABYLON.MeshBuilder.CreatePlane('fielder' + pos, {width: 50, height: 20}, scene);
//     if (pos === 2) {
//         // Make catcher texture smaller
//         plane.scaling = new BABYLON.Vector3(0.4, 0.4, 0.4);
//     }
//     plane.material = new BABYLON.StandardMaterial('mat' + pos, scene);
//     plane.material.diffuseTexture = texture;
//     plane.material.diffuseTexture.hasAlpha = true;
//     plane.position = new BABYLON.Vector3(fielderPositions[pos].x, 1, fielderPositions[pos].z);
//     plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
// }

// // status display
// const statusPlane = BABYLON.MeshBuilder.CreatePlane("statusPlane", {width: 110, height: 22.5}, scene);
// statusPlane.position = new BABYLON.Vector3(224, 65, 280);
// const statusTexture = new BABYLON.DynamicTexture("statusTexture", {width: 220, height: 45}, scene);
// statusPlane.material = new BABYLON.StandardMaterial("statusMat", scene);
// statusPlane.material.diffuseTexture = statusTexture;
// statusPlane.material.diffuseTexture.hasAlpha = true;
// statusPlane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;

// window.updateStatus = function(status) {
//     const {
//         state,
//         fielders,
//         runners,
//         hitting,
//     } = status;

//     // Check if counts changed for animation
//     const countsChanged = !previousCounts ||
//         state.balls !== previousCounts.balls ||
//         state.strikes !== previousCounts.strikes ||
//         state.outs !== previousCounts.outs;
//     console.log('countsChanged:', countsChanged, previousCounts);

//     if (countsChanged) {
//         isAnimating = true;
//         animationProgress = 0;
//         target = {
//             balls: state.balls,
//             strikes: state.strikes,
//             outs: state.outs,
//             inning: state.inning,
//             half: state.half,
//         };
//     }

//     const awayColor = "{{ $game->away_team->primary_color ?? '#1e88eA' }}";
//     const homeColor = "{{ $game->home_team->primary_color ?? '#43a047' }}";

//     // Update fielders
//     const fielderColor = state.half ? awayColor : homeColor;
//     for (let pos = 1; pos <= 9; pos++) {
//         const texture = scene.getTextureByName('texture' + pos);
//         if (texture && fielders[pos]) {
//             const person = fielders[pos].person;
//             const text = person.lastName + ', ' + person.firstName[0];
//             texture.clear();
//             texture.drawText(
//                 text,
//                 null,
//                 30,
//                 "bold 36px monospace",
//                 fielderColor,
//                 "transparent",
//                 true
//             );
//             texture.update();
//         }
//     }

//     // Update runners
//     const runnerColor = state.half ? homeColor : awayColor;
//     let planeIndex = 0;
//     console.log('Previous runners:', previousRunners);
//     const nextRunners = {};

//     // Handle runners on bases
//     for (const base in runners) {
//         const runner = runners[base];
//         console.log('Updating runner on base', base, runner);
//         let prevBase = null;
//         // Check if runner is already positioned.
//         if (runner.person_id in previousRunners) {
//             prevBase = previousRunners[runner.person_id];
//             delete previousRunners[runner.person_id];
//             if (prevBase === base) {
//                 nextRunners[runner.person_id] = base;
//                 continue;
//             }

//             // Need to move him to the next base.
//             nextRunners[runner.person_id] = base;
//             // Animate movement by move from the appropriate base to the target base, via any other bases along the way.
//             const mesh = scene.getMeshByName(`runner${runner.person_id}`);
//             if (mesh) {
//                 const startPosition = basePositions[prevBase];
//                 const positions = [];
//                 let currentBase = prevBase;
//                 while (currentBase != base) {
//                 }
//                 const targetPosition = basePositions[base];
//                 BABYLON.Animation.CreateAndStartAnimation(
//                     'moveRunner' + runner.person_id,
//                     mesh,
//                     'position',
//                     30,
//                     30,
//                     startPosition,
//                     targetPosition,
//                     BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
//                 );
//                 continue;
//             }
//         }

//         // Add any runners who don't have a mesh yet.
//         const plane = scene.getMeshByName('runnerPlaneTemplate').clone('runner' + runner.person_id);
//         const texture = scene.getTextureByName('runnerTemplate').clone('runner' + runner.person_id);
//         plane.material = new BABYLON.StandardMaterial('runnerMat' + runner.person_id, scene);
//         plane.material.diffuseTexture = texture;
//         plane.material.diffuseTexture.hasAlpha = true;
//         plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
//         const targetPosition = basePositions[base];
//         plane.position = targetPosition;
//         const text = runner.person.lastName + ', ' + runner.person.firstName[0];
//         texture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent');
//         nextRunners[runner.person_id] = base;
//     }

//     // Check the hitter.
//     if (hitting) {
//         console.log('Updating hitter', hitting);
//         if (hitting.person_id in previousRunners) {
//             const prevBase = previousRunners[hitting.person_id];
//             delete previousRunners[hitting.person_id];
//             if (prevBase === 'home') {
//                 // No change
//             } else {
//                 // Move to home
//                 const mesh = scene.getMeshByName(`runner${hitting.person_id}`);
//                 if (mesh) {
//                     const startPosition = basePositions[prevBase];
//                     const targetPosition = basePositions['home'];
//                     BABYLON.Animation.CreateAndStartAnimation(
//                         'moveRunner' + hitting.person_id,
//                         mesh,
//                         'position',
//                         30,
//                         30,
//                         startPosition,
//                         targetPosition,
//                         BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
//                     );
//                 }
//             }
//             nextRunners[hitting.person_id] = 'home';
//         } else {
//             // New hitter runner
//             const plane = scene.getMeshByName('runnerPlaneTemplate').clone('runner' + hitting.person_id);
//             const texture = scene.getTextureByName('runnerTemplate').clone('runner' + hitting.person_id);
//             plane.material = new BABYLON.StandardMaterial('runnerMat' + hitting.person_id, scene);
//             plane.material.diffuseTexture = texture;
//             plane.material.diffuseTexture.hasAlpha = true;
//             plane.material.useAlphaFromDiffuseTexture = true;
//             plane.material.transparencyMode = BABYLON.Material.MATERIAL_ALPHABLEND;
//             plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
//             const targetPosition = basePositions['home'];
//             plane.position = targetPosition;
//             const text = hitting.person.lastName + ', ' + hitting.person.firstName[0];
//             const ctx = texture.getContext();
//             ctx.clearRect(0, 0, texture.getSize().width, texture.getSize().height); // full transparency
//             texture.update();
//             texture.drawText(text, null, 30, 'bold 36px monospace', runnerColor, 'transparent');
//             nextRunners[hitting.person_id] = 'home';
//             // Animate a fade-in
//             if (Object.values(previousRunners).includes('home')) {
//                 plane.visibility = 0;
//                 BABYLON.Animation.CreateAndStartAnimation(
//                     'fadeInRunner' + hitting.person_id,
//                     plane,
//                     'visibility',
//                     30,
//                     15,
//                     0,
//                     1,
//                     BABYLON.Animation.ANIMATIONLOOPMODE_CONSTANT
//                 );
//             }
//         }
//     }

//     // Remove previousRunners no longer on base
//     for (const runnerId in previousRunners) {
//         const mesh = scene.getMeshByName(`runner${runnerId}`);
//         if (mesh) {
//             mesh.dispose();
//         }
//     }
//     previousRunners = nextRunners;
// };

// // render loop
// engine.runRenderLoop(() => {
//     // Animate status lights
//     if (isAnimating) {
//         animationProgress += 0.05;
//         if (animationProgress >= 1) {
//             animationProgress = 1;
//             isAnimating = false;
//             previousCounts = { ...target };
//         }

//         statusTexture.clear();
//         const ctx = statusTexture.getContext();

//         // Redraw borders
//         ctx.strokeStyle = "white";
//         ctx.fillStyle = "#1e88ea";
//         ctx.fillRect(0, 0, statusTexture.getSize().width, statusTexture.getSize().height);
//         ctx.lineWidth = 4;
//         ctx.strokeRect(0, 0, statusTexture.getSize().width, statusTexture.getSize().height);
//         ctx.lineWidth = 1;

//         ctx.font = "bold 16px monospace";
//         ctx.fillStyle = "white";
//         const lines = [
//             {text: 'INN', x: 4},
//             {text: 'BALLS', x: 4+ctx.measureText('INN ').width},
//             {text: 'STRIKES', x: 4+ctx.measureText('INN BALLS ').width},
//             {text: 'OUTS', x: 4+ctx.measureText('INN BALLS STRIKES ').width},
//         ];
//         for (const line of lines) {
//             ctx.fillText(line.text, line.x, 20);
//         }

//         // Innings (static)
//         ctx.fillText(`${target.half ? '⬇' : '⬆'}${target.inning}`, lines[0].x, 38);

//         // Animated lights
//         const calcAlpha = (type, i) => {
//             const targetState = i < target[type];
//             if (!previousCounts) return targetState ? 1 : 0;
//             const curState = i < previousCounts[type];
//             if (curState === targetState) return targetState ? 1 : 0;
//             const progress = curState ? (1 - animationProgress) : animationProgress;
//             return progress;
//         };

//         const LIGHT_Y = 32;
//         const LIGHT_RADIUS = 6;
//         const drawLight = (x, y, color, alpha) => {
//             ctx.fillStyle = 'black';
//             ctx.beginPath();
//             ctx.arc(x, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI);
//             ctx.fill();
//             ctx.fillStyle = color;
//             ctx.globalAlpha = alpha;
//             ctx.beginPath();
//             ctx.arc(x, LIGHT_Y, LIGHT_RADIUS, 0, 2 * Math.PI);
//             ctx.fill();
//             ctx.globalAlpha = 1;
//             ctx.strokeStyle = 'black';
//             ctx.stroke();
//         };

//         // Balls
//         for (let i = 0; i < 3; i++) {
//             ctx.fillStyle = 'black';
//             ctx.beginPath();
//             ctx.arc(lines[1].x + 13 + i * 14, 32, 6, 0, 2 * Math.PI);
//             ctx.fill();
//             ctx.fillStyle = 'green';
//             ctx.globalAlpha = calcAlpha('balls', i);
//             ctx.beginPath();
//             ctx.arc(lines[1].x + 13 + i * 14, 32, 6, 0, 2 * Math.PI);
//             ctx.fill();
//             ctx.globalAlpha = 1;
//             ctx.strokeStyle = 'black';
//             ctx.stroke();
//         }
//         // Strikes
//         for (let i = 0; i < 2; i++) {
//             drawLight(lines[2].x + 25 + i * 14, LIGHT_Y, 'red', calcAlpha('strikes', i));
//         }
//         // Outs
//         for (let i = 0; i < 2; i++) {
//             drawLight(lines[3].x + 11 + i * 14, LIGHT_Y, 'red', calcAlpha('outs', i));
//         }
//         statusTexture.update();
//     }

//     scene.render();
// });
</script>
</body>
</html>