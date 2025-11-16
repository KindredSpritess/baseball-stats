<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Play;
use Illuminate\Console\Command;

class RenamePersonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'person:rename {person_id : The ID of the person to rename} {new_first_name : The new first name} {new_last_name : The new last name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename a person and update all associated plays';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $personId = $this->argument('person_id');
        $newFirstName = $this->argument('new_first_name');
        $newLastName = $this->argument('new_last_name');

        $person = Person::find($personId);

        if (!$person) {
            $this->error("Person with ID {$personId} not found.");
            return 1;
        }

        $oldFirstName = $person->firstName;
        $oldLastName = $person->lastName;
        $oldName = "{$oldLastName}, {$oldFirstName}";

        if (!$this->confirm("Are you sure you want to rename {$oldFirstName} {$oldLastName} (ID: {$personId}) to {$newFirstName} {$newLastName}? This will update all associated plays.", false)) {
            $this->info('Rename cancelled.');
            return 0;
        }

        // Update the person
        $person->update([
            'firstName' => $newFirstName,
            'lastName' => $newLastName,
        ]);

        $this->info("Updated person {$personId} from {$oldFirstName} {$oldLastName} to {$newFirstName} {$newLastName}");

        // Update plays
        $plays = Play::where('play', 'like', "%{$oldName}%")->get();

        foreach ($plays as $play) {
            $newPlay = preg_replace('/(?<!\w)' . preg_quote($oldName, '/') . '(?!\w)/', "{$newLastName}, {$newFirstName}", $play->play);
            if ($newPlay !== $play->play) {
                $play->update(['play' => $newPlay]);
            }
        }

        $this->info("Updated " . $plays->count() . " plays.");

        return 0;
    }
}