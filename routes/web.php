<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierAccountController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\PurchaseController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/cancel', [\App\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');
});
Route::resource('categories', \App\Http\Controllers\CategoryController::class);
Route::resource('products', \App\Http\Controllers\ProductController::class);
Route::resource('customers', \App\Http\Controllers\CustomerController::class);
Route::get('/customers/{customer}/statement',[\App\Http\Controllers\CustomerController::class, 'statement'])->name('customers.statement');

Route::resource('invoices', InvoiceController::class);
Route::patch('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
Route::patch('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
Route::post( '/invoices/{invoice}/payments',[PaymentController::class, 'store'])->name('payments.store');

Route::resource('expenses', ExpenseController::class)->middleware(['auth','role:owner|accountant']);


Route::middleware(['auth', 'role:owner|accountant'])->group(function () {
    Route::resource('suppliers', SupplierController::class);
    //Route::get('/suppliers/{supplier}/account',[SupplierController::class, 'show'])->name('suppliers.account');
   // Route::get('/suppliers/{supplier}/account', [SupplierAccountController::class, 'show'])->name('supplier.account');
    
    Route::get('/suppliers/{supplier}/payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
    Route::get('/suppliers/{supplier}/payments/create', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('/supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    Route::delete('/supplier-payments/{payment}', [SupplierPaymentController::class, 'destroy'])->name('supplier-payments.destroy');
});

Route::get(
    'purchases/{purchase}/print',
    [PurchaseController::class, 'print']
)->name('purchases.print');
Route::resource('purchases', PurchaseController::class)->middleware(['auth', 'role:owner|accountant']);


Route::prefix('reports')->middleware(['auth', 'role:owner|accountant'])->group(function () {
    Route::get('/daily-sales',[ReportController::class, 'dailySales'])->name('reports.daily-sales');
    Route::get('/monthly-sales',[ReportController::class, 'monthlySales'])->name('reports.monthly-sales');
    Route::get('/stock',[ReportController::class, 'stock'])->name('reports.stock');
    Route::get('/low-stock',[ReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/profit-loss',[ReportController::class, 'profitLoss'])->name('reports.profit-loss');
});
Route::get('/reports/profit-loss',[ReportController::class, 'profitLoss'])->middleware(['auth','role:owner|accountant'])->name('reports.profit-loss');
require __DIR__.'/auth.php';
