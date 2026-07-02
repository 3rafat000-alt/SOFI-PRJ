<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Tests\TestCase;

class AgencyApiTest extends TestCase
{
    private User $user;
    private Agency $agency;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, SubscriptionPlanSeeder::class]);

        // Create agency owner with agency role
        $this->user = User::factory()->create();
        $this->user->assignRole('agency');

        // Create agency and assign owner
        $this->agency = Agency::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        // Assign agency_user relationship
        $this->user->agency_id = $this->agency->id;
        $this->user->save();

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    protected function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ─────────────── AUTH GUARD ───────────────

    public function test_agency_routes_require_auth(): void
    {
        $response = $this->getJson('/api/v1/agency/dashboard/stats');
        $response->assertStatus(401);
    }

    // ─────────────── DASHBOARD ───────────────

    public function test_can_get_dashboard_stats(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // ─────────────── PROPERTIES ───────────────

    public function test_can_list_agency_properties(): void
    {
        Property::factory()->count(2)->create(['agency_id' => $this->agency->id]);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/properties');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_property(): void
    {
        $type  = PropertyType::factory()->create();
        $gov   = Governorate::factory()->create();
        $area  = Area::factory()->create(['governorate_id' => $gov->id]);
        $agent = Agent::factory()->create(['agency_id' => $this->agency->id]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/v1/agency/properties', [
                'title_ar'        => 'فيلا فاخرة للبيع',
                'title_en'        => 'Luxury Villa for Sale',
                'property_type_id'=> $type->id,
                'purpose'         => 'sale',
                'price'           => 150000,
                'currency'        => 'USD',
                'area_sqm'        => 300,
                'bedrooms'        => 5,
                'bathrooms'       => 4,
                'governorate_id'  => $gov->id,
                'area_id'         => $area->id,
                'agent_id'        => $agent->id,
                'description_ar'  => 'وصف العقار باللغة العربية',
                'description_en'  => 'Property description in English',
                'address_ar'      => 'دمشق، المزة',
                'address_en'      => 'Damascus, Mazzeh',
            ]);

        $response->assertStatus(201);
    }

    // ─────────────── AGENTS ───────────────

    public function test_can_list_agency_agents(): void
    {
        Agent::factory()->count(2)->create(['agency_id' => $this->agency->id]);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/agents');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_agent(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/v1/agency/agents', [
                'display_name' => 'وكيل جديد',
                'email'        => 'agent@test.com',
                'phone'        => '0999123456',
                'bio_ar'       => 'سيرة ذاتية',
                'bio_en'       => 'Bio in English',
                'license_no'   => 'LIC-1234567',
            ]);

        $response->assertStatus(201);
    }

    // ─────────────── SUBSCRIPTION ───────────────

    public function test_can_get_subscription_info(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/subscription');

        $response->assertStatus(200);
    }

    // ─────────────── PROFILE ───────────────

    public function test_can_get_agency_profile(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->agency->id);
    }

    public function test_can_update_agency_profile(): void
    {
        $response = $this->withHeaders($this->headers())
            ->putJson('/api/v1/agency/profile', [
                'name'          => 'الوكالة المحدثة',
                'description_ar'=> 'وصف محدث',
                'phone'         => '0999000000',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('agencies', [
            'id'   => $this->agency->id,
            'name' => 'الوكالة المحدثة',
        ]);
    }

    // ─────────────── DEALS ───────────────

    public function test_can_list_deals(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/deals');

        $response->assertStatus(200);
    }

    // ─────────────── PAYMENTS ───────────────

    public function test_can_list_payments(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/payments');

        $response->assertStatus(200);
    }

    // ─────────────── CHAT ───────────────

    public function test_can_list_conversations(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/conversations');

        $response->assertStatus(200);
    }

    public function test_can_get_unread_count(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/chat/unread-count');

        $response->assertStatus(200);
    }

    // ─────────────── QUICK REPLIES ───────────────

    public function test_can_list_quick_replies(): void
    {
        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/agency/quick-replies');

        $response->assertStatus(200);
    }
}
