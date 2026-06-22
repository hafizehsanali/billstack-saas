<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_create_form_preserves_old_values_and_clear_balance_label(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $this
            ->from(route('customers.create'))
            ->post(route('customers.store'), [
                'name' => '',
                'phone' => '03000000000',
                'opening_balance' => 125,
            ])
            ->assertRedirect(route('customers.create'));

        $this
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('Opening Balance / Previous Due')
            ->assertSee('03000000000')
            ->assertSee('125');
    }

    public function test_supplier_edit_form_uses_consistent_layout_and_payable_label(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Form Supplier',
            'opening_balance' => 300,
        ]);

        $this
            ->get(route('suppliers.edit', $supplier))
            ->assertOk()
            ->assertSee('Edit Supplier')
            ->assertSee('Opening Balance / Previous Payable')
            ->assertSee('Update Supplier');
    }

    public function test_product_create_form_uses_old_values_and_opening_stock_label(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Hardware',
        ]);

        $this
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => $category->id,
                'name' => '',
                'sku' => 'HAM-001',
                'purchase_price' => 100,
                'selling_price' => 150,
                'stock_quantity' => 5,
                'low_stock_alert' => 2,
            ])
            ->assertRedirect(route('products.create'));

        $this
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Basic Information')
            ->assertSee('Product Image')
            ->assertSee('Customer Unit')
            ->assertSee('Supplier Unit')
            ->assertSee('Wholesale / Bulk Pricing')
            ->assertSee('This product has variants')
            ->assertDontSee('Enable Multiple Barcodes')
            ->assertDontSee('Track Expiry Date')
            ->assertSee('Product Summary')
            ->assertSee('removedVariantCombinations.clear()', false)
            ->assertSee('generateVariantCombinations()', false)
            ->assertSee('generatedVariantName', false)
            ->assertSee('generatedVariantSku', false)
            ->assertSee('Opening Stock')
            ->assertSee('HAM-001')
            ->assertSee('100')
            ->assertSee('5');
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
