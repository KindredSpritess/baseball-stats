<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert distinct season names into seasons table
        $distinctSeasons = DB::table('teams')
            ->select('season')
            ->whereNotNull('season')
            ->distinct()
            ->get();
        
        foreach ($distinctSeasons as $seasonData) {
            DB::table('seasons')->insert([
                'name' => $seasonData->season,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add season_id column to teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable()->after('season');
            $table->foreign('season_id')->references('id')->on('seasons');
        });

        // Update teams to set season_id
        $teams = DB::table('teams')->whereNotNull('season')->get();
        foreach ($teams as $team) {
            $season = DB::table('seasons')->where('name', $team->season)->first();
            if ($season) {
                DB::table('teams')->where('id', $team->id)->update(['season_id' => $season->id]);
            }
        }

        // Drop the season column from teams
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the season column
        Schema::table('teams', function (Blueprint $table) {
            $table->string('season')->nullable()->after('name');
        });

        // Update teams to set season back
        DB::statement('UPDATE teams SET season = (SELECT name FROM seasons WHERE seasons.id = teams.season_id) WHERE season_id IS NOT NULL');

        // Drop season_id column
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        // Delete from seasons
        DB::statement('DELETE FROM seasons');
    }
};