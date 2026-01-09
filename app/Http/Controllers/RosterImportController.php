<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RosterImportController extends Controller
{
    public function showImportForm()
    {
        $seasons = Season::with('teams')->get();
        if (request()->user()->role !== 'superuser') {
            // Filter seasons and teams based on user permissions
            $seasons = $seasons
                ->map(fn ($season) => (object)([
                    'id' => $season->id,
                    'name' => $season->name,
                    'teams' => $season->teams->filter(fn ($team) => Gate::allows('import-roster', $team))->values(),
                ]))
                ->filter(fn ($season) => $season->teams->isNotEmpty())
                ->values();
        }

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
                            $imported++;
                        }
                    } else {
                        // Create new player (without a game)
                        Player::create([
                            'person_id' => $person->id,
                            'team_id' => $team->id,
                            'number' => $number,
                            'game_id' => 0,  // No game associated
                        ]);
                        $imported++;
                    }
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
        // Validate URL format and scheme
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            throw new \Exception('Invalid URL format.');
        }

        // Only allow HTTP and HTTPS
        if (!in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            throw new \Exception('Only HTTP and HTTPS URLs are supported.');
        }

        // Prevent access to internal/private IP addresses
        $host = $parsedUrl['host'];
        $ip = gethostbyname($host);

        // Check for private/reserved IP ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new \Exception('Access to internal/private URLs is not allowed.');
        }

        // Check if it's a mygameday.app URL (validate host specifically)
        if (isset($parsedUrl['host']) && strpos(strtolower($parsedUrl['host']), 'mygameday.app') !== false) {
            // Ensure it's actually the mygameday.app domain, not a subdomain of another domain
            $hostParts = explode('.', $parsedUrl['host']);
            $lastTwo = implode('.', array_slice($hostParts, -2));
            if ($lastTwo === 'mygameday.app') {
                return $this->parseMyGameDayUrl($url);
            }
        }

        // For other URLs, try to fetch as CSV
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'follow_location' => 0, // Prevent redirects to internal URLs
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
        $html = Http::withOptions([
            'timeout' => 15,
            'follow_redirects' => false,
        ])->get($url)->throw()->body();

        $data = [];
        // Use DOMDocument to parse HTML
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOENT);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Try to find player rows in tables
        $players = $xpath->query('//div[@class="article player-profile"]');

        foreach ($players as $player) {
            $names = explode(' ', str_replace(".", "", trim($player->textContent)));
            // Assume first word is first name, last word is last name.
            // We've seen middle names/initials, but we haven't used these typically.
            $data[] = [
                $names[0] ?? '',
                end($names) ?? '',
            ];
        }

        if (empty($data)) {
            throw new \Exception('No player data found in the MyGameDay URL. Please verify the URL points to a team roster page.');
        }

        return $data;
    }
}
