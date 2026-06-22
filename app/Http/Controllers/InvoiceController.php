<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\ProductCatalogService;
use App\Services\StockLedgerService;
use App\Services\TenantUsageLimitService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('customer')
            ->latest()
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::all();

        $products = Product::with(['activeVariants' => fn ($query) => $query->with('unit')->orderByDesc('is_default')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $variants = $products->flatMap->activeVariants;

        return view('invoices.create', compact(
            'customers',
            'products',
            'variants'
        ));
    }

    public function pos()
    {
        $customers = Customer::orderBy('name')->get();

        $products = Product::with(['activeVariants' => fn ($query) => $query->with('unit')->orderByDesc('is_default')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $variants = $products->flatMap->activeVariants;

        return view('invoices.pos', compact(
            'customers',
            'products',
            'variants'
        ));
    }

    public function store(
        StoreInvoiceRequest $request,
        TenantUsageLimitService $usageLimits
    )
    {
        $usageLimits->assertCanCreateInvoice(auth()->user()->tenant);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $stockLedger = app(StockLedgerService::class);
            $catalog = app(ProductCatalogService::class);

            $subtotal = 0;
            $requestedQuantities = [];
            $variants = collect();

            foreach ($data['products'] as $item) {
                $variant = $catalog->resolveVariant(
                    (int) $item['product_id'],
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    true
                );
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];

                $variants->put($variant->id, $variant);
                $requestedQuantities[$variant->id] =
                    ($requestedQuantities[$variant->id] ?? 0) + $quantity;

                $subtotal += $quantity * $price;
            }

            foreach ($requestedQuantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if ($quantity > $variant->stock_quantity) {
                    throw ValidationException::withMessages([
                        'stock' => $variant->display_name.' does not have enough stock.',
                    ]);
                }
            }

            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $extraExpense = (float) ($data['extra_expense'] ?? 0);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $total = max(($subtotal + $tax + $extraExpense) - $discount, 0);

            if ($paidAmount > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount cannot be greater than invoice total.',
                ]);
            }

            $remainingAmount = max($total - $paidAmount, 0);
            $status = 'unpaid';

            if ($paidAmount >= $total && $total > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            $invoice = Invoice::create([
                'tenant_id' => auth()->user()->tenant_id,
                'customer_id' => $data['customer_id'],
                'invoice_no' => $data['invoice_no'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'extra_expense' => $extraExpense,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] as $item) {
                $variant = $catalog->resolveVariant(
                    (int) $item['product_id'],
                    isset($item['product_variant_id']) ? (int) $item['product_variant_id'] : null,
                    true
                );
                $product = $variant->product;
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                $lineTotal = $quantity * $price;
                $regularPrice = (float) ($variant->compare_at_price ?? 0);
                $hasPromotion = $regularPrice > $price;
                $itemSavings = $hasPromotion
                    ? ($regularPrice - $price) * $quantity
                    : 0;

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'regular_price' => $hasPromotion ? $regularPrice : null,
                    'item_savings' => $itemSavings,
                    'total' => $lineTotal,
                ]);

                $catalog->adjustStock($variant, $quantity, 'out');

                $stockLedger->record($variant, 'sale', $quantity, [
                    'direction' => 'out',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $price,
                    'stock_after' => $variant->stock_quantity,
                    'source_type' => InvoiceItem::class,
                    'source_id' => $invoiceItem->id,
                    'reference_no' => $invoice->invoice_no,
                    'movement_date' => $invoice->sale_date,
                ]);
            }

            if ($paidAmount > 0) {
                CustomerPayment::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date' => isset($data['payment_date'])
                        ? Carbon::parse($data['payment_date'])->toDateString()
                        : now()->toDateString(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['payment_notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                'Invoice created successfully.'
            );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items.product',
            'items.variant',
            'items.returnItems',
            'payments',
            'returns.items.product',
            'returns.items.variant',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function cancel(Invoice $invoice)
    {
        if (! $invoice->canBeCancelled()) {
            return back()->withErrors([
                'invoice' => 'Only an unpaid invoice without payments or returns can be cancelled.',
            ]);
        }

        DB::transaction(function () use ($invoice) {
            $lockedInvoice = Invoice::query()
                ->with(['items.product', 'items.variant'])
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if (! $lockedInvoice->canBeCancelled()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Only an unpaid invoice without payments or returns can be cancelled.',
                ]);
            }

            $stockLedger = app(StockLedgerService::class);

            foreach ($lockedInvoice->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $catalog = app(ProductCatalogService::class);
                $variant = $item->variant
                    ?? $catalog->resolveVariant($item->product_id, null, true);
                $catalog->adjustStock($variant, $item->quantity, 'in');

                $stockLedger->record($variant, 'sale_cancel', $item->quantity, [
                    'direction' => 'in',
                    'unit_cost' => $item->product->purchase_price,
                    'unit_price' => $item->price,
                    'stock_after' => $variant->stock_quantity,
                    'source_type' => InvoiceItem::class,
                    'source_id' => $item->id,
                    'reference_no' => $lockedInvoice->invoice_no,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Invoice cancelled.',
                ]);
            }

            $lockedInvoice->update([
                'status' => 'cancelled',
            ]);
        });

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                'Invoice cancelled successfully.'
            );
    }

    public function pdf(Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {

            abort(403, 'Cancelled invoice cannot be downloaded.');
        }
        $invoice->load([
            'customer',
            'items.product',
            'items.variant',
        ]);

        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView(
            'invoices.pdf',
            compact('invoice', 'tenant')
        );

        return $pdf->download(
            $invoice->invoice_no.'.pdf'
        );
    }
}
