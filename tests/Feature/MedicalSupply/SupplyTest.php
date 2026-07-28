<?php

namespace Tests\Feature\MedicalSupply;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProviderProfile;
use App\Models\MedicalSupplyStaff;
use App\Models\MedicalSupplySetting;
use App\Models\MedicalSupplyInventory;
use App\Models\MedicalSupplyOrder;
use App\Enums\ProviderType;

class SupplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys=0;');

        $this->provider = ProviderProfile::create([
            'user_id' => User::factory()->create()->id,
            'commercial_name' => 'Test Medical Supply',
            'type' => ProviderType::MEDICAL_SUPPLY,
            'rif' => 'J-123456789',
        ]);

        $this->managerUser = User::factory()->create();
        MedicalSupplyStaff::create([
            'user_id' => $this->managerUser->id,
            'provider_profile_id' => $this->provider->id,
            'role' => 'MANAGER',
        ]);

        $this->salesRepUser = User::factory()->create();
        MedicalSupplyStaff::create([
            'user_id' => $this->salesRepUser->id,
            'provider_profile_id' => $this->provider->id,
            'role' => 'SALES_REP',
        ]);
    }

    public function test_sales_rep_cannot_update_settings()
    {
        $response = $this->actingAs($this->salesRepUser, 'user_api')
                         ->putJson('/api/v1/medical-supply/settings', [
                             'is_24_hours' => true,
                         ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_update_settings()
    {
        $response = $this->actingAs($this->managerUser, 'user_api')
                         ->putJson('/api/v1/medical-supply/settings', [
                             'is_24_hours' => true,
                             'working_days' => ['Monday', 'Tuesday'],
                             'auto_matching_enabled' => true,
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('medical_supply_settings', [
            'provider_profile_id' => $this->provider->id,
            'is_24_hours' => true,
            'auto_matching_enabled' => true,
        ]);
    }

    public function test_auto_match_flow()
    {
        // Setup Setting
        MedicalSupplySetting::create([
            'provider_profile_id' => $this->provider->id,
            'auto_matching_enabled' => true,
        ]);

        // Setup Inventory
        MedicalSupplyInventory::create([
            'provider_profile_id' => $this->provider->id,
            'item_name' => 'Surgical Mask',
            'sku' => 'MSK-001',
            'price_usd' => 10,
            'price_bs' => 360,
            'stock' => 100,
            'is_active' => true,
        ]);

        // Create Order
        $order = MedicalSupplyOrder::create([
            'patient_id' => 1,
            'prescribing_doctor_id' => 1,
            'clinic_branch_id' => 1,
            'surgical_planning_id' => 1, // Dummy ID, usually nullable or we just ignore
            'status' => 'PENDING',
            'supplies_list' => ['MSK-001'],
        ]);

        // Trigger AutoMatch
        $response = $this->actingAs($this->managerUser, 'user_api')
                         ->postJson("/api/v1/medical-supply/quotes/auto-match/{$order->id}");

        $response->assertStatus(200);

        // Verify Quote Offer created
        $this->assertDatabaseHas('medical_supply_quote_offers', [
            'medical_supply_order_id' => $order->id,
            'provider_profile_id' => $this->provider->id,
            'status' => 'PENDING',
        ]);
    }
}
