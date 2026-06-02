<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_updated(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Old Customer',
            'phone' => '111',
            'email' => 'old@example.com',
            'address' => 'Old Address',
            'opening_balance' => 0,
        ]);

        $this
            ->put(route('customers.update', $customer), [
                'name' => 'Updated Customer',
                'phone' => '222',
                'email' => 'updated@example.com',
                'address' => 'Updated Address',
                'opening_balance' => 500,
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'phone' => '222',
            'email' => 'updated@example.com',
            'address' => 'Updated Address',
            'opening_balance' => 500,
        ]);
    }
}
