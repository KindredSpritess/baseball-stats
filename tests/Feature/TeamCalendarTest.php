<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamCalendarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the calendar route returns ICS format
     */
    public function test_calendar_route_returns_ics()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create teams
        $homeTeam = Team::create([
            'name' => 'Test Home Team',
            'short_name' => 'HOME',
            'season_id' => $season->id,
        ]);

        $awayTeam = Team::create([
            'name' => 'Test Away Team',
            'short_name' => 'AWAY',
            'season_id' => $season->id,
        ]);

        // Create a game
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now()->addDays(1),
            'duration' => 180,
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Make request to calendar route
        $response = $this->get(route('team.calendar', ['team' => $homeTeam->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertHeader('Content-Disposition');
    }

    /**
     * Test that ICS content is properly formatted
     */
    public function test_ics_content_is_valid()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create teams
        $homeTeam = Team::create([
            'name' => 'Home Team',
            'short_name' => 'HOME',
            'season_id' => $season->id,
        ]);

        $awayTeam = Team::create([
            'name' => 'Away Team',
            'short_name' => 'AWAY',
            'season_id' => $season->id,
        ]);

        // Create a game
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now()->addDays(1),
            'duration' => 180,
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Make request and get content
        $response = $this->get(route('team.calendar', ['team' => $homeTeam->id]));
        $content = $response->getContent();

        // Verify ICS structure
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
        $this->assertStringContainsString('VERSION:2.0', $content);
        $this->assertStringContainsString('PRODID:-//Baseball Stats//Team Calendar//EN', $content);
    }

    /**
     * Test that event contains correct team and game info
     */
    public function test_ics_event_contains_game_info()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create teams
        $homeTeam = Team::create([
            'name' => 'Home Team',
            'short_name' => 'HOME',
            'season_id' => $season->id,
        ]);

        $awayTeam = Team::create([
            'name' => 'Away Team',
            'short_name' => 'AWAY',
            'season_id' => $season->id,
        ]);

        // Create a game
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now()->addDays(1),
            'duration' => 180,
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Make request and get content
        $response = $this->get(route('team.calendar', ['team' => $homeTeam->id]));
        $content = $response->getContent();

        // Verify event contains team names and location
        $this->assertStringContainsString('Home Team', $content);
        $this->assertStringContainsString('Away Team', $content);
        $this->assertStringContainsString('Test Stadium', $content);
        $this->assertStringContainsString('SUMMARY:Home:', $content); // Home team perspective
    }

    /**
     * Test that multiple games are included in calendar
     */
    public function test_multiple_games_in_calendar()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create teams
        $homeTeam = Team::create([
            'name' => 'Home Team',
            'short_name' => 'HOME',
            'season_id' => $season->id,
        ]);

        $awayTeam = Team::create([
            'name' => 'Away Team',
            'short_name' => 'AWAY',
            'season_id' => $season->id,
        ]);

        $anotherTeam = Team::create([
            'name' => 'Another Team',
            'short_name' => 'ANOTHER',
            'season_id' => $season->id,
        ]);

        // Create multiple games
        $game1 = new Game([
            'location' => 'Stadium 1',
            'firstPitch' => now()->addDays(1),
            'duration' => 180,
        ]);
        $game1->home = $homeTeam->id;
        $game1->away = $awayTeam->id;
        $game1->save();

        $game2 = new Game([
            'location' => 'Stadium 2',
            'firstPitch' => now()->addDays(2),
            'duration' => 180,
        ]);
        $game2->home = $anotherTeam->id;
        $game2->away = $homeTeam->id;
        $game2->save();

        // Make request and get content
        $response = $this->get(route('team.calendar', ['team' => $homeTeam->id]));
        $content = $response->getContent();

        // Count VEVENT occurrences - should be 2
        $eventCount = substr_count($content, 'BEGIN:VEVENT');
        $this->assertEquals(2, $eventCount);

        // Verify both stadiums are mentioned
        $this->assertStringContainsString('Stadium 1', $content);
        $this->assertStringContainsString('Stadium 2', $content);
    }

    /**
     * Test that away team events show correct perspective
     */
    public function test_away_team_perspective()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create teams
        $homeTeam = Team::create([
            'name' => 'Home Team',
            'short_name' => 'HOME',
            'season_id' => $season->id,
        ]);

        $awayTeam = Team::create([
            'name' => 'Away Team',
            'short_name' => 'AWAY',
            'season_id' => $season->id,
        ]);

        // Create a game
        $game = new Game([
            'location' => 'Test Stadium',
            'firstPitch' => now()->addDays(1),
            'duration' => 180,
        ]);
        $game->home = $homeTeam->id;
        $game->away = $awayTeam->id;
        $game->save();

        // Make request for away team
        $response = $this->get(route('team.calendar', ['team' => $awayTeam->id]));
        $content = $response->getContent();

        // Verify event shows "Away:" perspective
        $this->assertStringContainsString('SUMMARY:Away:', $content);
    }

    /**
     * Test that empty team calendar is valid
     */
    public function test_empty_team_calendar()
    {
        // Create a season
        $season = Season::create([
            'name' => 'Test Season',
        ]);

        // Create a team with no games
        $team = Team::create([
            'name' => 'Empty Team',
            'short_name' => 'EMPTY',
            'season_id' => $season->id,
        ]);

        // Make request to calendar route
        $response = $this->get(route('team.calendar', ['team' => $team->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
        // Should not have any events
        $this->assertStringNotContainsString('BEGIN:VEVENT', $content);
    }
}
