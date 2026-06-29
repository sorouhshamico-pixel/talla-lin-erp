<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RevenueCategoryController;
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
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('/sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
    Route::post('/sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
    Route::post('/sales-invoices/{salesInvoice}/issue', [SalesInvoiceController::class, 'issue'])->name('sales-invoices.issue');
    Route::get('/sales-invoices/{salesInvoice}/payments/create', [SalesInvoiceController::class, 'createPayment'])->name('sales-invoices.payments.create');
    Route::post('/sales-invoices/{salesInvoice}/payments', [SalesInvoiceController::class, 'storePayment'])->name('sales-invoices.payments.store');
    Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');

    Route::get('/purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
    Route::get('/purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
    Route::post('/purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
    Route::post('/purchase-invoices/{purchaseInvoice}/receive', [PurchaseInvoiceController::class, 'receive'])->name('purchase-invoices.receive');
    Route::get('/purchase-invoices/{purchaseInvoice}/payments/create', [PurchaseInvoiceController::class, 'createPayment'])->name('purchase-invoices.payments.create');
    Route::post('/purchase-invoices/{purchaseInvoice}/payments', [PurchaseInvoiceController::class, 'storePayment'])->name('purchase-invoices.payments.store');
    Route::get('/purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');

    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::get('/expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::get('/expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit');
    Route::patch('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::patch('/expense-categories/{expenseCategory}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus'])->name('expense-categories.toggle-status');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::patch('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}/attachment', [ExpenseController::class, 'destroyAttachment'])->name('expenses.attachment.destroy');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Expense category activation toggle - Stage 11C
Route::patch('/expense-categories/{expenseCategory}/toggle', [\App\Http\Controllers\ExpenseCategoryController::class, 'toggle'])
    ->name('expense-categories.toggle');


// Expense category delete route - Stage 11C
Route::delete('/expense-categories/{expenseCategory}', [\App\Http\Controllers\ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

Route::get('/revenue-categories', [RevenueCategoryController::class, 'index'])->name('revenue-categories.index');
Route::post('/revenue-categories', [RevenueCategoryController::class, 'store'])->name('revenue-categories.store');
Route::get('/revenue-categories/{revenueCategory}/edit', [RevenueCategoryController::class, 'edit'])->name('revenue-categories.edit');
Route::put('/revenue-categories/{revenueCategory}', [RevenueCategoryController::class, 'update'])->name('revenue-categories.update');
Route::patch('/revenue-categories/{revenueCategory}/toggle', [RevenueCategoryController::class, 'toggle'])->name('revenue-categories.toggle');
Route::get('/revenues', [RevenueController::class, 'index'])->name('revenues.index');
Route::get('/revenues/create', [RevenueController::class, 'create'])->name('revenues.create');
Route::post('/revenues', [RevenueController::class, 'store'])->name('revenues.store');
Route::get('/revenues/{revenue}/edit', [RevenueController::class, 'edit'])->name('revenues.edit');
Route::put('/revenues/{revenue}', [RevenueController::class, 'update'])->name('revenues.update');
Route::patch('/revenues/{revenue}/toggle-collection', [RevenueController::class, 'toggleCollection'])->name('revenues.toggle-collection');
Route::patch('/revenues/{revenue}/archive', [RevenueController::class, 'archive'])->name('revenues.archive');
Route::patch('/revenues/{revenue}/restore', [RevenueController::class, 'restore'])->name('revenues.restore');

Route::get('/expenses/export/top-large', [ExpenseController::class, 'exportTopLarge'])->name('expenses.export-top-large');
Route::get('/expenses/export/large-unpaid', [ExpenseController::class, 'exportLargeUnpaid'])->name('expenses.export-large-unpaid');
Route::get('/expenses/export/large-paid', [ExpenseController::class, 'exportLargePaid'])->name('expenses.export-large-paid');
Route::get('/revenues/export', [\App\Http\Controllers\RevenueController::class, 'exportCsv'])
    ->middleware(['auth'])
    ->name('revenues.export');
Route::get('/revenues/uncollected/export', [\App\Http\Controllers\RevenueController::class, 'exportUncollectedCsv'])
    ->middleware(['auth'])
    ->name('revenues.uncollected.export');


Route::get('/reports/profit-loss', \App\Http\Controllers\ProfitLossReportController::class)
    ->middleware('auth')
    ->name('reports.profit-loss');

Route::get('/reports/profit-loss/export', [\App\Http\Controllers\ProfitLossReportController::class, 'export'])
    ->middleware('auth')
    ->name('reports.profit-loss.export');

Route::get('/reports/financial-dashboard', \App\Http\Controllers\FinancialDashboardController::class)
    ->middleware('auth')
    ->name('reports.financial-dashboard');

Route::get('/reports/center', \App\Http\Controllers\ReportsCenterController::class)
    ->middleware('auth')
    ->name('reports.center');
