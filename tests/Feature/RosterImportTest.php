<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RosterImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_form_requires_authentication()
    {
        $response = $this->get(route('roster.import'));

        $response->assertRedirect(route('login'));
    }

    public function test_import_form_accessible_to_authenticated_users()
    {
        $user = User::factory()->create(['role' => 'scorer']);

        $response = $this->actingAs($user)->get(route('roster.import'));

        $response->assertStatus(200);
    }

    public function test_csv_import_with_default_team_and_season()
    {
        $user = User::factory()->create(['role' => 'scorer']);
        $season = Season::factory()->create(['name' => 'Test Season']);
        $team = Team::factory()->create([
            'name' => 'Test Team',
            'season_id' => $season->id,
        ]);
        
        // Grant user access to team
        $user->teams()->attach($team->id);

        $csvContent = "First Name,Last Name,Number\nJohn,Doe,10\nJane,Smith,15";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('roster.import.process'), [
            'file' => $file,
            'team_id' => $team->id,
            'season_id' => $season->id,
            'columns_in_file' => false,
        ]);

        // Debug: Check response
        if (session('error')) {
            $this->fail('Import failed with error: ' . session('error'));
        }

        $response->assertSessionHas('success');
        $this->assertStringContainsString('Successfully imported 2 player(s)', session('success'));
        
        $this->assertDatabaseHas('people', ['firstName' => 'John', 'lastName' => 'Doe']);
        $this->assertDatabaseHas('people', ['firstName' => 'Jane', 'lastName' => 'Smith']);
        
        $johnPerson = Person::where('firstName', 'John')->where('lastName', 'Doe')->first();
        $this->assertDatabaseHas('players', [
            'person_id' => $johnPerson->id,
            'team_id' => $team->id,
            'number' => '10',
            'game_id' => 0,
        ]);
    }

    public function test_csv_import_with_columns_in_file()
    {
        $user = User::factory()->create(['role' => 'superuser']);
        $season = Season::factory()->create(['name' => 'Test Season']);
        $team = Team::factory()->create([
            'name' => 'Test Team',
            'short_name' => 'TT',
            'season_id' => $season->id,
        ]);

        $csvContent = "First Name,Last Name,Number,Team,Season\nJohn,Doe,10,Test Team,Test Season\nJane,Smith,,TT,Test Season";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('roster.import.process'), [
            'file' => $file,
            'columns_in_file' => true,
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('people', ['firstName' => 'John', 'lastName' => 'Doe']);
        $this->assertDatabaseHas('people', ['firstName' => 'Jane', 'lastName' => 'Smith']);
        
        $johnPerson = Person::where('firstName', 'John')->where('lastName', 'Doe')->first();
        $this->assertDatabaseHas('players', [
            'person_id' => $johnPerson->id,
            'team_id' => $team->id,
            'number' => '10',
        ]);
        
        $janePerson = Person::where('firstName', 'Jane')->where('lastName', 'Smith')->first();
        $this->assertDatabaseHas('players', [
            'person_id' => $janePerson->id,
            'team_id' => $team->id,
            'number' => null,
        ]);
    }

    public function test_unauthorized_user_cannot_import_to_team()
    {
        $user = User::factory()->create(['role' => 'scorer']);
        $season = Season::factory()->create(['name' => 'Test Season']);
        $team = Team::factory()->create([
            'name' => 'Test Team',
            'season_id' => $season->id,
        ]);

        $csvContent = "First Name,Last Name,Number\nJohn,Doe,10";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('roster.import.process'), [
            'file' => $file,
            'team_id' => $team->id,
            'season_id' => $season->id,
            'columns_in_file' => false,
        ]);

        // Should complete with error message about authorization
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Not authorized', session('success'));
    }

    public function test_season_admin_can_import_to_teams_in_their_season()
    {
        $user = User::factory()->create(['role' => 'scorer']);
        $season = Season::factory()->create(['name' => 'Test Season']);
        $team = Team::factory()->create([
            'name' => 'Test Team',
            'season_id' => $season->id,
        ]);
        
        // Grant user season admin access
        $user->seasons()->attach($season->id);

        $csvContent = "First Name,Last Name,Number\nJohn,Doe,10";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('roster.import.process'), [
            'file' => $file,
            'team_id' => $team->id,
            'season_id' => $season->id,
            'columns_in_file' => false,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('people', ['firstName' => 'John', 'lastName' => 'Doe']);
    }

    public function test_duplicate_player_updates_number()
    {
        $user = User::factory()->create(['role' => 'superuser']);
        $season = Season::factory()->create(['name' => 'Test Season']);
        $team = Team::factory()->create([
            'name' => 'Test Team',
            'season_id' => $season->id,
        ]);
        
        // Create existing person and player
        $person = Person::factory()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);
        Player::create([
            'person_id' => $person->id,
            'team_id' => $team->id,
            'number' => '5',
            'game_id' => 0,
        ]);

        $csvContent = "First Name,Last Name,Number\nJohn,Doe,10";
        $file = UploadedFile::fake()->createWithContent('roster.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('roster.import.process'), [
            'file' => $file,
            'team_id' => $team->id,
            'season_id' => $season->id,
            'columns_in_file' => false,
        ]);

        $response->assertSessionHas('success');
        
        // Player should be updated, not duplicated
        $this->assertEquals(1, Player::where('person_id', $person->id)
            ->where('team_id', $team->id)
            ->where('game_id', 0)
            ->count());
            
        $this->assertDatabaseHas('players', [
            'person_id' => $person->id,
            'team_id' => $team->id,
            'number' => '10',
            'game_id' => 0,
        ]);
    }
}
