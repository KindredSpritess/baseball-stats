<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\Play;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergePersonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'person:merge {source_id : The ID of the person to keep (Person A)} {target_id : The ID of the person to merge and delete (Person B)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge two people: reassigns all of Person B\'s players to Person A, rewrites Person B\'s name to Person A, then deletes Person B.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceId = $this->argument('source_id'); // Person A (keep)
        $targetId = $this->argument('target_id'); // Person B (merge/delete)

        $source = Person::find($sourceId);
        $target = Person::find($targetId);

        if (!$source) {
            $this->error("Person A (ID {$sourceId}) not found.");
            return 1;
        }
        if (!$target) {
            $this->error("Person B (ID {$targetId}) not found.");
            return 1;
        }
        if ($sourceId == $targetId) {
            $this->error("Source and target IDs must be different.");
            return 1;
        }

        $this->info("Merging Person B ({$target->firstName} {$target->lastName}, ID: {$targetId}) into Person A ({$source->firstName} {$source->lastName}, ID: {$sourceId})");
        if (!$this->confirm("Are you sure? This will reassign all Player records and delete Person B.", false)) {
            $this->info('Merge cancelled.');
            return 0;
        }

        DB::transaction(function () use ($source, $target) {
            $oldFirstName = $target->firstName;
            $oldLastName = $target->lastName;
            $oldName = "{$oldLastName}, {$oldFirstName}";

            // Reassign all Player records from Person B to Person A
            Player::where('person_id', $target->id)->update(['person_id' => $source->id]);

            // Update plays
            $plays = Play::where('play', 'like', "%{$oldName}%")->get();

            $this->withProgressBar($plays, function ($play) use ($source, $oldName) {
                $newPlay = preg_replace('/(?<!\w)' . preg_quote($oldName, '/') . '(?!\w)/', "{$source->lastName}, {$source->firstName}", $play->play);
                if ($newPlay !== $play->play) {
                    $play->update(['play' => $newPlay]);
                }
            });

            // Delete Person B
            $target->delete();
        });

        $this->info("Person B (ID {$targetId}) merged into Person A (ID {$sourceId}) and deleted.");
        return 0;
    }
}
