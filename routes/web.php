<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeatureUnavailableController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Platform\BillingController as PlatformBillingController;
use App\Http\Controllers\Platform\ActivityController as PlatformActivityController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\FeatureController as PlatformFeatureController;
use App\Http\Controllers\Platform\OfferController as PlatformOfferController;
use App\Http\Controllers\Platform\PaymentSubmissionController as PlatformPaymentSubmissionController;
use App\Http\Controllers\Platform\PlanController as PlatformPlanController;
use App\Http\Controllers\Platform\SettingController as PlatformSettingController;
use App\Http\Controllers\Platform\TenantController as PlatformTenantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPlanController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionStatusController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\SubscriptionPaymentSubmissionController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TenantBillingController;
use App\Http\Controllers\UnitController;
use App\Models\BusinessModule;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/plans', [PublicPlanController::class, 'index'])->name('plans.index');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/refund-policy', 'legal.refunds')->name('legal.refunds');

Route::middleware('auth')->group(function () {
    Route::get('/subscription/status', [SubscriptionStatusController::class, 'show'])->name('subscription.status');
    Route::get('/subscription/checkout', [SubscriptionCheckoutController::class, 'show'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.checkout');
    Route::post('/subscription/checkout', [SubscriptionCheckoutController::class, 'store'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.checkout.store');
    Route::post('/subscription/plans/{plan}/select', [SubscriptionCheckoutController::class, 'selectPlan'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.plans.select');
    Route::post('/subscription/invoices/{invoice}/cancel', [SubscriptionCheckoutController::class, 'cancel'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.invoices.cancel');
    Route::get('/subscription/outcome', [SubscriptionStatusController::class, 'outcome'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.outcome');
    Route::post('/subscription/invoices/{invoice}/payment-submission', [SubscriptionPaymentSubmissionController::class, 'store'])
        ->middleware(['verified', 'role:owner'])
        ->name('subscription.payment-submissions.store');
    Route::get('/features/unavailable', [FeatureUnavailableController::class, 'show'])->name('features.unavailable');
    Route::get('/billing', [TenantBillingController::class, 'index'])
        ->middleware(['verified', 'role:owner'])
        ->name('billing.index');

    Route::middleware(['verified', 'active_subscription'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/alerts', [AlertController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('alerts.index');
        Route::get('/settings/business', [SettingsController::class, 'business'])
            ->middleware('permission:settings.manage')
            ->name('settings.business');
        Route::put('/settings/business', [SettingsController::class, 'updateBusiness'])
            ->middleware('permission:settings.manage')
            ->name('settings.business.update');
        Route::middleware(['module:'.BusinessModule::TEAM_MANAGEMENT, 'permission:team.manage'])->group(function () {
            Route::get('/team', [TeamMemberController::class, 'index'])->name('team.index');
            Route::get('/team/create', [TeamMemberController::class, 'create'])->name('team.create');
            Route::post('/team', [TeamMemberController::class, 'store'])->name('team.store');
            Route::get('/team/{teamMember}/edit', [TeamMemberController::class, 'edit'])->name('team.edit');
            Route::put('/team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
            Route::patch('/team/{teamMember}/activate', [TeamMemberController::class, 'activate'])->name('team.activate');
            Route::patch('/team/{teamMember}/deactivate', [TeamMemberController::class, 'deactivate'])->name('team.deactivate');
            Route::post('/team/{teamMember}/resend-invitation', [TeamMemberController::class, 'resendInvitation'])
                ->name('team.resend-invitation');
        });

        Route::middleware(['module:'.BusinessModule::INVENTORY, 'permission:products.view'])->group(function () {
            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::get('/products/{product}/stock-ledger', [ProductController::class, 'stockLedger'])->name('products.stock-ledger');
        });
        Route::get('/product-attributes', [ProductAttributeController::class, 'index'])
            ->middleware(['module:'.BusinessModule::PRODUCT_VARIANTS, 'permission:products.view'])
            ->name('product-attributes.index');
        Route::middleware(['module:'.BusinessModule::INVENTORY, 'permission:products.create'])->group(function () {
            Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
            Route::post('/units', [UnitController::class, 'store'])->name('units.store');
            Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
            Route::get('/products/import', [ProductImportController::class, 'create'])->name('products.import');
            Route::get('/products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');
            Route::post('/products/import/preview', [ProductImportController::class, 'preview'])->name('products.import.preview');
            Route::post('/products/import', [ProductImportController::class, 'store'])->name('products.import.store');
            Route::get('/products/import/errors/{token}', [ProductImportController::class, 'errors'])->name('products.import.errors');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        });
        Route::post('/product-attributes', [ProductAttributeController::class, 'store'])
            ->middleware(['module:'.BusinessModule::PRODUCT_VARIANTS, 'permission:products.create'])
            ->name('product-attributes.store');
        Route::middleware(['module:'.BusinessModule::INVENTORY, 'permission:products.edit'])->group(function () {
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
            Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');
            Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        });
        Route::put('/product-attributes/{productAttribute}', [ProductAttributeController::class, 'update'])
            ->middleware(['module:'.BusinessModule::PRODUCT_VARIANTS, 'permission:products.edit'])
            ->name('product-attributes.update');
        Route::delete('/product-attributes/{productAttribute}', [ProductAttributeController::class, 'destroy'])
            ->middleware(['module:'.BusinessModule::PRODUCT_VARIANTS, 'permission:products.edit'])
            ->name('product-attributes.destroy');

        Route::middleware(['module:'.BusinessModule::CUSTOMER_LEDGER, 'permission:customers.view'])->group(function () {
            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
            Route::get('/customers/{customer}/account', [CustomerAccountController::class, 'show'])->name('customer.account');
        });
        Route::middleware('permission:customers.create')->group(function () {
            Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
            Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        });
        Route::middleware('permission:customers.edit')->group(function () {
            Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        });
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:customers.delete')
            ->name('customers.destroy');
        Route::post('/customers/{customer}/payments', [CustomerPaymentController::class, 'store'])
            ->middleware('permission:payments.create')
            ->name('customer-payments.store');

        Route::get('/pos', [InvoiceController::class, 'pos'])
            ->middleware(['module:'.BusinessModule::BILLING, 'permission:sales.create'])
            ->name('invoices.pos');
        Route::middleware(['feature:pro.barcode', 'permission:sales.create'])->group(function () {
            Route::get('/barcode', [BarcodeController::class, 'index'])->name('barcode.index');
            Route::post('/barcode/lookup', [BarcodeController::class, 'lookup'])->name('barcode.lookup');
        });
        Route::middleware(['module:'.BusinessModule::BILLING, 'permission:sales.view'])->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        });
        Route::middleware(['module:'.BusinessModule::BILLING, 'permission:sales.create'])->group(function () {
            Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        });
        Route::patch('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
            ->middleware(['module:'.BusinessModule::BILLING, 'permission:sales.cancel'])
            ->name('invoices.cancel');
        Route::post('/invoices/{invoice}/returns', [SalesReturnController::class, 'store'])
            ->middleware(['module:'.BusinessModule::RETURNS, 'permission:sales.cancel'])
            ->name('sales-returns.store');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->middleware(['module:'.BusinessModule::BILLING, 'permission:payments.create'])
            ->name('payments.store');

        Route::middleware('permission:expenses.view')->group(function () {
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        });
        Route::middleware('permission:expenses.create')->group(function () {
            Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        });
        Route::middleware('permission:expenses.edit')->group(function () {
            Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        });
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])
            ->middleware('permission:expenses.delete')
            ->name('expenses.destroy');

        Route::middleware(['module:'.BusinessModule::SUPPLIER_LEDGER, 'permission:suppliers.view'])->group(function () {
            Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier')->name('suppliers.show');
            Route::get('/suppliers/{supplier}/account', [SupplierAccountController::class, 'show'])->name('supplier.account');
            Route::get('/suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
            Route::get('/supplier/payments/{supplierPayment}', [SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
        });
        Route::middleware('permission:suppliers.create')->group(function () {
            Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
            Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        });
        Route::middleware('permission:suppliers.edit')->group(function () {
            Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
            Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        });
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->middleware('permission:suppliers.delete')
            ->name('suppliers.destroy');

        Route::middleware('permission:payments.create')->group(function () {
            Route::get('/suppliers/{supplier}/payments/create/{purchase?}', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
            Route::post('/supplier', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
        });
        Route::delete('/supplier/{payment}', [SupplierPaymentController::class, 'destroy'])
            ->middleware('permission:payments.create')
            ->name('supplier-payments.destroy');

        Route::middleware(['module:'.BusinessModule::PURCHASES, 'permission:purchases.view'])->group(function () {
            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->whereNumber('purchase')->name('purchases.show');
            Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
            Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
        });
        Route::middleware(['module:'.BusinessModule::PURCHASES, 'permission:purchases.create'])->group(function () {
            Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        });
        Route::middleware(['module:'.BusinessModule::PURCHASES, 'permission:purchases.edit'])->group(function () {
            Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
            Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        });
        Route::post('/purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])
            ->middleware(['module:'.BusinessModule::RETURNS, 'permission:purchases.cancel'])
            ->name('purchase-returns.store');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->middleware(['module:'.BusinessModule::PURCHASES, 'permission:purchases.cancel'])
            ->name('purchases.cancel');

        Route::prefix('reports')->middleware(['module:'.BusinessModule::REPORTS, 'permission:reports.view'])->group(function () {
            Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('reports.daily-sales');
            Route::get('/monthly-sales', [ReportController::class, 'monthlySales'])->name('reports.monthly-sales');
            Route::get('/stock', [ReportController::class, 'stock'])
                ->middleware('module:'.BusinessModule::INVENTORY)
                ->name('reports.stock');
            Route::get('/low-stock', [ReportController::class, 'lowStock'])
                ->middleware('module:'.BusinessModule::LOW_STOCK_ALERTS)
                ->name('reports.low-stock');
            Route::get('/expiring-stock', [ReportController::class, 'expiringStock'])
                ->middleware('module:'.BusinessModule::BATCH_EXPIRY)
                ->name('reports.expiring-stock');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        });
    });
});

Route::middleware(['auth', 'platform_admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('activities', [PlatformActivityController::class, 'index'])->name('activities.index');
        Route::get('tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}', [PlatformTenantController::class, 'show'])->name('tenants.show');
        Route::get('tenants/{tenant}/edit', [PlatformTenantController::class, 'edit'])->name('tenants.edit');
        Route::put('tenants/{tenant}', [PlatformTenantController::class, 'update'])->name('tenants.update');
        Route::get('billing', [PlatformBillingController::class, 'index'])->name('billing.index');
        Route::get('billing/create', [PlatformBillingController::class, 'create'])->name('billing.create');
        Route::post('billing', [PlatformBillingController::class, 'store'])->name('billing.store');
        Route::get('billing/{invoice}', [PlatformBillingController::class, 'show'])->name('billing.show');
        Route::post('billing/{invoice}/payments', [PlatformBillingController::class, 'storePayment'])
            ->name('billing.payments.store');
        Route::get('payment-submissions', [PlatformPaymentSubmissionController::class, 'index'])
            ->name('payment-submissions.index');
        Route::post('payment-submissions/{submission}/approve', [PlatformPaymentSubmissionController::class, 'approve'])
            ->name('payment-submissions.approve');
        Route::post('payment-submissions/{submission}/reject', [PlatformPaymentSubmissionController::class, 'reject'])
            ->name('payment-submissions.reject');
        Route::resource('plans', PlatformPlanController::class)->except(['show', 'destroy']);
        Route::resource('features', PlatformFeatureController::class)->except(['show', 'destroy']);
        Route::resource('offers', PlatformOfferController::class)->except(['show', 'destroy']);
        Route::get('settings', [PlatformSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [PlatformSettingController::class, 'update'])->name('settings.update');
    });

require __DIR__.'/auth.php';
