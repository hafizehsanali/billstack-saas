<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Services\StockLedgerService;
use App\Services\TenantUsageLimitService;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->latest()
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('products.create', compact('categories'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();

        return view('products.edit', compact(
            'product',
            'categories'
        ));
    }

    public function stockLedger(Product $product)
    {
        $product->load('category');

        $movements = $product->stockMovements()
            ->latest('movement_date')
            ->latest()
            ->paginate(25);

        $movements->getCollection()->transform(function (StockMovement $movement) {
            $movement->display_type = $this->stockMovementLabel($movement->type);
            $movement->reference_url = $this->stockMovementReferenceUrl($movement);

            return $movement;
        });

        return view('products.stock-ledger', compact(
            'product',
            'movements'
        ));
    }

    private function stockMovementLabel(string $type): string
    {
        return match ($type) {
            'opening_stock' => 'Opening Stock',
            'purchase' => 'Purchased from Supplier',
            'purchase_reversal' => 'Purchase Update Reversal',
            'purchase_cancel' => 'Purchase Cancelled',
            'purchase_return' => 'Returned to Supplier',
            'sale' => 'Sold to Customer',
            'sale_cancel' => 'Sale Cancelled',
            'sales_return' => 'Customer Return',
            'stock_adjustment_in' => 'Stock Added Manually',
            'stock_adjustment_out' => 'Stock Reduced Manually',
            default => str($type)->replace('_', ' ')->title()->toString(),
        };
    }

    private function stockMovementReferenceUrl(StockMovement $movement): ?string
    {
        if (! $movement->source_type || ! $movement->source_id) {
            return null;
        }

        return match ($movement->source_type) {
            InvoiceItem::class => $this->invoiceUrl($movement->source_id),
            PurchaseItem::class => $this->purchaseUrl($movement->source_id),
            SalesReturnItem::class => $this->salesReturnInvoiceUrl($movement->source_id),
            PurchaseReturnItem::class => $this->supplierReturnPurchaseUrl($movement->source_id),
            default => null,
        };
    }

    private function invoiceUrl(int $invoiceItemId): ?string
    {
        $invoiceId = InvoiceItem::whereKey($invoiceItemId)->value('invoice_id');

        return $invoiceId ? route('invoices.show', $invoiceId) : null;
    }

    private function purchaseUrl(int $purchaseItemId): ?string
    {
        $purchaseId = PurchaseItem::whereKey($purchaseItemId)->value('purchase_id');

        return $purchaseId ? route('purchases.show', $purchaseId) : null;
    }

    private function salesReturnInvoiceUrl(int $salesReturnItemId): ?string
    {
        $returnItem = SalesReturnItem::with('salesReturn:id,invoice_id')
            ->find($salesReturnItemId);

        return $returnItem?->salesReturn?->invoice_id
            ? route('invoices.show', $returnItem->salesReturn->invoice_id)
            : null;
    }

    private function supplierReturnPurchaseUrl(int $purchaseReturnItemId): ?string
    {
        $returnItem = PurchaseReturnItem::with('purchaseReturn:id,purchase_id')
            ->find($purchaseReturnItemId);

        return $returnItem?->purchaseReturn?->purchase_id
            ? route('purchases.show', $returnItem->purchaseReturn->purchase_id)
            : null;
    }

    public function store(
        StoreProductRequest $request,
        TenantUsageLimitService $usageLimits
    )
    {
        $usageLimits->assertCanCreateProduct(auth()->user()->tenant);

        $data = $request->validated();

        $product = Product::create([

            'tenant_id' => auth()->user()->tenant_id,

            'category_id' => $data['category_id'],

            'name' => $data['name'],

            'sku' => $data['sku'],

            'barcode' => $data['barcode'] ?? null,

            'purchase_price' => $data['purchase_price'],

            'selling_price' => $data['selling_price'],

            'stock_quantity' => $data['stock_quantity'],

            'low_stock_alert' => $data['low_stock_alert'],

        ]);

        if ($product->stock_quantity > 0) {
            app(StockLedgerService::class)->record(
                $product,
                'opening_stock',
                $product->stock_quantity,
                [
                    'direction' => 'in',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $product->selling_price,
                    'notes' => 'Opening stock from product creation.',
                ]
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $oldStock = $product->stock_quantity;

        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'purchase_price' => $data['purchase_price'],
            'selling_price' => $data['selling_price'],
            'stock_quantity' => $data['stock_quantity'],
            'low_stock_alert' => $data['low_stock_alert'],
        ]);

        $stockDifference = $product->stock_quantity - $oldStock;

        if ($stockDifference !== 0) {
            app(StockLedgerService::class)->record(
                $product,
                $stockDifference > 0 ? 'stock_adjustment_in' : 'stock_adjustment_out',
                abs($stockDifference),
                [
                    'direction' => $stockDifference > 0 ? 'in' : 'out',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $product->selling_price,
                    'notes' => 'Manual stock adjustment from product update.',
                ]
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully.'
            );
    }
}
