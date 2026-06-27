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
 *   area:       string[]   Vertical area of the batted ball: 'infield' | 'outfield'.
 *   side:       string[]   Lateral side of the batted ball: 'left' | 'center' | 'right'.
 *   sector:     string[]   Fine-grained sector id of the batted ball:
 *                            infield:  if-<ring>-<lane> (12 total; ring 1..3, lane 1..4)
 *                            outfield: of-<ring>-<lane> (9 total; ring 1..3, lane 1..3)
 *
 * --- Action fields ---
 *   type:   'moveToBall' | 'catchBall' | 'pickUpBall' | 'moveToBase' | 'moveToCutoff'
 *   base:   number | 'home'   For moveToBase — which base to cover: 1, 2, 3, or 'home'.
 *   toBase: number | 'home'   For moveToCutoff — target base for the relay throw.
 */

const HOME_PLATE_X = 224;
const INFIELD_MAX_DISTANCE = 90;
const SIDE_LEFT_MAX_X = 180;
const SIDE_RIGHT_MIN_X = 268;
const DEFAULT_RING = 2;
const INFIELD_RING_NEAR_MAX_DISTANCE = 30;
const INFIELD_RING_MID_MAX_DISTANCE = 60;
const OUTFIELD_RING_NEAR_MAX_DISTANCE = 180;
const OUTFIELD_RING_MID_MAX_DISTANCE = 270;
const INFIELD_LANE_1_MAX_X = 160;
const INFIELD_LANE_3_MAX_X = 288;

const getInfieldRing = (distance) => {
  if (distance === null || distance === undefined) return DEFAULT_RING;
  if (distance < INFIELD_RING_NEAR_MAX_DISTANCE) return 1;
  if (distance < INFIELD_RING_MID_MAX_DISTANCE) return 2;
  return 3;
};

const getOutfieldRing = (distance) => {
  if (distance === null || distance === undefined) return DEFAULT_RING;
  if (distance < OUTFIELD_RING_NEAR_MAX_DISTANCE) return 1;
  if (distance < OUTFIELD_RING_MID_MAX_DISTANCE) return 2;
  return 3;
};

const getInfieldLane = (x) => {
  if (x < INFIELD_LANE_1_MAX_X) return 1;
  if (x < HOME_PLATE_X) return 2;
  if (x < INFIELD_LANE_3_MAX_X) return 3;
  return 4;
};

const getOutfieldLane = (x) => {
  if (x < SIDE_LEFT_MAX_X) return 1;
  if (x <= SIDE_RIGHT_MIN_X) return 2;
  return 3;
};

const getBallSide = (x) => {
  if (x < SIDE_LEFT_MAX_X) return 'left';
  if (x > SIDE_RIGHT_MIN_X) return 'right';
  return 'center';
};

const matchesArrayCondition = (conditionValues, value) => {
  if (!conditionValues) return true;
  return conditionValues.includes(value ?? null);
};

/**
 * Determine the area/side/sector of a batted ball from its SVG-coordinate position.
 *
 * @param {number[] | null} ballPosition  [x, y] in SVG coords, or null
 * @param {number} [distance]             Optional distance from home plate (feet)
 * @returns {{ area: string, side: string, sector: string } | null}
 */
export function getBallContext(ballPosition, distance) {
  if (!ballPosition) return null;

  const x = ballPosition[0];
  const side = getBallSide(x);

  if (distance != null && distance < INFIELD_MAX_DISTANCE) {
    const ring = getInfieldRing(distance);
    const lane = getInfieldLane(x);
    return {
      area: 'infield',
      side,
      sector: `if-${ring}-${lane}`,
    };
  }

  const ring = getOutfieldRing(distance);
  const lane = getOutfieldLane(x);
  return {
    area: 'outfield',
    side,
    sector: `of-${ring}-${lane}`,
  };
}

/**
 * Determine the fine-grained sector id of a batted ball.
 *
 * @param {number[] | null} ballPosition  [x, y] in SVG coords, or null
 * @param {number} [distance]             Optional distance from home plate
 * @returns {string | null}               Sector string, or null when no ball
 */
export function getBallSector(ballPosition, distance) {
  return getBallContext(ballPosition, distance)?.sector ?? null;
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
 * @param {{ area: string, side: string, sector: string } | null} ballContext
 * @param {string | null} trajectory
 * @returns {boolean}
 */
function matchesCondition(condition, outs, runnerBitmask, ballContext, trajectory) {
  if (!matchesArrayCondition(condition.outs, outs)) return false;
  if (!matchesArrayCondition(condition.runners, runnerBitmask)) return false;
  if (!matchesArrayCondition(condition.trajectory, trajectory)) return false;
  if (!matchesArrayCondition(condition.area, ballContext?.area)) return false;
  if (!matchesArrayCondition(condition.side, ballContext?.side)) return false;
  if (!matchesArrayCondition(condition.sector, ballContext?.sector)) return false;
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
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['left'] },
    fielder: 7,
    action: { type: 'catchBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['left'] },
    fielder: 6,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['left'] },
    fielder: 5,
    action: { type: 'moveToBase', base: 3 },
  },

  // Center field — CF chases ball, SS takes cutoff, 2B covers second
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['center'] },
    fielder: 8,
    action: { type: 'catchBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['center'] },
    fielder: 6,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['center'] },
    fielder: 4,
    action: { type: 'moveToBase', base: 2 },
  },

  // Right field — RF chases ball, 2B takes cutoff, 1B covers first
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['right'] },
    fielder: 9,
    action: { type: 'catchBall' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['right'] },
    fielder: 4,
    action: { type: 'moveToCutoff', toBase: 'home' },
  },
  {
    conditions: { trajectory: ['F', 'L'], area: ['outfield'], side: ['right'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },

  // ── GROUND BALLS ──────────────────────────────────────────────────────────

  // Ground ball to left (3B/SS side) — SS fields, 1B covers first, 2B covers second
  {
    conditions: { trajectory: ['G'], side: ['left'] },
    fielder: 6,
    action: { type: 'pickUpBall' },
  },
  {
    conditions: { trajectory: ['G'], side: ['left'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], side: ['left'] },
    fielder: 4,
    action: { type: 'moveToBase', base: 2 },
  },

  // Ground ball up the middle (center) — 2B fields, 1B covers first, SS covers second
  {
    conditions: { trajectory: ['G'], side: ['center'] },
    fielder: 4,
    action: { type: 'pickUpBall' },
  },
  {
    conditions: { trajectory: ['G'], side: ['center'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], side: ['center'] },
    fielder: 6,
    action: { type: 'moveToBase', base: 2 },
  },

  // Ground ball to right (1B/2B side) — 1B fields, P covers first, SS covers second
  {
    conditions: { trajectory: ['G'], side: ['right'] },
    fielder: 3,
    action: { type: 'pickUpBall' },
  },
  {
    conditions: { trajectory: ['G'], side: ['right'] },
    fielder: 1,
    action: { type: 'moveToBase', base: 1 },
  },
  {
    conditions: { trajectory: ['G'], side: ['right'] },
    fielder: 6,
    action: { type: 'moveToBase', base: 2 },
  },

  // ── BUNTS ─────────────────────────────────────────────────────────────────

  // Bunt — P and C converge on ball, 1B covers first base
  {
    conditions: { trajectory: ['B'] },
    fielder: 1,
    action: { type: 'pickUpBall' },
  },
  {
    conditions: { trajectory: ['B'] },
    fielder: 2,
    action: { type: 'pickUpBall' },
  },
  {
    conditions: { trajectory: ['B'] },
    fielder: 3,
    action: { type: 'moveToBase', base: 1 },
  },

  // ── POP-UPS ───────────────────────────────────────────────────────────────

  // Infield pop-up (any infield sector) — C calls for it near home
  {
    conditions: { trajectory: ['P'], area: ['infield'] },
    fielder: 2,
    action: { type: 'catchBall' },
  },

  // Pop-up to the left (3B/SS side) — SS fields, 3B backs up
  {
    conditions: { trajectory: ['P'], area: ['outfield'], side: ['left'] },
    fielder: 6,
    action: { type: 'catchBall' },
  },
  {
    conditions: { trajectory: ['P'], area: ['outfield'], side: ['left'] },
    fielder: 5,
    action: { type: 'catchBall' },
  },

  // Pop-up to center — 2B or SS fields
  {
    conditions: { trajectory: ['P'], area: ['outfield'], side: ['center'] },
    fielder: 4,
    action: { type: 'catchBall' },
  },

  // Pop-up to the right (1B/2B side) — 2B fields, 1B backs up
  {
    conditions: { trajectory: ['P'], area: ['outfield'], side: ['right'] },
    fielder: 4,
    action: { type: 'catchBall' },
  },
  {
    conditions: { trajectory: ['P'], area: ['outfield'], side: ['right'] },
    fielder: 3,
    action: { type: 'catchBall' },
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
  const ballContext = getBallContext(battedBall.position, battedBall.distance);
  const trajectory = battedBall.type;

  return FIELDER_RULES
    .filter(rule => matchesCondition(rule.conditions, outs, runnerBitmask, ballContext, trajectory))
    .map(rule => ({ fielder: rule.fielder, action: rule.action }));
}
