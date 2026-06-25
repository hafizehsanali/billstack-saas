<?php

namespace Tests\Feature;

use App\Models\BusinessPreset;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\BusinessPresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_settings_can_be_updated(): void
    {
        $this->seed(BusinessPresetSeeder::class);
        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();
        $pharmacy = BusinessPreset::where('slug', BusinessPreset::PHARMACY)->firstOrFail();

        $tenant = Tenant::create([
            'name' => 'Old Store',
            'slug' => 'old-store',
            'business_preset_id' => $generalStore->id,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user);

        $this
            ->put(route('settings.business.update'), [
                'name' => 'Updated Store',
                'email' => 'store@example.com',
                'phone' => '03000000000',
                'address' => 'Main Market',
                'business_preset_id' => $pharmacy->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Store',
            'email' => 'store@example.com',
            'phone' => '03000000000',
            'address' => 'Main Market',
            'business_preset_id' => $generalStore->id,
        ]);
    }
}
