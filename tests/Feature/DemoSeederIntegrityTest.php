<?php

namespace Tests\Feature;

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
}
