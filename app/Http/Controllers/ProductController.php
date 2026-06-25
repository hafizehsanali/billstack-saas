<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\StockLedgerService;
use App\Services\ProductCatalogService;
use App\Services\TenantModuleService;
use App\Services\TenantUsageLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('parent')->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $filters = $request->only([
            'search',
            'category_id',
            'brand_id',
            'stock_status',
            'product_type',
            'active_status',
        ]);

        $products = Product::with(['category', 'brand', 'variants.unit', 'variants.attributeValues.attribute'])
            ->withCount([
                'invoiceItems',
                'purchaseItems',
                'salesReturnItems',
                'purchaseReturnItems',
                'stockMovements',
                'channelListings',
            ])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brands) => $brands->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn ($categories) => $categories->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($categories, $request): void {
                $query->whereIn(
                    'category_id',
                    $this->categoryFilterIds($categories, (int) $request->input('category_id'))
                );
            })
            ->when($request->filled('brand_id'), fn ($query) => $query->where('brand_id', $request->input('brand_id')))
            ->when($request->input('stock_status') === 'in_stock', fn ($query) => $query->whereColumn('stock_quantity', '>', 'low_stock_alert'))
            ->when($request->input('stock_status') === 'low_stock', fn ($query) => $query
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert'))
            ->when($request->input('stock_status') === 'out_of_stock', fn ($query) => $query->where('stock_quantity', '<=', 0))
            ->when($request->input('product_type') === 'simple', fn ($query) => $query->where('has_variants', false))
            ->when($request->input('product_type') === 'variants', fn ($query) => $query->where('has_variants', true))
            ->when($request->input('active_status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('active_status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->get();

        return view('products.index', compact('products', 'categories', 'brands', 'filters'));
    }

    private function categoryFilterIds($categories, int $categoryId): array
    {
        $ids = [$categoryId];

        foreach ($categories->where('parent_id', $categoryId) as $child) {
            $ids = array_merge($ids, $this->categoryFilterIds($categories, $child->id));
        }

        return array_values(array_unique($ids));
    }

    public function create(TenantModuleService $modules)
    {
        $categories = Category::all();
        $brands = Brand::orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $tenant = auth()->user()->tenant;
        $productModeOptions = $modules->productModeOptions($tenant);
        $enabledModuleKeys = $modules->enabledModuleKeys($tenant);

        return view('products.create', compact(
            'categories',
            'brands',
            'attributes',
            'units',
            'productModeOptions',
            'enabledModuleKeys'
        ));
    }

    public function edit(Product $product, TenantModuleService $modules)
    {
        $product->load(['variants.attributeValues', 'images']);
        $categories = Category::all();
        $brands = Brand::orderBy('name')->get();
        $attributes = ProductAttribute::with('values')->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $tenant = auth()->user()->tenant;
        $productModeOptions = $modules->productModeOptions($tenant);
        $enabledModuleKeys = $modules->enabledModuleKeys($tenant);

        if (! array_key_exists($product->product_sale_mode, $productModeOptions)) {
            $productModeOptions[$product->product_sale_mode] = str($product->product_sale_mode)->replace('_', ' ')->headline()->toString();
        }

        return view('products.edit', compact(
            'product',
            'categories',
            'brands',
            'attributes',
            'units',
            'productModeOptions',
            'enabledModuleKeys'
        ));
    }

    public function stockLedger(Product $product)
    {
        $product->load(['category', 'variants.unit']);

        $movements = $product->stockMovements()
            ->with('variant.unit')
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

        app(ProductCatalogService::class)->create($data);

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
        app(ProductCatalogService::class)->update($product, $data);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully.'
            );
    }

    public function destroy(Product $product)
    {
        abort_if(
            $product->tenant_id !== auth()->user()->tenant_id,
            403
        );

        if (! $product->canBeDeleted()) {
            return back()->withErrors([
                'product' => 'Product cannot be deleted because invoice, purchase, return, stock, or online listing history exists.',
            ]);
        }

        DB::transaction(function () use ($product): void {
            $product->variants()->delete();
            $product->images()->delete();
            $product->delete();
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
