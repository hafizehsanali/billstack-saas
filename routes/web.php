<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeatureUnavailableController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\FeatureController as PlatformFeatureController;
use App\Http\Controllers\Platform\OfferController as PlatformOfferController;
use App\Http\Controllers\Platform\PlanController as PlatformPlanController;
use App\Http\Controllers\Platform\TenantController as PlatformTenantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionStatusController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/subscription/status', [SubscriptionStatusController::class, 'show'])->name('subscription.status');
    Route::get('/features/unavailable', [FeatureUnavailableController::class, 'show'])->name('features.unavailable');

    Route::middleware('active_subscription')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
        Route::get('/settings/business', [SettingsController::class, 'business'])->name('settings.business');
        Route::put('/settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business.update');

        Route::middleware('role:owner')->group(function () {
            Route::get('/team', [TeamMemberController::class, 'index'])->name('team.index');
            Route::get('/team/create', [TeamMemberController::class, 'create'])->name('team.create');
            Route::post('/team', [TeamMemberController::class, 'store'])->name('team.store');
            Route::get('/team/{teamMember}/edit', [TeamMemberController::class, 'edit'])->name('team.edit');
            Route::put('/team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
            Route::patch('/team/{teamMember}/activate', [TeamMemberController::class, 'activate'])->name('team.activate');
            Route::patch('/team/{teamMember}/deactivate', [TeamMemberController::class, 'deactivate'])->name('team.deactivate');
        });

        Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store']);
        Route::get('/products/{product}/stock-ledger', [ProductController::class, 'stockLedger'])->name('products.stock-ledger');
        Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
        Route::post('/customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customer-payments.store');
        Route::get('/customers/{customer}/account', [CustomerAccountController::class, 'show'])->name('customer.account');

        Route::get('/pos', [InvoiceController::class, 'pos'])->name('invoices.pos');
        Route::middleware('feature:pro.barcode')->group(function () {
            Route::get('/barcode', [BarcodeController::class, 'index'])->name('barcode.index');
            Route::post('/barcode/lookup', [BarcodeController::class, 'lookup'])->name('barcode.lookup');
        });
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::patch('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::post('/invoices/{invoice}/returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
    });
});

Route::middleware(['auth', 'active_subscription', 'role:owner|accountant'])->group(function () {
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/{supplier}/account', [SupplierAccountController::class, 'show'])->name('supplier.account');

    Route::get('/suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
    Route::get('/suppliers/{supplier}/payments/create/{purchase?}', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('/supplier', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    Route::get('/supplier/payments/{supplierPayment}', [SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
    Route::delete('/supplier/{payment}', [SupplierPaymentController::class, 'destroy'])->name('supplier-payments.destroy');

    Route::resource('purchases', PurchaseController::class)->except(['destroy']);
    Route::post('/purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
    Route::get('/purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');

    Route::prefix('reports')->group(function () {
        Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('reports.daily-sales');
        Route::get('/monthly-sales', [ReportController::class, 'monthlySales'])->name('reports.monthly-sales');
        Route::get('/stock', [ReportController::class, 'stock'])->name('reports.stock');
        Route::get('/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    });
});

Route::middleware(['auth', 'platform_admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/{tenant}/edit', [PlatformTenantController::class, 'edit'])->name('tenants.edit');
        Route::put('tenants/{tenant}', [PlatformTenantController::class, 'update'])->name('tenants.update');
        Route::resource('plans', PlatformPlanController::class)->except(['show', 'destroy']);
        Route::resource('features', PlatformFeatureController::class)->except(['show', 'destroy']);
        Route::resource('offers', PlatformOfferController::class)->except(['show', 'destroy']);
    });

require __DIR__.'/auth.php';
