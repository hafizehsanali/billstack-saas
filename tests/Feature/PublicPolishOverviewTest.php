<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicPolishOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_index_shows_product_counts(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Hardware',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Hammer',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
        ]);

        $this
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Total Categories')
            ->assertSee('Product groups used for inventory organization')
            ->assertSee('Hardware')
            ->assertSee('1');
    }

    public function test_expense_index_uses_no_wrap_actions_and_clear_empty_state(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        Expense::create([
            'tenant_id' => $user->tenant_id,
            'title' => 'Shop Rent',
            'category' => 'Rent',
            'amount' => 25000,
            'expense_date' => '2026-06-03',
        ]);

        $this
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('d-inline-flex gap-1 flex-nowrap', false)
            ->assertSee('Shop Rent')
            ->assertSee('Rs 25,000.00');
    }

    public function test_supplier_payment_history_has_clear_summary(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Payment Supplier',
            'phone' => '03000000000',
        ]);

        SupplierPayment::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'amount' => 5000,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-03',
            'reference_no' => 'PAY-001',
        ]);

        $this
            ->get(route('supplier-payments.index', $supplier))
            ->assertOk()
            ->assertSee('Supplier Payment History')
            ->assertSee('Record Supplier Payment')
            ->assertSee('Total Paid to Supplier')
            ->assertSee('Rs 5,000.00')
            ->assertSee('Open Ledger');
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
