<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_sales_report_shows_business_summary_and_invoice_links(): void
    {
        $user = $this->userWithTenant();
        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Report Customer',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-DAILY-POLISH',
            'sale_date' => now()->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'status' => 'partial',
        ]);

        $this
            ->get(route('reports.daily-sales'))
            ->assertOk()
            ->assertSee('Sales Total')
            ->assertSee('Customer Owes Us')
            ->assertSee('Rs 600.00')
            ->assertSee(route('invoices.show', $invoice), false);
    }

    public function test_stock_reports_show_summary_statuses_and_ledger_links(): void
    {
        $user = $this->userWithTenant();
        $this->actingAs($user);

        $product = Product::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Low Stock Product',
            'sku' => 'LOW-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 2,
            'low_stock_alert' => 5,
        ]);

        $this
            ->get(route('reports.stock'))
            ->assertOk()
            ->assertSee('Stock Value at Cost')
            ->assertSee('Low Stock')
            ->assertSee('Rs 200.00')
            ->assertSee(route('products.stock-ledger', $product), false);

        $this
            ->get(route('reports.low-stock'))
            ->assertOk()
            ->assertSee('Needs Attention')
            ->assertSee('Products at or below their alert level')
            ->assertSee('Low Stock Product')
            ->assertSee('Stock Ledger');
    }

    private function userWithTenant(): User
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
