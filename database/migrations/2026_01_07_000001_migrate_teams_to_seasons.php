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
        // Use CURRENT_TIMESTAMP for SQLite compatibility
        DB::statement("INSERT INTO seasons (name, created_at, updated_at) SELECT DISTINCT season, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM teams WHERE season IS NOT NULL");

        // Add season_id column to teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable()->after('season');
            $table->foreign('season_id')->references('id')->on('seasons');
        });

        // Update teams to set season_id
        DB::statement('UPDATE teams SET season_id = (SELECT id FROM seasons WHERE seasons.name = teams.season) WHERE season IS NOT NULL');

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