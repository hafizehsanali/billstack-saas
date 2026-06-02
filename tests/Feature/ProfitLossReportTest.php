<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_loss_report_calculates_sales_cogs_expenses_and_profit(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Report Customer',
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Report Product',
            'purchase_price' => 100,
            'selling_price' => 200,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-REPORT',
            'sale_date' => '2026-06-02',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 1000,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 200,
            'total' => 1000,
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'title' => 'Shop Rent',
            'category' => 'Rent',
            'amount' => 200,
            'expense_date' => '2026-06-02',
        ]);

        $this
            ->get(route('reports.profit-loss', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('Rs 1,000.00')
            ->assertSee('Rs 500.00')
            ->assertSee('Rs 200.00')
            ->assertSee('Rs 300.00');
    }
}
