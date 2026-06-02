<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_center_lists_low_stock_products(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        Product::create([
            'name' => 'Out Item',
            'purchase_price' => 50,
            'selling_price' => 80,
            'stock_quantity' => 0,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'name' => 'Low Item',
            'purchase_price' => 60,
            'selling_price' => 90,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'name' => 'Healthy Item',
            'purchase_price' => 70,
            'selling_price' => 100,
            'stock_quantity' => 12,
            'low_stock_alert' => 5,
        ]);

        $this
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('Out Item')
            ->assertSee('Low Item')
            ->assertDontSee('Healthy Item');
    }
}
