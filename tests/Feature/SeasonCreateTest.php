<?php

namespace Tests\Feature;

use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_requires_superuser()
    {
        $user = User::factory()->create(['role' => 'scorer']);

        $response = $this->actingAs($user)->get(route('season.create'));

        $response->assertStatus(403);
    }

    public function test_create_form_accessible_to_superuser()
    {
        $user = User::factory()->create(['role' => 'superuser']);

        $response = $this->actingAs($user)->get(route('season.create'));

        $response->assertStatus(200);
        $response->assertSee('Create New Season');
    }

    public function test_create_form_requires_authentication()
    {
        $response = $this->get(route('season.create'));

        $response->assertStatus(403);
    }

    public function test_superuser_can_create_season()
    {
        $user = User::factory()->create(['role' => 'superuser']);

        $response = $this->actingAs($user)->post(route('season.store'), [
            'name' => 'Spring 2026',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('seasons', ['name' => 'Spring 2026']);
    }

    public function test_non_superuser_cannot_create_season()
    {
        $user = User::factory()->create(['role' => 'scorer']);

        $response = $this->actingAs($user)->post(route('season.store'), [
            'name' => 'Spring 2026',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('seasons', ['name' => 'Spring 2026']);
    }

    public function test_season_name_is_required()
    {
        $user = User::factory()->create(['role' => 'superuser']);

        $response = $this->actingAs($user)->post(route('season.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(0, Season::count());
    }

    public function test_store_redirects_to_team_create_with_season()
    {
        $user = User::factory()->create(['role' => 'superuser']);

        $response = $this->actingAs($user)->post(route('season.store'), [
            'name' => 'Fall 2026',
        ]);

        $season = Season::where('name', 'Fall 2026')->first();
        $response->assertRedirect(route('team.create', ['season' => $season->id]));
    }
}
