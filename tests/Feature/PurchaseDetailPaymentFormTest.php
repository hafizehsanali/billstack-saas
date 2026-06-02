<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseDetailPaymentFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_detail_shows_inline_payment_form_without_add_payment_button(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Main Supplier',
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-INLINE-PAYMENT',
            'purchase_date' => '2026-06-01',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $this
            ->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee('Purchase #PUR-INLINE-PAYMENT')
            ->assertSee('Supplier Ledger')
            ->assertSee('Print')
            ->assertSee('PDF')
            ->assertSee('Record Supplier Payment')
            ->assertSee('Supplier Return')
            ->assertSee('Save Payment')
            ->assertDontSee('Add Payment');
    }

    public function test_supplier_payment_from_purchase_detail_returns_to_purchase_page(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Main Supplier',
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-INLINE-PAYMENT-STORE',
            'purchase_date' => '2026-06-01',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $this
            ->from(route('purchases.show', $purchase))
            ->post(route('supplier-payments.store'), [
                'supplier_id' => $supplier->id,
                'purchase_id' => $purchase->id,
                'source' => 'purchase_detail',
                'payment_date' => '2026-06-02 10:00:00',
                'amount' => 400,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'status' => 'partial',
        ]);
    }

    private function ownerUser(): User
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => uniqid('demo-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        return $user;
    }
}
