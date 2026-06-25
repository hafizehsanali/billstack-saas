<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductSerialNumber;
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
                $quantity = (float) $item['quantity'];
                $price = (float) $item['price'];

                if (! $variant->product->allowsDecimalQuantity() && floor($quantity) !== $quantity) {
                    throw ValidationException::withMessages([
                        'products' => $variant->display_name.' must be sold in whole quantities.',
                    ]);
                }

                if ($variant->product->track_serial && count($this->serialNumbers($item['serial_numbers'] ?? '')) !== (int) $quantity) {
                    throw ValidationException::withMessages([
                        'products' => $variant->display_name.' requires one available serial number for each sold unit.',
                    ]);
                }

                $variants->put($variant->id, $variant);
                $requestedQuantities[$variant->id] =
                    ($requestedQuantities[$variant->id] ?? 0) + $quantity;

                $subtotal += $quantity * $price;
            }

            foreach ($requestedQuantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if ($variant->product->tracksStock() && $variant->track_stock && $quantity > $variant->stock_quantity) {
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
                $quantity = (float) $item['quantity'];
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

                if ($product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->consumeBatches(
                        $variant,
                        $quantity,
                        $item['batch_number'] ?? null
                    );
                    $catalog->adjustStock($variant, $quantity, 'out');

                    if ($batchMovements === []) {
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
                    } else {
                        foreach ($batchMovements as $batchMovement) {
                            $stockLedger->record($variant, 'sale', $batchMovement['quantity'], [
                                'direction' => 'out',
                                'unit_cost' => $product->purchase_price,
                                'unit_price' => $price,
                                'stock_after' => $variant->stock_quantity,
                                'source_type' => InvoiceItem::class,
                                'source_id' => $invoiceItem->id,
                                'reference_no' => $invoice->invoice_no,
                                'product_batch_id' => $batchMovement['batch']->id,
                                'batch_number' => $batchMovement['batch']->batch_number,
                                'expiry_date' => $batchMovement['batch']->expiry_date,
                                'movement_date' => $invoice->sale_date,
                            ]);
                        }
                    }
                }

                if ($product->track_serial) {
                    $this->markSerialNumbersSold(
                        $product,
                        $variant,
                        $invoiceItem,
                        $this->serialNumbers($item['serial_numbers'] ?? '')
                    );
                }
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

                if (! $item->product_variant_id) {
                    $item->product->update([
                        'stock_quantity' => (float) $item->product->stock_quantity + (float) $item->quantity,
                    ]);

                    $stockLedger->record($item->product, 'sale_cancel', $item->quantity, [
                        'direction' => 'in',
                        'unit_cost' => $item->product->purchase_price,
                        'unit_price' => $item->price,
                        'stock_after' => $item->product->stock_quantity,
                        'source_type' => InvoiceItem::class,
                        'source_id' => $item->id,
                        'reference_no' => $lockedInvoice->invoice_no,
                        'movement_date' => now()->toDateString(),
                        'notes' => 'Invoice cancelled.',
                    ]);

                    continue;
                }

                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->restoreBatchesFromInvoiceItem($item, (float) $item->quantity);
                    $catalog->adjustStock($variant, $item->quantity, 'in');

                    if ($batchMovements === []) {
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
                    } else {
                        foreach ($batchMovements as $batchMovement) {
                            $stockLedger->record($variant, 'sale_cancel', $batchMovement['quantity'], [
                                'direction' => 'in',
                                'unit_cost' => $item->product->purchase_price,
                                'unit_price' => $item->price,
                                'stock_after' => $variant->stock_quantity,
                                'source_type' => InvoiceItem::class,
                                'source_id' => $item->id,
                                'reference_no' => $lockedInvoice->invoice_no,
                                'product_batch_id' => $batchMovement['batch']->id,
                                'batch_number' => $batchMovement['batch']->batch_number,
                                'expiry_date' => $batchMovement['batch']->expiry_date,
                                'movement_date' => now()->toDateString(),
                                'notes' => 'Invoice cancelled.',
                            ]);
                        }
                    }

                    $item->product->refresh();
                    $item->product->syncFromVariants();
                }

                if ($variant->product->track_serial) {
                    $this->releaseSerialNumbers($item, (float) $item->quantity);
                }
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

    private function serialNumbers(string|array|null $value): array
    {
        $numbers = is_array($value) ? $value : preg_split('/[\r\n,]+/', (string) $value);

        return collect($numbers)
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function markSerialNumbersSold(Product $product, $variant, InvoiceItem $invoiceItem, array $serialNumbers): void
    {
        foreach ($serialNumbers as $serialNumber) {
            $serial = ProductSerialNumber::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->where('serial_number', $serialNumber)
                ->where('status', ProductSerialNumber::STATUS_AVAILABLE)
                ->lockForUpdate()
                ->first();

            if (! $serial) {
                throw ValidationException::withMessages([
                    'products' => "Serial number {$serialNumber} is not available.",
                ]);
            }

            $serial->update([
                'status' => ProductSerialNumber::STATUS_SOLD,
                'invoice_item_id' => $invoiceItem->id,
            ]);
        }
    }

    private function releaseSerialNumbers(InvoiceItem $invoiceItem, float $quantity): void
    {
        ProductSerialNumber::query()
            ->where('invoice_item_id', $invoiceItem->id)
            ->where('status', ProductSerialNumber::STATUS_SOLD)
            ->limit((int) $quantity)
            ->get()
            ->each(function (ProductSerialNumber $serial): void {
                $serial->update([
                    'status' => ProductSerialNumber::STATUS_AVAILABLE,
                    'invoice_item_id' => null,
                ]);
            });
    }
}
