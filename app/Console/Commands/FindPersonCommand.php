<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;

class FindPersonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'person:find {--first= : The first name to search} {--last= : The last name to search}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search for people by first and/or last name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $firstName = $this->option('first');
        $lastName = $this->option('last');

        $query = Person::query();

        if (!$firstName && !$lastName) {
            $this->error('Please provide at least one of --first or --last to search.');
            return 1;
        }

        if ($firstName) {
            $query->where('firstName', 'like', '%' . $firstName . '%');
        }

        if ($lastName) {
            $query->where('lastName', 'like', '%' . $lastName . '%');
        }

        $people = $query->with('players.team')->get();

        if ($people->isEmpty()) {
            $this->info('No people found matching the criteria.');
            return 0;
        }

        $this->table(
            ['ID', 'First Name', 'Last Name', 'Teams'],
            $people->map(function ($person) {
                $teams = $person->players->pluck('team')->unique('id')->map(fn($team) => $team->name . ' (' . $team->season . ')');
                $teamsString = $teams->take(3)->implode(', ');
                if ($teams->count() > 3) {
                    $teamsString .= ', ...';
                }
                return [$person->id, $person->firstName, $person->lastName, $teamsString];
            })
        );

        return 0;
    }
}