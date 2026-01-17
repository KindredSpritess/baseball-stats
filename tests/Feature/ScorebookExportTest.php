<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Person;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScorebookExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the scorebook export command exists and is registered
     */
    public function test_scorebook_export_command_exists()
    {
        $this->artisan('list')
            ->assertExitCode(0);
        
        // Check that our command is registered
        $this->artisan('scorebook:export --help')
            ->assertExitCode(0);
    }

    /**
     * Test scorebook export with minimal game data
     */
    public function test_scorebook_export_with_minimal_game()
    {
        // Create teams
        $homeTeam = Team::create([
            'name' => 'Test Home Team',
            'short_name' => 'HOME',
        ]);

        $awayTeam = Team::create([
            'name' => 'Test Away Team',
            'short_name' => 'AWAY',
        ]);

        // Create a game - use mass assignment for allowed fields
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now(),
            'duration' => 120,
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Create some minimal players
        $person1 = Person::create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'bats' => 'R',
            'throws' => 'R',
        ]);

        $player1 = Player::create([
            'person_id' => $person1->id,
            'team_id' => $homeTeam->id,
            'game_id' => $game->id,
            'number' => '1',
            'stats' => ['PA' => 4, 'AB' => 3, 'R' => 1, '1' => 2],
        ]);

        // Set a basic game state
        $game->lineup = [
            [],
            [[$player1]],
        ];
        $game->defense = [
            [],
            ['1' => $player1],
        ];
        $game->linescore = [[0, 1, 0], [2, 0, 1]];
        $game->score = [1, 3];
        $game->state = 'force encode';
        $game->save();

        // Create storage directory if it doesn't exist
        Storage::fake('public');

        // Run the command - it should not throw an error
        $this->artisan('scorebook:export', ['game' => $game->id])
            ->assertExitCode(0);
    }

    /**
     * Test that command fails gracefully with non-existent game
     */
    public function test_scorebook_export_fails_with_invalid_game()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        
        $this->artisan('scorebook:export', ['game' => 99999]);
    }

    /**
     * Test scorebook export for specific team
     */
    public function test_scorebook_export_for_specific_team()
    {
        // Create teams
        $homeTeam = Team::create([
            'name' => 'Test Home Team',
            'short_name' => 'HOME',
        ]);

        $awayTeam = Team::create([
            'name' => 'Test Away Team',
            'short_name' => 'AWAY',
        ]);

        // Create a game
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now(),
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Set minimal game state
        $game->state = 'force encode';
        $game->save();

        Storage::fake('public');

        // Test exporting for home team only
        $this->artisan('scorebook:export', [
            'game' => $game->id,
            '--team' => 'home',
        ])->assertExitCode(0);

        // Test exporting for away team only
        $this->artisan('scorebook:export', [
            'game' => $game->id,
            '--team' => 'away',
        ])->assertExitCode(0);
    }
}
