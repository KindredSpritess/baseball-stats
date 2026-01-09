# Roster Import Feature

## Overview

The roster import feature allows authorized users to bulk import player rosters via CSV or Excel (XLSX/XLS) files. This makes it easy to add multiple players to a team without entering them individually.

## Access Requirements

To import a roster, you need one of the following:
- **Team Scorer**: Access to the specific team you're importing to
- **Season Admin**: Access to any team within the season
- **Superuser**: Full access to all teams

## How to Use

1. Navigate to `/roster/import` in your browser
2. Choose your import method:
   - **Option A**: Upload a file with just player data and select team/season from dropdowns
   - **Option B**: Include team and season information in your file

3. Upload your CSV or Excel file
4. Click "Import Roster"

## File Format

### Option A: Team/Season from Form

Your file should have these columns (first row as header):
```csv
First Name,Last Name,Number
John,Doe,10
Jane,Smith,15
Bob,Johnson,
```

Note: Number is optional. Leave blank if player doesn't have a number yet.

### Option B: Team/Season in File

Your file should have these columns (first row as header):
```csv
First Name,Last Name,Number,Team,Season
John,Doe,10,Blue Jays,2024 Season
Jane,Smith,15,Blue Jays,2024 Season
Bob,Johnson,,Red Sox,2024 Season
```

Notes:
- Team can match either the full team name or short name
- Season must match the exact season name
- Number is optional (can be blank)

## Important Notes

- **Header Row**: The first row of your file should contain column headers and will be skipped during import
- **Duplicate Players**: If a player already exists on the team (same first name, last name, and team) without a game association, their number will be updated rather than creating a duplicate
- **Game Association**: Imported players are created without game association (game_id = 0) and can be added to game rosters later
- **Error Handling**: If some rows fail to import (e.g., due to missing data or authorization issues), successful imports will still be saved, and you'll see a detailed error report

## Examples

### Example 1: Simple CSV Import
```csv
First Name,Last Name,Number
Mike,Trout,27
Shohei,Ohtani,17
Aaron,Judge,99
```

Select team: "Los Angeles Angels"
Select season: "2024 MLB Season"

### Example 2: Multi-Team Import
```csv
First Name,Last Name,Number,Team,Season
Mike,Trout,27,Angels,2024 MLB Season
Shohei,Ohtani,17,Dodgers,2024 MLB Season
Aaron,Judge,99,Yankees,2024 MLB Season
```

Check "Team and Season columns are in the file"

## Troubleshooting

### "Not authorized" errors
- Verify you have access to the team (team scorer or season admin)
- Confirm the season exists and you're a season admin if trying to import across teams

### "Team not found" errors
- Check that the team name matches exactly (or use the short name)
- Verify the team belongs to the specified season

### "Season not found" errors
- Verify the season name matches exactly
- Check for extra spaces or typos

### Import appears successful but no players created
- Check that you selected the correct team and season
- Verify the CSV format matches the expected format
- Ensure first name and last name columns have data

## Technical Details

- **Supported File Types**: CSV (.csv), Excel (.xlsx, .xls)
- **Maximum File Size**: Depends on PHP configuration (typically 2MB-8MB)
- **Character Encoding**: UTF-8 recommended
- **Database**: Players are stored in the `players` table with `game_id = 0`
