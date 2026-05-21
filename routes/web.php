<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

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
Route::resource('invoices', InvoiceController::class);
Route::patch('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])
    ->name('invoices.markPaid');

Route::patch('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
    ->name('invoices.cancel');

Route::prefix('reports')->middleware(['auth', 'role:owner'])->group(function () {

    Route::get(
        '/daily-sales',
        [\App\Http\Controllers\ReportController::class, 'dailySales']
    )->name('reports.daily-sales');

    Route::get(
        '/monthly-sales',
        [\App\Http\Controllers\ReportController::class, 'monthlySales']
    )->name('reports.monthly-sales');

    Route::get(
        '/stock',
        [\App\Http\Controllers\ReportController::class, 'stock']
    )->name('reports.stock');

    Route::get(
        '/low-stock',
        [\App\Http\Controllers\ReportController::class, 'lowStock']
    )->name('reports.low-stock');

});
require __DIR__.'/auth.php';
