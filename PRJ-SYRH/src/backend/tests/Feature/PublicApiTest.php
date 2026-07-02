<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Agency;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Property;
use App\Models\PropertyType;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ─────────────── PROPERTY TYPES ───────────────

    public function test_can_list_property_types(): void
    {
        PropertyType::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/property-types');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // ─────────────── LOCATIONS ───────────────

    public function test_can_list_locations(): void
    {
        Governorate::factory()
            ->count(2)
            ->has(Area::factory()->count(2), 'areas')
            ->create();

        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_list_areas_for_governorate(): void
    {
        $governorate = Governorate::factory()
            ->has(Area::factory()->count(3), 'areas')
            ->create();

        $response = $this->getJson("/api/v1/locations/{$governorate->slug}/areas");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // ─────────────── PROPERTIES ───────────────

    public function test_can_list_published_properties(): void
    {
        Property::factory()->count(3)->published()->create();

        $response = $this->getJson('/api/v1/properties');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_properties_list_excludes_drafts(): void
    {
        Property::factory()->published()->create(['title_ar' => 'متاح']);
        Property::factory()->create(['status' => 'draft', 'published_at' => null, 'title_ar' => 'مسودة']);

        $response = $this->getJson('/api/v1/properties');

        $response->assertStatus(200);
        $titles = collect($response->json('data'))->pluck('title_ar');
        $this->assertContains('متاح', $titles);
        $this->assertNotContains('مسودة', $titles);
    }

    public function test_can_get_property_detail(): void
    {
        $property = Property::factory()->published()->create();

        $response = $this->getJson("/api/v1/properties/{$property->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $property->id);
    }

    public function test_property_detail_returns_404_for_draft(): void
    {
        $property = Property::factory()->create(['status' => 'draft', 'published_at' => null]);

        $response = $this->getJson("/api/v1/properties/{$property->slug}");
        $response->assertStatus(404);
    }

    public function test_can_list_featured_properties(): void
    {
        Property::factory()->count(2)->published()->featured()->create();
        Property::factory()->published()->create(['is_featured' => false]);

        $response = $this->getJson('/api/v1/properties/featured');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ─────────────── AGENCIES ───────────────

    public function test_can_list_agencies(): void
    {
        Agency::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/agencies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_agency_detail(): void
    {
        $agency = Agency::factory()->create();

        $response = $this->getJson("/api/v1/agencies/{$agency->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $agency->id);
    }

    public function test_can_list_agency_properties(): void
    {
        $agency = Agency::factory()->create();
        Property::factory()->count(2)->published()->create(['agency_id' => $agency->id]);

        $response = $this->getJson("/api/v1/agencies/{$agency->id}/properties");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ─────────────── AGENTS ───────────────

    public function test_can_list_agents(): void
    {
        Agent::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/agents');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_agent_detail(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->getJson("/api/v1/agents/{$agent->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $agent->id);
    }

    // ─────────────── TESTIMONIALS ───────────────

    public function test_can_list_testimonials(): void
    {
        $response = $this->getJson('/api/v1/testimonials');

        $response->assertStatus(200);
    }

    // ─────────────── STATS ───────────────

    public function test_can_get_stats(): void
    {
        $response = $this->getJson('/api/v1/stats');

        $response->assertStatus(200);
    }

    // ─────────────── SETTINGS ───────────────

    public function test_can_get_public_settings(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertStatus(200);
    }

    // ─────────────── 404 ───────────────

    public function test_returns_404_for_unknown_property(): void
    {
        $response = $this->getJson('/api/v1/properties/nonexistent-slug-12345');
        $response->assertStatus(404);
    }

    public function test_returns_404_for_unknown_agency(): void
    {
        $response = $this->getJson('/api/v1/agencies/nonexistent-agency');
        $response->assertStatus(404);
    }

}
