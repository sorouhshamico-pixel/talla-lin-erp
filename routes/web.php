<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SupplierController;
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
    Route::post('/sales-invoices/{salesInvoice}/issue', [SalesInvoiceController::class, 'issue'])->name('sales-invoices.issue');
    Route::get('/sales-invoices/{salesInvoice}/payments/create', [SalesInvoiceController::class, 'createPayment'])->name('sales-invoices.payments.create');
    Route::post('/sales-invoices/{salesInvoice}/payments', [SalesInvoiceController::class, 'storePayment'])->name('sales-invoices.payments.store');
    Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');

    Route::get('/purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
    Route::get('/purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
    Route::post('/purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
    Route::post('/purchase-invoices/{purchaseInvoice}/receive', [PurchaseInvoiceController::class, 'receive'])->name('purchase-invoices.receive');
    Route::get('/purchase-invoices/{purchaseInvoice}/payments/create', [PurchaseInvoiceController::class, 'createPayment'])->name('purchase-invoices.payments.create');
    Route::post('/purchase-invoices/{purchaseInvoice}/payments', [PurchaseInvoiceController::class, 'storePayment'])->name('purchase-invoices.payments.store');
    Route::get('/purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
