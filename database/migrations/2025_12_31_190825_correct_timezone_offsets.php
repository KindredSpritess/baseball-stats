<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Accidentally set the timezone with a value for UTC+10 but in UTC.
        $games = \App\Models\Game::all();
        foreach ($games as $game) {
            $firstPitch = $game->firstPitch;
            if ($firstPitch) {
                // Adjust the time by adding 10 hours
                $correctedTime = $firstPitch->subHours(10);
                $game->firstPitch = $correctedTime;
                $game->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the timezone correction by subtracting 10 hours
        $games = \App\Models\Game::all();
        foreach ($games as $game) {
            $firstPitch = $game->firstPitch;
            if ($firstPitch) {
                // Adjust the time by subtracting 10 hours
                $revertedTime = $firstPitch->addHours(10);
                $game->firstPitch = $revertedTime;
                $game->save();
            }
        }
    }
};
