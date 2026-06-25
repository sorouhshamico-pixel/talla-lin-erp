<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements/create', [InventoryController::class, 'createMovement'])->name('inventory.movements.create');
    Route::post('/inventory/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('/sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
    Route::post('/sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
    Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
