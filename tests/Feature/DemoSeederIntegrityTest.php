<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_data_respects_tenant_ownership(): void
    {
        $this->seed();

        $this->assertSame(3, Tenant::count());
        $this->assertSame(7, User::whereNotNull('tenant_id')->count());
        $this->assertSame(1, User::where('is_platform_admin', true)->count());

        User::whereNotNull('tenant_id')
            ->each(fn (User $user) => $this->assertNotNull($user->tenant));

        Purchase::withoutGlobalScopes()
            ->with(['supplier', 'items.product'])
            ->each(function (Purchase $purchase): void {
                $this->assertSame($purchase->tenant_id, $purchase->supplier->tenant_id);

                foreach ($purchase->items as $item) {
                    $this->assertSame($purchase->tenant_id, $item->product->tenant_id);
                }
            });

        SupplierPayment::withoutGlobalScopes()
            ->with(['supplier', 'purchase'])
            ->each(function (SupplierPayment $payment): void {
                $this->assertSame($payment->tenant_id, $payment->supplier->tenant_id);
                $this->assertSame($payment->tenant_id, $payment->purchase->tenant_id);
            });
    }

    public function test_demo_catalog_includes_business_ready_products_and_unit_conversions(): void
    {
        $this->seed();

        $tenant = Tenant::firstOrFail();

        $rice = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'basmati-rice')
            ->firstOrFail();

        $this->assertSame('Basmati Rice', $rice->name);
        $this->assertFalse($rice->has_variants);

        $riceVariant = ProductVariant::withoutGlobalScopes()
            ->with(['unit', 'purchaseUnit'])
            ->where('product_id', $rice->id)
            ->firstOrFail();

        $this->assertSame('Kilogram', $riceVariant->unit->name);
        $this->assertSame('Bag', $riceVariant->purchaseUnit->name);
        $this->assertSame('50.000', (string) $riceVariant->purchase_unit_factor);

        $softDrink = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'soft-drink-500ml')
            ->firstOrFail();

        $this->assertTrue($softDrink->has_variants);
        $this->assertSame(3, $softDrink->variants()->count());
    }
}
