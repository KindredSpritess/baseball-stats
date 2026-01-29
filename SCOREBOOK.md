# Australian Scorebook PDF Export

This feature allows you to export Australian-style scorebook PDFs for baseball games.

## Overview

Each game can generate two scorebook PDFs - one for the home team and one for the away team. The scorebook follows the Australian baseball scoring format with:

- Game information (teams, venue, date, time, duration)
- Fielding positions for each batter
- Batting order with player names and numbers
- Inning-by-inning tracking grid
- Diamond notation cells for each at-bat
- Individual batting statistics (PA, AB, R, H, RBI, BB, SO)
- Pitcher statistics (IP, H, R, ER, BB, K)
- Pitchers of record (W, L, SV)
- Final score

## Usage

### Export for Both Teams

```bash
php artisan scorebook:export {game_id}
```

This will generate two PDF files, one for each team.

### Export for Specific Team

```bash
# Home team only
php artisan scorebook:export {game_id} --team=home

# Away team only
php artisan scorebook:export {game_id} --team=away
```

### Output Location

PDFs are saved to `storage/app/public/scorebooks/` with the following naming convention:
- `scorebook_game{id}_home_{team_short_name}.pdf`
- `scorebook_game{id}_away_{team_short_name}.pdf`

Example: `scorebook_game123_home_EAGLES.pdf`

## Features

### Dynamic Layout

- **Flexible innings**: Automatically expands beyond 12 innings if needed
- **Dynamic lineup**: Accommodates any number of batters (not limited to 9)
- **Responsive statistics**: Shows actual game data from the database

### Statistics Display

The scorebook displays comprehensive statistics:

**Per Batter:**
- PA (Plate Appearances)
- AB (At Bats)
- R (Runs)
- H (Hits)
- RBI (Runs Batted In)
- BB (Walks)
- SO (Strikeouts)

**Per Pitcher:**
- IP (Innings Pitched)
- H (Hits Allowed)
- R (Runs Allowed)
- ER (Earned Runs)
- BB (Walks)
- K (Strikeouts)

### Numbers vs Tally Marks

Per requirements, all statistics are displayed as actual numbers rather than tally marks.

## Data Requirements

For the scorebook to generate properly, the game should have:

1. Home and away teams configured
2. Game state with lineup information
3. Players with associated person records
4. Play-by-play data (for detailed at-bat tracking)
5. Basic game information (date, location, duration)

## Future Enhancements

The template includes structure for the following enhancements to be implemented:

- **Detailed diamond notation**: Display specific play results (K, 6-3, 1B, 2B, etc.) within each diamond
- **Base running paths**: Visual representation of base advancement
- **Fielding details**: Specific fielding plays and errors within each at-bat cell
- **Count tracking**: Ball-strike count for each plate appearance

These features are documented in the template's notation notes section.

## Technical Details

### Dependencies

- **barryvdh/laravel-dompdf**: PDF generation library
- Laravel 10.x or higher

### Implementation

- **Command**: `App\Console\Commands\ExportScorebookCommand`
- **Template**: `resources/views/scorebook/australian.blade.php`
- **Tests**: `tests/Feature/ScorebookExportTest.php`

### PDF Configuration

The scorebook is generated on A3 paper in landscape orientation for optimal layout of the grid structure.

## Testing

Run the test suite to verify functionality:

```bash
php artisan test --filter=ScorebookExportTest
```

Tests cover:
- Command registration
- PDF generation with minimal game data
- Error handling for invalid game IDs
- Team-specific exports

## Troubleshooting

### PDFs not generating

1. Ensure the storage directory is writable:
   ```bash
   chmod -R 775 storage
   ```

2. Create the scorebooks directory if it doesn't exist:
   ```bash
   mkdir -p storage/app/public/scorebooks
   ```

### Missing data in scorebook

Verify that the game has:
- Complete lineup data in the game state
- Players associated with the game
- Pitcher information recorded

### Incorrect statistics

Ensure that:
- Game has been processed with play-by-play data
- Player stats have been calculated and saved
- Game state has been properly encoded

## Support

For issues or questions about the scorebook feature, please open an issue on the project repository.
