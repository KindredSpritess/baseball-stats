/**
 * Fielder movement rules system for the 3D baseball field.
 *
 * Rules define the conditions under which a fielder should move and the action to take.
 * Multiple rules may fire simultaneously (e.g. one fielder chases the ball while another
 * covers a base).
 *
 * --- Condition fields (all optional — omit a field to match any value) ---
 *   outs:       number[]   Valid out counts, e.g. [0, 1].
 *   runners:    number[]   Valid runner bitmask values:
 *                            bit 0 (1) = runner on 1B
 *                            bit 1 (2) = runner on 2B
 *                            bit 2 (4) = runner on 3B
 *                          e.g. [0] = bases empty, [7] = bases loaded.
 *   trajectory: string[]   Ball trajectory types: 'F' fly, 'L' line drive,
 *                            'G' ground ball, 'B' bunt, 'P' pop-up.
 *   sector:     string[]   Horizontal zone of the batted ball:
 *                            'left'    — LF / 3B side (SVG x < 180)
 *                            'center'  — CF area     (SVG 180 ≤ x ≤ 268)
 *                            'right'   — RF / 1B side (SVG x > 268)
 *                            'infield' — shallow hit (distance < 90 ft)
 *
 * --- Action fields ---
 *   type:   'moveToBall' | 'moveToBase' | 'moveToCutoff'
 *   base:   number | 'home'   For moveToBase — which base to cover: 1, 2, 3, or 'home'.
 *   toBase: number | 'home'   For moveToCutoff — target base for the relay throw.
 */

/**
 * Determine the lateral/depth sector of a batted ball from its SVG-coordinate position.
 *
 * In the SVG coordinate system (also used for ball positions):
 *   x < 224 — left field / 3B side (batter's left, standard LF)
 *   x > 224 — right field / 1B side (batter's right, standard RF)
 *
 * @param {number[] | null} ballPosition  [x, y] in SVG coords, or null
 * @param {number} [distance]             Optional distance from home plate
 * @returns {string | null}               Sector string, or null when no ball
 */
export function getBallSector(ballPosition, distance) {
  if (!ballPosition) return null;

  const x = ballPosition[0];

  // Shallow / infield hit overrides lateral classification
  if (distance != null && distance < 90) return 'infield';

  if (x < 180) return 'left';
  if (x > 268) return 'right';
  return 'center';
}

/**
 * Convert a runners object { base: runnerObj } to a bitmask.
 *   bit 0 (1) = runner on 1B, bit 1 (2) = runner on 2B, bit 2 (4) = runner on 3B
 *
 * @param {Object | null} runners
 * @returns {number}  0–7
 */
export function getRunnerBitmask(runners) {
  let bitmask = 0;
  if (runners) {
    if (runners[1]) bitmask |= 1;
    if (runners[2]) bitmask |= 2;
    if (runners[3]) bitmask |= 4;
  }
  return bitmask;
}

/**
 * Test a single rule condition against the current game state.
 *
 * @param {Object} condition
 * @param {number} outs
 * @param {number} runnerBitmask
 * @param {string | null} sector
 * @param {string | null} trajectory
 * @returns {boolean}
 */
function matchesCondition(condition, outs, runnerBitmask, sector, trajectory) {
  if (condition.outs && !condition.outs.includes(outs)) return false;
  if (condition.runners && !condition.runners.includes(runnerBitmask)) return false;
  if (condition.trajectory && !condition.trajectory.includes(trajectory)) return false;
  if (condition.sector && !condition.sector.includes(sector)) return false;
  return true;
}

/**
 * Default fielder movement rules.
 *
 * Add or modify entries here to change how fielders respond to batted balls.
 * Rules are evaluated in order; all matching rules fire (not first-match-wins).
 */
export const FIELDER_RULES = [
  // ── FLY BALLS & LINE DRIVES ────────────────────────────────────────────────

  // Left field — LF chases ball, SS takes cutoff, 3B covers third
  {
    conditions: { trajectory: ['F', 'L'], sector: ['left'] },
    fielder: 7,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['left'] },
    fielder: 6,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['left'] },
    fielder: 5,
    action: { type: 'moveToBase', base: 3 },
  },

  // Center field — CF chases ball, SS takes cutoff, 2B covers second
  {
    conditions: { trajectory: ['F', 'L'], sector: ['center'] },
    fielder: 8,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['center'] },
    fielder: 6,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['center'] },
    fielder: 4,
    action: { type: 'moveToBase', base: 2 },
  },

  // Right field — RF chases ball, 2B takes cutoff, 1B covers first
  {
    conditions: { trajectory: ['F', 'L'], sector: ['right'] },
    fielder: 9,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['right'] },
    fielder: 4,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], sector: ['right'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },

  // ── GROUND BALLS ──────────────────────────────────────────────────────────

  // Ground ball to left (3B/SS side) — SS fields, 1B covers first, 2B covers second
  {
    conditions: { trajectory: ['G'], sector: ['left'] },
    fielder: 6,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['G'], sector: ['left'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], sector: ['left'] },
    fielder: 4,
    action: { type: 'moveToBase', base: 2 },
  },

  // Ground ball up the middle (center) — 2B fields, 1B covers first, SS covers second
  {
    conditions: { trajectory: ['G'], sector: ['center'] },
    fielder: 4,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['G'], sector: ['center'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], sector: ['center'] },
    fielder: 6,
    action: { type: 'moveToBase', base: 2 },
  },

  // Ground ball to right (1B/2B side) — 1B fields, P covers first, SS covers second
  {
    conditions: { trajectory: ['G'], sector: ['right'] },
    fielder: 3,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['G'], sector: ['right'] },
    fielder: 1,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], sector: ['right'] },
    fielder: 6,
    action: { type: 'moveToBase', base: 2 },
  },

  // ── BUNTS ─────────────────────────────────────────────────────────────────

  // Bunt — P and C converge on ball, 1B covers first base
  {
    conditions: { trajectory: ['B'] },
    fielder: 1,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['B'] },
    fielder: 2,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['B'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },

  // ── POP-UPS ───────────────────────────────────────────────────────────────

  // Infield pop-up (any sector) — C calls for it near home
  {
    conditions: { trajectory: ['P'], sector: ['infield'] },
    fielder: 2,
    action: { type: 'moveToBall' },
  },

  // Pop-up to the left (3B/SS side) — SS fields, 3B backs up
  {
    conditions: { trajectory: ['P'], sector: ['left'] },
    fielder: 6,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['P'], sector: ['left'] },
    fielder: 5,
    action: { type: 'moveToBall' },
  },

  // Pop-up to center — 2B or SS fields
  {
    conditions: { trajectory: ['P'], sector: ['center'] },
    fielder: 4,
    action: { type: 'moveToBall' },
  },

  // Pop-up to the right (1B/2B side) — 2B fields, 1B backs up
  {
    conditions: { trajectory: ['P'], sector: ['right'] },
    fielder: 4,
    action: { type: 'moveToBall' },
  },
  {
    conditions: { trajectory: ['P'], sector: ['right'] },
    fielder: 3,
    action: { type: 'moveToBall' },
  },
];

/**
 * Return the list of fielder movements that apply to the current play.
 *
 * @param {number}       outs        Current number of outs (0–2)
 * @param {Object|null}  runners     runners object { base: runnerObj }
 * @param {Object|null}  battedBall  battedBall object { position, distance, type }
 * @returns {{ fielder: number, action: Object }[]}
 */
export function getFielderMovements(outs, runners, battedBall) {
  if (!battedBall) return [];

  const runnerBitmask = getRunnerBitmask(runners);
  const sector = getBallSector(battedBall.position, battedBall.distance);
  const trajectory = battedBall.type;

  return FIELDER_RULES
    .filter(rule => matchesCondition(rule.conditions, outs, runnerBitmask, sector, trajectory))
    .map(rule => ({ fielder: rule.fielder, action: rule.action }));
}
