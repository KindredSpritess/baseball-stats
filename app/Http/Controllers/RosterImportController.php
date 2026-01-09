<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RosterImportController extends Controller
{
    public function showImportForm()
    {
        $seasons = Season::with('teams')->get();
        
        return view('roster.import', [
            'seasons' => $seasons,
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'team_id' => 'nullable|exists:teams,id',
            'season_id' => 'nullable|exists:seasons,id',
            'columns_in_file' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $columnsInFile = $request->boolean('columns_in_file');
        $defaultTeamId = $request->input('team_id');
        $defaultSeasonId = $request->input('season_id');

        try {
            $data = $this->parseFile($file);
            
            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    if ($columnsInFile) {
                        // Columns in file: First Name, Last Name, Number (optional), Team, Season
                        $firstName = $row[0] ?? null;
                        $lastName = $row[1] ?? null;
                        $number = isset($row[2]) && $row[2] !== '' ? $row[2] : null;
                        $teamName = $row[3] ?? null;
                        $seasonName = $row[4] ?? null;

                        if (!$firstName || !$lastName || !$teamName || !$seasonName) {
                            $errors[] = "Row " . ($index + 1) . ": Missing required fields (First Name, Last Name, Team, or Season)";
                            continue;
                        }

                        // Find season
                        $season = Season::where('name', $seasonName)->first();
                        if (!$season) {
                            $errors[] = "Row " . ($index + 1) . ": Season '$seasonName' not found";
                            continue;
                        }

                        // Find team
                        $team = Team::where('season_id', $season->id)
                            ->where(function($query) use ($teamName) {
                                $query->where('name', $teamName)
                                      ->orWhere('short_name', $teamName);
                            })->first();

                        if (!$team) {
                            $errors[] = "Row " . ($index + 1) . ": Team '$teamName' not found in season '$seasonName'";
                            continue;
                        }
                    } else {
                        // No columns in file: First Name, Last Name, Number (optional)
                        $firstName = $row[0] ?? null;
                        $lastName = $row[1] ?? null;
                        $number = isset($row[2]) && $row[2] !== '' ? $row[2] : null;

                        if (!$firstName || !$lastName) {
                            $errors[] = "Row " . ($index + 1) . ": Missing required fields (First Name or Last Name)";
                            continue;
                        }

                        if (!$defaultTeamId) {
                            $errors[] = "Row " . ($index + 1) . ": No team specified";
                            continue;
                        }

                        $team = Team::find($defaultTeamId);
                    }

                    // Check authorization
                    if (!Gate::allows('import-roster', $team)) {
                        $errors[] = "Row " . ($index + 1) . ": Not authorized to import players to team '{$team->name}'";
                        continue;
                    }

                    // Create or find person
                    $person = Person::firstOrCreate([
                        'firstName' => trim($firstName),
                        'lastName' => trim($lastName),
                    ], [
                        'bats' => 'R',  // Default values
                        'throws' => 'R',
                    ]);

                    // Check if player already exists on this team without a game
                    $existingPlayer = Player::where('person_id', $person->id)
                        ->where('team_id', $team->id)
                        ->where('game_id', 0)
                        ->first();

                    if ($existingPlayer) {
                        // Update number if provided
                        if ($number !== null) {
                            $existingPlayer->number = $number;
                            $existingPlayer->save();
                        }
                    } else {
                        // Create new player (without a game)
                        Player::create([
                            'person_id' => $person->id,
                            'team_id' => $team->id,
                            'number' => $number,
                            'game_id' => 0,  // No game associated
                        ]);
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            if (count($errors) > 0) {
                if ($imported === 0) {
                    // All rows failed
                    $message = "Import failed. " . count($errors) . " error(s) occurred: " . implode('; ', array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= ' (and ' . (count($errors) - 5) . ' more)';
                    }
                    return redirect()->back()->with('error', $message);
                } else {
                    // Partial success
                    $message = "Successfully imported $imported player(s), but " . count($errors) . " error(s) occurred: " . implode('; ', array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $message .= ' (and ' . (count($errors) - 5) . ' more)';
                    }
                    return redirect()->back()->with('warning', $message);
                }
            }

            return redirect()->back()->with('success', "Successfully imported $imported player(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error importing roster: ' . $e->getMessage());
        }
    }

    private function parseFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if ($extension === 'csv') {
            return $this->parseCsv($file);
        } else {
            return $this->parseExcel($file);
        }
    }

    private function parseCsv($file)
    {
        $data = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        try {
            // Skip header row
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = $row;
            }
        } finally {
            fclose($handle);
        }
        
        return $data;
    }

    private function parseExcel($file)
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        return $rows;
    }
}
