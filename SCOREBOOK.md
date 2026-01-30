# Australian Scorebook HTML Export

This feature allows you to export Australian-style scorebook HTML files for baseball games that can be printed directly from a browser to create PDFs.

## Overview

Each game can generate separate scorebook HTML files - one for the home team and one for the away team, or a combined file for both teams. The scorebook follows the Australian baseball scoring format with:

- Game information (teams, venue, date, time, duration, timezone)
- Fielding positions and statistics (DO, PO, A, E) for each batter
- Batting order with player names and jersey numbers
- Inning-by-inning tracking grid (auto-expands for extra innings)
- Play cells with 4-quadrant grid and center circle for each at-bat
- Pitch sequence and progressive pitch count below each play cell
- Visual indicators for outs, runs (earned/unearned), and inning transitions
- Individual batting statistics (PA, AB, R, H, 2B, 3B, HR, RBI, SAB, SAF, BB, HBP, CI, K, GDP, SB, CS, LOB)
- Pitcher inning-by-inning breakdown with totals
- Pitcher statistics (IP, H, K, BB, HBP, RS, ER, WP, BLK, PO, PCS, BFP, B, S, PIT, W/L/S)
- Pitcher fielding positions (separate section for pitchers who play field)
- Catcher statistics (INN, PB, SB, CS)
- Pitchers of record (WIN/LOSS/SAVE)
- Final score and runs per inning
- LOB (Left on Base) tracking per inning

## Usage

### Command Line Interface

#### Export for Both Teams

```bash
php artisan scorebook:export {game_id}
```

This will generate a combined HTML file with both teams' scorebooks.

#### Export for Specific Team

```bash
# Home team only
php artisan scorebook:export {game_id} --team=home

# Away team only
php artisan scorebook:export {game_id} --team=away
```

### Web Interface

1. Open a game in touch screen scoring mode
2. Click the "⚙️ Options" button
3. Select "Export Scorebook"
4. The browser will open the generated HTML file
5. Use your browser's print function (Ctrl+P / Cmd+P) to create a PDF if desired

### Output Location

HTML files are saved to `storage/app/public/scorebooks/` with the following naming convention:
- Single team: `scorebook_game{id}_{home|away}_{team_short_name}.html`
- Both teams: `scorebook_game{id}_both.html`

Example: `scorebook_game123_home_EAGLES.html`

## Features

### Dynamic Layout

- **Flexible innings**: Automatically expands for extra innings (no 12-inning limit)
- **Dynamic lineup**: Accommodates any number of batters (not limited to 9)
- **Wide innings**: Expands for games with many at-bats per inning
- **Responsive statistics**: Shows actual game data from the database
- **A3 landscape format**: Optimized page layout for printing

### Visual Indicators

**Out Numbers:**
- Out number (1, 2, 3) displayed in orange inside the circle when batter is retired
- Tracks outs before and after each play

**Run Scoring:**
- Circle background colored green for earned runs
- Circle background colored red for unearned runs
- Empty circle if no run scored

**Inning Transitions:**
- Diagonal orange line through cell when inning ends
- Orange top border on next inning's cell
- Helps track where innings change

**Player Substitutions:**
- Green border for next at-bat when player is substituted (PH, PR, DSUB)
- Visual markers for pinch hitters, pinch runners, and defensive substitutions

**Pitcher Changes:**
- Inning number with overline (top of inning) or underline (bottom of inning)
- Shows when pitcher entered the game
- Separate tracking for pitchers who also play field positions

**Inning Change Timing:**
- Decorated timestamps showing when innings change
- Helps track game flow and pace

### Statistics Display

The scorebook displays comprehensive statistics with all values centered in uniform-width columns:

**Per Batter:**
- PA (Plate Appearances), AB (At Bats), R (Runs), H (Hits)
- 2B (Doubles), 3B (Triples), HR (Home Runs), RBI (Runs Batted In)
- SAB (Sacrifice Bunts), SAF (Sacrifice Flies)
- BB (Walks), HBP (Hit By Pitch), CI (Catcher Interference)
- K (Strikeouts), GDP (Ground into Double Play)
- SB (Stolen Bases), CS (Caught Stealing), LOB (Left on Base)

**Per Pitcher:**
- Inning-by-inning breakdown showing performance per inning
- IP (Innings Pitched), H (Hits Allowed), K (Strikeouts)
- BB (Walks), HBP (Hit Batters), RS (Runs Scored), ER (Earned Runs)
- WP (Wild Pitches), BLK (Balks), PO (Pickoffs), PCS (Pickoff Caught Stealing)
- BFP (Batters Faced), B (Balls), S (Strikes), PIT (Total Pitches)
- W/L/S (Win/Loss/Save designation)

**Per Catcher:**
- INN (Innings Caught), PB (Passed Balls)
- SB (Stolen Bases Allowed), CS (Caught Stealing)

**Fielding Statistics:**
- DO (Defensive Outs), PO (Putouts), A (Assists), E (Errors)

### Numbers vs Tally Marks

Per requirements, all statistics are displayed as actual numbers rather than tally marks.

## Data Requirements

For the scorebook to generate properly, the game should have:

1. Home and away teams configured
2. Game state with lineup and defense information
3. Players with associated person records
4. Play-by-play data (for detailed at-bat tracking and visual indicators)
5. Basic game information (date, location, duration, timezone)
6. Pitcher assignments and changes
7. Substitution information (PH, PR, DSUB)

## Advanced Features

**Play Cell Structure:**
- 4-quadrant grid showing base running paths
- Center circle for earned/unearned run indication
- Space below for pitch sequence and progressive pitch count
- Support for parenthesized play notation

**Substitution Tracking:**
- Pinch hitters (PH) with visual markers
- Pinch runners (PR) with visual markers
- Defensive substitutions (DSUB) properly marked
- Green border on next at-bat after substitution

**Pitcher Fielding:**
- Separate section for pitchers who also play field positions
- Tracks position changes and timing
- Shows fielding statistics separately from pitching

**Layout Optimizations:**
- Page break prevention to keep scorebook on single page
- Dynamic row heights based on number of pitchers
- Consistent column widths throughout
- Optimized spacing and borders for readability

## Technical Details

### Architecture

- **Pure HTML export**: No PDF library dependencies
- **Table-based layouts**: Compatible with all browsers (no flexbox/grid)
- **GameState cast**: Proper data extraction from game state
- **Component-based**: Reusable components for pitcher stats
- **StatsHelper**: On-the-fly statistics calculation

### Dependencies

- Laravel 10.x or higher
- No external PDF libraries (removed barryvdh/laravel-dompdf dependency)
- Browser-based PDF creation via print function

### Implementation

- **Command**: `App\Console\Commands\ExportScorebookCommand`
- **Template**: `resources/views/scorebook/australian.blade.php`
- **Components**: 
  - `resources/views/components/pitcher-fielding-stats-column.blade.php`
  - `resources/views/components/score-quadrant.blade.php`
- **Tests**: `tests/Feature/ScorebookExportTest.php`
- **Route**: `GET /game/{game}/scorebook` (requires score-game permission)
- **Controller**: `GameController::exportScorebook()`

### HTML/CSS Configuration

- **Page format**: A3 landscape with 10mm margins
- **Page break prevention**: Ensures scorebook stays on single page
- **Font sizes**: 6-7pt optimized for A3 print
- **Column widths**: Fixed widths for consistent layout
- **Dynamic heights**: Pitcher rows adjust based on number of pitchers

### Data Handling

- Extracts lineup, defense, and pitching data from GameState cast
- Calculates statistics using StatsHelper
- Handles complex substitution patterns (PH, PR, DSUB)
- Tracks pitcher fielding positions separately
- Supports parenthesized play notation
- Properly handles timezone conversions

## Testing

Run the test suite to verify functionality:

```bash
php artisan test --filter=ScorebookExportTest
```

Tests cover:
- Command registration
- HTML generation with minimal game data
- Error handling for invalid game IDs
- Team-specific exports (home/away)

All tests should pass with 4 test cases and 6 assertions.

## Troubleshooting

### HTML files not generating

1. Ensure the storage directory is writable:
   ```bash
   chmod -R 775 storage
   ```

2. Create the scorebooks directory if it doesn't exist:
   ```bash
   mkdir -p storage/app/public/scorebooks
   ```

3. Check that the public storage link exists:
   ```bash
   php artisan storage:link
   ```

### Missing data in scorebook

Verify that the game has:
- Complete lineup data in the game state
- Defense positions assigned
- Players associated with the game
- Pitcher information recorded
- Play-by-play data for visual indicators

### Incorrect statistics

Ensure that:
- Game has been processed with play-by-play data
- Player stats have been calculated and saved
- Game state has been properly encoded
- StatsHelper is calculating derived statistics correctly

### Print/PDF Issues

When printing from browser:
- Select A3 paper size in print dialog
- Choose landscape orientation
- Set margins to minimum (or 10mm)
- Disable headers and footers for cleaner output
- Use "Print backgrounds" option to preserve colors
- Consider using "Save as PDF" instead of printing to physical printer

## Recent Improvements

- **Removed PDF dependency** (commit b75bfbc): Switched from barryvdh/laravel-dompdf to pure HTML
  - Simpler implementation
  - Better browser compatibility
  - Users print directly from browser
  - No external PDF library dependencies
- **Enhanced pitcher tracking**: Separate handling for pitchers playing field positions
- **Improved layout**: Better spacing, borders, and readability
- **Substitution support**: Full PH, PR, and DSUB tracking with visual indicators
- **Inning decorations**: Visual markers for timing and transitions
- **Parenthesized plays**: Support for complex play notation

## Support

For issues or questions about the scorebook feature, please open an issue on the project repository.
