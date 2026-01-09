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
            'file' => 'nullable|file|mimes:csv,xlsx,xls',
            'url' => 'nullable|url',
            'team_id' => 'nullable|exists:teams,id',
            'season_id' => 'nullable|exists:seasons,id',
            'columns_in_file' => 'nullable|boolean',
        ]);

        // Validate that either file or URL is provided
        if (!$request->hasFile('file') && !$request->filled('url')) {
            return redirect()->back()->with('error', 'Please provide either a file or a URL.');
        }

        $file = $request->file('file');
        $url = $request->input('url');
        $columnsInFile = $request->boolean('columns_in_file');
        $defaultTeamId = $request->input('team_id');
        $defaultSeasonId = $request->input('season_id');

        try {
            // Parse data from either file or URL
            if ($url) {
                $data = $this->parseUrl($url);
            } else {
                $data = $this->parseFile($file);
            }
            
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

    private function parseUrl($url)
    {
        // Check if it's a mygameday.app URL
        if (strpos($url, 'mygameday.app') !== false) {
            return $this->parseMyGameDayUrl($url);
        }
        
        // For other URLs, try to fetch as CSV
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \Exception('Failed to fetch data from URL. Please check the URL and try again.');
        }
        
        // Try to parse as CSV
        $lines = explode("\n", $content);
        $data = [];
        
        // Skip header row
        array_shift($lines);
        
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            if (!empty(array_filter($row))) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    private function parseMyGameDayUrl($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        
        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            throw new \Exception('Failed to fetch data from MyGameDay URL. Please check the URL and try again.');
        }
        
        // Parse HTML to extract player data
        // MyGameDay typically shows players in a table format
        $data = [];
        
        // Use DOMDocument to parse HTML
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        
        // Try to find player rows in tables
        // Look for tables with player information
        $tables = $xpath->query('//table');
        
        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            $headerProcessed = false;
            
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td | .//th', $row);
                
                if ($cells->length === 0) {
                    continue;
                }
                
                // Skip header rows
                if (!$headerProcessed) {
                    $headerProcessed = true;
                    continue;
                }
                
                $rowData = [];
                foreach ($cells as $cell) {
                    $rowData[] = trim($cell->textContent);
                }
                
                // Try to extract first name, last name, and number
                // Typical format might be: Number, Name, other fields...
                if (count($rowData) >= 2) {
                    // Check if first column is a number (jersey number)
                    $number = '';
                    $nameStart = 0;
                    
                    if (is_numeric($rowData[0]) || $rowData[0] === '') {
                        $number = $rowData[0];
                        $nameStart = 1;
                    }
                    
                    // Parse name - could be "LastName, FirstName" or "FirstName LastName"
                    if (isset($rowData[$nameStart])) {
                        $name = $rowData[$nameStart];
                        
                        if (strpos($name, ',') !== false) {
                            // Format: "LastName, FirstName"
                            $parts = array_map('trim', explode(',', $name, 2));
                            $lastName = $parts[0];
                            $firstName = $parts[1] ?? '';
                        } else {
                            // Format: "FirstName LastName" or just single name
                            $parts = explode(' ', trim($name), 2);
                            $firstName = $parts[0];
                            $lastName = $parts[1] ?? '';
                        }
                        
                        if ($firstName || $lastName) {
                            $data[] = [$firstName, $lastName, $number];
                        }
                    }
                }
            }
        }
        
        if (empty($data)) {
            throw new \Exception('No player data found in the MyGameDay URL. Please verify the URL points to a team roster page.');
        }
        
        return $data;
    }
}
